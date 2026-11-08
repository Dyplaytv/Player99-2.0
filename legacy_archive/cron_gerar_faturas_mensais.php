<?php
/**
 * CRON JOBS - GERAR FATURAS MENSAIS
 * Este script deve ser executado no dia 5 de cada mês.
 * Ele varre os clientes ativos, conta o número de telas e gera a fatura do ciclo cheio.
 */

require_once __DIR__ . '/includes/db_cliente.php'; // Para manipular tabelas do cliente
require_once __DIR__ . '/includes/db_financeiro.php';

// Apenas executa se for o dia 5, mas se quisermos forçar, podemos passar ?force=1 ou --force no CLI
$diaAtual = (int)date('d');
$isForce = (isset($_GET['force']) && $_GET['force'] == '1') || (isset($argv) && in_array('--force', $argv));

if ($diaAtual !== 5 && !$isForce) {
    die("Este script só roda no dia 5 de cada mês.");
}

$hoje = new DateTime();
$dataGerada = $hoje->format('Y-m-d');
$dataVencimento = $hoje->modify('+3 days')->format('Y-m-d');

// Carregar config do Mercado Pago
$config_file = __DIR__ . '/api/mercado_pago_gold/config.json';
$access_token = '';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    $access_token = $config['access_token'] ?? '';
}

// Busca todos os clientes ativos usando a conexão que já aponta para clientes.db
$resClientes = $dbFinanceiro->query("SELECT id, username, multiplo FROM ibo WHERE active = 1");

$faturasGeradas = 0;

while ($row = $resClientes->fetchArray(SQLITE3_ASSOC)) {
    $idCliente = $row['id'];
    $username = $row['username'];
    $multiplo = isset($row['multiplo']) ? (float)$row['multiplo'] : 1.0;

    // Conta quantas telas ativas ele tem
    $countTelas = $dbCliente->querySingle("SELECT COUNT(*) FROM telas WHERE id_cliente = $idCliente");

    if ($countTelas > 0) {
        $mpGoldConfig = json_decode(file_get_contents(__DIR__ . '/api/mercado_pago_gold/config.json'), true);
        $baseValorPorTela = isset($mpGoldConfig['plano_telas_ativas']['valor']) ? (float)$mpGoldConfig['plano_telas_ativas']['valor'] : 100.00;
        
        $valorPorTela = $baseValorPorTela * $multiplo;
        $valorTotal = $countTelas * $valorPorTela;
        
        // Verifica se já gerou uma fatura para este cliente hoje (evita duplicidade em caso de re-execução do Cron)
        $checkFatura = $dbFinanceiro->querySingle("SELECT id FROM faturas WHERE id_cliente = $idCliente AND data_gerada = '$dataGerada'");
        
        if (!$checkFatura) {
            // Insere a fatura
            $stmtFat = $dbFinanceiro->prepare("INSERT INTO faturas (id_cliente, data_gerada, data_vencimento, valor_total, status) VALUES (:id_cliente, :data_gerada, :data_vencimento, :valor, 'PENDENTE')");
            $stmtFat->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
            $stmtFat->bindValue(':data_gerada', $dataGerada, SQLITE3_TEXT);
            $stmtFat->bindValue(':data_vencimento', $dataVencimento, SQLITE3_TEXT);
            $stmtFat->bindValue(':valor', $valorTotal, SQLITE3_FLOAT);
            $stmtFat->execute();
            $idFatura = $dbFinanceiro->lastInsertRowID();
            
            // Integrar com Mercado Pago para gerar o link PIX
            if (!empty($access_token)) {
                $httpHost = $_SERVER['HTTP_HOST'] ?? 'painel.player99.com.br';
                $base_url = "https://" . $httpHost . "/api/mercado_pago_gold";
                $dataMP = [
                    "items" => [
                        [
                            "title" => "Fatura Mensal ($countTelas Telas)",
                            "quantity" => 1,
                            "currency_id" => "BRL",
                            "unit_price" => round($valorTotal, 2)
                        ]
                    ],
                    "back_urls" => [
                        "success" => "https://" . $httpHost . "/cliente_dashboard.php?tab=config",
                        "failure" => "https://" . $httpHost . "/cliente_dashboard.php?tab=config",
                        "pending" => "https://" . $httpHost . "/cliente_dashboard.php?tab=config"
                    ],
                    "notification_url" => $base_url . "/webhook.php",
                    "auto_return" => "approved",
                    "external_reference" => "MPGFATURA|" . $idFatura,
                    "payment_methods" => ["excluded_payment_types" => [["id" => "ticket"]]]
                ];
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/checkout/preferences");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "Authorization: Bearer " . $access_token,
                    "Content-Type: application/json"
                ]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataMP));
                $resMP = curl_exec($ch);
                curl_close($ch);
                
                if ($resMP) {
                    $mpDecoded = json_decode($resMP, true);
                    if (isset($mpDecoded['init_point'])) {
                        $link = $mpDecoded['init_point'];
                        $idPref = $mpDecoded['id'];
                        $dbFinanceiro->exec("UPDATE faturas SET link_pagamento = '$link', id_preferencia = '$idPref' WHERE id = $idFatura");
                    }
                }
            }
            
            $faturasGeradas++;
        }
    }
}

$mensagemLog = date('Y-m-d H:i:s') . " - Varredura concluída. $faturasGeradas faturas geradas com sucesso.\n";
file_put_contents(__DIR__ . '/log_faturas.txt', $mensagemLog, FILE_APPEND);

echo "Varredura concluída. $faturasGeradas faturas geradas com sucesso.";
?>
