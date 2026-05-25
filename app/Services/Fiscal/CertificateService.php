<?php

namespace App\Services\Fiscal;

use App\Models\Setting;
use RuntimeException;

/**
 * Guarda e abre certificado A1 sem expor a senha em claro no banco.
 */
class CertificateService
{
    public static function saveUploaded(array $file, string $password): array
    {
        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Envie um arquivo de certificado A1 (.pfx ou .p12).');
        }

        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['pfx', 'p12'], true)) {
            throw new RuntimeException('O certificado precisa ser um arquivo .pfx ou .p12.');
        }

        $contents = file_get_contents($file['tmp_name']);
        if ($contents === false || $contents === '') {
            throw new RuntimeException('Nao foi possivel ler o certificado enviado.');
        }

        $certs = [];
        if (!openssl_pkcs12_read($contents, $certs, $password)) {
            throw new RuntimeException('Nao foi possivel abrir o certificado. Confira a senha do A1.');
        }

        $certDir = self::certDir();
        if (!is_dir($certDir)) {
            mkdir($certDir, 0700, true);
        }

        $filename = 'nfse-a1-' . date('YmdHis') . '.' . $ext;
        $path = $certDir . '/' . $filename;
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Nao foi possivel salvar o certificado.');
        }
        @chmod($path, 0600);

        $oldPath = Setting::get('fiscal_certificate_path');
        if ($oldPath && is_file($oldPath) && realpath(dirname($oldPath)) === realpath($certDir)) {
            @unlink($oldPath);
        }

        Setting::set('fiscal_certificate_path', $path);
        Setting::set('fiscal_certificate_password', self::encrypt($password));

        return self::certificateInfoFromParts($certs);
    }

    public static function getCertificateInfo(): ?array
    {
        $parts = self::readCertificateParts();
        return $parts ? self::certificateInfoFromParts($parts) : null;
    }

    public static function readCertificateParts(): ?array
    {
        $path = Setting::get('fiscal_certificate_path');
        $encryptedPassword = Setting::get('fiscal_certificate_password');

        if (!$path || !$encryptedPassword || !is_file($path)) {
            return null;
        }

        $password = self::decrypt($encryptedPassword);
        $contents = file_get_contents($path);
        $certs = [];

        if ($contents === false || !openssl_pkcs12_read($contents, $certs, $password)) {
            throw new RuntimeException('Nao foi possivel abrir o certificado fiscal salvo.');
        }

        return $certs;
    }

    public static function createTemporaryPem(): string
    {
        $parts = self::readCertificateParts();
        if (!$parts || empty($parts['cert']) || empty($parts['pkey'])) {
            throw new RuntimeException('Certificado fiscal nao configurado.');
        }

        $pem = $parts['cert'] . "\n" . $parts['pkey'];
        if (!empty($parts['extracerts']) && is_array($parts['extracerts'])) {
            $pem .= "\n" . implode("\n", $parts['extracerts']);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'nfse_cert_');
        if ($tmp === false || file_put_contents($tmp, $pem) === false) {
            throw new RuntimeException('Nao foi possivel preparar o certificado para conexao.');
        }

        @chmod($tmp, 0600);
        return $tmp;
    }

    private static function certificateInfoFromParts(array $parts): array
    {
        $parsed = openssl_x509_parse($parts['cert'] ?? '');

        return [
            'subject' => $parsed['subject']['CN'] ?? 'Certificado A1',
            'issuer' => $parsed['issuer']['CN'] ?? '',
            'valid_from' => isset($parsed['validFrom_time_t']) ? date('d/m/Y H:i', $parsed['validFrom_time_t']) : '',
            'valid_to' => isset($parsed['validTo_time_t']) ? date('d/m/Y H:i', $parsed['validTo_time_t']) : '',
            'serial' => $parsed['serialNumberHex'] ?? ($parsed['serialNumber'] ?? ''),
        ];
    }

    private static function certDir(): string
    {
        return ABSPATH . '/storage/certificates';
    }

    private static function encrypt(string $value): string
    {
        $key = hash('sha256', (string) env('APP_KEY', 'membros-fiscal'), true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($value, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($cipher === false) {
            throw new RuntimeException('Nao foi possivel proteger a senha do certificado.');
        }

        return base64_encode($iv . $cipher);
    }

    private static function decrypt(string $value): string
    {
        $raw = base64_decode($value, true);
        if ($raw === false || strlen($raw) < 17) {
            throw new RuntimeException('Senha do certificado invalida.');
        }

        $key = hash('sha256', (string) env('APP_KEY', 'membros-fiscal'), true);
        $iv = substr($raw, 0, 16);
        $cipher = substr($raw, 16);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            throw new RuntimeException('Nao foi possivel abrir a senha do certificado.');
        }

        return $plain;
    }
}
