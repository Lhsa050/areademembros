<?php

namespace App\Services;

use App\Core\Database;
use App\Models\FiscalInvoice;
use App\Models\FiscalSale;
use App\Models\Member;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Fiscal\CertificateService;
use App\Services\Fiscal\NfseNationalClient;
use App\Services\Fiscal\NfseXmlBuilder;
use App\Services\Fiscal\XmlSigner;
use RuntimeException;

/**
 * Orquestra vendas fiscais, NFS-e, cancelamento e exportação contábil.
 */
class FiscalService
{
    public function settings(): array
    {
        $settings = Setting::getAll();
        $defaults = [
            'fiscal_enabled' => '0',
            'fiscal_auto_issue' => '0',
            'fiscal_auto_cancel_refund' => '1',
            'fiscal_environment' => 'restricted',
            'fiscal_company_cnpj' => '',
            'fiscal_company_im' => '',
            'fiscal_company_legal_name' => '',
            'fiscal_company_email' => '',
            'fiscal_company_phone' => '',
            'fiscal_company_municipality_code' => '',
            'fiscal_service_municipality_code' => '',
            'fiscal_op_simp_nac' => '1',
            'fiscal_reg_esp_trib' => '0',
            'fiscal_trib_issqn' => '1',
            'fiscal_tp_ret_issqn' => '1',
            'fiscal_ind_tot_trib' => '0',
            'fiscal_default_series' => '1',
            'fiscal_next_dps_number' => '1',
            'fiscal_service_code_course' => '',
            'fiscal_service_code_saas' => '',
            'fiscal_service_code_ebook' => '',
            'fiscal_service_code_other' => '',
            'fiscal_description_course' => 'Curso online',
            'fiscal_description_saas' => 'Licenciamento ou cessao de direito de uso de software',
            'fiscal_description_ebook' => 'Livro eletronico / conteudo digital',
            'fiscal_description_other' => 'Servico digital',
        ];

        return array_merge($defaults, array_intersect_key($settings, $defaults));
    }

    public function saveSettings(array $data): void
    {
        $keys = [
            'fiscal_enabled', 'fiscal_auto_issue', 'fiscal_auto_cancel_refund', 'fiscal_environment',
            'fiscal_company_cnpj', 'fiscal_company_im', 'fiscal_company_legal_name',
            'fiscal_company_email', 'fiscal_company_phone', 'fiscal_company_municipality_code',
            'fiscal_service_municipality_code', 'fiscal_op_simp_nac', 'fiscal_reg_esp_trib',
            'fiscal_trib_issqn', 'fiscal_tp_ret_issqn', 'fiscal_ind_tot_trib',
            'fiscal_default_series', 'fiscal_next_dps_number',
            'fiscal_service_code_course', 'fiscal_service_code_saas', 'fiscal_service_code_ebook',
            'fiscal_service_code_other', 'fiscal_description_course', 'fiscal_description_saas',
            'fiscal_description_ebook', 'fiscal_description_other',
        ];

        foreach ($keys as $key) {
            $value = $data[$key] ?? '';
            if (in_array($key, ['fiscal_enabled', 'fiscal_auto_issue', 'fiscal_auto_cancel_refund'], true)) {
                $value = isset($data[$key]) ? '1' : '0';
            }
            if (str_ends_with($key, '_cnpj') || str_ends_with($key, '_phone') || str_ends_with($key, '_code')) {
                $value = preg_replace('/\D+/', '', (string) $value);
            }
            Setting::set($key, trim((string) $value));
        }
    }

    public function recordProductSaleFromWebhook(array $payload, array $product, array $member, string $source, string $event): ?int
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $transactionId = $this->extractTransactionId($payload, $source);
        $duplicate = FiscalSale::findDuplicate($source, $transactionId, (int) $product['id'], null);
        if ($duplicate) {
            return (int) $duplicate['id'];
        }

        $amount = $this->extractAmount($payload, $source);
        if ($amount <= 0 && isset($product['price'])) {
            $amount = (float) $product['price'];
        }

