<?php
namespace App\Models;

use App\Core\Model;

class Client extends Model
{
    protected static string $table = 'ibo';
    protected static string $connection = 'clients';

    public static function ensureSchema(): void
    {
        try {
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
                affiliateClicks INTEGER DEFAULT 0,
                pixKey TEXT,
                commissionPercent REAL DEFAULT 20.0,
                referredById INTEGER
            )");

            $cols = $pdo->query("PRAGMA table_info(ibo)")->fetchAll();
            $colNames = array_column($cols, 'name');

            $migrations = [
                'email' => "ALTER TABLE ibo ADD COLUMN email VARCHAR(150)",
                'whatsapp' => "ALTER TABLE ibo ADD COLUMN whatsapp VARCHAR(50)",
                'affiliateCode' => "ALTER TABLE ibo ADD COLUMN affiliateCode VARCHAR(100)",
                'affiliateClicks' => "ALTER TABLE ibo ADD COLUMN affiliateClicks INTEGER DEFAULT 0",
                'pixKey' => "ALTER TABLE ibo ADD COLUMN pixKey TEXT",
                'commissionPercent' => "ALTER TABLE ibo ADD COLUMN commissionPercent REAL DEFAULT 20.0",
                'referredById' => "ALTER TABLE ibo ADD COLUMN referredById INTEGER",
                'is_reseller' => "ALTER TABLE ibo ADD COLUMN is_reseller INTEGER DEFAULT 0",
            ];

            foreach ($migrations as $col => $sql) {
                if (!in_array($col, $colNames, true)) {
                    try { $pdo->exec($sql); } catch (\Throwable $e) {}
                }
            }

            $pdo->exec("CREATE TABLE IF NOT EXISTS affiliate_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                userId INTEGER,
                amount REAL,
                type TEXT,
                description TEXT,
                createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Garante que todos os clientes possuam código de afiliado gerado
            $missingCodes = $pdo->query("SELECT id FROM ibo WHERE affiliateCode IS NULL OR TRIM(affiliateCode) = ''")->fetchAll();
            if (!empty($missingCodes)) {
                $stmtCode = $pdo->prepare("UPDATE ibo SET affiliateCode = :c WHERE id = :id");
                foreach ($missingCodes as $row) {
                    $code = 'REF-' . strtoupper(substr(md5('p99_' . $row['id']), 0, 6));
                    $stmtCode->execute([':c' => $code, ':id' => $row['id']]);
                }
            }
        } catch (\Throwable $e) {
            // Silencia erro caso o banco esteja em transação
        }
    }

    /**
     * Registra o clique no link de afiliado (?ref=...) e persiste a referência em sessão e cookie.
     */
    public static function trackAffiliateClick(?string $ref): void
    {
        if (empty($ref)) {
            return;
        }
        $ref = strtoupper(trim($ref));
        if ($ref === '') {
            return;
        }

        // Salva na sessão e em cookie (30 dias) para manter a indicação até o cadastro
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['affiliate_ref'] = $ref;
        }
        @setcookie('affiliate_ref', $ref, time() + (86400 * 30), '/');

