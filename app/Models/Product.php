<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Produtos
 */
class Product extends BaseModel
{
    protected static string $table = 'products';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'funnel_id', 'type', 'title', 'description', 'image', 'file', 'checkout_url', 'webhook_token', 'external_product_id', 'sort_order', 'release_days', 'is_public',
        'price', 'fiscal_kind', 'fiscal_service_code', 'fiscal_service_description', 'fiscal_nbs_code', 'fiscal_iss_rate',
        'fiscal_tax_group_id', 'fiscal_document_model', 'fiscal_issue_policy', 'fiscal_warranty_days',
        'fiscal_lc116_code', 'fiscal_municipal_service_code', 'fiscal_cnae_code'
    ];

    /**
     * Retorna produtos de um funil ordenados
     */
    public static function getByFunnel(int $funnelId): array
    {
        if (self::supportsFunnelProducts()) {
            $linked = Database::fetchAll(
                "SELECT p.*,
                        p.funnel_id AS original_funnel_id,
                        fp.id AS funnel_product_id,
                        fp.funnel_id AS linked_funnel_id,
                        fp.checkout_url AS linked_checkout_url,
                        fp.webhook_token AS linked_webhook_token,
                        fp.sort_order AS linked_sort_order,
                        fp.release_days AS linked_release_days,
                        fp.is_public AS linked_is_public,
                        fp.external_product_id AS linked_external_product_id,
                        fp.funnel_role
                 FROM funnel_products fp
                 INNER JOIN products p ON p.id = fp.product_id
                 WHERE fp.funnel_id = ?",
                [$funnelId]
            );

            $products = array_map(fn(array $row) => self::applyFunnelLink($row), $linked);

            // Compatibilidade: se a migracao ainda nao rodou, produtos antigos ligados
            // diretamente por products.funnel_id continuam aparecendo no funil.
            $legacy = Database::fetchAll(
                "SELECT p.*, p.funnel_id AS original_funnel_id, NULL AS funnel_product_id
                 FROM products p
                 WHERE p.funnel_id = ?
                   AND NOT EXISTS (
                       SELECT 1 FROM funnel_products fp
                       WHERE fp.funnel_id = p.funnel_id AND fp.product_id = p.id
                   )",
                [$funnelId]
            );

            foreach ($legacy as $row) {
                $products[] = $row;
            }

            self::sortFunnelProducts($products);
            return $products;
        }

        $orderBy = self::orderByClause();
        return Database::fetchAll(
            "SELECT * FROM products WHERE funnel_id = ? ORDER BY {$orderBy}",
            [$funnelId]
        );
    }

    /**
     * Retorna produtos ordenados (todos)
     */
    public static function allOrdered(): array
    {
        if (self::supportsFunnelProducts()) {
            return Database::fetchAll(
                "SELECT p.*, COALESCE(stats.funnel_count, 0) AS funnel_count, stats.funnel_names
                 FROM products p
                 LEFT JOIN (
                    SELECT fp.product_id,
                           COUNT(*) AS funnel_count,
                           GROUP_CONCAT(fp.funnel_id ORDER BY fp.funnel_id SEPARATOR ',') AS funnel_ids,
                           GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') AS funnel_names
                    FROM funnel_products fp
                    LEFT JOIN funnels f ON f.id = fp.funnel_id
                    GROUP BY fp.product_id
                 ) stats ON stats.product_id = p.id
                 ORDER BY p.title ASC, p.id ASC"
            );
        }

        $orderBy = self::orderByClause();
        return Database::fetchAll(
            "SELECT * FROM products ORDER BY {$orderBy}"
        );
    }

    /**
     * Retorna produto com módulos e aulas
     */
    public static function findWithModules(int $id): ?array
    {
        $product = self::find($id);
        
        if (!$product) {
            return null;
        }

        $product['modules'] = Module::getByProduct($id);
        
        foreach ($product['modules'] as &$module) {
            $module['lessons'] = Lesson::getByModule($module['id']);
        }

        return $product;
    }

    /**
     * Retorna produto com metadados especificos do funil, módulos e aulas.
     */
    public static function findWithModulesForFunnel(int $productId, int $funnelId): ?array
    {
        $product = self::findForFunnel($productId, $funnelId);

        if (!$product) {
            return null;
        }

        $product['modules'] = Module::getByProduct($productId);

        foreach ($product['modules'] as &$module) {
            $module['lessons'] = Lesson::getByModule($module['id']);
        }

        return $product;
    }

    /**
     * Retorna produto apenas se ele estiver vinculado ao funil informado.
     */
    public static function findForFunnel(int $productId, int $funnelId): ?array
    {
        $product = self::find($productId);

        if (!$product) {
            return null;
        }

        if (self::supportsFunnelProducts()) {
            $link = Database::fetch(
                "SELECT id AS funnel_product_id,
                        funnel_id AS linked_funnel_id,
                        checkout_url AS linked_checkout_url,
                        webhook_token AS linked_webhook_token,
                        sort_order AS linked_sort_order,
                        release_days AS linked_release_days,
                        is_public AS linked_is_public,
                        external_product_id AS linked_external_product_id,
                        funnel_role
                 FROM funnel_products
                 WHERE funnel_id = ? AND product_id = ?",
                [$funnelId, $productId]
            );

            if ($link) {
                return self::applyFunnelLink(array_merge($product, [
                    'original_funnel_id' => $product['funnel_id'] ?? null,
                ], $link));
            }
        }

        if ((int) ($product['funnel_id'] ?? 0) === $funnelId) {
            return $product;
        }

        return null;
    }

    /**
     * Busca produto pelo token do webhook
     */
    public static function findByWebhookToken(string $token): ?array
    {
        if (self::supportsFunnelProducts()) {
            $linked = Database::fetch(
                "SELECT p.*,
                        p.funnel_id AS original_funnel_id,
                        fp.id AS funnel_product_id,
                        fp.funnel_id AS linked_funnel_id,
                        fp.checkout_url AS linked_checkout_url,
                        fp.webhook_token AS linked_webhook_token,
                        fp.sort_order AS linked_sort_order,
                        fp.release_days AS linked_release_days,
                        fp.is_public AS linked_is_public
                 FROM funnel_products fp
                 INNER JOIN products p ON p.id = fp.product_id
                 WHERE fp.webhook_token = ?",
                [$token]
            );

            if ($linked) {
                return self::applyFunnelLink($linked);
            }
        }

        return Database::fetch(
            "SELECT * FROM products WHERE webhook_token = ?",
            [$token]
        );
    }

    /**
     * Busca produto pelo ID externo (CartPanda) dentro de um funil.
     */
    public static function findByExternalIdInFunnel(string $externalId, int $funnelId): ?array
    {
        if (!self::supportsFunnelProducts()) {
            return null;
        }

        $linked = Database::fetch(
            "SELECT p.*,
                    p.funnel_id AS original_funnel_id,
                    fp.id AS funnel_product_id,
                    fp.funnel_id AS linked_funnel_id,
                    fp.checkout_url AS linked_checkout_url,
                    fp.webhook_token AS linked_webhook_token,
                    fp.sort_order AS linked_sort_order,
                    fp.release_days AS linked_release_days,
                    fp.is_public AS linked_is_public,
                    fp.external_product_id AS linked_external_product_id,
                    fp.funnel_role
             FROM funnel_products fp
             INNER JOIN products p ON p.id = fp.product_id
             WHERE fp.funnel_id = ?
               AND (
                   p.external_product_id = ?
                   OR (
                       (p.external_product_id IS NULL OR p.external_product_id = '')
                       AND fp.external_product_id = ?
                   )
               )",
            [$funnelId, $externalId, $externalId]
        );

        return $linked ? self::applyFunnelLink($linked) : null;
    }

    /**
     * Busca múltiplos produtos pelo ID externo dentro de um funil.
     */
    public static function findAllByExternalIdsInFunnel(array $externalIds, int $funnelId): array
    {
        if (!self::supportsFunnelProducts() || empty($externalIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($externalIds), '?'));
        $params = array_merge([$funnelId], $externalIds);

        $rows = Database::fetchAll(
            "SELECT p.*,
                    p.funnel_id AS original_funnel_id,
                    fp.id AS funnel_product_id,
                    fp.funnel_id AS linked_funnel_id,
                    fp.checkout_url AS linked_checkout_url,
                    fp.webhook_token AS linked_webhook_token,
                    fp.sort_order AS linked_sort_order,
                    fp.release_days AS linked_release_days,
                    fp.is_public AS linked_is_public,
                    fp.external_product_id AS linked_external_product_id
             FROM funnel_products fp
             INNER JOIN products p ON p.id = fp.product_id
             WHERE fp.funnel_id = ?
               AND (
                   p.external_product_id IN ({$placeholders})
                   OR (
                       (p.external_product_id IS NULL OR p.external_product_id = '')
                       AND fp.external_product_id IN ({$placeholders})
                   )
               )",
            array_merge($params, $externalIds)
        );

        return array_map(fn(array $row) => self::applyFunnelLink($row), $rows);
    }

    /**
     * Busca produtos do funil por role (principal, bonus).
     */
    public static function getByRoleInFunnel(int $funnelId, array $roles = ['principal', 'bonus']): array
    {
        if (!self::supportsFunnelProducts() || empty($roles)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $params = array_merge([$funnelId], $roles);

        $rows = Database::fetchAll(
            "SELECT p.*,
                    p.funnel_id AS original_funnel_id,
                    fp.id AS funnel_product_id,
                    fp.funnel_id AS linked_funnel_id,
                    fp.checkout_url AS linked_checkout_url,
                    fp.webhook_token AS linked_webhook_token,
                    fp.sort_order AS linked_sort_order,
                    fp.release_days AS linked_release_days,
                    fp.is_public AS linked_is_public,
                    fp.external_product_id AS linked_external_product_id,
                    fp.funnel_role
             FROM funnel_products fp
             INNER JOIN products p ON p.id = fp.product_id
             WHERE fp.funnel_id = ? AND fp.funnel_role IN ({$placeholders})",
            $params
        );

        return array_map(fn(array $row) => self::applyFunnelLink($row), $rows);
    }

    /**
     * Gera um token único para webhook
     */
    public static function generateWebhookToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
            $exists = Database::fetch(
                "SELECT id FROM products WHERE webhook_token = ?",
                [$token]
            );
            if (!$exists && self::supportsFunnelProducts()) {
                $exists = Database::fetch(
                    "SELECT id FROM funnel_products WHERE webhook_token = ?",
                    [$token]
                );
            }
        } while ($exists);

        return $token;
    }

    /**
     * Retorna todos os produtos com nome do funil
     */
    public static function allWithFunnel(): array
    {
        if (self::supportsFunnelProducts()) {
            return self::allOrdered();
        }

        $productOrder = self::hasColumn('sort_order')
            ? 'CASE WHEN p.sort_order IS NULL OR p.sort_order <= 0 THEN 1 ELSE 0 END ASC, p.sort_order ASC, p.id ASC'
            : 'p.id ASC';

        return Database::fetchAll(
            "SELECT p.*, f.name as funnel_name
             FROM products p
             LEFT JOIN funnels f ON f.id = p.funnel_id
             ORDER BY f.name ASC, {$productOrder}"
        );
    }

    public static function nextSortOrder(int $funnelId): int
    {
        if (self::supportsFunnelProducts()) {
            $row = Database::fetch(
                "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order
                 FROM funnel_products
                 WHERE funnel_id = ? AND sort_order > 0",
                [$funnelId]
            );

            return max(1, (int) ($row['next_order'] ?? 1));
        }

        $row = Database::fetch(
            "SELECT COALESCE(MAX(sort_order), 0) + 1 AS next_order
             FROM products
             WHERE funnel_id = ? AND sort_order > 0",
            [$funnelId]
        );

        return max(1, (int) ($row['next_order'] ?? 1));
    }

    public static function appendMissingSortOrders(int $funnelId): void
    {
        if (self::supportsFunnelProducts()) {
            $products = Database::fetchAll(
                "SELECT id FROM funnel_products
                 WHERE funnel_id = ? AND (sort_order IS NULL OR sort_order <= 0)
                 ORDER BY id ASC",
                [$funnelId]
            );

            $nextOrder = self::nextSortOrder($funnelId);
            foreach ($products as $product) {
                Database::query(
                    "UPDATE funnel_products SET sort_order = ?, updated_at = NOW() WHERE id = ?",
                    [$nextOrder++, $product['id']]
                );
            }

            return;
        }

        $products = Database::fetchAll(
            "SELECT id FROM products
             WHERE funnel_id = ? AND (sort_order IS NULL OR sort_order <= 0)
             ORDER BY id ASC",
            [$funnelId]
        );

        $nextOrder = self::nextSortOrder($funnelId);
        foreach ($products as $product) {
            Database::query(
                "UPDATE products SET sort_order = ?, updated_at = NOW() WHERE id = ?",
                [$nextOrder++, $product['id']]
            );
        }
    }

    public static function shiftSortOrderFrom(int $funnelId, int $startOrder): void
    {
        if ($startOrder <= 0) {
            return;
        }

        if (self::supportsFunnelProducts()) {
            Database::query(
                "UPDATE funnel_products
                 SET sort_order = sort_order + 1, updated_at = NOW()
                 WHERE funnel_id = ? AND sort_order >= ?",
                [$funnelId, $startOrder]
            );
            return;
        }

        Database::query(
            "UPDATE products
             SET sort_order = sort_order + 1, updated_at = NOW()
             WHERE funnel_id = ? AND sort_order >= ?",
            [$funnelId, $startOrder]
        );
    }

    public static function reorderForFunnel(int $funnelId, array $orderedIds): array
    {
        $orderedIds = array_values(array_unique(array_filter(array_map('intval', $orderedIds), fn($id) => $id > 0)));
        if (empty($orderedIds)) {
            throw new \InvalidArgumentException('Nenhum produto recebido para reordenar.');
        }

        self::appendMissingSortOrders($funnelId);

        $products = self::getByFunnel($funnelId);
        $validIds = array_map('intval', array_column($products, 'id'));
        $invalidIds = array_values(array_diff($orderedIds, $validIds));
        if (!empty($invalidIds)) {
            throw new \InvalidArgumentException('Produto invalido para este funil.');
        }

        $orderedSet = array_flip($orderedIds);
        foreach ($validIds as $productId) {
            if (!isset($orderedSet[$productId])) {
                $orderedIds[] = $productId;
            }
        }

        foreach ($orderedIds as $index => $productId) {
            if (self::supportsFunnelProducts()) {
                Database::query(
                    "UPDATE funnel_products SET sort_order = ?, updated_at = NOW() WHERE product_id = ? AND funnel_id = ?",
                    [$index + 1, $productId, $funnelId]
                );
            } else {
                Database::query(
                    "UPDATE products SET sort_order = ?, updated_at = NOW() WHERE id = ? AND funnel_id = ?",
                    [$index + 1, $productId, $funnelId]
                );
            }
        }

        return array_map('intval', array_column(self::getByFunnel($funnelId), 'id'));
    }

    /**
     * Vincula um produto global a um funil.
     */
    public static function linkToFunnel(int $funnelId, int $productId, array $settings = []): void
    {
        if (!self::supportsFunnelProducts()) {
            self::update($productId, ['funnel_id' => $funnelId]);
            return;
        }

        $product = self::find($productId);
        if (!$product) {
            throw new \InvalidArgumentException('Produto nao encontrado.');
        }

        $existing = Database::fetch(
            "SELECT id FROM funnel_products WHERE funnel_id = ? AND product_id = ?",
            [$funnelId, $productId]
        );

        if ($existing) {
            return;
        }

        $sortOrder = !empty($settings['sort_order'])
            ? max(1, (int) $settings['sort_order'])
            : self::nextSortOrder($funnelId);

        Database::query(
            "INSERT INTO funnel_products
                (funnel_id, product_id, checkout_url, webhook_token, external_product_id, funnel_role, sort_order, release_days, is_public, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                $funnelId,
                $productId,
                null,
                $settings['webhook_token'] ?? self::generateWebhookToken(),
                null,
                $settings['funnel_role'] ?? null,
                $sortOrder,
                array_key_exists('release_days', $settings) ? $settings['release_days'] : ($product['release_days'] ?? null),
                !empty($settings['is_public']) ? 1 : (!empty($product['is_public']) ? 1 : 0),
            ]
        );
    }

    /**
     * Atualiza configuracoes do produto dentro de um funil.
     */
    public static function updateFunnelSettings(int $funnelId, int $productId, array $settings): void
    {
        if (!self::supportsFunnelProducts()) {
            self::update($productId, $settings);
            return;
        }

        $allowed = array_intersect_key($settings, array_flip([
            'webhook_token', 'sort_order', 'release_days', 'is_public', 'funnel_role'
        ]));

        if (empty($allowed)) {
            return;
        }

        if (!self::belongsToFunnel($productId, $funnelId)) {
            self::linkToFunnel($funnelId, $productId);
        }

        $allowed['updated_at'] = date('Y-m-d H:i:s');
        $set = implode(' = ?, ', array_keys($allowed)) . ' = ?';
        $values = array_values($allowed);
        $values[] = $funnelId;
        $values[] = $productId;

        Database::query(
            "UPDATE funnel_products SET {$set} WHERE funnel_id = ? AND product_id = ?",
            $values
        );
    }

    public static function unlinkFromFunnel(int $funnelId, int $productId): void
    {
        if (self::supportsFunnelProducts()) {
            Database::query(
                "DELETE FROM funnel_products WHERE funnel_id = ? AND product_id = ?",
                [$funnelId, $productId]
            );
            return;
        }

        $product = self::find($productId);
        if ($product && (int) ($product['funnel_id'] ?? 0) === $funnelId) {
            self::delete($productId);
        }
    }

    public static function unlinkFromAllFunnels(int $productId): void
    {
        if (self::supportsFunnelProducts()) {
            Database::query("DELETE FROM funnel_products WHERE product_id = ?", [$productId]);
        }
    }

    public static function belongsToFunnel(int $productId, int $funnelId): bool
    {
        if (self::supportsFunnelProducts()) {
            $row = Database::fetch(
                "SELECT id FROM funnel_products WHERE funnel_id = ? AND product_id = ?",
                [$funnelId, $productId]
            );

            if ($row) {
                return true;
            }
        }

        $product = self::find($productId);
        return $product && (int) ($product['funnel_id'] ?? 0) === $funnelId;
    }

    public static function availableForFunnel(int $funnelId): array
    {
        if (!self::supportsFunnelProducts()) {
            return Database::fetchAll(
                "SELECT * FROM products WHERE funnel_id IS NULL OR funnel_id != ? ORDER BY title ASC, id ASC",
                [$funnelId]
            );
        }

        return Database::fetchAll(
            "SELECT p.*, COALESCE(stats.funnel_count, 0) AS funnel_count, stats.funnel_names
             FROM products p
             LEFT JOIN (
                SELECT fp.product_id,
                       COUNT(*) AS funnel_count,
                       GROUP_CONCAT(f.name ORDER BY f.name SEPARATOR ', ') AS funnel_names
                FROM funnel_products fp
                LEFT JOIN funnels f ON f.id = fp.funnel_id
                GROUP BY fp.product_id
             ) stats ON stats.product_id = p.id
             WHERE NOT EXISTS (
                SELECT 1 FROM funnel_products fp
                WHERE fp.product_id = p.id AND fp.funnel_id = ?
             )
             AND (p.funnel_id IS NULL OR p.funnel_id != ?)
             ORDER BY p.title ASC, p.id ASC",
            [$funnelId, $funnelId]
        );
    }

    /**
     * Retorna produtos vinculados a um nivel de acesso.
     */
    public static function getByAccessLevel(int $accessLevelId): array
    {
        if (self::supportsFunnelProducts()) {
            $rows = Database::fetchAll(
                "SELECT p.*,
                        p.funnel_id AS original_funnel_id,
                        fp.id AS funnel_product_id,
                        fp.funnel_id AS linked_funnel_id,
                        fp.checkout_url AS linked_checkout_url,
                        fp.webhook_token AS linked_webhook_token,
                        fp.sort_order AS linked_sort_order,
                        fp.release_days AS linked_release_days,
                        fp.is_public AS linked_is_public
                 FROM access_level_products alp
                 INNER JOIN access_levels al ON al.id = alp.access_level_id
                 INNER JOIN products p ON p.id = alp.product_id
                 LEFT JOIN funnel_products fp ON fp.product_id = p.id AND fp.funnel_id = al.funnel_id
                 WHERE alp.access_level_id = ?",
                [$accessLevelId]
            );

            $products = array_map(fn(array $row) => self::applyFunnelLink($row), $rows);
            self::sortFunnelProducts($products);
            return $products;
        }

        return Database::fetchAll(
            "SELECT p.*
             FROM products p
             INNER JOIN access_level_products alp ON alp.product_id = p.id
             WHERE alp.access_level_id = ?
             ORDER BY " . self::orderByClause(),
            [$accessLevelId]
        );
    }

    /**
     * Retorna IDs dos produtos liberados para todos os membros de um funil.
     */
    public static function getPublicProductIdsByFunnel(int $funnelId): array
    {
        if (self::supportsFunnelProducts()) {
            $rows = Database::fetchAll(
                "SELECT product_id AS id
                 FROM funnel_products
                 WHERE funnel_id = ? AND is_public = 1
                 ORDER BY CASE WHEN sort_order IS NULL OR sort_order <= 0 THEN 1 ELSE 0 END ASC, sort_order ASC, product_id ASC",
                [$funnelId]
            );

            $ids = array_map('intval', array_column($rows, 'id'));

            if (self::hasColumn('is_public')) {
                $legacyRows = Database::fetchAll(
                    "SELECT id FROM products
                     WHERE funnel_id = ? AND is_public = 1
                       AND NOT EXISTS (
                           SELECT 1 FROM funnel_products fp
                           WHERE fp.funnel_id = products.funnel_id AND fp.product_id = products.id
                       )
                     ORDER BY " . self::orderByClause(),
                    [$funnelId]
                );
                $ids = array_merge($ids, array_map('intval', array_column($legacyRows, 'id')));
            }

            return array_values(array_unique($ids));
        }

        if (!self::hasColumn('is_public')) {
            return [];
        }

        $rows = Database::fetchAll(
            "SELECT id FROM products WHERE funnel_id = ? AND is_public = 1 ORDER BY " . self::orderByClause(),
            [$funnelId]
        );

        return array_map('intval', array_column($rows, 'id'));
    }

    /**
     * Verifica se um produto esta liberado para qualquer membro logado do funil.
     */
    public static function isPublicForFunnel(int $productId, int $funnelId): bool
    {
        if (self::supportsFunnelProducts()) {
            $row = Database::fetch(
                "SELECT id FROM funnel_products WHERE product_id = ? AND funnel_id = ? AND is_public = 1",
                [$productId, $funnelId]
            );

            if ($row) {
                return true;
            }
        }

        if (!self::hasColumn('is_public')) {
            return false;
        }

        $row = Database::fetch(
            "SELECT id FROM products WHERE id = ? AND funnel_id = ? AND is_public = 1",
            [$productId, $funnelId]
        );

        return (bool) $row;
    }

    public static function delete(int $id): bool
    {
        self::unlinkFromAllFunnels($id);
        return parent::delete($id);
    }

    private static function orderByClause(): string
    {
        if (self::hasColumn('sort_order')) {
            return 'CASE WHEN sort_order IS NULL OR sort_order <= 0 THEN 1 ELSE 0 END ASC, sort_order ASC, id ASC';
        }

        return 'id ASC';
    }

    private static function hasColumn(string $column): bool
    {
        static $columns = [];

        if (!array_key_exists($column, $columns)) {
            try {
                $columns[$column] = (bool) Database::fetch("SHOW COLUMNS FROM products LIKE ?", [$column]);
            } catch (\Throwable $e) {
                $columns[$column] = false;
            }
        }

        return $columns[$column];
    }

    private static function supportsFunnelProducts(): bool
    {
        static $supports = null;

        if ($supports === null) {
            try {
                $supports = (bool) Database::fetch("SHOW TABLES LIKE 'funnel_products'");
            } catch (\Throwable $e) {
                $supports = false;
            }
        }

        return $supports;
    }

    private static function applyFunnelLink(array $row): array
    {
        if (array_key_exists('linked_funnel_id', $row) && $row['linked_funnel_id'] !== null) {
            $row['product_funnel_id'] = $row['original_funnel_id'] ?? ($row['funnel_id'] ?? null);
            $row['funnel_id'] = $row['linked_funnel_id'];
            // checkout_url e external_product_id são propriedades GLOBAIS do produto — não sobrescreve
            $row['webhook_token'] = $row['linked_webhook_token'];
            $row['sort_order'] = $row['linked_sort_order'];
            $row['release_days'] = $row['linked_release_days'];
            $row['is_public'] = $row['linked_is_public'];
        }

        unset(
            $row['linked_funnel_id'],
            $row['linked_checkout_url'],
            $row['linked_external_product_id'],
            $row['linked_webhook_token'],
            $row['linked_sort_order'],
            $row['linked_release_days'],
            $row['linked_is_public']
        );

        return $row;
    }

    private static function sortFunnelProducts(array &$products): void
    {
        usort($products, function (array $a, array $b): int {
            $aOrder = (int) ($a['sort_order'] ?? 0);
            $bOrder = (int) ($b['sort_order'] ?? 0);
            $aMissing = $aOrder <= 0 ? 1 : 0;
            $bMissing = $bOrder <= 0 ? 1 : 0;

            return [$aMissing, $aOrder, (int) $a['id']] <=> [$bMissing, $bOrder, (int) $b['id']];
        });
    }
}
