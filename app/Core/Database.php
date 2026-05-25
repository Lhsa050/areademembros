<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Classe Database - Singleton para conexão PDO
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Obtém instância da conexão
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::connect();
        }
        return self::$instance;
    }

    /**
     * Conecta ao banco de dados
     */
    private static function connect(): void
    {
        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $name = $_ENV['DB_NAME'] ?? 'gerador_membros';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            self::$instance = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            if (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                throw $e;
            }
            die('Erro de conexão com o banco de dados.');
        }
    }

    /**
     * Executa query com prepared statement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Busca uma linha
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Busca todas as linhas
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Obtém último ID inserido
     */
    public static function lastInsertId(): string
    {
        return self::getInstance()->lastInsertId();
    }
}
