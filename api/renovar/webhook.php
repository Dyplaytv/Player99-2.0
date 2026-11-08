<?php
// Caminho do arquivo de configuração
$configFile = __DIR__ . '/mp.json';

if (!file_exists($configFile)) {
    file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " - ERRO: mp.json não encontrado\n", FILE_APPEND);
    exit;
}
$config = json_decode(file_get_contents($configFile), true);
$token = $config['access_token'] ?? null;

if (empty($token)) {
    file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " - ERRO: Access Token não configurado\n", FILE_APPEND);
    exit;
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " - Raw: $raw_input\n", FILE_APPEND);

if (isset($input['type']) && $input['type'] === 'payment') {
    $payment_id = $input['data']['id'] ?? '';

    $ch = curl_init("https://api.mercadopago.com/v1/payments/{$payment_id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $resposta = curl_exec($ch);
    curl_close($ch);

    $dados = json_decode($resposta, true);
    file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " - Dados MP: " . print_r($dados, true) . "\n", FILE_APPEND);

    if (isset($dados['status']) && $dados['status'] === 'approved') {
        $mac = $dados['external_reference'] ?? '';
        $amount = isset($dados['transaction_amount']) ? (float)$dados['transaction_amount'] : 0.0;
        $vencimento = date('Y-m-d', strtotime('+30 days'));

        $db = new SQLite3(__DIR__ . "/../db/studiolivecode_clientes.db");
        $db->busyTimeout(5000);
        $db->exec("CREATE TABLE IF NOT EXISTS affiliate_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userId INTEGER,
            amount REAL,
            type TEXT,
            description TEXT,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $stmt = $db->prepare("UPDATE ibo SET expire_date = :vencimento WHERE mac_address = :mac AND active = 1");
        $stmt->bindValue(':vencimento', $vencimento, SQLITE3_TEXT);
        $stmt->bindValue(':mac', $mac, SQLITE3_TEXT);
        $stmt->execute();
        $changes = $db->changes();

        // Crédito automático de comissão para o afiliado indicador
        $stmtCli = $db->prepare("SELECT id, username, title, referredById FROM ibo WHERE mac_address = :mac LIMIT 1");
        $stmtCli->bindValue(':mac', $mac, SQLITE3_TEXT);
        $resCli = $stmtCli->execute();
        if ($resCli && ($rowCli = $resCli->fetchArray(SQLITE3_ASSOC))) {
            if (!empty($rowCli['referredById']) && $amount > 0) {
                $refId = (int)$rowCli['referredById'];
                $stmtPai = $db->prepare("SELECT COALESCE(commissionPercent, 20.0) as commissionPercent FROM ibo WHERE id = :id");
                $stmtPai->bindValue(':id', $refId, SQLITE3_INTEGER);
                $resPai = $stmtPai->execute();
                if ($resPai && ($rowPai = $resPai->fetchArray(SQLITE3_ASSOC))) {
                    $percent = (float)($rowPai['commissionPercent'] ?: 20.0);
                    $commission = round(($amount * $percent) / 100.0, 2);
                    if ($commission > 0) {
                        $cliLabel = $rowCli['title'] ?: $rowCli['username'] ?: $mac;
                        $stmtComm = $db->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description) VALUES (:uid, :amt, 'COMMISSION', :desc)");
                        $stmtComm->bindValue(':uid', $refId, SQLITE3_INTEGER);
                        $stmtComm->bindValue(':amt', $commission, SQLITE3_FLOAT);
                        $stmtComm->bindValue(':desc', "Comissão renovação de {$cliLabel}", SQLITE3_TEXT);
                        $stmtComm->execute();
                    }
                }
            }
        }

        $db->close();
        file_put_contents(__DIR__ . '/webhook_log.txt', date('Y-m-d H:i:s') . " - DB atualizado ($changes alterações) para MAC $mac\n", FILE_APPEND);
    }
}
