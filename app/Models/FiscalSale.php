<?php

namespace App\Models;

use App\Core\Database;

/**
 * Venda fiscal capturada a partir de webhooks ou lançada manualmente.
 */
class FiscalSale extends BaseModel
{
    protected static string $table = 'fiscal_sales';
    protected static bool $useUuid = true;
    protected static array $fillable = [
        'uuid', 'funnel_id', 'member_id', 'product_id', 'offer_id',
        'source_platform', 'source_event', 'transaction_id', 'order_reference',
        'customer_name', 'customer_email', 'customer_document', 'customer_document_type', 'customer_phone',
        'amount', 'currency', 'status', 'invoice_status', 'payload', 'paid_at', 'refunded_at',
    ];

    public static function findDuplicate(string $sourcePlatform, ?string $transactionId, ?int $productId = null, ?int $offerId = null): ?array
    {
        if (!$transactionId) {
            return null;
        }

        return Database::fetch(
            "SELECT * FROM fiscal_sales
             WHERE source_platform = ?
               AND transaction_id = ?
               AND ((product_id <=> ?) AND (offer_id <=> ?))
             LIMIT 1",
            [$sourcePlatform, $transactionId, $productId, $offerId]
        );
    }

    public static function latestForRefund(string $sourcePlatform, ?string $transactionId, ?int $productId = null, ?int $offerId = null, ?string $email = null): ?array
    {
        if ($transactionId) {
            $sale = self::findDuplicate($sourcePlatform, $transactionId, $productId, $offerId);
            if ($sale) {
                return $sale;
            }
        }

        $where = ["source_platform = ?", "status = 'paid'"];
        $params = [$sourcePlatform];

        if ($productId) {
            $where[] = 'product_id = ?';
            $params[] = $productId;
        }

        if ($offerId) {
            $where[] = 'offer_id = ?';
            $params[] = $offerId;
        }

        if ($email) {
            $where[] = 'customer_email = ?';
            $params[] = strtolower(trim($email));
        }

        return Database::fetch(
            'SELECT * FROM fiscal_sales WHERE ' . implode(' AND ', $where) . ' ORDER BY paid_at DESC, id DESC LIMIT 1',
            $params
        );
    }

    public static function markRefunded(int $id): void
    {
        Database::query(
            "UPDATE fiscal_sales
             SET status = 'refunded', refunded_at = NOW(), updated_at = NOW()
             WHERE id = ?",
            [$id]
        );
    }

    public static function updateInvoiceStatus(int $id, string $status): void
    {
        Database::query(
            'UPDATE fiscal_sales SET invoice_status = ?, updated_at = NOW() WHERE id = ?',
            [$status, $id]
        );
    }

    public static function search(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        [$where, $params] = self::buildSearchWhere($filters);

        return Database::fetchAll(
            "SELECT fs.*, f.name AS funnel_name, p.title AS product_title, o.title AS offer_title,
                    fi.id AS invoice_id, fi.status AS fiscal_status, fi.access_key, fi.xml_path, fi.pdf_path, fi.issued_at, fi.canceled_at
             FROM fiscal_sales fs
             LEFT JOIN funnels f ON f.id = fs.funnel_id
             LEFT JOIN products p ON p.id = fs.product_id
             LEFT JOIN offers o ON o.id = fs.offer_id
             LEFT JOIN fiscal_invoices fi ON fi.id = (
                 SELECT fi2.id FROM fiscal_invoices fi2
                 WHERE fi2.sale_id = fs.id
                 ORDER BY fi2.id DESC
                 LIMIT 1
             )
             {$where}
             ORDER BY fs.created_at DESC, fs.id DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );
    }

    public static function countSearch(array $filters = []): int
    {
        [$where, $params] = self::buildSearchWhere($filters);
        $row = Database::fetch("SELECT COUNT(*) AS total FROM fiscal_sales fs {$where}", $params);
        return (int) ($row['total'] ?? 0);
    }

    public static function totals(): array
    {
        $row = Database::fetch(
            "SELECT
                COUNT(*) AS total_sales,
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS paid_amount,
                SUM(CASE WHEN invoice_status = 'issued' THEN 1 ELSE 0 END) AS issued_count,
                SUM(CASE WHEN invoice_status IN ('error','rejected') THEN 1 ELSE 0 END) AS error_count,
                SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) AS refunded_count
             FROM fiscal_sales"
        );

        return [
            'total_sales' => (int) ($row['total_sales'] ?? 0),
            'paid_amount' => (float) ($row['paid_amount'] ?? 0),
            'issued_count' => (int) ($row['issued_count'] ?? 0),
            'error_count' => (int) ($row['error_count'] ?? 0),
            'refunded_count' => (int) ($row['refunded_count'] ?? 0),
        ];
    }

    private static function buildSearchWhere(array $filters): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = 'fs.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['invoice_status'])) {
            $where[] = 'fs.invoice_status = ?';
            $params[] = $filters['invoice_status'];
        }

        if (!empty($filters['q'])) {
            $search = '%' . trim($filters['q']) . '%';
            $where[] = '(fs.customer_name LIKE ? OR fs.customer_email LIKE ? OR fs.customer_document LIKE ? OR fs.transaction_id LIKE ?)';
            array_push($params, $search, $search, $search, $search);
        }

        return [empty($where) ? '' : 'WHERE ' . implode(' AND ', $where), $params];
    }
}
