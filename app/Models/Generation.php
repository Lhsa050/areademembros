<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Gerações (histórico de HTMLs gerados)
 */
class Generation extends BaseModel
{
    protected static string $table = 'generations';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'funnel_id', 'site_name', 'theme', 'html_file', 'created_at'
    ];

    /**
     * Cria registro de geração
     */
    public static function create(array $data): int
    {
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        Database::query(
            "INSERT INTO generations ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return (int) Database::lastInsertId();
    }

    /**
     * Retorna gerações recentes
     */
    public static function recent(int $limit = 10): array
    {
        return Database::fetchAll(
            "SELECT * FROM generations ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }
}

