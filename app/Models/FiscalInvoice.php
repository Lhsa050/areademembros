<?php

namespace App\Models;

use App\Core\Database;

/**
 * Documento fiscal emitido ou tentado para uma venda.
 */
class FiscalInvoice extends BaseModel
{
    protected static string $table = 'fiscal_invoices';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'sale_id', 'provider', 'environment', 'document_type', 'status',
        'dps_series', 'dps_number', 'dps_id', 'access_key', 'verification_code',
        'issued_at', 'canceled_at', 'cancel_reason', 'total_amount',
        'service_code', 'service_description', 'xml_path', 'pdf_path',
        'request_payload', 'response_payload', 'errors',
    ];

    public static function findBySale(int $saleId): ?array
    {
        return Database::fetch(
            'SELECT * FROM fiscal_invoices WHERE sale_id = ? ORDER BY id DESC LIMIT 1',
            [$saleId]
        );
    }

    public static function withSale(int $id): ?array
    {
        return Database::fetch(
            "SELECT fi.*, fs.customer_name, fs.customer_email, fs.customer_document, fs.amount, fs.status AS sale_status
             FROM fiscal_invoices fi
             INNER JOIN fiscal_sales fs ON fs.id = fi.sale_id
             WHERE fi.id = ?",
            [$id]
        );
    }

    public static function markIssued(int $id, array $data): void
    {
        Database::query(
            "UPDATE fiscal_invoices
             SET status = 'issued',
                 access_key = ?,
                 verification_code = ?,
                 issued_at = COALESCE(?, NOW()),
                 xml_path = ?,
                 pdf_path = ?,
                 response_payload = ?,
                 errors = NULL,
                 updated_at = NOW()
             WHERE id = ?",
            [
                $data['access_key'] ?? null,
                $data['verification_code'] ?? null,
                $data['issued_at'] ?? null,
                $data['xml_path'] ?? null,
                $data['pdf_path'] ?? null,
                $data['response_payload'] ?? null,
                $id,
            ]
        );
    }

    public static function markRejected(int $id, string $errors, ?string $responsePayload = null): void
    {
        Database::query(
            "UPDATE fiscal_invoices
             SET status = 'rejected', errors = ?, response_payload = ?, updated_at = NOW()
             WHERE id = ?",
            [$errors, $responsePayload, $id]
        );
    }

    public static function markCancelError(int $id, string $errors, ?string $responsePayload = null): void
    {
        Database::query(
            "UPDATE fiscal_invoices
             SET status = 'cancel_error', errors = ?, response_payload = ?, updated_at = NOW()
             WHERE id = ?",
            [$errors, $responsePayload, $id]
        );
    }

    public static function markCanceled(int $id, string $reason, ?string $responsePayload = null): void
    {
        Database::query(
            "UPDATE fiscal_invoices
             SET status = 'canceled', cancel_reason = ?, canceled_at = NOW(), response_payload = ?, updated_at = NOW()
             WHERE id = ?",
            [$reason, $responsePayload, $id]
        );
    }
}
