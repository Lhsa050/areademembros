<?php

namespace App\Models;

use App\Core\Database;

/**
 * Compra/acesso por pedido recebido via webhook.
 */
class MemberProductOrder extends BaseModel
{
    protected static string $table = 'member_product_orders';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'funnel_id', 'member_id', 'customer_email', 'product_id', 'course_id',
        'order_id', 'order_number', 'source_platform', 'source_event',
        'external_product_id', 'payment_method', 'payment_status', 'access_status',
        'paid_at', 'refunded_at',
    ];

    private const ALLOWED_STATUSES = ['pending', 'paid', 'cancelled', 'refunded', 'chargeback'];

    public static function saveStatus(array $data): array
    {
        self::ensureTable();

        $source = trim((string) ($data['source_platform'] ?? 'cartpanda')) ?: 'cartpanda';
        $orderId = trim((string) ($data['order_id'] ?? ''));
        $productId = (int) ($data['product_id'] ?? 0);

        if ($orderId === '' || $productId <= 0) {
            throw new \InvalidArgumentException('Pedido/produto invalido para registrar compra.');
        }

        $status = self::normalizeStatus((string) ($data['payment_status'] ?? 'pending'));
        $existing = self::findByOrderProduct($orderId, $productId, $source);
        $now = date('Y-m-d H:i:s');

        $paidAt = $existing['paid_at'] ?? null;
        $refundedAt = $existing['refunded_at'] ?? null;
        $accessStatus = $existing['access_status'] ?? 'none';
        $paymentStatus = $existing['payment_status'] ?? 'pending';

        if ($status === 'paid') {
            $paymentStatus = 'paid';
            $accessStatus = 'active';
            $paidAt = self::dateOrNull($data['paid_at'] ?? null) ?? $paidAt ?? $now;
        } elseif (in_array($status, ['refunded', 'chargeback'], true)) {
            $paymentStatus = $status;
            $accessStatus = 'revoked';
            $refundedAt = self::dateOrNull($data['refunded_at'] ?? null) ?? $refundedAt ?? $now;
        } elseif ($status === 'cancelled') {
            if (in_array($paymentStatus, ['refunded', 'chargeback'], true)) {
                $accessStatus = 'revoked';
            } else {
                $paymentStatus = 'cancelled';
                if ($accessStatus !== 'active') {
                    $accessStatus = 'none';
                }
            }
        } elseif (!$paidAt && !in_array($paymentStatus, ['refunded', 'chargeback'], true)) {
            $paymentStatus = 'pending';
            if ($accessStatus !== 'active') {
                $accessStatus = 'none';
            }
        }

        $fields = [
            'funnel_id' => (int) ($data['funnel_id'] ?? 0),
            'member_id' => !empty($data['member_id']) ? (int) $data['member_id'] : null,
            'customer_email' => strtolower(trim((string) ($data['customer_email'] ?? ''))),
            'product_id' => $productId,
            'course_id' => !empty($data['course_id']) ? (int) $data['course_id'] : null,
            'order_id' => $orderId,
            'order_number' => self::nullIfEmpty($data['order_number'] ?? null),
            'source_platform' => $source,
            'source_event' => self::nullIfEmpty($data['source_event'] ?? null),
            'external_product_id' => self::nullIfEmpty($data['external_product_id'] ?? null),
            'payment_method' => self::nullIfEmpty($data['payment_method'] ?? null),
            'payment_status' => $paymentStatus,
            'access_status' => $accessStatus,
            'paid_at' => $paidAt,
            'refunded_at' => $refundedAt,
        ];

        if ($existing) {
            Database::query(
                "UPDATE member_product_orders
                 SET funnel_id = ?, member_id = COALESCE(?, member_id), customer_email = ?,
                     order_number = COALESCE(?, order_number), source_event = ?,
                     external_product_id = COALESCE(?, external_product_id),
                     payment_method = COALESCE(?, payment_method),
                     payment_status = ?, access_status = ?, paid_at = ?, refunded_at = ?,
                     updated_at = NOW()
                 WHERE id = ?",
                [
                    $fields['funnel_id'],
                    $fields['member_id'],
                    $fields['customer_email'],
                    $fields['order_number'],
                    $fields['source_event'],
                    $fields['external_product_id'],
                    $fields['payment_method'],
                    $fields['payment_status'],
                    $fields['access_status'],
                    $fields['paid_at'],
                    $fields['refunded_at'],
                    $existing['id'],
                ]
            );
        } else {
            Database::query(
                "INSERT INTO member_product_orders
                    (funnel_id, member_id, customer_email, product_id, course_id, order_id,
                     order_number, source_platform, source_event, external_product_id,
                     payment_method, payment_status, access_status, paid_at, refunded_at,
                     created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
                [
                    $fields['funnel_id'],
                    $fields['member_id'],
                    $fields['customer_email'],
                    $fields['product_id'],
                    $fields['course_id'],
                    $fields['order_id'],
                    $fields['order_number'],
                    $fields['source_platform'],
                    $fields['source_event'],
                    $fields['external_product_id'],
                    $fields['payment_method'],
                    $fields['payment_status'],
                    $fields['access_status'],
                    $fields['paid_at'],
                    $fields['refunded_at'],
                ]
            );
        }

        return self::findByOrderProduct($orderId, $productId, $source) ?? [];
    }

    public static function findByOrderProduct(string $orderId, int $productId, string $source = 'cartpanda'): ?array
    {
        self::ensureTable();

        return Database::fetch(
            "SELECT * FROM member_product_orders
             WHERE source_platform = ? AND order_id = ? AND product_id = ?
             LIMIT 1",
            [$source, $orderId, $productId]
        );
    }

    public static function wasPaid(?array $order): bool
    {
        if (!$order) {
            return false;
        }

        return !empty($order['paid_at']) || ($order['payment_status'] ?? '') === 'paid';
    }

    public static function hasAnotherPaidActiveOrder(
        int $memberId,
        int $productId,
        string $exceptOrderId,
        string $source = 'cartpanda'
    ): bool {
        self::ensureTable();

        $row = Database::fetch(
            "SELECT id FROM member_product_orders
             WHERE source_platform = ?
               AND member_id = ?
               AND product_id = ?
               AND order_id <> ?
               AND paid_at IS NOT NULL
               AND access_status = 'active'
               AND payment_status NOT IN ('refunded', 'chargeback')
             LIMIT 1",
            [$source, $memberId, $productId, $exceptOrderId]
        );

        return (bool) $row;
    }

    public static function markAccessRevoked(string $orderId, int $productId, string $source = 'cartpanda'): void
    {
        self::ensureTable();

        Database::query(
            "UPDATE member_product_orders
             SET access_status = 'revoked', updated_at = NOW()
             WHERE source_platform = ? AND order_id = ? AND product_id = ?",
            [$source, $orderId, $productId]
        );
    }

    private static function ensureTable(): void
    {
        static $ready = false;

        if ($ready) {
            return;
        }

        Database::query(
            "CREATE TABLE IF NOT EXISTS member_product_orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                funnel_id INT NOT NULL,
                member_id INT NULL,
                customer_email VARCHAR(191) NOT NULL,
                product_id INT NOT NULL,
                course_id INT NULL,
                order_id VARCHAR(191) NOT NULL,
                order_number VARCHAR(191) NULL,
                source_platform VARCHAR(50) NOT NULL DEFAULT 'cartpanda',
                source_event VARCHAR(100) NULL,
                external_product_id VARCHAR(191) NULL,
                payment_method VARCHAR(80) NULL,
                payment_status ENUM('pending','paid','cancelled','refunded','chargeback') NOT NULL DEFAULT 'pending',
                access_status ENUM('none','active','revoked') NOT NULL DEFAULT 'none',
                paid_at DATETIME NULL,
                refunded_at DATETIME NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_order_product (source_platform, order_id, product_id),
                INDEX idx_member_product (member_id, product_id),
                INDEX idx_email_product (customer_email, product_id),
                INDEX idx_order (order_id),
                INDEX idx_status (payment_status, access_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $ready = true;
    }

    private static function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        return in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'pending';
    }

    private static function nullIfEmpty($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    private static function dateOrNull($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int) $value);
        }

        $timestamp = strtotime((string) $value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }
}
