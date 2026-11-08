<?php
namespace App\Models;

use App\Core\Model;

class Client extends Model
{
    protected static string $table = 'ibo';
    protected static string $connection = 'clients';

    public static function ensureSchema(): void
    {
        $pdo = self::getConnection();

        $pdo->exec("CREATE TABLE IF NOT EXISTS ibo (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mac_address VARCHAR(100),
            key VARCHAR(100),
            username VARCHAR(100),
            password VARCHAR(255),
            expire_date DATE,
            title VARCHAR(150),
            active INTEGER DEFAULT 1,
            email VARCHAR(150),
            whatsapp VARCHAR(50),
            affiliateCode VARCHAR(100),
            referredById INTEGER
        )");

        // Garante colunas caso a tabela seja de versão legada
        $cols = $pdo->query("PRAGMA table_info(ibo)")->fetchAll();
        $colNames = array_column($cols, 'name');

        if (!in_array('email', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN email VARCHAR(150)"); } catch (\Throwable $e) {}
        }
        if (!in_array('whatsapp', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN whatsapp VARCHAR(50)"); } catch (\Throwable $e) {}
        }
        if (!in_array('affiliateCode', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN affiliateCode VARCHAR(100)"); } catch (\Throwable $e) {}
        }
        if (!in_array('referredById', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN referredById INTEGER"); } catch (\Throwable $e) {}
        }
    }

    public static function findByLogin(string $login): ?array
    {
        self::ensureSchema();

        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM ibo WHERE LOWER(username) = LOWER(:login) OR LOWER(email) = LOWER(:login) LIMIT 1");
        $stmt->bindValue(':login', $login);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ?: null;
    }
}

