<?php

namespace App\Services\Fiscal;

use RuntimeException;

/**
 * Cliente HTTP para Sefin Nacional e ADN NFS-e.
 */
class NfseNationalClient
{
    private string $environment;

    public function __construct(string $environment = 'restricted')
    {
        $this->environment = $environment === 'production' ? 'production' : 'restricted';
    }

    public function issue(string $signedDpsXml): array
    {
        return $this->requestJson('POST', $this->sefinBaseUrl() . '/nfse', [
            'dpsXmlGZipB64' => base64_encode(gzencode($signedDpsXml)),
        ]);
    }

    public function consult(string $accessKey): array
    {
        return $this->requestJson('GET', $this->sefinBaseUrl() . '/nfse/' . rawurlencode($accessKey));
    }

    public function cancel(string $accessKey, string $signedEventXml): array
    {
        return $this->requestJson('POST', $this->sefinBaseUrl() . '/nfse/' . rawurlencode($accessKey) . '/eventos', [
            'pedidoRegistroEventoXmlGZipB64' => base64_encode(gzencode($signedEventXml)),
        ]);
    }

    public function danfsePdf(string $accessKey): string
    {
        return $this->requestRaw('GET', $this->danfseBaseUrl() . '/danfse/' . rawurlencode($accessKey), null, [
            'Accept: application/pdf',
        ]);
    }

    private function requestJson(string $method, string $url, ?array $payload = null): array
    {
        $headers = ['Accept: application/json'];
        $body = null;

        if ($payload !== null) {
            $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
        }

        $raw = $this->requestRaw($method, $url, $body, $headers);
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return ['raw' => $raw];
    }

    private function requestRaw(string $method, string $url, ?string $body = null, array $headers = []): string
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensao cURL do PHP precisa estar ativa para emitir NFS-e.');
        }

        $pem = CertificateService::createTemporaryPem();
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSLCERT => $pem,
            CURLOPT_SSLKEY => $pem,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        @unlink($pem);

        if ($errno) {
            throw new RuntimeException('Falha de conexao com NFS-e: ' . $error);
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException("NFS-e retornou HTTP {$status}: " . substr((string) $response, 0, 2000));
        }

        return (string) $response;
    }

    private function sefinBaseUrl(): string
    {
        if ($this->environment === 'production') {
            return 'https://sefin.nfse.gov.br/SefinNacional';
        }

        return 'https://sefin.producaorestrita.nfse.gov.br/API/SefinNacional';
    }

    private function danfseBaseUrl(): string
    {
        if ($this->environment === 'production') {
            return 'https://adn.nfse.gov.br/danfse';
        }

        return 'https://adn.producaorestrita.nfse.gov.br/danfse';
    }
}
