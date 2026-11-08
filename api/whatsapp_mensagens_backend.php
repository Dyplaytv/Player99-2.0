<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['U'])) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$dbPath = __DIR__ . '/db/studiolivecode_clientes.db';
if (!file_exists($dbPath)) {
    echo json_encode(['error' => 'Banco de dados não encontrado.']);
    exit;
}

$db = new SQLite3($dbPath);
$action = $_POST['action'] ?? '';

if ($action === 'list') {
    $res = $db->query("SELECT * FROM whatsapp_templates ORDER BY id DESC");
    $data = [];
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

if ($action === 'save') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $tipo = $_POST['tipo'] ?? '';
    $dias = isset($_POST['dias']) && $_POST['dias'] !== '' ? (int)$_POST['dias'] : null;
    $hora = $_POST['hora'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';
    $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 0;

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE whatsapp_templates SET tipo = :tipo, dias = :dias, hora = :hora, mensagem = :mensagem, ativo = :ativo WHERE id = :id");
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    } else {
        $stmt = $db->prepare("INSERT INTO whatsapp_templates (tipo, dias, hora, mensagem, ativo) VALUES (:tipo, :dias, :hora, :mensagem, :ativo)");
    }
    
    $stmt->bindValue(':tipo', $tipo, SQLITE3_TEXT);
    if ($dias === null) {
        $stmt->bindValue(':dias', null, SQLITE3_NULL);
    } else {
        $stmt->bindValue(':dias', $dias, SQLITE3_INTEGER);
    }
    $stmt->bindValue(':hora', $hora, SQLITE3_TEXT);
    $stmt->bindValue(':mensagem', $mensagem, SQLITE3_TEXT);
    $stmt->bindValue(':ativo', $ativo, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Falha ao salvar no banco.']);
    }
    exit;
}

if ($action === 'delete') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        $db->exec("DELETE FROM whatsapp_templates WHERE id = $id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'ID inválido.']);
    }
    exit;
}

if ($action === 'toggle_status') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
    if ($id > 0) {
        $db->exec("UPDATE whatsapp_templates SET ativo = $status WHERE id = $id");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'ID inválido.']);
    }
    exit;
}

echo json_encode(['error' => 'Ação inválida.']);
?>
