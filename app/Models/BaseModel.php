<?php

namespace App\Models;

use App\Core\Database;
use Ramsey\Uuid\Uuid;

/**
 * BaseModel - Classe base para todos os models
 */
abstract class BaseModel
{
    protected static string $table = '';
    protected static array $fillable = [];
    protected static bool $useUuid = true;
    protected static bool $timestamps = true;

    /**
     * Busca por ID
     */
    public static function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . static::$table . " WHERE id = ?",
            [$id]
        );
    }

    /**
     * Busca por UUID
     */
    public static function findByUuid(string $uuid): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . static::$table . " WHERE uuid = ?",
            [$uuid]
        );
    }

    /**
     * Busca por coluna
     */
    public static function findBy(string $column, $value): ?array
    {
        return Database::fetch(
            "SELECT * FROM " . static::$table . " WHERE {$column} = ?",
            [$value]
        );
    }

    /**
     * Retorna todos os registros
     */
    public static function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        return Database::fetchAll(
            "SELECT * FROM " . static::$table . " ORDER BY {$orderBy} {$direction}"
        );
    }

    /**
     * Busca múltiplos por coluna
     */
    public static function where(string $column, $value, string $orderBy = 'id'): array
    {
        return Database::fetchAll(
            "SELECT * FROM " . static::$table . " WHERE {$column} = ? ORDER BY {$orderBy}",
            [$value]
        );
    }

    /**
     * Cria novo registro
     */
    public static function create(array $data): int
    {
        // Filtra apenas campos permitidos
        $filtered = array_intersect_key($data, array_flip(static::$fillable));
        
        // Adiciona UUID se necessário
        if (static::$useUuid && !isset($filtered['uuid'])) {
            $filtered['uuid'] = Uuid::uuid4()->toString();
        }

        // Adiciona timestamps
        if (static::$timestamps) {
            $filtered['created_at'] = date('Y-m-d H:i:s');
            $filtered['updated_at'] = date('Y-m-d H:i:s');
        }

        $columns = implode(', ', array_keys($filtered));
        $placeholders = implode(', ', array_fill(0, count($filtered), '?'));

        Database::query(
            "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})",
            array_values($filtered)
        );

        return (int) Database::lastInsertId();
    }

    /**
     * Atualiza registro
     */
    public static function update(int $id, array $data): bool
    {
        // Filtra apenas campos permitidos
        $filtered = array_intersect_key($data, array_flip(static::$fillable));
        
        if (empty($filtered)) {
            return false;
        }

        if ($id <= 0) {
            throw new \Exception("Tentativa de update com ID inválido: {$id}");
        }

        // Atualiza timestamp
        if (static::$timestamps) {
            $filtered['updated_at'] = date('Y-m-d H:i:s');
        }

        $set = implode(' = ?, ', array_keys($filtered)) . ' = ?';
        $values = array_values($filtered);
        $values[] = $id;

        Database::query(
            "UPDATE " . static::$table . " SET {$set} WHERE id = ?",
            $values
        );

        return true;
    }

    /**
     * Deleta registro
     */
    public static function delete(int $id): bool
    {
        Database::query(
            "DELETE FROM " . static::$table . " WHERE id = ?",
            [$id]
        );
        return true;
    }

    /**
     * Conta registros
     */
    public static function count(?string $where = null, array $params = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM " . static::$table;
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $result = Database::fetch($sql, $params);
        return (int) ($result['total'] ?? 0);
    }
}