        $saleId = FiscalSale::create([
            'funnel_id' => (int) $product['funnel_id'],
            'member_id' => (int) $member['id'],
            'product_id' => (int) $product['id'],
            'offer_id' => null,
            'source_platform' => $source,
            'source_event' => $event,
            'transaction_id' => $transactionId,
            'order_reference' => $this->extractOrderReference($payload, $source),
            'customer_name' => $member['name'] ?? '',
            'customer_email' => strtolower(trim($member['email'] ?? '')),
            'customer_document' => preg_replace('/\D+/', '', (string) ($member['cpf'] ?? '')),
            'customer_document_type' => $this->documentType((string) ($member['cpf'] ?? '')),
            'customer_phone' => preg_replace('/\D+/', '', (string) ($member['phone'] ?? '')),
            'amount' => $amount,
            'currency' => 'BRL',
            'status' => 'paid',
            'invoice_status' => 'not_issued',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        $this->tryAutoIssue($saleId);
        return $saleId;
    }

    public function recordOfferSaleFromWebhook(array $payload, array $offer, array $member, string $source, string $event): ?int
    {
        if (!$this->isEnabled()) {
            return null;
        }

        $transactionId = $this->extractTransactionId($payload, $source);
        $duplicate = FiscalSale::findDuplicate($source, $transactionId, null, (int) $offer['id']);
        if ($duplicate) {
            return (int) $duplicate['id'];
        }

        $amount = $this->extractAmount($payload, $source);
        if ($amount <= 0 && isset($offer['price'])) {
            $amount = (float) $offer['price'];
        }

        $saleId = FiscalSale::create([
            'funnel_id' => (int) $offer['funnel_id'],
            'member_id' => (int) $member['id'],
            'product_id' => null,
            'offer_id' => (int) $offer['id'],
            'source_platform' => $source,
            'source_event' => $event,
            'transaction_id' => $transactionId,
            'order_reference' => $this->extractOrderReference($payload, $source),
            'customer_name' => $member['name'] ?? '',
            'customer_email' => strtolower(trim($member['email'] ?? '')),
            'customer_document' => preg_replace('/\D+/', '', (string) ($member['cpf'] ?? '')),
            'customer_document_type' => $this->documentType((string) ($member['cpf'] ?? '')),
            'customer_phone' => preg_replace('/\D+/', '', (string) ($member['phone'] ?? '')),
            'amount' => $amount,
            'currency' => 'BRL',
            'status' => 'paid',
            'invoice_status' => 'not_issued',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'paid_at' => date('Y-m-d H:i:s'),
        ]);

        $this->tryAutoIssue($saleId);
        return $saleId;
    }

    public function recordRefundFromWebhook(array $payload, ?array $product, ?array $offer, string $source, string $email = ''): void
    {
        if (!$this->isEnabled()) {
            return;
        }

        $sale = FiscalSale::latestForRefund(
            $source,
            $this->extractTransactionId($payload, $source),
            $product ? (int) $product['id'] : null,
            $offer ? (int) $offer['id'] : null,
            $email
        );

        if (!$sale) {
            return;
        }

        FiscalSale::markRefunded((int) $sale['id']);

        if ($this->settings()['fiscal_auto_cancel_refund'] === '1') {
            $invoice = FiscalInvoice::findBySale((int) $sale['id']);
            if ($invoice && $invoice['status'] === 'issued') {
                try {
                    $this->cancelInvoice((int) $invoice['id'], 'Reembolso da compra informado pela plataforma de pagamento.', '2');
                } catch (\Throwable $e) {
                    FiscalInvoice::markCancelError((int) $invoice['id'], 'Falha ao cancelar apos reembolso: ' . $e->getMessage());
                    FiscalSale::updateInvoiceStatus((int) $sale['id'], 'error');
                }
            }
        }
    }

    public function issueSale(int $saleId): array
    {
        $sale = FiscalSale::find($saleId);
        if (!$sale) {
            throw new RuntimeException('Venda fiscal nao encontrada.');
        }

        $existing = FiscalInvoice::findBySale($saleId);
        if ($existing && in_array($existing['status'], ['issued', 'processing'], true)) {
            return $existing;
        }

        $settings = $this->settings();
        $this->validateReadyForIssue($settings, $sale);
        $profile = $this->resolveFiscalProfile($sale, $settings);
        $sequence = $this->nextDpsNumber($settings);
        $series = preg_replace('/\D+/', '', $settings['fiscal_default_series'] ?: '1');
        $dpsId = NfseXmlBuilder::buildDpsId(
            $settings['fiscal_company_municipality_code'],
            $settings['fiscal_company_cnpj'],
            $series,
            $sequence
        );

        $invoiceId = FiscalInvoice::create([
            'sale_id' => $saleId,
            'provider' => 'nfse_nacional',
            'environment' => $settings['fiscal_environment'],
            'document_type' => 'nfse',
            'status' => 'processing',
            'dps_series' => $series,
            'dps_number' => $sequence,
            'dps_id' => $dpsId,
            'total_amount' => (float) $sale['amount'],
            'service_code' => $profile['service_code'],
            'service_description' => $profile['description'],
        ]);

        FiscalSale::updateInvoiceStatus($saleId, 'pending');

        try {
            $invoice = FiscalInvoice::find($invoiceId);
            $settings['fiscal_nbs_code'] = $profile['nbs_code'] ?? '';
            $dpsXml = NfseXmlBuilder::buildDps($settings, $sale, $invoice);
            $signedXml = XmlSigner::sign($dpsXml, 'infDPS');
            $xmlPath = $this->storeFiscalFile('xml', "dps-{$invoiceId}.xml", $signedXml);

            FiscalInvoice::update($invoiceId, [
                'xml_path' => $xmlPath,
                'request_payload' => json_encode([
                    'dps_id' => $dpsId,
                    'dps_series' => $series,
                    'dps_number' => $sequence,
                    'service_code' => $profile['service_code'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);

            $client = new NfseNationalClient($settings['fiscal_environment']);
            $response = $client->issue($signedXml);
            $responseJson = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $accessKey = $this->extractAccessKeyFromResponse($response);
            $nfseXmlPath = $this->storeResponseXmlIfPresent($invoiceId, $response);
            $pdfPath = null;

            if ($accessKey) {
                try {
                    $pdf = $client->danfsePdf($accessKey);
                    $pdfPath = $this->storeFiscalFile('pdf', "danfse-{$invoiceId}.pdf", $pdf);
                } catch (\Throwable $e) {
                    $pdfPath = null;
                }

                FiscalInvoice::markIssued($invoiceId, [
                    'access_key' => $accessKey,
                    'verification_code' => $response['codigoVerificacao'] ?? null,
                    'issued_at' => date('Y-m-d H:i:s'),
                    'xml_path' => $nfseXmlPath ?: $xmlPath,
                    'pdf_path' => $pdfPath,
                    'response_payload' => $responseJson,
                ]);
                FiscalSale::updateInvoiceStatus($saleId, 'issued');
            } else {
                $error = $this->formatResponseErrors($response) ?: 'A API nao retornou chave de acesso.';
                FiscalInvoice::markRejected($invoiceId, $error, $responseJson);
                FiscalSale::updateInvoiceStatus($saleId, 'error');
            }
        } catch (\Throwable $e) {
            FiscalInvoice::markRejected($invoiceId, $e->getMessage());
            FiscalSale::updateInvoiceStatus($saleId, 'error');
            throw $e;
        }

        return FiscalInvoice::find($invoiceId);
    }

    public function cancelInvoice(int $invoiceId, string $reason, string $reasonCode = '1'): void
    {
        $invoice = FiscalInvoice::withSale($invoiceId);
        if (!$invoice || empty($invoice['access_key'])) {
            throw new RuntimeException('Nota fiscal sem chave de acesso para cancelar.');
        }
        if ($invoice['status'] === 'canceled') {
            return;
        }

        $settings = $this->settings();
        $this->validateCertificate();

        $eventXml = NfseXmlBuilder::buildCancelEvent($settings, $invoice['access_key'], $reason, $reasonCode);
        $signedEventXml = XmlSigner::sign($eventXml, 'infPedReg');
        $this->storeFiscalFile('xml', "cancelamento-{$invoiceId}.xml", $signedEventXml);

        $client = new NfseNationalClient($settings['fiscal_environment']);
        $response = $client->cancel($invoice['access_key'], $signedEventXml);
        $responseJson = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $errors = $this->formatResponseErrors($response);

        if ($errors) {
            FiscalInvoice::markCancelError($invoiceId, 'Cancelamento rejeitado: ' . $errors, $responseJson);
            FiscalSale::updateInvoiceStatus((int) $invoice['sale_id'], 'error');
            throw new RuntimeException($errors);
        }

        FiscalInvoice::markCanceled($invoiceId, $reason, $responseJson);
        FiscalSale::updateInvoiceStatus((int) $invoice['sale_id'], 'canceled');
    }

    public function exportCsv(array $filters = []): string
    {
        $rows = FiscalSale::search($filters, 5000, 0);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'Data venda', 'Cliente', 'Email', 'Documento', 'Valor', 'Status venda',
            'Status nota', 'Chave NFS-e', 'Produto/Oferta', 'Plataforma', 'Transacao',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['paid_at'],
                $row['customer_name'],
                $row['customer_email'],
                $row['customer_document'],
                number_format((float) $row['amount'], 2, ',', '.'),
                $row['status'],
                $row['fiscal_status'] ?: $row['invoice_status'],
                $row['access_key'],
                $row['product_title'] ?: $row['offer_title'],
                $row['source_platform'],
                $row['transaction_id'],
            ], ';');
        }

        rewind($handle);
        return stream_get_contents($handle) ?: '';
    }

    private function isEnabled(): bool
    {
        return $this->settings()['fiscal_enabled'] === '1';
    }

    private function tryAutoIssue(int $saleId): void
    {
        if ($this->settings()['fiscal_auto_issue'] !== '1') {
            return;
        }

        try {
            $this->issueSale($saleId);
        } catch (\Throwable $e) {
            error_log('[fiscal] auto issue failed sale=' . $saleId . ': ' . $e->getMessage());
        }
    }

    private function validateReadyForIssue(array $settings, array $sale): void
    {
        foreach ([
            'fiscal_company_cnpj' => 'CNPJ do emitente',
            'fiscal_company_municipality_code' => 'codigo IBGE do municipio',
            'fiscal_default_series' => 'serie da DPS',
        ] as $key => $label) {
            if (empty($settings[$key])) {
                throw new RuntimeException("Configure {$label} em Fiscal > Configuracoes.");
            }
        }

        if ((float) $sale['amount'] <= 0) {
            throw new RuntimeException('A venda precisa ter valor maior que zero para emitir nota.');
        }

        if (empty($sale['customer_document'])) {
            throw new RuntimeException('O cliente precisa ter CPF/CNPJ para emitir esta NFS-e.');
        }

        $this->validateCertificate();
    }

    private function validateCertificate(): void
    {
        if (!CertificateService::getCertificateInfo()) {
            throw new RuntimeException('Envie o certificado A1 em Fiscal > Configuracoes.');
        }
    }

    private function resolveFiscalProfile(array $sale, array $settings): array
    {
        $kind = 'other';
        $description = '';
        $serviceCode = '';

        if (!empty($sale['product_id'])) {
            $product = Product::find((int) $sale['product_id']);
            if (!$product) {
                throw new RuntimeException('Produto da venda fiscal nao encontrado.');
            }
            $kind = $product['fiscal_kind'] ?? ($product['type'] === 'pdf' ? 'ebook' : 'course');
            $serviceCode = $product['fiscal_service_code'] ?? '';
            $description = $product['fiscal_service_description'] ?? '';
            $nbsCode = $product['fiscal_nbs_code'] ?? '';
            $title = $product['title'] ?? 'Produto';
        } elseif (!empty($sale['offer_id'])) {
            $offer = Offer::find((int) $sale['offer_id']);
            if (!$offer) {
                throw new RuntimeException('Oferta da venda fiscal nao encontrada.');
            }
            $kind = $offer['fiscal_kind'] ?? 'course';
            $serviceCode = $offer['fiscal_service_code'] ?? '';
            $description = $offer['fiscal_service_description'] ?? '';
            $nbsCode = $offer['fiscal_nbs_code'] ?? '';
            $title = $offer['title'] ?? 'Oferta';
        } else {
            $nbsCode = '';
            $title = 'Venda';
        }

        $serviceCode = $serviceCode ?: ($settings['fiscal_service_code_' . $kind] ?? $settings['fiscal_service_code_other'] ?? '');
        $description = $description ?: ($settings['fiscal_description_' . $kind] ?? $settings['fiscal_description_other'] ?? 'Servico digital');
        $description = trim($description . ' - ' . $title);

        if (!$serviceCode) {
            throw new RuntimeException("Configure o codigo de servico fiscal para '{$kind}'.");
        }

        return [
            'kind' => $kind,
            'service_code' => $serviceCode,
            'description' => $description,
            'nbs_code' => $nbsCode,
        ];
    }

    private function nextDpsNumber(array $settings): int
    {
        $current = max(1, (int) ($settings['fiscal_next_dps_number'] ?? 1));
        Setting::set('fiscal_next_dps_number', (string) ($current + 1));
        return $current;
    }

    private function storeFiscalFile(string $type, string $filename, string $contents): string
    {
        $dir = ABSPATH . '/storage/fiscal/' . $type . '/' . date('Y/m');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = $dir . '/' . preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
        file_put_contents($path, $contents);
        return $path;
    }

    private function storeResponseXmlIfPresent(int $invoiceId, array $response): ?string
    {
        $keys = ['nfseXmlGZipB64', 'xmlGZipB64', 'dfeXmlGZipB64'];
        foreach ($keys as $key) {
            if (!empty($response[$key])) {
                $binary = base64_decode((string) $response[$key], true);
                $decoded = $binary !== false ? gzdecode($binary) : false;
                if ($decoded) {
                    return $this->storeFiscalFile('xml', "nfse-{$invoiceId}.xml", $decoded);
                }
            }
        }

        if (!empty($response['xml']) && is_string($response['xml'])) {
            return $this->storeFiscalFile('xml', "nfse-{$invoiceId}.xml", $response['xml']);
        }

        return null;
    }

    private function extractAccessKeyFromResponse(array $response): ?string
    {
        foreach (['chaveAcesso', 'chNFSe', 'access_key'] as $key) {
            if (!empty($response[$key])) {
                return preg_replace('/\D+/', '', (string) $response[$key]);
            }
        }

        foreach (['nfseXmlGZipB64', 'xmlGZipB64', 'dfeXmlGZipB64'] as $key) {
            if (!empty($response[$key])) {
                $binary = base64_decode((string) $response[$key], true);
                $xml = $binary !== false ? gzdecode($binary) : false;
                if ($xml && preg_match('/<ch(?:ave)?(?:Acesso|NFSe)>(\d+)<\/ch(?:ave)?(?:Acesso|NFSe)>/i', $xml, $m)) {
                    return $m[1];
                }
            }
        }

        return null;
    }

    private function formatResponseErrors(array $response): string
    {
        $messages = [];
        foreach (['erros', 'erro', 'errors'] as $key) {
            if (!empty($response[$key])) {
                $items = is_array($response[$key]) && array_is_list($response[$key]) ? $response[$key] : [$response[$key]];
                foreach ($items as $item) {
                    if (is_array($item)) {
                        $messages[] = trim(($item['codigo'] ?? '') . ' ' . ($item['descricao'] ?? $item['mensagem'] ?? json_encode($item)));
                    } else {
                        $messages[] = (string) $item;
                    }
                }
            }
        }

        if (!empty($response['lote']) && is_array($response['lote'])) {
            foreach ($response['lote'] as $item) {
                if (!empty($item['erros'])) {
                    foreach ($item['erros'] as $error) {
                        $messages[] = trim(($error['codigo'] ?? '') . ' ' . ($error['descricao'] ?? ''));
                    }
                }
            }
        }

        return implode('; ', array_filter($messages));
    }

    private function extractAmount(array $payload, string $source): float
    {
        if ($source === 'hotmart') {
            return $this->firstMoney([
                $payload['data']['purchase']['price']['value'] ?? null,
                $payload['data']['purchase']['full_price']['value'] ?? null,
                $payload['data']['purchase']['price']['amount'] ?? null,
            ]);
        }

        return $this->firstMoney([
            $payload['order']['total_price'] ?? null,
            $payload['order']['total'] ?? null,
            $payload['order']['total_amount'] ?? null,
            $payload['order']['amount'] ?? null,
            $payload['order']['subtotal_price'] ?? null,
        ]);
    }

    private function firstMoney(array $values): float
    {
        foreach ($values as $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_string($value)) {
                $value = str_replace(['R$', ' '], '', $value);
                $value = str_replace('.', '', $value);
                $value = str_replace(',', '.', $value);
            }
            $float = (float) $value;
            if ($float > 0) {
                return $float;
            }
        }

        return 0.0;
    }

    private function extractTransactionId(array $payload, string $source): ?string
    {
        if ($source === 'hotmart') {
            return $payload['data']['purchase']['transaction'] ?? $payload['data']['purchase']['order_id'] ?? null;
        }

        return (string) (
            $payload['order']['id']
            ?? $payload['order']['order_id']
            ?? $payload['order']['order_number']
            ?? $payload['order']['token']
            ?? $payload['order']['transaction_id']
            ?? ''
        );
    }

    private function extractOrderReference(array $payload, string $source): ?string
    {
        if ($source === 'hotmart') {
            return $payload['data']['purchase']['order_id'] ?? $payload['data']['purchase']['transaction'] ?? null;
        }

        return (string) (
            $payload['order']['order_number']
            ?? $payload['order']['number']
            ?? $payload['order']['name']
            ?? $payload['order']['order_id']
            ?? $payload['order']['id']
            ?? ''
        );
    }

    private function documentType(string $document): string
    {
        $digits = preg_replace('/\D+/', '', $document);
        return strlen($digits) === 14 ? 'cnpj' : (strlen($digits) === 11 ? 'cpf' : 'unknown');
    }
}
