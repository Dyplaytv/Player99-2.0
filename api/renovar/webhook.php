<?php
// Caminho do arquivo de configuração
$configFile = __DIR__ . '/mp.json';

// Lê o Access Token salvo
if (!file_exists($configFile)) {
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - ERRO: mp.json não encontrado\n", FILE_APPEND);
    exit;
}
$config = json_decode(file_get_contents($configFile), true);
$token = $config['access_token'] ?? null;

if (empty($token)) {
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - ERRO: Access Token não configurado\n", FILE_APPEND);
    exit;
}

// Lê o conteúdo bruto enviado pelo Mercado Pago
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

// Cria o log
$log = fopen('webhook_log.txt', 'a');
fwrite($log, date('Y-m-d H:i:s') . " - Raw: $raw_input\n");
fwrite($log, date('Y-m-d H:i:s') . " - Parsed: " . print_r($input, true) . "\n");
fclose($log);

// Verifica o tipo de evento
if (isset($input['type']) && $input['type'] === 'payment') {
    $payment_id = $input['data']['id'];

    // Faz a requisição à API do Mercado Pago com o token dinâmico
    $ch = curl_init("https://api.mercadopago.com/v1/payments/{$payment_id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $resposta = curl_exec($ch);
    curl_close($ch);

    $dados = json_decode($resposta, true);

    // Loga a resposta
    file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - Dados MP: " . print_r($dados, true) . "\n", FILE_APPEND);

    // Se aprovado, atualiza o banco
    if (isset($dados['status']) && $dados['status'] === 'approved') {
        $mac = $dados['external_reference'];
        $vencimento = date('Y-m-d', strtotime('+30 days'));

        $db = new SQLite3("../db/studiolivecode_clientes.db");
        $stmt = $db->prepare("UPDATE ibo SET expire_date = :vencimento WHERE mac_address = :mac AND active = 1");
        $stmt->bindValue(':vencimento', $vencimento, SQLITE3_TEXT);
        $stmt->bindValue(':mac', $mac, SQLITE3_TEXT);
        $stmt->execute();
        $changes = $db->changes();
        $db->close();

        file_put_contents('webhook_log.txt', date('Y-m-d H:i:s') . " - DB atualizado ($changes alterações) para MAC $mac\n", FILE_APPEND);
    }
}
?>
