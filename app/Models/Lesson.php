<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Aulas
 */
class Lesson extends BaseModel
{
    protected static string $table = 'lessons';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    protected static array $fillable = [
        'module_id', 'title', 'description', 'youtube_id', 'file', 'sort_order', 'release_days'
    ];

    /**
     * Retorna aulas de um módulo
     */
    public static function getByModule(int $moduleId): array
    {
        $orderBy = self::orderByClause();
        return Database::fetchAll(
            "SELECT * FROM lessons WHERE module_id = ? ORDER BY {$orderBy}",
            [$moduleId]
        );
    }

    /**
     * Extrai ID do YouTube de URL completa
     */
    public static function extractYoutubeId(string $urlOrId): string
    {
        // Se já é um ID limpo (11 caracteres)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $urlOrId)) {
            return $urlOrId;
        }

        // Extrai de URLs variadas do YouTube (incluindo live, shorts, etc)
        $pattern = '/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/|live\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        if (preg_match($pattern, $urlOrId, $matches)) {
            return $matches[1];
        }

        return $urlOrId;
    }

    private static function orderByClause(): string
    {
        if (self::hasColumn('sort_order')) {
            return 'sort_order ASC, id ASC';
        }

        return 'id ASC';
    }

    private static function hasColumn(string $column): bool
    {
        static $columns = [];

        if (!array_key_exists($column, $columns)) {
            try {
                $columns[$column] = (bool) Database::fetch("SHOW COLUMNS FROM lessons LIKE ?", [$column]);
            } catch (\Throwable $e) {
                $columns[$column] = false;
            }
        }

        return $columns[$column];
    }
}
