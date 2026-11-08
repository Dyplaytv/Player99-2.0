<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once './includes/db_cliente.php';

$code = $_GET['code'] ?? '';

if (empty($code)) {
    echo json_encode(["paired" => false]);
    exit;
}

// Verifica se o código existe na tabela de telas
$stmt = $dbCliente->prepare("SELECT id FROM telas WHERE player_code = :codigo");
$stmt->bindValue(':codigo', $code, SQLITE3_TEXT);
$res = $stmt->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);

if ($row) {
    echo json_encode(["paired" => true]);
} else {
    echo json_encode(["paired" => false]);
}
?>
