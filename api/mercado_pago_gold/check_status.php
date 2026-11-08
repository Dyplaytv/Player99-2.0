<?php
// api/mercado_pago_gold/check_status.php
header('Content-Type: application/json');

if (!isset($_POST['payment_id'])) {
    echo json_encode(['status' => 'pending']);
    exit;
}

$payment_id = $_POST['payment_id'];

// Verifica se já processou este pagamento
$db_log = new SQLite3('../db/studiolivecode_clientes.db');
$db_log->exec("CREATE TABLE IF NOT EXISTS mp_payments_log (payment_id VARCHAR(100) PRIMARY KEY, data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP)");

$stmt_check = $db_log->prepare("SELECT * FROM mp_payments_log WHERE payment_id = :payment_id");
$stmt_check->bindValue(':payment_id', $payment_id, SQLITE3_TEXT);
$res_check = $stmt_check->execute();

if ($res_check->fetchArray()) {
    echo json_encode(['status' => 'approved']);
    exit;
}

// Como backup, verifica na API do Mercado Pago
$config_file = 'config.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    $access_token = $config['access_token'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/" . $payment_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $access_token
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $payment_info = json_decode($response, true);
    if (isset($payment_info['status']) && $payment_info['status'] == 'approved') {
        echo json_encode(['status' => 'approved']);
        exit;
    }
}

echo json_encode(['status' => 'pending']);
?>
