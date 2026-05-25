<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de Administradores
 */
class Admin extends BaseModel
{
    protected static string $table = 'admins';
    protected static array $fillable = [
        'uuid', 'name', 'email', 'password', 'role', 'status',
        'last_login_at', 'last_login_ip'
    ];

    /**
     * Busca por email
     */
    public static function findByEmail(string $email): ?array
    {
        return Database::fetch(
            "SELECT * FROM admins WHERE email = ?",
            [$email]
        );
    }

    /**
     * Cria admin com senha hasheada
     */
    public static function createWithPassword(array $data): int
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        return self::create($data);
    }

    /**
     * Atualiza admin (com senha opcional)
     */
    public static function updateWithPassword(int $id, array $data): bool
    {
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        } else {
            unset($data['password']);
        }
        return self::update($id, $data);
    }
}
