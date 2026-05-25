<?php

namespace App\Services\Fiscal;

use DOMDocument;
use DOMElement;

/**
 * Monta XMLs do padrão nacional NFS-e a partir das configurações fiscais.
 */
class NfseXmlBuilder
{
    private const NS = 'http://www.sped.fazenda.gov.br/nfse';

    public static function buildDps(array $settings, array $sale, array $invoice): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dps = $dom->createElementNS(self::NS, 'DPS');
        $dps->setAttribute('versao', '1.01');
        $dom->appendChild($dps);

        $inf = $dom->createElementNS(self::NS, 'infDPS');
        $inf->setAttribute('Id', $invoice['dps_id']);
        $dps->appendChild($inf);

        self::append($dom, $inf, 'tpAmb', self::environmentCode($settings));
        self::append($dom, $inf, 'dhEmi', date('c'));
        self::append($dom, $inf, 'verAplic', 'MembrosFiscal1.0');
        self::append($dom, $inf, 'serie', (string) $invoice['dps_series']);
        self::append($dom, $inf, 'nDPS', (string) $invoice['dps_number']);
        self::append($dom, $inf, 'dCompet', date('Y-m-d', strtotime($sale['paid_at'] ?? 'now')));
        self::append($dom, $inf, 'tpEmit', '1');
        self::append($dom, $inf, 'cLocEmi', self::digits($settings['fiscal_company_municipality_code'] ?? ''));

        $prest = $dom->createElementNS(self::NS, 'prest');
        $inf->appendChild($prest);
        self::append($dom, $prest, 'CNPJ', self::digits($settings['fiscal_company_cnpj'] ?? ''));
        if (!empty($settings['fiscal_company_im'])) {
            self::append($dom, $prest, 'IM', trim($settings['fiscal_company_im']));
        }
        if (!empty($settings['fiscal_company_phone'])) {
            self::append($dom, $prest, 'fone', self::digits($settings['fiscal_company_phone']));
        }
        if (!empty($settings['fiscal_company_email'])) {
            self::append($dom, $prest, 'email', trim($settings['fiscal_company_email']));
        }

        $regTrib = $dom->createElementNS(self::NS, 'regTrib');
        $prest->appendChild($regTrib);
        self::append($dom, $regTrib, 'opSimpNac', (string) ($settings['fiscal_op_simp_nac'] ?? '1'));
        self::append($dom, $regTrib, 'regEspTrib', (string) ($settings['fiscal_reg_esp_trib'] ?? '0'));

        $doc = self::digits($sale['customer_document'] ?? '');
        if ($doc !== '') {
            $toma = $dom->createElementNS(self::NS, 'toma');
            $inf->appendChild($toma);
            self::append($dom, $toma, strlen($doc) === 14 ? 'CNPJ' : 'CPF', $doc);
            self::append($dom, $toma, 'xNome', self::limit($sale['customer_name'] ?: 'Consumidor', 150));
            if (!empty($sale['customer_phone'])) {
                self::append($dom, $toma, 'fone', self::digits($sale['customer_phone']));
            }
            if (!empty($sale['customer_email'])) {
                self::append($dom, $toma, 'email', trim($sale['customer_email']));
            }
        }

        $serv = $dom->createElementNS(self::NS, 'serv');
        $inf->appendChild($serv);
        $locPrest = $dom->createElementNS(self::NS, 'locPrest');
        $serv->appendChild($locPrest);
        self::append($dom, $locPrest, 'cLocPrestacao', self::digits($settings['fiscal_service_municipality_code'] ?? $settings['fiscal_company_municipality_code'] ?? ''));

        $cServ = $dom->createElementNS(self::NS, 'cServ');
        $serv->appendChild($cServ);
        self::append($dom, $cServ, 'cTribNac', self::serviceCode($invoice['service_code']));
        self::append($dom, $cServ, 'xDescServ', self::limit($invoice['service_description'], 2000));
        if (!empty($settings['fiscal_nbs_code'])) {
            self::append($dom, $cServ, 'cNBS', self::digits($settings['fiscal_nbs_code']));
        }

        $valores = $dom->createElementNS(self::NS, 'valores');
        $inf->appendChild($valores);
        $vServPrest = $dom->createElementNS(self::NS, 'vServPrest');
        $valores->appendChild($vServPrest);
        self::append($dom, $vServPrest, 'vServ', self::money($sale['amount']));

        $trib = $dom->createElementNS(self::NS, 'trib');
        $valores->appendChild($trib);
        $tribMun = $dom->createElementNS(self::NS, 'tribMun');
        $trib->appendChild($tribMun);
        self::append($dom, $tribMun, 'tribISSQN', (string) ($settings['fiscal_trib_issqn'] ?? '1'));
        self::append($dom, $tribMun, 'tpRetISSQN', (string) ($settings['fiscal_tp_ret_issqn'] ?? '1'));

        $totTrib = $dom->createElementNS(self::NS, 'totTrib');
        $trib->appendChild($totTrib);
        self::append($dom, $totTrib, 'indTotTrib', (string) ($settings['fiscal_ind_tot_trib'] ?? '0'));

        return $dom->saveXML();
    }

    public static function buildCancelEvent(array $settings, string $accessKey, string $reason, string $reasonCode = '1'): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;

        $root = $dom->createElementNS(self::NS, 'pedRegEvento');
        $root->setAttribute('versao', '1.01');
        $dom->appendChild($root);

        $inf = $dom->createElementNS(self::NS, 'infPedReg');
        $inf->setAttribute('Id', 'PRE' . $accessKey . '101101');
        $root->appendChild($inf);

        self::append($dom, $inf, 'tpAmb', self::environmentCode($settings));
        self::append($dom, $inf, 'verAplic', 'MembrosFiscal1.0');
        self::append($dom, $inf, 'dhEvento', date('c'));
        self::append($dom, $inf, 'CNPJAutor', self::digits($settings['fiscal_company_cnpj'] ?? ''));
        self::append($dom, $inf, 'chNFSe', self::digits($accessKey));

        $event = $dom->createElementNS(self::NS, 'e101101');
        $inf->appendChild($event);
        self::append($dom, $event, 'xDesc', 'Cancelamento de NFS-e');
        self::append($dom, $event, 'cMotivo', $reasonCode);
        self::append($dom, $event, 'xMotivo', self::limit($reason, 255));

        return $dom->saveXML();
    }

    public static function buildDpsId(string $municipalityCode, string $federalDocument, string $series, int $number): string
    {
        $doc = self::digits($federalDocument);
        $type = strlen($doc) === 14 ? '2' : '1';
        $doc = str_pad($doc, 14, '0', STR_PAD_LEFT);
        $series = str_pad(self::digits($series), 5, '0', STR_PAD_LEFT);
        $number = str_pad((string) $number, 15, '0', STR_PAD_LEFT);

        return 'DPS' . self::digits($municipalityCode) . $type . $doc . $series . $number;
    }

    private static function append(DOMDocument $dom, DOMElement $parent, string $name, string $value): DOMElement
    {
        $node = $dom->createElementNS(self::NS, $name);
        $node->appendChild($dom->createTextNode($value));
        $parent->appendChild($node);
        return $node;
    }

    private static function environmentCode(array $settings): string
    {
        return ($settings['fiscal_environment'] ?? 'restricted') === 'production' ? '1' : '2';
    }

    private static function serviceCode(string $value): string
    {
        return self::digits($value);
    }

    private static function digits(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value);
    }

    private static function money(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private static function limit(string $value, int $max): string
    {
        $value = trim($value);
        return strlen($value) > $max ? substr($value, 0, $max) : $value;
    }
}
