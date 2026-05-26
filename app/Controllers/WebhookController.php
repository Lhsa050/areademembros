<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\Member;
use App\Models\MemberProduct;
use App\Models\MemberProductOrder;
use App\Models\WebhookLog;
use App\Models\Setting;
use App\Models\Funnel;
use App\Models\SupportContact;
use App\Services\EmailService;
use App\Services\FiscalService;

/**
 * Controller de Webhooks (CartPanda + Hotmart)
 * Endpoint público: POST /webhook/{token}
 */
class WebhookController
{
    /**
     * Processa webhook recebido — detecta automaticamente CartPanda ou Hotmart
     */
    public function handle(string $token): void
    {
        // Apenas aceita POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            json_response(['error' => 'Method not allowed'], 405);
        }

        // Lê payload JSON
        $rawPayload = file_get_contents('php://input');
        $payload = json_decode($rawPayload, true);

        if (!$payload) {
            json_response(['error' => 'Invalid JSON payload'], 400);
        }

        // Busca produto pelo token (ou oferta)
        $product = Product::findByWebhookToken($token);
        $offer = null;
        $funnel = null;
        if (!$product) {
            $offer = \App\Models\Offer::findByWebhookToken($token);
        }
        if (!$product && !$offer) {
            $funnel = Funnel::findByWebhookToken($token);
        }

        // Detecta origem do webhook
        $source = $this->detectSource($payload);

        // Loga o webhook (sempre, mesmo se token inválido)
        $logId = WebhookLog::log(
            $product ? $product['id'] : null,
            ($source === 'hotmart' ? 'hotmart:' : '') . ($funnel ? 'funnel:' : '') . ($this->extractEvent($payload, $source) ?? 'unknown'),
            $payload,
            $_SERVER['REMOTE_ADDR'] ?? null
        );

        if (!$product && !$offer && !$funnel) {
            WebhookLog::markError($logId, 'Produto/Oferta/Funil não encontrado para token: ' . $token);
            json_response(['error' => 'Product not found'], 404);
        }

