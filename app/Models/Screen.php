<?php
namespace App\Models;

use App\Core\Model;

class Screen extends Model
{
    protected static string $table = 'telas';
    protected static string $connection = 'clients';

    public static function ensureSchema(): void
    {
        try {
            $pdo = self::getConnection();

            $pdo->exec("CREATE TABLE IF NOT EXISTS telas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cliente INTEGER DEFAULT 0,
                nome_tela TEXT,
                title TEXT,
                player_code TEXT UNIQUE,
                id_playlist INTEGER DEFAULT 0,
                ultimo_ping TEXT,
                active_session_id TEXT,
                muted INTEGER DEFAULT 0,
                audio_mode TEXT DEFAULT 'video',
                id_radio INTEGER DEFAULT 0,
                force_reload INTEGER DEFAULT 0,
                rotation INTEGER DEFAULT 0
            )");

            $cols = $pdo->query("PRAGMA table_info(telas)")->fetchAll();
            $colNames = array_column($cols, 'name');
            $requiredCols = [
                'title' => 'TEXT',
                'nome_tela' => 'TEXT',
                'audio_mode' => "TEXT DEFAULT 'video'",
                'rotation' => 'INTEGER DEFAULT 0',
                'force_reload' => 'INTEGER DEFAULT 0',
                'ultimo_ping' => 'TEXT'
            ];
            foreach ($requiredCols as $cName => $cDef) {
                if (!in_array($cName, $colNames)) {
                    try { $pdo->exec("ALTER TABLE telas ADD COLUMN {$cName} {$cDef}"); } catch (\Throwable $e) {}
                }
            }

            $pdo->exec("CREATE TABLE IF NOT EXISTS videos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_cliente INTEGER DEFAULT 0,
                id_tela INTEGER DEFAULT 0,
                title TEXT,
                filename TEXT,
                upload_date DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $vCols = $pdo->query("PRAGMA table_info(videos)")->fetchAll();
            $vColNames = array_column($vCols, 'name');
            if (!in_array('id_tela', $vColNames)) {
                try { $pdo->exec("ALTER TABLE videos ADD COLUMN id_tela INTEGER DEFAULT 0"); } catch (\Throwable $e) {}
            }
            if (!in_array('sequence', $vColNames)) {
                try { $pdo->exec("ALTER TABLE videos ADD COLUMN sequence INTEGER DEFAULT 0"); } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {
            // Silencia erro caso o banco esteja em modo transação ou permissão temporária
        }
    }

    public static function findByCode(string $code): ?array
    {
        self::ensureSchema();
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM telas WHERE UPPER(player_code) = UPPER(:code) LIMIT 1");
        $stmt->bindValue(':code', trim($code));
        $stmt->execute();
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function updatePing(int $id): bool
    {
        self::ensureSchema();
        $pdo = self::getConnection();
        $stmt = $pdo->prepare("UPDATE telas SET ultimo_ping = datetime('now', 'localtime'), force_reload = 0 WHERE id = :id");
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }
}
