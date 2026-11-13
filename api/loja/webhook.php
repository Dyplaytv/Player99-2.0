<?php
require_once __DIR__ . '/../../init_loja_db.php';
require_once __DIR__ . '/notify_helper.php';

$access_token = '';
try {
    $usersDbPath = __DIR__ . '/../db/studiolivecode_users.db';
    if (file_exists($usersDbPath)) {
        $pdoUsers = new PDO("sqlite:{$usersDbPath}");
        $stmtMp = $pdoUsers->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'mercadopago_access_token' LIMIT 1");
        $stmtMp->execute();
        $access_token = trim((string)$stmtMp->fetchColumn());
    }
} catch (\Throwable $e) {}

if ($access_token === '') {
    $config_file = __DIR__ . '/../mercado_pago_gold/config.json';
    if (file_exists($config_file)) {
        $config = json_decode(file_get_contents($config_file), true);
        $access_token = trim((string)($config['access_token'] ?? ''));
    }
}

if ($access_token === '') {
    http_response_code(500);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['type']) && !isset($data['topic'])) {
    http_response_code(200);
    exit;
}

$topic = $data['type'] ?? $data['topic'];

if ($topic == 'payment') {
    $id = $data['data']['id'] ?? $_GET['id'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/" . $id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $access_token
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $payment = json_decode($response, true);
    
    if (isset($payment['status']) && $payment['status'] == 'approved') {
        $external_reference = $payment['external_reference'];
        
        if (strpos($external_reference, 'LOJA|') === 0) {
            $parts = explode('|', $external_reference);
            $pedido_id = (int)$parts[1];
            
            $dbLoja = new SQLite3(__DIR__ . '/../db/studiolivecode_loja.db');
            
            // Verifica se o pedido já não foi pago para evitar duplicidade na comissão
            $stmtCheck = $dbLoja->prepare("SELECT status_pagamento, produto_id FROM pedidos WHERE id = :id");
            $stmtCheck->bindValue(':id', $pedido_id, SQLITE3_INTEGER);
            $resCheck = $stmtCheck->execute();
            $pedido = $resCheck->fetchArray(SQLITE3_ASSOC);
            
            if ($pedido) {
                $wasNotPaid = ($pedido['status_pagamento'] !== 'pago');
                processarVendaAprovadaLoja($dbLoja, $pedido_id, (string)$id);

                if ($wasNotPaid) {
                    // Repassa o valor para o parceiro no saldo (Dashboard do Parceiro)
                    $stmtProd = $dbLoja->prepare("SELECT parceiro_id, preco FROM produtos WHERE id = :pid");
                    $stmtProd->bindValue(':pid', $pedido['produto_id'], SQLITE3_INTEGER);
                    $resProd = $stmtProd->execute();
                    $produto = $resProd->fetchArray(SQLITE3_ASSOC);
                    
                    if ($produto) {
                        $parceiro_id = $produto['parceiro_id'];
                        $valor = $produto['preco'];
                        
                        $dbClientes = new SQLite3(__DIR__ . '/../db/studiolivecode_clientes.db');
                        $stmtTx = $dbClientes->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description) VALUES (:uid, :amount, 'COMMISSION', 'Venda na Loja - Pedido #$pedido_id')");
                        $stmtTx->bindValue(':uid', $parceiro_id, SQLITE3_INTEGER);
                        $stmtTx->bindValue(':amount', $valor, SQLITE3_FLOAT);
                        $stmtTx->execute();
                    }
                }
            }
        }
    }
}

http_response_code(200);
echo "OK";
