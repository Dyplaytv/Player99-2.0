<?php
namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    protected static string $table = 'users';
    protected static string $connection = 'users';

    public static function ensureSchema(): void
    {
        try {
            $pdo = self::getConnection();

            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100),
                username VARCHAR(100) UNIQUE,
                password VARCHAR(255),
                logo VARCHAR(100),
                nivel VARCHAR(100) DEFAULT '1',
                admin VARCHAR(100) DEFAULT '1',
                store_type VARCHAR(100),
                mac_amount VARCHAR(100),
                email VARCHAR(150)
            )");

            // Garante coluna email
            $cols = $pdo->query("PRAGMA table_info(users)")->fetchAll();
            $colNames = array_column($cols, 'name');
            if (!in_array('email', $colNames)) {
                try { $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150)"); } catch (\Throwable $e) {}
            }

            // Garante usuário root baitabric
            $stmtRoot = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = 'baitabric' OR LOWER(email) = 'baitabric@gmail.com' LIMIT 1");
            $stmtRoot->execute();
            $rootRow = $stmtRoot->fetch();
            if (!$rootRow) {
                $stmtIns = $pdo->prepare("INSERT INTO users (name, username, password, email, nivel, admin, mac_amount) VALUES ('BaitaBric Root', 'baitabric', :pwd, 'baitabric@gmail.com', '1', '1', '9999')");
                $stmtIns->bindValue(':pwd', password_hash('baitabric123', PASSWORD_DEFAULT));
                $stmtIns->execute();
            } else {
                $stmtUp = $pdo->prepare("UPDATE users SET email = 'baitabric@gmail.com', admin = '1', nivel = '1' WHERE id = :id");
                $stmtUp->bindValue(':id', $rootRow['id']);
                $stmtUp->execute();
            }

            // Garante usuário root diego
            $stmtDiego = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = 'diego' LIMIT 1");
            $stmtDiego->execute();
            $diegoRow = $stmtDiego->fetch();
            if (!$diegoRow) {
                $stmtInsD = $pdo->prepare("INSERT INTO users (name, username, password, email, nivel, admin, mac_amount) VALUES ('Diego Root', 'diego', :pwd, 'diego@baitabric.com', '1', '1', '9999')");
                $stmtInsD->bindValue(':pwd', password_hash('diego123', PASSWORD_DEFAULT));
                $stmtInsD->execute();
            } else {
                $stmtUpD = $pdo->prepare("UPDATE users SET admin = '1', nivel = '1' WHERE id = :id");
                $stmtUpD->bindValue(':id', $diegoRow['id']);
                $stmtUpD->execute();
            }
        } catch (\Throwable $e) {
            // Silencia para não bloquear caso banco esteja em modo transação
        }
    }

    public static function findByLogin(string $login): ?array
    {
        self::ensureSchema();

        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(:login) OR LOWER(email) = LOWER(:login) LIMIT 1");
        $stmt->bindValue(':login', $login);
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ?: null;
    }
}
