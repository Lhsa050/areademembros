<?php

namespace App\Services\Fiscal;

use DOMDocument;
use DOMElement;
use RuntimeException;

/**
 * Assinatura XMLDSIG enveloped compatível com o padrão NFS-e.
 */
class XmlSigner
{
    private const XMLDSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';
    private const C14N = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private const ENVELOPED = 'http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private const RSA_SHA1 = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private const SHA1 = 'http://www.w3.org/2000/09/xmldsig#sha1';

    public static function sign(string $xml, string $tagName, string $idAttribute = 'Id'): string
    {
        $parts = CertificateService::readCertificateParts();
        if (!$parts || empty($parts['pkey']) || empty($parts['cert'])) {
            throw new RuntimeException('Certificado fiscal A1 nao configurado.');
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (!$dom->loadXML($xml)) {
            throw new RuntimeException('XML invalido para assinatura.');
        }

        $target = self::findTarget($dom, $tagName);
        $id = $target->getAttribute($idAttribute);
        if ($id === '') {
            throw new RuntimeException("Tag {$tagName} sem atributo {$idAttribute}.");
        }

        self::removeExistingSignature($dom);

        $digestValue = base64_encode(sha1($target->C14N(false, false), true));
        $signature = $dom->createElementNS(self::XMLDSIG_NS, 'Signature');
        $signedInfo = $dom->createElementNS(self::XMLDSIG_NS, 'SignedInfo');
        $signature->appendChild($signedInfo);

        self::appendMethod($dom, $signedInfo, 'CanonicalizationMethod', self::C14N);
        self::appendMethod($dom, $signedInfo, 'SignatureMethod', self::RSA_SHA1);

        $reference = $dom->createElementNS(self::XMLDSIG_NS, 'Reference');
        $reference->setAttribute('URI', '#' . $id);
        $signedInfo->appendChild($reference);

        $transforms = $dom->createElementNS(self::XMLDSIG_NS, 'Transforms');
        $reference->appendChild($transforms);
        self::appendMethod($dom, $transforms, 'Transform', self::ENVELOPED);
        self::appendMethod($dom, $transforms, 'Transform', self::C14N);
        self::appendMethod($dom, $reference, 'DigestMethod', self::SHA1);
        $reference->appendChild($dom->createElementNS(self::XMLDSIG_NS, 'DigestValue', $digestValue));

        $canonicalSignedInfo = $signedInfo->C14N(false, false);
        if (!openssl_sign($canonicalSignedInfo, $rawSignature, $parts['pkey'], OPENSSL_ALGO_SHA1)) {
            throw new RuntimeException('Falha ao assinar XML com o certificado.');
        }

        $signature->appendChild($dom->createElementNS(self::XMLDSIG_NS, 'SignatureValue', base64_encode($rawSignature)));

        $keyInfo = $dom->createElementNS(self::XMLDSIG_NS, 'KeyInfo');
        $x509Data = $dom->createElementNS(self::XMLDSIG_NS, 'X509Data');
        $certificate = self::cleanCertificate($parts['cert']);
        $x509Data->appendChild($dom->createElementNS(self::XMLDSIG_NS, 'X509Certificate', $certificate));
        $keyInfo->appendChild($x509Data);
        $signature->appendChild($keyInfo);

        $dom->documentElement->appendChild($signature);

        return $dom->saveXML();
    }

    private static function findTarget(DOMDocument $dom, string $tagName): DOMElement
    {
        $nodes = $dom->getElementsByTagName($tagName);
        if ($nodes->length === 0) {
            throw new RuntimeException("Tag {$tagName} nao encontrada no XML.");
        }

        $node = $nodes->item(0);
        if (!$node instanceof DOMElement) {
            throw new RuntimeException("Tag {$tagName} invalida.");
        }

        return $node;
    }

    private static function appendMethod(DOMDocument $dom, DOMElement $parent, string $name, string $algorithm): void
    {
        $element = $dom->createElementNS(self::XMLDSIG_NS, $name);
        $element->setAttribute('Algorithm', $algorithm);
        $parent->appendChild($element);
    }

    private static function removeExistingSignature(DOMDocument $dom): void
    {
        $signatures = $dom->getElementsByTagNameNS(self::XMLDSIG_NS, 'Signature');
        for ($i = $signatures->length - 1; $i >= 0; $i--) {
            $node = $signatures->item($i);
            if ($node && $node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    private static function cleanCertificate(string $certificate): string
    {
        return trim(str_replace([
            '-----BEGIN CERTIFICATE-----',
            '-----END CERTIFICATE-----',
            "\r",
            "\n",
            ' ',
        ], '', $certificate));
    }
}
