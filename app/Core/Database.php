<?php
namespace App\Core;

use PDO;
use Exception;

/**
 * Gerenciador de Banco de Dados Centralizado (PDO SQLite)
 * Suporta múltiplas bases (users, clients, radios, etc.) com singleton e conexões seguras.
 */
class Database
{
    private static array $instances = [];

    public static function connection(string $name = 'users'): PDO
    {
        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        $configPath = dirname(__DIR__, 2) . '/config/database.php';
        $config = require $configPath;

        if (!isset($config['connections'][$name])) {
            throw new Exception("Conexão com banco de dados '{$name}' não está configurada.");
        }

        $connConfig = $config['connections'][$name];
        $dbPath = $connConfig['database'];
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0777, true);
        }
        @chmod($dbDir, 0777);
        if (file_exists($dbPath)) {
            @chmod($dbPath, 0666);
        }

        $dsn = "sqlite:{$dbPath}";
        $pdo = new PDO($dsn, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => false
        ]);

        try {
            $pdo->exec("PRAGMA busy_timeout = 5000;");
            $pdo->exec("PRAGMA journal_mode = WAL;");
        } catch (\Throwable $e) {}

        self::$instances[$name] = $pdo;
        return $pdo;
    }

    public static function users(): PDO
    {
        return self::connection('users');
    }

    public static function clients(): PDO
    {
        return self::connection('clients');
    }

    public static function radios(): PDO
    {
        return self::connection('radios');
    }

    public static function deals(): PDO
    {
        return self::connection('deals');
    }
}
