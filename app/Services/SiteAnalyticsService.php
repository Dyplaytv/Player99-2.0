<?php
namespace App\Services;

use PDO;

class SiteAnalyticsService
{
    private static function getDb(): PDO
    {
        $dbDir = dirname(__DIR__, 2) . '/api/db';
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0777, true);
        }
        $dbPath = $dbDir . '/studiolivecode_analytics.db';
        $pdo = new PDO("sqlite:{$dbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $pdo->exec("PRAGMA busy_timeout = 3000;");
        $pdo->exec("CREATE TABLE IF NOT EXISTS site_visits (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            page_key TEXT NOT NULL,
            visitor_hash TEXT NOT NULL,
            visit_date TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_site_visits_date ON site_visits(visit_date)");
        return $pdo;
    }

    public static function record(string $pageKey = 'home'): void
    {
        try {
            $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
            if ($ua !== '' && preg_match('/(bot|crawler|spider|slurp|facebookexternalhit|whatsapp|curl|wget)/i', $ua)) {
                return;
            }

            $ip = (string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')));
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            $today = date('Y-m-d');
            $visitorHash = substr(hash('sha256', $ip . '|' . $ua . '|' . $today), 0, 24);

            $pdo = self::getDb();
            $stmt = $pdo->prepare("INSERT INTO site_visits (page_key, visitor_hash, visit_date) VALUES (:p, :v, :d)");
            $stmt->execute([
                ':p' => $pageKey,
                ':v' => $visitorHash,
                ':d' => $today
            ]);
        } catch (\Throwable $e) {
            // Silencioso para nunca impactar a navegação pública
        }
    }

    public static function getSummary(): array
    {
        $summary = [
            'diario' => 0,
            'semanal' => 0,
            'mensal' => 0,
            'total' => 0,
            'unicos_mes' => 0
        ];

        try {
            $pdo = self::getDb();
            $today = date('Y-m-d');
            $weekStart = date('Y-m-d', strtotime('-6 days'));
            $monthPrefix = date('Y-m');

            $stmtDia = $pdo->prepare("SELECT COUNT(*) FROM site_visits WHERE visit_date = :d");
            $stmtDia->execute([':d' => $today]);
            $summary['diario'] = (int)$stmtDia->fetchColumn();

            $stmtSem = $pdo->prepare("SELECT COUNT(*) FROM site_visits WHERE visit_date >= :ws");
            $stmtSem->execute([':ws' => $weekStart]);
            $summary['semanal'] = (int)$stmtSem->fetchColumn();

            $stmtMes = $pdo->prepare("SELECT COUNT(*) FROM site_visits WHERE substr(visit_date, 1, 7) = :m");
            $stmtMes->execute([':m' => $monthPrefix]);
            $summary['mensal'] = (int)$stmtMes->fetchColumn();

            $summary['total'] = (int)$pdo->query("SELECT COUNT(*) FROM site_visits")->fetchColumn();

            $stmtUni = $pdo->prepare("SELECT COUNT(DISTINCT visitor_hash) FROM site_visits WHERE substr(visit_date, 1, 7) = :m");
            $stmtUni->execute([':m' => $monthPrefix]);
            $summary['unicos_mes'] = (int)$stmtUni->fetchColumn();
        } catch (\Throwable $e) {}

        return $summary;
    }
}
