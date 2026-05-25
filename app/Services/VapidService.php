<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Service para gerar e gerenciar chaves VAPID
 * Gera automaticamente se não existirem
 */
class VapidService
{
    /**
     * Retorna as chaves VAPID, gerando automaticamente se necessário
     */
    public static function getKeys(): array
    {
        $publicKey = Setting::get('vapid_public_key', '');
        $privateKey = Setting::get('vapid_private_key', '');

        if (empty($publicKey) || empty($privateKey)) {
            $keys = self::generateKeys();
            if ($keys) {
                Setting::set('vapid_public_key', $keys['public']);
                Setting::set('vapid_private_key', $keys['private']);
                return $keys;
            }
            return ['public' => '', 'private' => ''];
        }

        return ['public' => $publicKey, 'private' => $privateKey];
    }

    /**
     * Gera par de chaves VAPID usando OpenSSL
     */
    public static function generateKeys(): ?array
    {
        if (!function_exists('openssl_pkey_new')) {
            error_log('[VAPID] OpenSSL não disponível');
            return null;
        }

        try {
            // Gera chave EC P-256
            $key = openssl_pkey_new([
                'curve_name' => 'prime256v1',
                'private_key_type' => OPENSSL_KEYTYPE_EC,
            ]);

            if (!$key) {
                error_log('[VAPID] Falha ao gerar chave EC');
                return null;
            }

            $details = openssl_pkey_get_details($key);
            if (!$details || !isset($details['ec'])) {
                error_log('[VAPID] Falha ao extrair detalhes da chave');
                return null;
            }

            // Extrair coordenadas x, y e d
            $x = $details['ec']['x'];
            $y = $details['ec']['y'];
            $d = $details['ec']['d'];

            // Chave pública = 0x04 + x + y (formato não comprimido, base64url)
            $publicKeyBin = "\x04" . str_pad($x, 32, "\x00", STR_PAD_LEFT) . str_pad($y, 32, "\x00", STR_PAD_LEFT);
            $publicKey = self::base64UrlEncode($publicKeyBin);

            // Chave privada = d (base64url)
            $privateKey = self::base64UrlEncode(str_pad($d, 32, "\x00", STR_PAD_LEFT));

            return [
                'public' => $publicKey,
                'private' => $privateKey,
            ];
        } catch (\Throwable $e) {
            error_log('[VAPID] Erro: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retorna apenas a chave pública (para o client JS)
     */
    public static function getPublicKey(): string
    {
        $keys = self::getKeys();
        return $keys['public'];
    }

    /**
     * Base64 URL-safe encoding
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
