<?php

namespace App\Services;

/**
 * Gerador de Senhas
 */
class PasswordGenerator
{
    /**
     * Gera senha simples (fácil de lembrar)
     */
    public static function simple(): string
    {
        $prefixes = [
            'CURSO', 'VIP', 'ACESSO', 'MEMBRO', 'PREMIUM', 'GOLD',
            'SILVER', 'BRONZE', 'MASTER', 'PRO', 'ELITE', 'PLUS'
        ];
        
        $prefix = $prefixes[array_rand($prefixes)];
        $number = random_int(100, 9999);
        
        return $prefix . $number;
    }

    /**
     * Gera senha segura (alfanumérica)
     */
    public static function secure(int $length = 12): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }

    /**
     * Gera senha baseada em palavras
     */
    public static function words(): string
    {
        $words = [
            'SOL', 'LUA', 'AGUA', 'FOGO', 'TERRA', 'VENTO',
            'VIDA', 'AMOR', 'PAZ', 'LUZ', 'BRILHO', 'FORCA'
        ];
        
        $word1 = $words[array_rand($words)];
        $word2 = $words[array_rand($words)];
        $number = random_int(10, 99);
        
        return $word1 . $word2 . $number;
    }
}
