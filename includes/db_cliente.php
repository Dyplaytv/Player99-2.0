<?php
/**
 * Abre um SQLite3 com modo WAL + busyTimeout para evitar "database is locked"
 * quando vários players/painéis acessam ao mesmo tempo.
 */
if (!function_exists('openSqliteSafe')) {
    function openSqliteSafe($path) {
        $conn = new SQLite3($path);
        $conn->busyTimeout(5000);
        @$conn->exec("PRAGMA journal_mode = WAL");
        @$conn->exec("PRAGMA synchronous = NORMAL");
        return $conn;
    }
}

try {
    $dbCliente = openSqliteSafe(__DIR__ . "/../api/db/studiolivecode_cliente_media.db");
} catch (Exception $e) {
    die('Unable to open database: ' . $e->getMessage());
}

$dbCliente->exec("CREATE TABLE IF NOT EXISTS videos(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    id_cliente INTEGER, 
    title TEXT, 
    filename TEXT, 
    upload_date TEXT
)");

$dbCliente->exec("CREATE TABLE IF NOT EXISTS playlists(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    id_cliente INTEGER, 
    title TEXT
)");

// Migração de colunas caso não existam
$res = $dbCliente->query("PRAGMA table_info(playlists)");
$columns = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
}
if (!in_array('is_active', $columns)) {
    $dbCliente->exec("ALTER TABLE playlists ADD COLUMN is_active INTEGER DEFAULT 0");
}
if (!in_array('creation_date', $columns)) {
    $dbCliente->exec("ALTER TABLE playlists ADD COLUMN creation_date TEXT");
}

// Migração para telas
$resTelas = $dbCliente->query("PRAGMA table_info(telas)");
if ($resTelas) {
    $colTelas = [];
    while ($rowT = $resTelas->fetchArray(SQLITE3_ASSOC)) {
        $colTelas[] = $rowT['name'];
    }
    if (!in_array('active_session_id', $colTelas)) {
        $dbCliente->exec("ALTER TABLE telas ADD COLUMN active_session_id TEXT");
    }
    if (!in_array('muted', $colTelas)) {
        $dbCliente->exec("ALTER TABLE telas ADD COLUMN muted INTEGER DEFAULT 0");
    }
    if (!in_array('audio_mode', $colTelas)) {
        $dbCliente->exec("ALTER TABLE telas ADD COLUMN audio_mode TEXT DEFAULT 'video'");
    }
    if (!in_array('id_radio', $colTelas)) {
        $dbCliente->exec("ALTER TABLE telas ADD COLUMN id_radio INTEGER DEFAULT 0");
    }
    if (!in_array('force_reload', $colTelas)) {
        $dbCliente->exec("ALTER TABLE telas ADD COLUMN force_reload INTEGER DEFAULT 0");
    }
    if (!in_array('ultimo_ping', $colTelas)) {
        $dbCliente->exec("ALTER TABLE telas ADD COLUMN ultimo_ping TEXT");
    }
    if (!in_array('rotation', $colTelas)) {
        $dbCliente->exec("ALTER TABLE telas ADD COLUMN rotation INTEGER DEFAULT 0");
    }
}

// Migração para limites, cotas de mídia e WhatsApp na tabela 'ibo' (studiolivecode_clientes.db)
try {
    $dbIboQuota = openSqliteSafe(__DIR__ . "/../api/db/studiolivecode_clientes.db");
    $resIboCols = $dbIboQuota->query("PRAGMA table_info(ibo)");
    $colsIbo = [];
    while ($rowI = $resIboCols->fetchArray(SQLITE3_ASSOC)) {
        $colsIbo[] = $rowI['name'];
    }
    if (!in_array('max_videos', $colsIbo)) {
        $dbIboQuota->exec("ALTER TABLE ibo ADD COLUMN max_videos INTEGER DEFAULT 10");
    }
    if (!in_array('max_video_size_mb', $colsIbo)) {
        $dbIboQuota->exec("ALTER TABLE ibo ADD COLUMN max_video_size_mb INTEGER DEFAULT 50");
    }
    if (!in_array('whatsapp', $colsIbo)) {
        $dbIboQuota->exec("ALTER TABLE ibo ADD COLUMN whatsapp TEXT DEFAULT ''");
        // Migra dados existentes do whats.json para o banco de dados se houver
        $wFile = __DIR__ . "/../whats.json";
        if (file_exists($wFile)) {
            $wData = json_decode(file_get_contents($wFile), true);
            if (is_array($wData)) {
                foreach ($wData as $wMac => $wInfo) {
                    $wNum = trim($wInfo['whatsapp'] ?? '');
                    if ($wNum !== '') {
                        $stmtW = $dbIboQuota->prepare("UPDATE ibo SET whatsapp = :w WHERE UPPER(TRIM(mac_address)) = :m AND (whatsapp IS NULL OR whatsapp = '')");
                        $stmtW->bindValue(':w', $wNum, SQLITE3_TEXT);
                        $stmtW->bindValue(':m', strtoupper(trim($wMac)), SQLITE3_TEXT);
                        $stmtW->execute();
                    }
                }
            }
        }
    }
    $dbIboQuota->close();
} catch (Exception $e) {
    // Silently continue if error
}