        try {
            self::ensureSchema();
            $pdo = self::getConnection();

            // Evita duplicidade apenas se for um duplo disparo no mesmo segundo (< 3s)
            $sessionKey = 'last_ref_click_' . $ref;
            $now = time();
            $lastClick = isset($_SESSION[$sessionKey]) ? (int)$_SESSION[$sessionKey] : 0;
            if ($now - $lastClick < 3) {
                return;
            }

            $stmt = $pdo->prepare("UPDATE ibo SET affiliateClicks = COALESCE(affiliateClicks, 0) + 1 WHERE UPPER(TRIM(affiliateCode)) = :ref");
            $stmt->execute([':ref' => $ref]);

            if ($stmt->rowCount() > 0 && session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION[$sessionKey] = $now;
            }
        } catch (\Throwable $e) {
            // Silencia falha para não interromper navegação
        }
    }

    /**
     * Credita comissão ao afiliado indicador quando o cliente indicado realiza um pagamento.
     */
    public static function creditAffiliateCommission(int $clientId, float $paymentAmount, string $description = ''): float
    {
        if ($clientId <= 0 || $paymentAmount <= 0) {
            return 0.0;
        }

        try {
            self::ensureSchema();
            $pdo = self::getConnection();

            $stmtCli = $pdo->prepare("SELECT id, username, title, referredById FROM ibo WHERE id = :id LIMIT 1");
            $stmtCli->execute([':id' => $clientId]);
            $client = $stmtCli->fetch();

            if (!$client || empty($client['referredById'])) {
                return 0.0;
            }

            $affiliateId = (int)$client['referredById'];
            if ($affiliateId <= 0 || $affiliateId === $clientId) {
                return 0.0;
            }

            $stmtAff = $pdo->prepare("SELECT id, COALESCE(commissionPercent, 20.0) as commissionPercent FROM ibo WHERE id = :id LIMIT 1");
            $stmtAff->execute([':id' => $affiliateId]);
            $affiliate = $stmtAff->fetch();

            if (!$affiliate) {
                return 0.0;
            }

            $percent = (float)($affiliate['commissionPercent'] ?? 20.0);
            if ($percent <= 0) {
                $percent = 20.0;
            }

            $commission = round(($paymentAmount * $percent) / 100.0, 2);
            if ($commission <= 0) {
                return 0.0;
            }

            $clientName = $client['title'] ?: $client['username'] ?: ('Cliente #' . $clientId);
            $desc = $description !== ''
                ? $description
                : ("Comissão ({$percent}%) sobre pagamento de {$clientName}");

            $stmtTx = $pdo->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description, createdAt) VALUES (:uid, :amt, 'COMMISSION', :desc, datetime('now', 'localtime'))");
            $stmtTx->execute([
                ':uid' => $affiliateId,
                ':amt' => $commission,
                ':desc' => $desc
            ]);

            return $commission;
        } catch (\Throwable $e) {
            return 0.0;
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

    /**
     * Garante e retorna um registro na tabela ibo com is_reseller = 1 para usuários
     * cadastrados como Revendedor/Franqueado (nivel = 2) na tabela users.
     */
    public static function syncResellerClientFromUser(array $user): ?array
    {
        self::ensureSchema();

        $username = trim((string)($user['username'] ?? ''));
        $email = strtolower(trim((string)($user['email'] ?? '')));
        $name = trim((string)($user['name'] ?? $username));
        if ($username === '' && $email === '') {
            return null;
        }

        $pdo = self::getConnection();
        $client = null;
        if ($username !== '') {
            $stmt = $pdo->prepare("SELECT * FROM ibo WHERE LOWER(username) = LOWER(:u) LIMIT 1");
            $stmt->execute([':u' => $username]);
            $client = $stmt->fetch() ?: null;
        }
        if (!$client && $email !== '') {
            $stmt = $pdo->prepare("SELECT * FROM ibo WHERE LOWER(email) = LOWER(:e) LIMIT 1");
            $stmt->execute([':e' => $email]);
            $client = $stmt->fetch() ?: null;
        }

        if ($client) {
            if (empty($client['is_reseller'])) {
                $pdo->prepare("UPDATE ibo SET is_reseller = 1 WHERE id = :id")->execute([':id' => $client['id']]);
                $client['is_reseller'] = 1;
            }
            if (empty($client['affiliateCode'])) {
                $affCode = 'REF-' . strtoupper(substr(md5('p99_' . $client['id']), 0, 6));
                $pdo->prepare("UPDATE ibo SET affiliateCode = :c WHERE id = :id")->execute([':c' => $affCode, ':id' => $client['id']]);
                $client['affiliateCode'] = $affCode;
            }
            return $client;
        }

        $mac = sprintf('%02X:%02X:%02X:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $affCode = 'REF-' . strtoupper(substr(md5(uniqid('p99_frq_', true)), 0, 6));
        $stmtIns = $pdo->prepare("INSERT INTO ibo (mac_address, username, password, email, title, expire_date, active, affiliateCode, affiliateClicks, commissionPercent, is_reseller)
            VALUES (:mac, :u, :p, :e, :t, :exp, 1, :aff, 0, 20.0, 1)");
        $stmtIns->execute([
            ':mac' => $mac,
            ':u'   => $username ?: $email,
            ':p'   => (string)($user['password'] ?? ''),
            ':e'   => $email,
            ':t'   => $name ?: $username,
            ':exp' => date('Y-m-d', strtotime('+365 days')),
            ':aff' => $affCode
        ]);

        return self::find((int)$pdo->lastInsertId());
    }
}
