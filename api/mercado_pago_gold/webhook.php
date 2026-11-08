<?php
// webhook.php

$config_file = 'config.json';
$config = json_decode(file_get_contents($config_file), true);
$access_token = $config['access_token'];

// Tabela de log de pagamentos para evitar duplicação
$db_log = new SQLite3('../db/studiolivecode_clientes.db');
$db_log->exec("CREATE TABLE IF NOT EXISTS mp_payments_log (payment_id VARCHAR(100) PRIMARY KEY, data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP)");

if (isset($_GET['topic']) && $_GET['topic'] == 'payment') {
    $payment_id = $_GET['id'];
} elseif (isset($_GET['type']) && $_GET['type'] == 'payment') {
    $payment_id = $_GET['data']['id'];
} else {
    $json_input = file_get_contents('php://input');
    $data = json_decode($json_input, true);
    if (isset($data['type']) && $data['type'] == 'payment') {
        $payment_id = $data['data']['id'];
    }
}

if (!isset($payment_id)) {
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Acesso sem ID de pagamento.\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Iniciando processamento do ID: $payment_id\n", FILE_APPEND);

// Verifica se já processou este pagamento
$stmt_check = $db_log->prepare("SELECT * FROM mp_payments_log WHERE payment_id = :payment_id");
$stmt_check->bindValue(':payment_id', $payment_id, SQLITE3_TEXT);
$res_check = $stmt_check->execute();
if ($res_check->fetchArray()) {
    // Já processado
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Pagamento $payment_id já processado anteriormente.\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

// Consulta status na API
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
    // Registra que foi pago
    $stmt_insert = $db_log->prepare("INSERT INTO mp_payments_log (payment_id) VALUES (:payment_id)");
    $stmt_insert->bindValue(':payment_id', $payment_id, SQLITE3_TEXT);
    $stmt_insert->execute();

    $amount = isset($payment_info['transaction_amount']) ? floatval($payment_info['transaction_amount']) : 0;


    // Extrai referencia externa
    $ext_ref = $payment_info['external_reference']; // "MPG|username|dias"
    $parts = explode("|", $ext_ref);
    
    if (count($parts) === 3 && $parts[0] === 'MPG') {
        $username = $parts[1];
        $dias_add = intval($parts[2]);

        // Adiciona os dias ao usuário
        $stmt_user = $db_log->prepare("SELECT * FROM ibo WHERE username = :username");
        $stmt_user->bindValue(':username', $username, SQLITE3_TEXT);
        $res_user = $stmt_user->execute();
        $row_user = $res_user->fetchArray();

        if ($row_user) {
            $current_expire = strtotime($row_user['expire_date']);
            $now = time();
            
            // Se já venceu, começa a contar de hoje. Se não, soma com a data atual de vencimento.
            if ($current_expire < $now) {
                $new_expire_time = strtotime("+$dias_add days", $now);
            } else {
                $new_expire_time = strtotime("+$dias_add days", $current_expire);
            }
            
            $new_expire_date = date("Y-m-d", $new_expire_time);

            $stmt_upd = $db_log->prepare("UPDATE ibo SET expire_date = :new_expire WHERE username = :username");
            $stmt_upd->bindValue(':new_expire', $new_expire_date, SQLITE3_TEXT);
            $stmt_upd->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt_upd->execute();
            file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Login $username renovado com sucesso para $new_expire_date!\n", FILE_APPEND);

            // Sistema de Afiliados - Comissão
            if (!empty($row_user['referredById']) && $amount > 0) {
                $referredById = $row_user['referredById'];
                $stmt_pai = $db_log->prepare("SELECT commissionPercent FROM ibo WHERE id = :id");
                $stmt_pai->bindValue(':id', $referredById, SQLITE3_INTEGER);
                $res_pai = $stmt_pai->execute();
                if ($row_pai = $res_pai->fetchArray(SQLITE3_ASSOC)) {
                    $percent = floatval($row_pai['commissionPercent']);
                    $commission = ($amount * $percent) / 100;
                    
                    $stmt_comm = $db_log->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description) VALUES (:userId, :amount, 'COMMISSION', :desc)");
                    $stmt_comm->bindValue(':userId', $referredById, SQLITE3_INTEGER);
                    $stmt_comm->bindValue(':amount', $commission, SQLITE3_FLOAT);
                    $stmt_comm->bindValue(':desc', "Comissão renovação de $username", SQLITE3_TEXT);
                    $stmt_comm->execute();
                    
                    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Comissão R$ $commission paga ao afiliado ID $referredById (ref $username).\n", FILE_APPEND);
                }
            }
            
            // Enviar WhatsApp de Comprovante / Agradecimento
            $templateQuery = $db_log->query("SELECT mensagem FROM whatsapp_templates WHERE tipo = 'Comprovante' AND ativo = 1 LIMIT 1");
            $templateRow = $templateQuery->fetchArray(SQLITE3_ASSOC);
            if ($templateRow && !empty($templateRow['mensagem'])) {
                $whatsFile = __DIR__ . '/../../whats.json';
                $whatsData = file_exists($whatsFile) ? json_decode(file_get_contents($whatsFile), true) : [];
                $mac_address = strtoupper(trim($row_user['mac_address']));
                $rawWhats = !empty($row_user['whatsapp']) ? $row_user['whatsapp'] : ($whatsData[$mac_address]['whatsapp'] ?? '');
                if (!empty($rawWhats)) {
                    include_once __DIR__ . '/../../whatsapp_api.php';
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                    $link_payment = $protocol . $_SERVER['HTTP_HOST'] . "/api/renovar/consulta_mac.php?mac=" . $mac_address;
                    
                    $msgAgradecimento = str_replace(
                        ['{nome}', '{vencimento}', '{link}'], 
                        [$row_user['title'], date('d/m/Y', strtotime($new_expire_date)), $link_payment], 
                        $templateRow['mensagem']
                    );
                    enviarMensagemWasender(preg_replace('/[^0-9]/', '', $rawWhats), $msgAgradecimento);
                }
            }
        } else {
            file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Erro: Login $username não encontrado no banco de dados.\n", FILE_APPEND);
        }
    } elseif (count($parts) === 2 && $parts[0] === 'MPGMULTI') {
        $codigo = $parts[1];
        $multi_pacote = null;
        foreach ($config['multi_macs'] ?? [] as $m) {
            if ($m['codigo'] === $codigo) {
                $multi_pacote = $m;
                break;
            }
        }
        
        if ($multi_pacote) {
            $dias_add = intval($multi_pacote['dias']);
            $macs_array = explode(',', $multi_pacote['macs']);
            $sucesso_count = 0;
            
            foreach ($macs_array as $mac_raw) {
                $mac_address = trim($mac_raw);
                if (empty($mac_address)) continue;
                
                $stmt_user = $db_log->prepare("SELECT * FROM ibo WHERE mac_address = :mac");
                $stmt_user->bindValue(':mac', $mac_address, SQLITE3_TEXT);
                $res_user = $stmt_user->execute();
                $row_user = $res_user->fetchArray();

                if ($row_user) {
                    $current_expire = strtotime($row_user['expire_date']);
                    $now = time();
                    
                    if ($current_expire < $now) {
                        $new_expire_time = strtotime("+$dias_add days", $now);
                    } else {
                        $new_expire_time = strtotime("+$dias_add days", $current_expire);
                    }
                    
                    $new_expire_date = date("Y-m-d", $new_expire_time);

                    $stmt_upd = $db_log->prepare("UPDATE ibo SET expire_date = :new_expire WHERE mac_address = :mac");
                    $stmt_upd->bindValue(':new_expire', $new_expire_date, SQLITE3_TEXT);
                    $stmt_upd->bindValue(':mac', $mac_address, SQLITE3_TEXT);
                    $stmt_upd->execute();
                    $sucesso_count++;

                    // Sistema de Afiliados - Comissão para MultiMAC
                    // Se for multi mac, dividiremos o valor total pela quantidade de MACs para calcular a comissão
                    if (!empty($row_user['referredById']) && $amount > 0) {
                        $referredById = $row_user['referredById'];
                        $stmt_pai = $db_log->prepare("SELECT commissionPercent FROM ibo WHERE id = :id");
                        $stmt_pai->bindValue(':id', $referredById, SQLITE3_INTEGER);
                        $res_pai = $stmt_pai->execute();
                        if ($row_pai = $res_pai->fetchArray(SQLITE3_ASSOC)) {
                            $percent = floatval($row_pai['commissionPercent']);
                            $valorFracionado = $amount / max(1, count($macs_array));
                            $commission = ($valorFracionado * $percent) / 100;
                            
                            $stmt_comm = $db_log->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description) VALUES (:userId, :amount, 'COMMISSION', :desc)");
                            $stmt_comm->bindValue(':userId', $referredById, SQLITE3_INTEGER);
                            $stmt_comm->bindValue(':amount', $commission, SQLITE3_FLOAT);
                            $stmt_comm->bindValue(':desc', "Comissão pacote Multi MACs (MAC: $mac_address)", SQLITE3_TEXT);
                            $stmt_comm->execute();
                        }
                    }
                } else {
                    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - MPGMULTI Aviso: MAC $mac_address não encontrado no banco de dados.\n", FILE_APPEND);
                }
            }
            file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Pacote Multi MACs $codigo processado. $sucesso_count MACs renovados com sucesso!\n", FILE_APPEND);
        } else {
            file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Erro MPGMULTI: Código $codigo não encontrado nas configurações.\n", FILE_APPEND);
        }
    } elseif (count($parts) === 2 && $parts[0] === 'MPGFATURA') {
        $idFatura = intval($parts[1]);
        
        $stmt_fat = $db_log->prepare("SELECT id_cliente FROM faturas WHERE id = :id");
        $stmt_fat->bindValue(':id', $idFatura, SQLITE3_INTEGER);
        $res_fat = $stmt_fat->execute();
        $row_fat = $res_fat->fetchArray();
        
        if ($row_fat) {
            $idCliente = $row_fat['id_cliente'];
            
            // Marca a fatura como paga
            $db_log->exec("UPDATE faturas SET status = 'PAGA' WHERE id = $idFatura");
            
            // Calcula a próxima data de expiração do login (dia 8 do próximo ciclo)
            $hoje = new DateTime();
            $diaAtual = (int)$hoje->format('d');
            $mesAtual = (int)$hoje->format('m');
            $anoAtual = (int)$hoje->format('Y');
            
            if ($diaAtual >= 5) {
                // Ex: 21 de Junho -> Vai até 8 de Julho
                $vencimentoAcesso = new DateTime("$anoAtual-$mesAtual-08");
                $vencimentoAcesso->modify('+1 month');
            } else {
                // Ex: 3 de Julho -> Vai até 8 de Julho
                $vencimentoAcesso = new DateTime("$anoAtual-$mesAtual-08");
            }
            $new_expire_date = $vencimentoAcesso->format('Y-m-d');
            
            $db_log->exec("UPDATE ibo SET expire_date = '$new_expire_date', active = 1 WHERE id = $idCliente");
            file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Fatura $idFatura PAGA! Login do cliente $idCliente estendido para $new_expire_date.\n", FILE_APPEND);

            // Sistema de Afiliados - Comissão
            $stmt_user = $db_log->prepare("SELECT referredById, username FROM ibo WHERE id = :id");
            $stmt_user->bindValue(':id', $idCliente, SQLITE3_INTEGER);
            $res_user = $stmt_user->execute();
            if ($row_user = $res_user->fetchArray()) {
                if (!empty($row_user['referredById']) && $amount > 0) {
                    $referredById = $row_user['referredById'];
                    $username = $row_user['username'];
                    $stmt_pai = $db_log->prepare("SELECT commissionPercent FROM ibo WHERE id = :id");
                    $stmt_pai->bindValue(':id', $referredById, SQLITE3_INTEGER);
                    $res_pai = $stmt_pai->execute();
                    if ($row_pai = $res_pai->fetchArray(SQLITE3_ASSOC)) {
                        $percent = floatval($row_pai['commissionPercent']);
                        $commission = ($amount * $percent) / 100;
                        
                        $stmt_comm = $db_log->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description) VALUES (:userId, :amount, 'COMMISSION', :desc)");
                        $stmt_comm->bindValue(':userId', $referredById, SQLITE3_INTEGER);
                        $stmt_comm->bindValue(':amount', $commission, SQLITE3_FLOAT);
                        $stmt_comm->bindValue(':desc', "Comissão pagamento fatura de $username", SQLITE3_TEXT);
                        $stmt_comm->execute();
                        
                        file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Comissão Fatura R$ $commission paga ao afiliado ID $referredById (ref $username).\n", FILE_APPEND);
                    }
                }
            }
            
            // Enviar WhatsApp de Comprovante / Agradecimento para Fatura
            $templateQuery = $db_log->query("SELECT mensagem FROM whatsapp_templates WHERE tipo = 'Comprovante' AND ativo = 1 LIMIT 1");
            $templateRow = $templateQuery->fetchArray(SQLITE3_ASSOC);
            if ($templateRow && !empty($templateRow['mensagem'])) {
                $whatsFile = __DIR__ . '/../../whats.json';
                $whatsData = file_exists($whatsFile) ? json_decode(file_get_contents($whatsFile), true) : [];
                
                // Pega o mac_address e whatsapp do cliente
                $stmt_mac = $db_log->prepare("SELECT mac_address, title, whatsapp FROM ibo WHERE id = :id");
                $stmt_mac->bindValue(':id', $idCliente, SQLITE3_INTEGER);
                $res_mac = $stmt_mac->execute();
                if ($row_mac = $res_mac->fetchArray()) {
                    $mac_address = strtoupper(trim($row_mac['mac_address']));
                    $rawWhats = !empty($row_mac['whatsapp']) ? $row_mac['whatsapp'] : ($whatsData[$mac_address]['whatsapp'] ?? '');
                    if (!empty($rawWhats)) {
                        include_once __DIR__ . '/../../whatsapp_api.php';
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                        $link_payment = $protocol . $_SERVER['HTTP_HOST'] . "/api/renovar/consulta_mac.php?mac=" . $mac_address;
                        
                        $msgAgradecimento = str_replace(
                            ['{nome}', '{vencimento}', '{link}'], 
                            [$row_mac['title'], date('d/m/Y', strtotime($new_expire_date)), $link_payment], 
                            $templateRow['mensagem']
                        );
                        enviarMensagemWasender(preg_replace('/[^0-9]/', '', $rawWhats), $msgAgradecimento);
                    }
                }
            }
        }
    } else {
        file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Referência externa inválida: $ext_ref\n", FILE_APPEND);
    }
} else {
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Pagamento $payment_id ainda não foi aprovado. Status: " . ($payment_info['status'] ?? 'desconhecido') . "\n", FILE_APPEND);
}

http_response_code(200);
echo "OK";
?>
