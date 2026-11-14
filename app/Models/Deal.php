<?php
namespace App\Models;

use App\Core\Database;
use PDO;

class Deal
{
    public static function ensureSchema(): void
    {
        $pdo = Database::deals();

        $pdo->exec("CREATE TABLE IF NOT EXISTS affiliate_settings (
            platform_key VARCHAR(50) PRIMARY KEY,
            platform_name VARCHAR(100) NOT NULL,
            affiliate_tag TEXT NOT NULL DEFAULT '',
            api_key TEXT NOT NULL DEFAULT '',
            active INTEGER DEFAULT 1,
            driver_split_percent REAL DEFAULT 40.0,
            group_jid TEXT DEFAULT '',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS deals (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            platform VARCHAR(50) NOT NULL,
            title TEXT NOT NULL,
            description TEXT DEFAULT '',
            original_url TEXT NOT NULL,
            affiliate_url TEXT NOT NULL,
            image_url TEXT,
            price_old REAL DEFAULT 0,
            price_new REAL DEFAULT 0,
            discount_pct INTEGER DEFAULT 0,
            category VARCHAR(100) DEFAULT 'Geral',
            clicks_count INTEGER DEFAULT 0,
            active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME
        )");

        try {
            $pdo->exec("ALTER TABLE deals ADD COLUMN description TEXT DEFAULT ''");
        } catch (\Throwable $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS deal_clicks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            deal_id INTEGER NOT NULL,
            screen_id INTEGER NOT NULL DEFAULT 0,
            driver_user_id INTEGER NOT NULL DEFAULT 0,
            ip_address VARCHAR(50),
            user_agent TEXT,
            clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Garante as plataformas suportadas cadastradas como base
        $defaults = [
            ['mercadolivre', 'Mercado Livre', 'matt='],
            ['shopee', 'Shopee', ''],
            ['amazon', 'Amazon', 'tag='],
            ['temu', 'Temu', ''],
            ['magalu', 'Magazine Luiza (Magalu)', ''],
            ['aliexpress', 'AliExpress', '']
        ];

        $checkStmt = $pdo->prepare("SELECT platform_key FROM affiliate_settings WHERE platform_key = :k");
        $insertStmt = $pdo->prepare("INSERT INTO affiliate_settings (platform_key, platform_name, affiliate_tag, active, driver_split_percent) VALUES (:k, :name, :tag, 1, 40.0)");

        foreach ($defaults as $d) {
            $checkStmt->execute([':k' => $d[0]]);
            if (!$checkStmt->fetch()) {
                $insertStmt->execute([
                    ':k' => $d[0],
                    ':name' => $d[1],
                    ':tag' => $d[2]
                ]);
            }
        }
    }

    public static function getPlatforms(): array
    {
        self::ensureSchema();
        $pdo = Database::deals();
        return $pdo->query("SELECT * FROM affiliate_settings ORDER BY platform_name ASC")->fetchAll();
    }

    public static function getActiveDeals(int $limit = 20): array
    {
        self::ensureSchema();
        $pdo = Database::deals();
        $stmt = $pdo->prepare("SELECT * FROM deals WHERE active = 1 ORDER BY id DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();
        $pdo = Database::deals();
        $stmt = $pdo->prepare("SELECT * FROM deals WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function recordClick(int $dealId, int $screenId, int $driverUserId = 0, ?string $ip = null, ?string $ua = null): void
    {
        self::ensureSchema();
        $pdo = Database::deals();

        $stmt = $pdo->prepare("INSERT INTO deal_clicks (deal_id, screen_id, driver_user_id, ip_address, user_agent) VALUES (:did, :sid, :uid, :ip, :ua)");
        $stmt->execute([
            ':did' => $dealId,
            ':sid' => $screenId,
            ':uid' => $driverUserId,
            ':ip' => $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'),
            ':ua' => $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? '')
        ]);

        $pdo->prepare("UPDATE deals SET clicks_count = clicks_count + 1 WHERE id = :id")
            ->execute([':id' => $dealId]);
    }
}
