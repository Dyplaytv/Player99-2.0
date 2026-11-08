<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
date_default_timezone_set('America/Sao_Paulo');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once './includes/db_cliente.php';
    
    $codigo = $_POST['codigo'] ?? '';
    $sessionId = $_POST['session_id'] ?? '';
    
    if (!empty($codigo)) {
        // Verifica a sessão atual e o status do cliente
        if ($sessionId !== 'old_player') {
            $dbCliente->exec("ATTACH DATABASE '" . __DIR__ . "/api/db/studiolivecode_clientes.db' AS clientesDB");
            $stmt = $dbCliente->prepare("SELECT t.active_session_id, t.force_reload, i.active, i.expire_date FROM telas t LEFT JOIN clientesDB.ibo i ON t.id_cliente = i.id WHERE t.player_code = :codigo");
            $stmt->bindValue(':codigo', $codigo, SQLITE3_TEXT);
            $res = $stmt->execute();
            $row = $res->fetchArray(SQLITE3_ASSOC);
            
            if ($row) {
                // Checa validade e bloqueio do cliente principal
                if (isset($row['active'])) {
                    if ($row['active'] == 0) {
                        echo json_encode(["success" => false, "action" => "stop"]);
                        exit;
                    }
                    
                    $exp = $row['expire_date'];
                    if (strpos($exp, '/') !== false) {
                        $parts = explode('/', $exp);
                        if (count($parts) == 3 && strlen($parts[0]) == 2 && strlen($parts[2]) == 4) {
                            $exp = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                        } else if (count($parts) == 3 && strlen($parts[0]) == 4) {
                            $exp = $parts[0] . '-' . $parts[1] . '-' . $parts[2];
                        }
                    }
                    date_default_timezone_set('America/Sao_Paulo');
                    if (strtotime(date('Y-m-d')) > strtotime($exp)) {
                        echo json_encode(["success" => false, "action" => "stop"]);
                        exit;
                    }
                }

                if ($row['active_session_id'] !== $sessionId) {
                    // Sessão expirada/sobrescrita por outro dispositivo
                    echo json_encode(["success" => false, "action" => "stop"]);
                    exit;
                }
                
                if (isset($row['force_reload']) && $row['force_reload'] == 1) {
                    // Limpa o aviso de reload
                    $stmtReset = $dbCliente->prepare("UPDATE telas SET force_reload = 0 WHERE player_code = :codigo");
                    $stmtReset->bindValue(':codigo', $codigo, SQLITE3_TEXT);
                    $stmtReset->execute();
                    
                    echo json_encode(["success" => true, "action" => "reload"]);
                    exit;
                }
            }
        }
        
        $hasPlaylist = false;
        if (isset($_POST['check_playlist']) && $_POST['check_playlist'] == 1) {
            $stmt = $dbCliente->prepare("SELECT id_playlist FROM telas WHERE player_code = :codigo");
            $stmt->bindValue(':codigo', $codigo, SQLITE3_TEXT);
            $pRow = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
            if ($pRow && $pRow['id_playlist'] > 0) {
                $plId = $pRow['id_playlist'];
                $count = $dbCliente->querySingle("SELECT COUNT(*) FROM playlist_videos WHERE id_playlist = $plId");
                if ($count > 0) {
                    $hasPlaylist = true;
                }
            }
        }
        
        $agora = date('Y-m-d H:i:s');
        $stmt = $dbCliente->prepare("UPDATE telas SET ultimo_ping = :agora WHERE player_code = :codigo");
        $stmt->bindValue(':agora', $agora, SQLITE3_TEXT);
        $stmt->bindValue(':codigo', $codigo, SQLITE3_TEXT);
        $stmt->execute();
        
        echo json_encode(["success" => true, "action" => "continue", "has_playlist" => $hasPlaylist]);
        exit;
    }
}
echo json_encode(["success" => false]);
?>
