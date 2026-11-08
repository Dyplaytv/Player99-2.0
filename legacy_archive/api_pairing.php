<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$code = strtoupper(trim((string)($_GET['code'] ?? '')));
if ($code === '') {
    echo json_encode(['paired' => false]);
    exit;
}

$paired = false;
$dbPaths = [
    dirname(__DIR__) . '/api/db/studiolivecode_clientes.db',
    dirname(__DIR__) . '/api/db/studiolivecode_cliente_media.db'
];

foreach ($dbPaths as $path) {
    if (file_exists($path)) {
        try {
            $db = new SQLite3($path);
            $db->busyTimeout(5000);
            $stmt = $db->prepare("SELECT id FROM telas WHERE UPPER(player_code) = :c LIMIT 1");
            if ($stmt) {
                $stmt->bindValue(':c', $code, SQLITE3_TEXT);
                $res = $stmt->execute();
                if ($res && $res->fetchArray(SQLITE3_ASSOC)) {
                    $paired = true;
                }
            }
            $db->close();
            if ($paired) break;
        } catch (\Throwable $e) {}
    }
}

echo json_encode(['paired' => $paired]);
