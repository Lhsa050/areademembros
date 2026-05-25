<?php

namespace App\Models;

use App\Core\Database;

/**
 * Model de configuracoes.
 *
 * As configuracoes globais continuam na tabela `settings`.
 * Quando um funnel_id e informado, a tabela `funnel_settings` sobrescreve
 * apenas os valores daquele funil e cai no valor global quando nao existir.
 */
class Setting extends BaseModel
{
    protected static string $table = 'settings';
    protected static bool $useUuid = false;
    protected static array $fillable = [
        'setting_key', 'setting_value'
    ];

    public static function get(string $key, mixed $default = null, ?int $funnelId = null): mixed
    {
        if ($funnelId && self::supportsFunnelSettings()) {
            $funnelSetting = Database::fetch(
                "SELECT setting_value FROM funnel_settings WHERE funnel_id = ? AND setting_key = ?",
                [$funnelId, $key]
            );

            if ($funnelSetting) {
                return $funnelSetting['setting_value'];
            }
        }

        $setting = Database::fetch(
            "SELECT setting_value FROM settings WHERE setting_key = ?",
            [$key]
        );

        return $setting ? $setting['setting_value'] : $default;
    }

    public static function set(string $key, mixed $value, ?int $funnelId = null): void
    {
        if ($funnelId && self::supportsFunnelSettings()) {
            $existing = Database::fetch(
                "SELECT id FROM funnel_settings WHERE funnel_id = ? AND setting_key = ?",
                [$funnelId, $key]
            );

            if ($existing) {
                Database::query(
                    "UPDATE funnel_settings SET setting_value = ?, updated_at = NOW() WHERE funnel_id = ? AND setting_key = ?",
                    [$value, $funnelId, $key]
                );
            } else {
                Database::query(
                    "INSERT INTO funnel_settings (funnel_id, setting_key, setting_value) VALUES (?, ?, ?)",
                    [$funnelId, $key, $value]
                );
            }

            return;
        }

        $existing = Database::fetch(
            "SELECT id FROM settings WHERE setting_key = ?",
            [$key]
        );

        if ($existing) {
            Database::query(
                "UPDATE settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?",
                [$value, $key]
            );
        } else {
            Database::query(
                "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)",
                [$key, $value]
            );
        }
    }

    public static function getAll(?int $funnelId = null): array
    {
        $settings = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }

        if ($funnelId && self::supportsFunnelSettings()) {
            $funnelSettings = Database::fetchAll(
                "SELECT setting_key, setting_value FROM funnel_settings WHERE funnel_id = ?",
                [$funnelId]
            );

            foreach ($funnelSettings as $setting) {
                $result[$setting['setting_key']] = $setting['setting_value'];
            }
        }

        return $result;
    }

    private static function supportsFunnelSettings(): bool
    {
        static $supports = null;

        if ($supports !== null) {
            return $supports;
        }

        try {
            $supports = (bool) Database::fetch("SHOW TABLES LIKE 'funnel_settings'");
        } catch (\Throwable $e) {
            $supports = false;
        }

        return $supports;
    }
}
