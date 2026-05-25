<?php

namespace App\Models;

use App\Core\Database;

class LessonFile extends BaseModel
{
    protected static string $table = 'lesson_files';
    protected static bool $useUuid = false;
    protected static bool $timestamps = false;
    protected static array $fillable = ['lesson_id', 'title', 'file', 'file_type', 'sort_order', 'release_days'];

    /**
     * Retorna os arquivos de uma aula (ordenados)
     */
    public static function getByLesson(int $lessonId): array
    {
        $orderBy = self::orderByClause();
        return Database::fetchAll(
            "SELECT * FROM lesson_files WHERE lesson_id = ? ORDER BY {$orderBy}",
            [$lessonId]
        );
    }

    /**
     * Retorna arquivos de múltiplas aulas de uma vez (para evitar N+1)
     */
    public static function getByLessonIds(array $lessonIds): array
    {
        if (empty($lessonIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($lessonIds), '?'));
        $orderBy = self::orderByClause();
        $files = Database::fetchAll(
            "SELECT * FROM lesson_files WHERE lesson_id IN ({$placeholders}) ORDER BY lesson_id ASC, {$orderBy}",
            $lessonIds
        );
        
        // Agrupa por lesson_id
        $grouped = [];
        foreach ($files as $file) {
            $grouped[$file['lesson_id']][] = $file;
        }
        return $grouped;
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
                $columns[$column] = (bool) Database::fetch("SHOW COLUMNS FROM lesson_files LIKE ?", [$column]);
            } catch (\Throwable $e) {
                $columns[$column] = false;
            }
        }

        return $columns[$column];
    }
}
