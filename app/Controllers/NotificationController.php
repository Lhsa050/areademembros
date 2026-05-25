<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\MemberAuth;
use App\Core\Security;
use App\Core\Database;
use App\Models\PushSubscription;
use App\Models\PushNotification;
use App\Models\Funnel;
use App\Models\Setting;

/**
 * Controller de Notificações Push
 */
class NotificationController
{
    /**
     * API: Registra subscription (chamado pelo client JS)
     */
    public function subscribe(string $slug): void
    {
        $funnel = Funnel::findBy('slug', $slug);
        if (!$funnel) {
            json_response(['error' => 'Funnel not found'], 404);
        }

        MemberAuth::require($slug);
        $member = MemberAuth::user();

        $rawPayload = file_get_contents('php://input');
        $data = json_decode($rawPayload, true);

        if (empty($data['endpoint']) || empty($data['keys']['p256dh']) || empty($data['keys']['auth'])) {
            json_response(['error' => 'Invalid subscription data'], 400);
        }

        // Verifica se já existe
        $existing = PushSubscription::findByEndpoint($data['endpoint'], (int) $funnel['id']);
        if ($existing) {
            json_response(['success' => true, 'message' => 'Already subscribed']);
        }

        PushSubscription::create([
            'member_id' => (int) $member['id'],
            'funnel_id' => (int) $funnel['id'],
            'endpoint' => $data['endpoint'],
            'p256dh' => $data['keys']['p256dh'],
            'auth_key' => $data['keys']['auth'],
        ]);

        json_response(['success' => true]);
    }

    /**
     * Admin: Lista notificações enviadas do funil
     */
    public function index(string $funnelId): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        $notifications = PushNotification::getByFunnel((int) $funnelId);
        $subscriberCount = PushSubscription::count('funnel_id = ?', [(int) $funnelId]);
        
        // Auto-gera chaves VAPID se não existirem
        $vapidKeys = \App\Services\VapidService::getKeys();
        $vapidPublicKey = $vapidKeys['public'];

        view('admin.funnels.notifications.index', [
            'funnel' => $funnel,
            'notifications' => $notifications,
            'subscriberCount' => $subscriberCount,
            'vapidConfigured' => !empty($vapidPublicKey),
            'user' => Auth::user()
        ]);
    }

    /**
     * Admin: Envia notificação push para todos os subscribers do funil
     */
    public function send(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            json_response(['error' => 'Funil não encontrado'], 404);
        }

        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $pushUrl = trim($_POST['url'] ?? '');

        if (empty($title) || empty($body)) {
            flash('error', 'Título e mensagem são obrigatórios.');
            back();
        }

        $vapidKeys = \App\Services\VapidService::getKeys();
        $vapidPublicKey = $vapidKeys['public'];
        $vapidPrivateKey = $vapidKeys['private'];

        if (empty($vapidPublicKey) || empty($vapidPrivateKey)) {
            flash('error', 'Chaves VAPID não puderam ser geradas. Verifique se o OpenSSL está disponível.');
            back();
        }

        // Busca subscriptions do funil
        $subscriptions = PushSubscription::getByFunnel((int) $funnelId);

        if (empty($subscriptions)) {
            flash('error', 'Nenhum subscriber neste funil.');
            back();
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $pushUrl ?: url('/m/' . $funnel['slug'] . '/dashboard'),
            'icon' => url('/assets/images/pwa-icon.php?s=192'),
        ]);

        $sentCount = 0;
        $failedEndpoints = [];

        foreach ($subscriptions as $sub) {
            $success = $this->sendPushNotification(
                $sub['endpoint'],
                $sub['p256dh'],
                $sub['auth_key'],
                $payload,
                $vapidPublicKey,
                $vapidPrivateKey
            );

            if ($success) {
                $sentCount++;
            } else {
                $failedEndpoints[] = $sub['endpoint'];
            }
        }

        // Remove endpoints que falharam (expirados)
        foreach ($failedEndpoints as $endpoint) {
            PushSubscription::removeByEndpoint($endpoint);
        }

        // Salva registro
        PushNotification::create([
            'funnel_id' => (int) $funnelId,
            'title' => $title,
            'body' => $body,
            'url' => $pushUrl,
            'sent_count' => $sentCount,
        ]);

        flash('success', "Notificação enviada para {$sentCount} de " . count($subscriptions) . " subscribers!");
        redirect(url('/funnels/' . $funnelId . '/notifications'));
    }

    /**
     * Envia uma notificação push via Web Push Protocol (VAPID)
     */
    private function sendPushNotification(
        string $endpoint,
        string $p256dh,
        string $authKey,
        string $payload,
        string $vapidPublicKey,
        string $vapidPrivateKey
    ): bool {
        // Tenta usar minishlink/web-push se disponível
        if (class_exists('Minishlink\WebPush\WebPush')) {
            return $this->sendViaLibrary($endpoint, $p256dh, $authKey, $payload, $vapidPublicKey, $vapidPrivateKey);
        }

        // Fallback: POST direto (funciona sem payload criptografado para endpoints simples)
        // Para produção completa, instale: composer require minishlink/web-push
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($payload),
                'TTL: 86400',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 201 = Created (success), 410 = Gone (subscription expired)
        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Envia via biblioteca minishlink/web-push (se instalada)
     */
    private function sendViaLibrary(
        string $endpoint,
        string $p256dh,
        string $authKey,
        string $payload,
        string $vapidPublicKey,
        string $vapidPrivateKey
    ): bool {
        try {
            $auth = [
                'VAPID' => [
                    'subject' => 'mailto:admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
                    'publicKey' => $vapidPublicKey,
                    'privateKey' => $vapidPrivateKey,
                ],
            ];

            $webPush = new \Minishlink\WebPush\WebPush($auth);
            $subscription = \Minishlink\WebPush\Subscription::create([
                'endpoint' => $endpoint,
                'publicKey' => $p256dh,
                'authToken' => $authKey,
            ]);

            $report = $webPush->sendOneNotification($subscription, $payload);
            return $report->isSuccess();
        } catch (\Throwable $e) {
            error_log('[Push] Error: ' . $e->getMessage());
            return false;
        }
    }
}