        try {
            if ($funnel) {
                // Webhook unificado do funil — identifica produtos via line_items
                $this->processFunnelWebhook($payload, $funnel, $source, $logId);
            } elseif ($offer) {
                $this->processOfferWebhook($payload, $offer, $source, $logId);
            } elseif ($source === 'hotmart') {
                $this->processHotmart($payload, $product, $source, $logId);
            } else {
                $this->processCartPanda($payload, $product, $source, $logId);
            }

            json_response(['success' => true]);

        } catch (\Exception $e) {
            WebhookLog::markError($logId, $e->getMessage());
            json_response(['error' => 'Internal error'], 500);
        }
    }

    /**
     * Detecta se o webhook é do Hotmart ou CartPanda
     */
    private function detectSource(array $payload): string
    {
        // Hotmart usa 'data.buyer' e eventos como 'PURCHASE_APPROVED'
        if (isset($payload['data']['buyer']) || isset($payload['event']) && str_starts_with($payload['event'], 'PURCHASE')) {
            return 'hotmart';
        }

        // Hotmart webhook v2 usa 'hottok' header ou 'data' com 'purchase'
        if (isset($payload['data']['purchase']) || isset($payload['hottok'])) {
            return 'hotmart';
        }

        // Padrão: CartPanda
        return 'cartpanda';
    }

    /**
     * Extrai nome do evento
     */
    private function extractEvent(array $payload, string $source): ?string
    {
        if ($source === 'hotmart') {
            return $payload['event'] ?? null;
        }
        return $payload['event'] ?? null;
    }

    // =========================================
    // CARTPANDA
    // =========================================

    /**
     * Processa webhook CartPanda
     */
    private function processCartPanda(array $payload, array $product, string $source, int $logId): void
    {
        $event = $payload['event'] ?? '';
        $order = $payload['order'] ?? [];
        $customer = $order['customer'] ?? null;

        if (!$customer) {
            WebhookLog::markError($logId, 'Dados do cliente não encontrados no payload');
            json_response(['error' => 'Customer data missing'], 400);
        }

        // Extrai dados do cliente
        $email = strtolower(trim($customer['email'] ?? ''));
        $name = trim($customer['full_name'] ?? ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $cpf = preg_replace('/[^0-9]/', '', $customer['cpf'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $customer['phone'] ?? '');

        if (empty($email)) {
            WebhookLog::markError($logId, 'Email do cliente vazio');
            json_response(['error' => 'Customer email required'], 400);
        }

        $productGroups = $this->resolveCartPandaProductGroups($order, (int) $product['funnel_id'], $product);
        $accessProducts = $productGroups['access'];
        $purchasedProducts = $productGroups['purchased'];

        if (empty($accessProducts)) {
            WebhookLog::markError($logId, '[CartPanda] Nenhum produto comprado encontrado no payload. Produto token=' . (int) $product['id']);
            json_response(['error' => 'No matching products found'], 200);
        }

        // Processa evento
        switch ($event) {
            case 'order.created':
                foreach ($accessProducts as $accessProduct) {
                    $this->saveCartPandaOrderStatus($payload, $accessProduct, $email, 'pending');
                }
                WebhookLog::markProcessed($logId);
                break;

            case 'order.paid':
                $memberForFiscal = null;
                foreach ($accessProducts as $accessProduct) {
                    $memberForFiscal = $this->handleApproved($email, $name, $cpf, $phone, $accessProduct, $logId);
                    $this->saveCartPandaOrderStatus($payload, $accessProduct, $email, 'paid', $memberForFiscal);
                }

                if ($memberForFiscal) {
                    foreach ($purchasedProducts as $purchasedProduct) {
                        $this->safeFiscal(fn() => (new FiscalService())->recordProductSaleFromWebhook($payload, $purchasedProduct, $memberForFiscal, $source, $event));
                    }
                }
                break;

            case 'order.cancelled':
                foreach ($accessProducts as $accessProduct) {
                    $this->saveCartPandaOrderStatus($payload, $accessProduct, $email, 'cancelled');
                }
                WebhookLog::markProcessed($logId);
                break;

            case 'order.refunded':
                if (!$this->isRealCartPandaRefund($order)) {
                    foreach ($accessProducts as $accessProduct) {
                        $this->saveCartPandaOrderStatus($payload, $accessProduct, $email, 'cancelled');
                    }
                    WebhookLog::markProcessed($logId);
                    break;
                }

                $shouldRecordFiscalRefunds = false;
                foreach ($accessProducts as $accessProduct) {
                    $member = Member::findByEmail($email, (int) $accessProduct['funnel_id']);
                    $orderRecord = $this->saveCartPandaOrderStatus($payload, $accessProduct, $email, 'refunded', $member);

                    if (MemberProductOrder::wasPaid($orderRecord)) {
                        $shouldRecordFiscalRefunds = true;
                        $this->handleCartPandaRefunded($email, $accessProduct, $this->extractCartPandaOrderId($order, $email, (int) $accessProduct['id']), $logId);
                    }
                }

                if ($shouldRecordFiscalRefunds) {
                    foreach ($purchasedProducts as $purchasedProduct) {
                        $this->safeFiscal(fn() => (new FiscalService())->recordRefundFromWebhook($payload, $purchasedProduct, null, $source, $email));
                    }
                }

                WebhookLog::markProcessed($logId);
                break;

            default:
                WebhookLog::markProcessed($logId);
                break;
        }
    }

    private function saveCartPandaOrderStatus(
        array $payload,
        array $product,
        string $email,
        string $status,
        ?array $member = null
    ): array {
        $order = $payload['order'] ?? [];

        return MemberProductOrder::saveStatus([
            'funnel_id' => (int) $product['funnel_id'],
            'member_id' => $member ? (int) $member['id'] : null,
            'customer_email' => $email,
            'product_id' => (int) $product['id'],
            'course_id' => null,
            'order_id' => $this->extractCartPandaOrderId($order, $email, (int) $product['id']),
            'order_number' => $this->extractCartPandaOrderNumber($order),
            'source_platform' => 'cartpanda',
            'source_event' => $payload['event'] ?? null,
            'external_product_id' => $this->extractCartPandaExternalProductId($order),
            'payment_method' => $this->extractCartPandaPaymentMethod($order),
            'payment_status' => $status,
            'paid_at' => $status === 'paid' ? $this->extractCartPandaPaidAt($order) : null,
            'refunded_at' => in_array($status, ['refunded', 'chargeback'], true) ? $this->extractCartPandaRefundedAt($order) : null,
        ]);
    }

    private function isRealCartPandaRefund(array $order): bool
    {
        $statusId = strtolower($this->scalarText($order['status_id'] ?? ($order['status'] ?? '')));
        $cancelReason = strtolower($this->scalarText($order['cancel_reason'] ?? ''));
        $refunds = $order['refunds'] ?? [];
        $payment = is_array($order['payment'] ?? null) ? $order['payment'] : [];
        $refundedAt = $payment['refunded_at'] ?? ($order['refunded_at'] ?? null);

        $isCancelled = str_contains($statusId, 'cancel');
        $isExpired = str_contains($cancelReason, 'expired')
            || str_contains($cancelReason, 'expirado')
            || str_contains($statusId, 'expired')
            || str_contains($statusId, 'expirado');

        $hasRealRefund = !empty($refunds) || !empty($refundedAt);

        if (($isCancelled || $isExpired) && !$hasRealRefund) {
            return false;
        }

        return $hasRealRefund;
    }

    private function extractCartPandaOrderId(array $order, string $email, int $productId): string
    {
        foreach ([
            $order['id'] ?? null,
            $order['order_id'] ?? null,
            $order['order_number'] ?? null,
            $order['token'] ?? null,
            $order['transaction_id'] ?? null,
            $order['number'] ?? null,
            $order['name'] ?? null,
        ] as $candidate) {
            $value = $this->scalarText($candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return 'missing:' . sha1(implode('|', [
            strtolower(trim($email)),
            (string) $productId,
            $this->scalarText($order['created_at'] ?? ''),
            $this->scalarText($order['total_price'] ?? ''),
        ]));
    }

    private function extractCartPandaOrderNumber(array $order): ?string
    {
        foreach ([
            $order['order_number'] ?? null,
            $order['number'] ?? null,
            $order['name'] ?? null,
        ] as $candidate) {
            $value = $this->scalarText($candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractCartPandaExternalProductId(array $order): ?string
    {
        $items = $order['line_items'] ?? [];
        if (!is_array($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            foreach ($this->cartPandaLineItemCandidates($item) as $candidate) {
                return $candidate;
            }
        }

        return null;
    }

    private function extractCartPandaPaymentMethod(array $order): ?string
    {
        $payment = is_array($order['payment'] ?? null) ? $order['payment'] : [];

        foreach ([$payment['method'] ?? null, $payment['type'] ?? null, $payment['gateway'] ?? null] as $candidate) {
            $value = $this->scalarText($candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractCartPandaPaidAt(array $order): ?string
    {
        $payment = is_array($order['payment'] ?? null) ? $order['payment'] : [];

        foreach ([$payment['paid_at'] ?? null, $order['paid_at'] ?? null, $order['updated_at'] ?? null] as $candidate) {
            $value = $this->scalarText($candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function extractCartPandaRefundedAt(array $order): ?string
    {
        $payment = is_array($order['payment'] ?? null) ? $order['payment'] : [];

        foreach ([$payment['refunded_at'] ?? null, $order['refunded_at'] ?? null, $order['updated_at'] ?? null] as $candidate) {
            $value = $this->scalarText($candidate);
            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function scalarText($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return '';
    }

    // =========================================
    // HOTMART
    // =========================================

    /**
     * Processa webhook Hotmart
     * Documentação: https://developers.hotmart.com/docs/pt-BR/v2/webhook/
     * 
     * Eventos suportados:
     * - PURCHASE_APPROVED / PURCHASE_COMPLETE → libera acesso
     * - PURCHASE_REFUNDED / PURCHASE_CHARGEBACK → revoga acesso
     * - PURCHASE_CANCELED → evento informativo; nao revoga acesso
     */
    private function processHotmart(array $payload, array $product, string $source, int $logId): void
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];
        $buyer = $data['buyer'] ?? [];

        if (empty($buyer)) {
            WebhookLog::markError($logId, '[Hotmart] Dados do comprador não encontrados');
            json_response(['error' => 'Buyer data missing'], 400);
        }

        // Extrai dados do comprador Hotmart
        $email = strtolower(trim($buyer['email'] ?? ''));
        $name = trim($buyer['name'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $buyer['checkout_phone'] ?? ($buyer['phone'] ?? ''));
        // Hotmart envia CPF no campo 'document'
        $cpf = preg_replace('/[^0-9]/', '', $buyer['document'] ?? '');

        if (empty($email)) {
            WebhookLog::markError($logId, '[Hotmart] Email do comprador vazio');
            json_response(['error' => 'Buyer email required'], 400);
        }

        // Mapeia eventos Hotmart para ações
        switch ($event) {
            case 'PURCHASE_APPROVED':
            case 'PURCHASE_COMPLETE':
                $member = $this->handleApproved($email, $name, $cpf, $phone, $product, $logId);
                $this->safeFiscal(fn() => (new FiscalService())->recordProductSaleFromWebhook($payload, $product, $member, $source, $event));
                break;

            case 'PURCHASE_REFUNDED':
            case 'PURCHASE_CHARGEBACK':
                $this->safeFiscal(fn() => (new FiscalService())->recordRefundFromWebhook($payload, $product, null, $source, $email));
                $this->handleRefunded($email, $product, $logId);
                break;

            case 'PURCHASE_PROTEST':
            case 'PURCHASE_DELAYED':
            case 'PURCHASE_CANCELED':
            default:
                // Eventos informativos — apenas loga
                WebhookLog::markProcessed($logId);
                break;
        }
    }

    // =========================================
    // AÇÕES COMUNS
    // =========================================

    /**
     * Processa compra aprovada — membro é criado no funil do produto
     */
    private function handleApproved(
        string $email,
        string $name,
        string $cpf,
        string $phone,
        array $product,
        int $logId
    ): array {
        $isNewMember = false;
        $password = null;
        $funnelId = (int) $product['funnel_id'];
        $emailService = new EmailService($funnelId);

        // Busca funil para obter slug
        $funnel = Funnel::find($funnelId);
        if (!$funnel) {
            WebhookLog::markError($logId, 'Funil do produto não encontrado: funnel_id=' . $funnelId);
            throw new \RuntimeException('Funil do produto nao encontrado.');
        }

        // Busca ou cria membro DENTRO DO FUNIL
        $member = Member::findByEmail($email, $funnelId);

        if (!$member) {
            // Novo membro
            $isNewMember = true;

            // Verifica se tem senha padrão configurada
            $defaultPassword = Setting::get('default_password', '', $funnelId);
            $loginMode = Setting::get('login_mode', 'email_only', $funnelId);

            $passwordHash = null;
            if (!empty($defaultPassword) && $loginMode === 'password') {
                $password = $defaultPassword;
                $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);
            }

            $memberId = Member::create([
                'funnel_id' => $funnelId,
                'name' => $name,
                'email' => $email,
                'cpf' => $cpf ?: null,
                'phone' => $phone ?: null,
                'password' => $passwordHash,
                'status' => 'active',
            ]);

            $member = Member::find($memberId);
        } else {
            // Atualiza dados se necessário
            $updates = [];
            if (empty($member['cpf']) && !empty($cpf)) {
                $updates['cpf'] = $cpf;
            }
            if (empty($member['phone']) && !empty($phone)) {
                $updates['phone'] = $phone;
            }
            if (!empty($name) && $name !== $member['name']) {
                $updates['name'] = $name;
            }
            if (!empty($updates)) {
                Member::update($member['id'], $updates);
                $member = Member::find($member['id']);
            }
        }

        // Adiciona produto ao membro
        $granted = MemberProduct::grant($member['id'], $product['id'], 'webhook');
        SupportContact::linkMemberByEmail($member['email'], (int) $member['id'], $funnelId);

        // Envia email com link do funil
        if ($emailService->isConfigured()) {
            if ($isNewMember) {
                $emailService->sendAccessEmail($member, $password, $funnel['slug']);
            } elseif ($granted) {
                $emailService->sendPurchaseApprovedEmail($member, $product, $funnel['slug']);
            }
        }

        WebhookLog::markProcessed($logId);
        return $member;
    }

    /**
     * Processa reembolso
     */
    private function handleRefunded(
        string $email,
        array $product,
        int $logId
    ): void {
        $funnelId = (int) $product['funnel_id'];
        $member = Member::findByEmail($email, $funnelId);

        if (!$member) {
            WebhookLog::markError($logId, 'Membro não encontrado para reembolso: ' . $email);
            return;
        }

        // Revoga acesso ao produto
        $revoked = MemberProduct::revoke($member['id'], $product['id']);

        // Envia email
        $emailService = new EmailService($funnelId);
        if ($revoked && $emailService->isConfigured()) {
            $emailService->sendProductRevokedEmail($member, $product);
        }

        WebhookLog::markProcessed($logId);
    }

    private function handleCartPandaRefunded(
        string $email,
        array $product,
        string $orderId,
        int $logId
    ): void {
        $funnelId = (int) $product['funnel_id'];
        $member = Member::findByEmail($email, $funnelId);

        if (!$member) {
            WebhookLog::markProcessed($logId);
            return;
        }

        if (MemberProductOrder::hasAnotherPaidActiveOrder((int) $member['id'], (int) $product['id'], $orderId)) {
            WebhookLog::markProcessed($logId);
            return;
        }

        $revoked = MemberProduct::revokeWebhook((int) $member['id'], (int) $product['id']);
        MemberProductOrder::markAccessRevoked($orderId, (int) $product['id']);

        $emailService = new EmailService($funnelId);
        if ($revoked && $emailService->isConfigured()) {
            $emailService->sendProductRevokedEmail($member, $product);
        }

        WebhookLog::markProcessed($logId);
    }

    // =========================================
    // OFERTAS (Multi-produto)
    // =========================================

    /**
     * Processa webhook de oferta — libera todos os produtos vinculados
     */
    private function processOfferWebhook(array $payload, array $offer, string $source, int $logId): void
    {
        // Extrai dados do cliente de acordo com a plataforma
        if ($source === 'hotmart') {
            $buyer = $payload['data']['buyer'] ?? [];
            $email = strtolower(trim($buyer['email'] ?? ''));
            $name = trim($buyer['name'] ?? '');
            $phone = preg_replace('/[^0-9]/', '', $buyer['checkout_phone'] ?? ($buyer['phone'] ?? ''));
            $cpf = preg_replace('/[^0-9]/', '', $buyer['document'] ?? '');
            $event = $payload['event'] ?? '';
        } else {
            $customer = $payload['order']['customer'] ?? [];
            $email = strtolower(trim($customer['email'] ?? ''));
            $name = trim($customer['full_name'] ?? ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
            $cpf = preg_replace('/[^0-9]/', '', $customer['cpf'] ?? '');
            $phone = preg_replace('/[^0-9]/', '', $customer['phone'] ?? '');
            $event = $payload['event'] ?? '';
        }

        if (empty($email)) {
            WebhookLog::markError($logId, '[Oferta] Email vazio');
            json_response(['error' => 'Email required'], 400);
        }

        // Eventos de compra
        $approveEvents = ['order.paid', 'PURCHASE_APPROVED', 'PURCHASE_COMPLETE'];
        $refundEvents = ['order.refunded', 'PURCHASE_REFUNDED', 'PURCHASE_CHARGEBACK'];

        // Busca produtos da oferta
        $productIds = \App\Models\Offer::getProductIds($offer['id']);
        if (empty($productIds)) {
            WebhookLog::markError($logId, '[Oferta] Nenhum produto vinculado à oferta ID=' . $offer['id']);
            return;
        }

        if ($source === 'cartpanda') {
            $this->processCartPandaOfferWebhook($payload, $offer, $productIds, $email, $name, $cpf, $phone, $event, $logId);
            return;
        }

        if (in_array($event, $approveEvents)) {
            $memberForFiscal = null;
            // Libera cada produto
            foreach ($productIds as $pid) {
                $product = Product::findForFunnel((int) $pid, (int) $offer['funnel_id']);
                if ($product) {
                    $memberForFiscal = $this->handleApproved($email, $name, $cpf, $phone, $product, $logId);
                }
            }
            if ($memberForFiscal) {
                $this->safeFiscal(fn() => (new FiscalService())->recordOfferSaleFromWebhook($payload, $offer, $memberForFiscal, $source, $event));
            }
        } elseif (in_array($event, $refundEvents)) {
            $this->safeFiscal(fn() => (new FiscalService())->recordRefundFromWebhook($payload, null, $offer, $source, $email));
            // Revoga cada produto
            foreach ($productIds as $pid) {
                $product = Product::findForFunnel((int) $pid, (int) $offer['funnel_id']);
                if ($product) {
                    $this->handleRefunded($email, $product, $logId);
                }
            }
        } else {
            WebhookLog::markProcessed($logId);
        }
    }

    private function processCartPandaOfferWebhook(
        array $payload,
        array $offer,
        array $productIds,
        string $email,
        string $name,
        string $cpf,
        string $phone,
        string $event,
        int $logId
    ): void {
        $order = $payload['order'] ?? [];

        if ($event === 'order.created' || $event === 'order.cancelled') {
            $status = $event === 'order.created' ? 'pending' : 'cancelled';
            foreach ($productIds as $pid) {
                $product = Product::findForFunnel((int) $pid, (int) $offer['funnel_id']);
                if ($product) {
                    $this->saveCartPandaOrderStatus($payload, $product, $email, $status);
                }
            }
            WebhookLog::markProcessed($logId);
            return;
        }

        if ($event === 'order.paid') {
            $memberForFiscal = null;
            foreach ($productIds as $pid) {
                $product = Product::findForFunnel((int) $pid, (int) $offer['funnel_id']);
                if ($product) {
                    $memberForFiscal = $this->handleApproved($email, $name, $cpf, $phone, $product, $logId);
                    $this->saveCartPandaOrderStatus($payload, $product, $email, 'paid', $memberForFiscal);
                }
            }

            if ($memberForFiscal) {
                $this->safeFiscal(fn() => (new FiscalService())->recordOfferSaleFromWebhook($payload, $offer, $memberForFiscal, 'cartpanda', $event));
            }
            return;
        }

        if ($event === 'order.refunded') {
            if (!$this->isRealCartPandaRefund($order)) {
                foreach ($productIds as $pid) {
                    $product = Product::findForFunnel((int) $pid, (int) $offer['funnel_id']);
                    if ($product) {
                        $this->saveCartPandaOrderStatus($payload, $product, $email, 'cancelled');
                    }
                }
                WebhookLog::markProcessed($logId);
                return;
            }

            $shouldRecordFiscalRefund = false;
            foreach ($productIds as $pid) {
                $product = Product::findForFunnel((int) $pid, (int) $offer['funnel_id']);
                if (!$product) {
                    continue;
                }

                $member = Member::findByEmail($email, (int) $product['funnel_id']);
                $orderRecord = $this->saveCartPandaOrderStatus($payload, $product, $email, 'refunded', $member);
                if (MemberProductOrder::wasPaid($orderRecord)) {
                    $shouldRecordFiscalRefund = true;
                    $this->handleCartPandaRefunded($email, $product, $this->extractCartPandaOrderId($order, $email, (int) $product['id']), $logId);
                }
            }

            if ($shouldRecordFiscalRefund) {
                $this->safeFiscal(fn() => (new FiscalService())->recordRefundFromWebhook($payload, null, $offer, 'cartpanda', $email));
            }

            WebhookLog::markProcessed($logId);
            return;
        }

        WebhookLog::markProcessed($logId);
    }

    private function safeFiscal(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            error_log('[fiscal] webhook fiscal step failed: ' . $e->getMessage());
        }
    }

    // =========================================
    // WEBHOOK UNIFICADO DO FUNIL
    // =========================================

    /**
     * Processa webhook unificado do funil.
     * Identifica os produtos comprados via line_items do payload
     * e libera acesso para cada produto mapeado.
     */
    private function processFunnelWebhook(array $payload, array $funnel, string $source, int $logId): void
    {
        if ($source === 'hotmart') {
            $this->processFunnelHotmart($payload, $funnel, $logId);
            return;
        }

        $this->processFunnelCartPanda($payload, $funnel, $logId);
    }

    /**
     * Processa webhook unificado CartPanda.
     * Extrai line_items e faz match com o codigo CartPanda global dos produtos do funil.
     */
    private function processFunnelCartPanda(array $payload, array $funnel, int $logId): void
    {
        $event = $payload['event'] ?? '';
        $order = $payload['order'] ?? [];
        $customer = $order['customer'] ?? null;

        if (!$customer) {
            WebhookLog::markError($logId, '[Funil] Dados do cliente não encontrados no payload');
            json_response(['error' => 'Customer data missing'], 400);
        }

        $email = strtolower(trim($customer['email'] ?? ''));
        $name = trim($customer['full_name'] ?? ($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? ''));
        $cpf = preg_replace('/[^0-9]/', '', $customer['cpf'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $customer['phone'] ?? '');

        if (empty($email)) {
            WebhookLog::markError($logId, '[Funil] Email do cliente vazio');
            json_response(['error' => 'Customer email required'], 400);
        }

        $funnelId = (int) $funnel['id'];
        $productGroups = $this->resolveCartPandaProductGroups($order, $funnelId);
        $matchedProducts = $productGroups['access'];
        $purchasedProducts = $productGroups['purchased'];

        if (empty($matchedProducts)) {
            WebhookLog::markError($logId, '[Funil] Nenhum produto comprado encontrado no payload. Funil ID=' . $funnelId);
            json_response(['error' => 'No matching products found'], 200);
        }

        switch ($event) {
            case 'order.created':
            case 'order.cancelled':
                $status = $event === 'order.created' ? 'pending' : 'cancelled';
                foreach ($matchedProducts as $product) {
                    $this->saveCartPandaOrderStatus($payload, $product, $email, $status);
                }
                WebhookLog::markProcessed($logId);
                break;

            case 'order.paid':
                $memberForFiscal = null;
                foreach ($matchedProducts as $product) {
                    $memberForFiscal = $this->handleApproved($email, $name, $cpf, $phone, $product, $logId);
                    $this->saveCartPandaOrderStatus($payload, $product, $email, 'paid', $memberForFiscal);
                }

                if ($memberForFiscal) {
                    foreach ($purchasedProducts as $purchasedProduct) {
                        $this->safeFiscal(fn() => (new FiscalService())->recordProductSaleFromWebhook($payload, $purchasedProduct, $memberForFiscal, 'cartpanda', $event));
                    }
                }
                break;

            case 'order.refunded':
                if (!$this->isRealCartPandaRefund($order)) {
                    foreach ($matchedProducts as $product) {
                        $this->saveCartPandaOrderStatus($payload, $product, $email, 'cancelled');
                    }
                    WebhookLog::markProcessed($logId);
                    break;
                }

                $shouldRecordFiscalRefund = false;
                foreach ($matchedProducts as $product) {
                    $member = Member::findByEmail($email, (int) $product['funnel_id']);
                    $orderRecord = $this->saveCartPandaOrderStatus($payload, $product, $email, 'refunded', $member);
                    if (MemberProductOrder::wasPaid($orderRecord)) {
                        $shouldRecordFiscalRefund = true;
                        $this->handleCartPandaRefunded(
                            $email,
                            $product,
                            $this->extractCartPandaOrderId($order, $email, (int) $product['id']),
                            $logId
                        );
                    }
                }

                if ($shouldRecordFiscalRefund) {
                    foreach ($purchasedProducts as $purchasedProduct) {
                        $this->safeFiscal(fn() => (new FiscalService())->recordRefundFromWebhook($payload, $purchasedProduct, null, 'cartpanda', $email));
                    }
                }

                WebhookLog::markProcessed($logId);
                break;

            default:
                WebhookLog::markProcessed($logId);
                break;
        }
    }

    /**
     * Processa webhook unificado Hotmart.
     */
    private function processFunnelHotmart(array $payload, array $funnel, int $logId): void
    {
        $event = $payload['event'] ?? '';
        $data = $payload['data'] ?? [];
        $buyer = $data['buyer'] ?? [];

        if (empty($buyer)) {
            WebhookLog::markError($logId, '[Funil/Hotmart] Dados do comprador não encontrados');
            json_response(['error' => 'Buyer data missing'], 400);
        }

        $email = strtolower(trim($buyer['email'] ?? ''));
        $name = trim($buyer['name'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $buyer['checkout_phone'] ?? ($buyer['phone'] ?? ''));
        $cpf = preg_replace('/[^0-9]/', '', $buyer['document'] ?? '');

        if (empty($email)) {
            WebhookLog::markError($logId, '[Funil/Hotmart] Email do comprador vazio');
            json_response(['error' => 'Buyer email required'], 400);
        }

        $funnelId = (int) $funnel['id'];

        // Hotmart: tenta extrair product ID do payload
        $hotmartProductId = (string) ($data['product']['id'] ?? '');
        $matchedProducts = [];

        if ($hotmartProductId !== '') {
            $product = Product::findByExternalIdInFunnel($hotmartProductId, $funnelId);
            if ($product) {
                $matchedProducts[] = $product;
            }
        }

        // Se não encontrou por external_id, libera todos os produtos do funil
        if (empty($matchedProducts)) {
            $allProducts = Product::getByFunnel($funnelId);
            $matchedProducts = $allProducts;
        }

        $approveEvents = ['PURCHASE_APPROVED', 'PURCHASE_COMPLETE'];
        $refundEvents = ['PURCHASE_REFUNDED', 'PURCHASE_CHARGEBACK'];

        if (in_array($event, $approveEvents)) {
            foreach ($matchedProducts as $product) {
                $this->handleApproved($email, $name, $cpf, $phone, $product, $logId);
            }
        } elseif (in_array($event, $refundEvents)) {
            foreach ($matchedProducts as $product) {
                $this->handleRefunded($email, $product, $logId);
            }
        } else {
            WebhookLog::markProcessed($logId);
        }
    }

    private function resolveCartPandaProductGroups(array $order, int $funnelId, ?array $fallbackProduct = null): array
    {
        $purchasedProducts = $this->matchLineItemsToProducts($order, $funnelId);

        if ($fallbackProduct) {
            $purchasedProducts[] = $fallbackProduct;
        }

        $purchasedProducts = $this->uniqueProducts($purchasedProducts);

        return [
            'purchased' => $purchasedProducts,
            'access' => $this->expandCartPandaAccessProducts($purchasedProducts, $funnelId),
        ];
    }

    private function expandCartPandaAccessProducts(array $purchasedProducts, int $funnelId): array
    {
        if (empty($purchasedProducts)) {
            return [];
        }

        $accessProducts = $purchasedProducts;
        $isCorePurchase = false;

        foreach ($purchasedProducts as $product) {
            if ($this->cartPandaPurchaseGrantsCoreBundle($product)) {
                $isCorePurchase = true;
                break;
            }
        }

        if ($isCorePurchase) {
            $accessProducts = array_merge($accessProducts, Product::getByRoleInFunnel($funnelId, ['principal', 'bonus']));
        }

        return $this->uniqueProducts($accessProducts);
    }

    private function cartPandaPurchaseGrantsCoreBundle(array $product): bool
    {
        $role = strtolower(trim((string) ($product['funnel_role'] ?? '')));

        return $role !== 'orderbump';
    }

    private function uniqueProducts(array $products): array
    {
        $unique = [];
        $seen = [];

        foreach ($products as $product) {
            if (!is_array($product) || !isset($product['id'])) {
                continue;
            }

            $id = (int) $product['id'];
            if (isset($seen[$id])) {
                continue;
            }

            $unique[] = $product;
            $seen[$id] = true;
        }

        return $unique;
    }

    /**
     * Extrai line_items do payload CartPanda e faz match com produtos do funil
     * via external_product_id global do produto.
     * 
     * Tenta match por:
     * 1. product_id do line_item -> products.external_product_id
     * 2. variant_id do line_item -> products.external_product_id
     * 3. sku do line_item -> products.external_product_id
     * 4. name do line_item -> título do produto (fallback fuzzy)
     */
    private function matchLineItemsToProducts(array $order, int $funnelId): array
    {
        $lineItems = $order['line_items'] ?? [];
        if (!is_array($lineItems) || empty($lineItems)) {
            return [];
        }

        // Coleta todos os candidatos a external_id dos line_items
        $candidates = [];
        $lineItemNames = [];
        foreach ($lineItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            foreach ($this->cartPandaLineItemCandidates($item) as $value) {
                $candidates[$value] = true;
            }

            // Guarda nome para fallback
            foreach ($this->cartPandaLineItemNames($item) as $itemName) {
                $lineItemNames[] = strtolower($itemName);
            }
        }

        if (empty($candidates) && empty($lineItemNames)) {
            return [];
        }

        $matchedProducts = [];
        $matchedIds = []; // Evita duplicatas

        // 1. Match pelo codigo CartPanda global do produto vinculado ao funil
        if (!empty($candidates)) {
            $products = Product::findAllByExternalIdsInFunnel(array_keys($candidates), $funnelId);
            foreach ($products as $product) {
                if (!isset($matchedIds[(int) $product['id']])) {
                    $matchedProducts[] = $product;
                    $matchedIds[(int) $product['id']] = true;
                }
            }
        }

        // 2. Fallback em memoria para payloads com variacoes de tipo/espacos
        if (!empty($candidates)) {
            $allFunnelProducts = $allFunnelProducts ?? Product::getByFunnel($funnelId);
            foreach ($allFunnelProducts as $product) {
                $globalExtId = trim($product['external_product_id'] ?? '');
                if ($globalExtId !== '' && isset($candidates[$globalExtId])) {
                    if (!isset($matchedIds[(int) $product['id']])) {
                        $matchedProducts[] = $product;
                        $matchedIds[(int) $product['id']] = true;
                    }
                }
            }
        }

        // 3. Fallback: match por nome do produto (se nenhum match por ID)
        if (empty($matchedProducts) && !empty($lineItemNames)) {
            $allFunnelProducts = $allFunnelProducts ?? Product::getByFunnel($funnelId);
            foreach ($allFunnelProducts as $product) {
                $productTitle = strtolower(trim($product['title']));
                foreach ($lineItemNames as $itemName) {
                    if ($productTitle === $itemName || str_contains($itemName, $productTitle) || str_contains($productTitle, $itemName)) {
                        if (!isset($matchedIds[(int) $product['id']])) {
                            $matchedProducts[] = $product;
                            $matchedIds[(int) $product['id']] = true;
                        }
                        break;
                    }
                }
            }
        }

        return $matchedProducts;
    }

    private function cartPandaLineItemCandidates(array $item): array
    {
        $paths = [
            ['product_id'],
            ['external_product_id'],
            ['product_external_id'],
            ['external_id'],
            ['cartpanda_product_id'],
            ['variant_id'],
            ['sku'],
            ['offer_id'],
            ['id'],
            ['item_id'],
            ['product', 'id'],
            ['product', 'product_id'],
            ['product', 'external_product_id'],
            ['product', 'external_id'],
            ['product', 'sku'],
            ['product', 'code'],
            ['variant', 'id'],
            ['variant', 'variant_id'],
            ['variant', 'external_product_id'],
            ['variant', 'external_id'],
            ['variant', 'sku'],
            ['offer', 'id'],
            ['offer', 'external_product_id'],
            ['offer', 'external_id'],
            ['offer', 'sku'],
        ];

        $values = [];
        foreach ($paths as $path) {
            $value = $this->nestedScalarText($item, $path);
            if ($value !== '') {
                $values[$value] = true;
            }
        }

        return array_keys($values);
    }

    private function cartPandaLineItemNames(array $item): array
    {
        $paths = [
            ['name'],
            ['title'],
            ['product_name'],
            ['product', 'name'],
            ['product', 'title'],
            ['variant', 'name'],
            ['variant', 'title'],
        ];

        $values = [];
        foreach ($paths as $path) {
            $value = $this->nestedScalarText($item, $path);
            if ($value !== '') {
                $values[$value] = true;
            }
        }

        return array_keys($values);
    }

    private function nestedScalarText(array $data, array $path): string
    {
        $value = $data;

        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return '';
            }

            $value = $value[$key];
        }

        return $this->scalarText($value);
    }
}
