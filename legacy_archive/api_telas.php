<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION["cliente_logado"]) || $_SESSION["cliente_logado"] !== true) {
    echo json_encode(["success" => false, "msg" => "Não autenticado"]);
    exit;
}

require_once './includes/db_cliente.php';
$idCliente = $_SESSION["cliente_id"];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'vincular_playlist') {
        $idTela = (int)($_POST['id_tela'] ?? 0);
        $idPlaylist = (int)($_POST['id_playlist'] ?? 0);

        $stmt = $dbCliente->prepare("UPDATE telas SET id_playlist = :id_playlist WHERE id = :id_tela AND id_cliente = :id_cliente");
        $stmt->bindValue(':id_playlist', $idPlaylist, SQLITE3_INTEGER);
        $stmt->bindValue(':id_tela', $idTela, SQLITE3_INTEGER);
        $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
    } elseif ($action === 'toggle_mute') {
        $idTela = (int)($_POST['id_tela'] ?? 0);
        $muted = (int)($_POST['muted'] ?? 0);

        $stmt = $dbCliente->prepare("UPDATE telas SET muted = :muted WHERE id = :id_tela AND id_cliente = :id_cliente");
        $stmt->bindValue(':muted', $muted, SQLITE3_INTEGER);
        $stmt->bindValue(':id_tela', $idTela, SQLITE3_INTEGER);
        $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
    } elseif ($action === 'set_audio') {
        $idTela = (int)($_POST['id_tela'] ?? 0);
        $audioMode = $_POST['audio_mode'] ?? 'video';
        $idRadio = (int)($_POST['id_radio'] ?? 0);

        // Atualiza modo e também mantém o "muted" legado em sincronia 
        // para não quebrar players antigos que não foram atualizados
        $mutedFallback = ($audioMode === 'mute' || $audioMode === 'radio') ? 1 : 0;

        $stmt = $dbCliente->prepare("UPDATE telas SET audio_mode = :amode, id_radio = :iradio, muted = :muted WHERE id = :id_tela AND id_cliente = :id_cliente");
        $stmt->bindValue(':amode', $audioMode, SQLITE3_TEXT);
        $stmt->bindValue(':iradio', $idRadio, SQLITE3_INTEGER);
        $stmt->bindValue(':muted', $mutedFallback, SQLITE3_INTEGER);
        $stmt->bindValue(':id_tela', $idTela, SQLITE3_INTEGER);
        $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
    } elseif ($action === 'toggle_rotation') {
        $idTela = (int)($_POST['id_tela'] ?? 0);
        $rotation = (int)($_POST['rotation'] ?? 0);

        // Atualiza a rotação e força o reload para o player pegar a nova configuração
        $stmt = $dbCliente->prepare("UPDATE telas SET rotation = :rotation, force_reload = 1 WHERE id = :id_tela AND id_cliente = :id_cliente");
        $stmt->bindValue(':rotation', $rotation, SQLITE3_INTEGER);
        $stmt->bindValue(':id_tela', $idTela, SQLITE3_INTEGER);
        $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false]);
        }
    } else {
        echo json_encode(["success" => false, "msg" => "Ação inválida."]);
    }
}
?>