// Helper para obter limites de cota do cliente
function getClienteMediaLimits($idCliente) {
    $defaultLimits = ['max_videos' => 10, 'max_video_size_mb' => 50];
    if (!$idCliente || $idCliente <= 0) return $defaultLimits;
    try {
        $db = openSqliteSafe(__DIR__ . "/../api/db/studiolivecode_clientes.db");
        $stmt = $db->prepare("SELECT max_videos, max_video_size_mb FROM ibo WHERE id = :id");
        $stmt->bindValue(':id', $idCliente, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $db->close();
        if ($row) {
            $mv = (isset($row['max_videos']) && $row['max_videos'] !== null && $row['max_videos'] > 0) ? (int)$row['max_videos'] : 10;
            $ms = (isset($row['max_video_size_mb']) && $row['max_video_size_mb'] !== null && $row['max_video_size_mb'] > 0) ? (int)$row['max_video_size_mb'] : 50;
            return ['max_videos' => $mv, 'max_video_size_mb' => $ms];
        }
    } catch (Exception $e) {}
    return $defaultLimits;
}

// Inicialização da Tabela de Rádios Globais (studiolivecode_radios.db)
try {
    $dbRadios = new SQLite3(__DIR__ . "/../api/db/studiolivecode_radios.db");
    $dbRadios->exec("CREATE TABLE IF NOT EXISTS radios (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        url TEXT NOT NULL,
        is_visible INTEGER DEFAULT 1
    )");
    
    $countRadios = $dbRadios->querySingle("SELECT COUNT(*) FROM radios");
    if ($countRadios == 0) {
        $defaultRadios = [
            ['Antena 1', 'https://antena1.newradio.it/stream'],
            ['Alpha FM', 'https://26583.live.streamtheworld.com/ALPHA_FMAAC.aac'],
            ['CBN SP', 'https://22453.live.streamtheworld.com/CBN_SPAAC.aac'],
            ['Transamérica SP', 'https://19313.live.streamtheworld.com/TRANSAMERICA_SPAAC.aac'],
            ['Mix FM', 'https://21303.live.streamtheworld.com/MIXFM_SPAAC.aac']
        ];
        $stmtR = $dbRadios->prepare("INSERT INTO radios (nome, url) VALUES (:nome, :url)");
        foreach ($defaultRadios as $radio) {
            $stmtR->bindValue(':nome', $radio[0], SQLITE3_TEXT);
            $stmtR->bindValue(':url', $radio[1], SQLITE3_TEXT);
            $stmtR->execute();
        }
    }

    $dbRadios->exec("CREATE TABLE IF NOT EXISTS entretenimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        url TEXT NOT NULL,
        icon TEXT DEFAULT 'bx bx-globe',
        is_visible INTEGER DEFAULT 1,
        rss_url TEXT,
        bg_image TEXT
    )");

    // Migração de colunas entretenimentos
    $resEntCols = $dbRadios->query("PRAGMA table_info(entretenimentos)");
    $colsEnt = [];
    while ($rowE = $resEntCols->fetchArray(SQLITE3_ASSOC)) {
        $colsEnt[] = $rowE['name'];
    }
    if (!in_array('rss_url', $colsEnt)) {
        $dbRadios->exec("ALTER TABLE entretenimentos ADD COLUMN rss_url TEXT");
    }
    if (!in_array('bg_image', $colsEnt)) {
        $dbRadios->exec("ALTER TABLE entretenimentos ADD COLUMN bg_image TEXT");
    }
    
    $countEnt = $dbRadios->querySingle("SELECT COUNT(*) FROM entretenimentos");
    if ($countEnt == 0) {
        $defaultEnt = [
            ['Hora Atual', '/widgets/hora.php', 'fas fa-clock'],
            ['Frases e Pensamentos', '/widgets/frases.php', 'fas fa-quote-left'],
            ['Notícias (Últimas)', '/widgets/noticias.php', 'fas fa-newspaper'],
            ['Notícias de Carros', '/widgets/carros.php', 'fas fa-car']
        ];
        $stmtE = $dbRadios->prepare("INSERT INTO entretenimentos (nome, url, icon) VALUES (:nome, :url, :icon)");
        foreach ($defaultEnt as $ent) {
            $stmtE->bindValue(':nome', $ent[0], SQLITE3_TEXT);
            $stmtE->bindValue(':url', $ent[1], SQLITE3_TEXT);
            $stmtE->bindValue(':icon', $ent[2], SQLITE3_TEXT);
            $stmtE->execute();
        }
    }
} catch (Exception $e) {
    // Silently fail if cannot create radios db
}

$dbCliente->exec("CREATE TABLE IF NOT EXISTS playlist_videos(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    id_playlist INTEGER, 
    id_video INTEGER, 
    sequence INTEGER,
    duration INTEGER DEFAULT 15
)");

$resPV = $dbCliente->query("PRAGMA table_info(playlist_videos)");
$colPV = [];
while ($rowP = $resPV->fetchArray(SQLITE3_ASSOC)) {
    $colPV[] = $rowP['name'];
}
if (!in_array('duration', $colPV)) {
    $dbCliente->exec("ALTER TABLE playlist_videos ADD COLUMN duration INTEGER DEFAULT 15");
}

$dbCliente->exec("CREATE TABLE IF NOT EXISTS cliente_settings(
    id_cliente INTEGER PRIMARY KEY, 
    player_code TEXT
)");

// Nova tabela de Telas Múltiplas
$dbCliente->exec("CREATE TABLE IF NOT EXISTS telas(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    id_cliente INTEGER, 
    nome_tela TEXT,
    player_code TEXT UNIQUE,
    id_playlist INTEGER DEFAULT 0,
    ultimo_ping TEXT,
    active_session_id TEXT
)");

function generatePlayerCode($dbCliente, $idCliente) {
    $code = substr(md5(uniqid(rand(), true)), 0, 8); // 8 characters code
    $stmt = $dbCliente->prepare("INSERT OR REPLACE INTO cliente_settings (id_cliente, player_code) VALUES (:id_cliente, :player_code)");
    $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
    $stmt->bindValue(':player_code', $code, SQLITE3_TEXT);
    $stmt->execute();
    return $code;
}

function getPlayerCode($dbCliente, $idCliente) {
    $stmt = $dbCliente->prepare("SELECT player_code FROM cliente_settings WHERE id_cliente = :id_cliente");
    $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    if ($row && !empty($row['player_code'])) {
        return $row['player_code'];
    } else {
        return generatePlayerCode($dbCliente, $idCliente);
    }
}

// Helper robusto para checar se cliente está bloqueado ou vencido
function checkUserStatus($idCliente) {
    if (!$idCliente || $idCliente <= 0) return true; // Bloqueado por padrão
    
    try {
        $db = openSqliteSafe(__DIR__ . "/../api/db/studiolivecode_clientes.db");
        $stmt = $db->prepare("SELECT expire_date, active FROM ibo WHERE id = :id");
        $stmt->bindValue(':id', $idCliente, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $db->close();
        
        if (!$row) return true;
        if ($row['active'] == 0) return true; // Inativo
        
        $exp = $row['expire_date'];
        // Normalizar data (Ex: 21/06/2026 para 2026-06-21)
        if (strpos($exp, '/') !== false) {
            $parts = explode('/', $exp);
            if (count($parts) == 3) {
                if (strlen($parts[0]) == 2 && strlen($parts[2]) == 4) {
                    $exp = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                } else if (strlen($parts[0]) == 4) {
                    $exp = $parts[0] . '-' . $parts[1] . '-' . $parts[2];
                }
            }
        }
        
        date_default_timezone_set('America/Sao_Paulo');
        $hoje = date('Y-m-d');
        
        if (strtotime($hoje) > strtotime($exp)) {
            return true; // Vencido
        }
        
        return false; // Ativo e dentro do prazo
    } catch (Exception $e) {
        return false; // Em caso de erro na DB de clientes, não bloqueia por segurança
    }
}
?>
