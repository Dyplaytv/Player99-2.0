<?php
require_once __DIR__ . '/../../init_loja_db.php';
require_once __DIR__ . '/notify_helper.php';

$external_reference = $_GET['external_reference'] ?? '';

if (empty($external_reference) || strpos($external_reference, 'LOJA|') !== 0) {
    die("Referência inválida.");
}

$parts = explode('|', $external_reference);
$pedido_id = (int)$parts[1];

$dbLoja = new SQLite3(__DIR__ . '/../db/studiolivecode_loja.db');
$stmt = $dbLoja->prepare("SELECT p.*, pr.titulo, pr.observacoes_pos_compra FROM pedidos p JOIN produtos pr ON p.produto_id = pr.id WHERE p.id = :id");
$stmt->bindValue(':id', $pedido_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$pedido = $res->fetchArray(SQLITE3_ASSOC);

if (!$pedido) {
    die("Pedido não encontrado.");
}

// Fallback caso o usuário retorne do Mercado Pago antes do webhook disparar
$mpStatusParam = $_GET['status'] ?? $_GET['collection_status'] ?? '';
$mpPaymentId = trim((string)($_GET['payment_id'] ?? $_GET['collection_id'] ?? ''));

if ($pedido['status_pagamento'] !== 'pago' && $mpStatusParam === 'approved' && $mpPaymentId !== '') {
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

    if ($access_token !== '') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/" . urlencode($mpPaymentId));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $access_token]);
        $mpResp = curl_exec($ch);
        curl_close($ch);

        $mpPayment = json_decode((string)$mpResp, true);
        if (
            isset($mpPayment['status']) && $mpPayment['status'] === 'approved' &&
            ($mpPayment['external_reference'] ?? '') === $external_reference
        ) {
            $updated = processarVendaAprovadaLoja($dbLoja, $pedido_id, $mpPaymentId);
            if ($updated) {
                $pedido = $updated;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <title>Pagamento Confirmado!</title>
    <style>
        body { background-color: #181828; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; }
        .success-box { background: rgba(0, 0, 0, 0.5); padding: 50px; border-radius: 20px; border-top: 5px solid #28a745; box-shadow: 0 10px 30px rgba(40,167,69,0.2); max-width: 600px; width: 100%; }
        .icon-success { font-size: 5rem; color: #28a745; margin-bottom: 20px; }
        .voucher-code { background: #333; padding: 15px; font-size: 2rem; font-weight: bold; letter-spacing: 2px; color: #34e2e4; border-radius: 10px; margin: 20px 0; border: 1px dashed #34e2e4; }
    </style>
</head>
<body>
    <div class="success-box">
        <?php if ($pedido['status_pagamento'] == 'pago'): ?>
            <i class="fas fa-check-circle icon-success"></i>
            <h2>Pagamento Confirmado!</h2>
            <p class="text-muted mb-3">Obrigado pela sua compra do serviço <strong><?php echo htmlspecialchars($pedido['titulo']); ?></strong>.</p>
            
            <?php if (!empty($pedido['numero_venda'])): ?>
                <p class="text-muted small mb-2">Nº da Venda: <strong class="text-white"><?php echo htmlspecialchars($pedido['numero_venda']); ?></strong></p>
            <?php endif; ?>

            <p class="mb-1">Seu Voucher de Acesso:</p>
            <div class="voucher-code"><?php echo htmlspecialchars((string)$pedido['voucher']); ?></div>
            <p class="text-muted small">Guarde este código. Apresente-o ao prestador de serviços para resgatar sua compra.</p>

            <?php if (!empty(trim((string)($pedido['observacoes_pos_compra'] ?? '')))): ?>
                <div class="text-start mt-3 p-3 rounded" style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.35);">
                    <div class="small fw-bold text-success mb-1"><i class="fas fa-info-circle"></i> Observações do Vendedor:</div>
                    <div class="small text-light"><?php echo nl2br(htmlspecialchars((string)$pedido['observacoes_pos_compra'])); ?></div>
                </div>
            <?php endif; ?>
            
            <a href="/loja" class="btn btn-primary mt-4"><i class="fas fa-arrow-left"></i> Voltar à Loja</a>
        <?php else: ?>
            <i class="fas fa-clock text-warning" style="font-size: 5rem; margin-bottom: 20px;"></i>
            <h2>Processando...</h2>
            <p class="text-muted mb-3">Seu pagamento está sendo processado pelo sistema. Assim que for confirmado, seu Voucher aparecerá aqui.</p>
            <?php if (!empty($pedido['numero_venda'])): ?>
                <p class="text-muted small mb-2">Nº da Venda: <strong class="text-white"><?php echo htmlspecialchars($pedido['numero_venda']); ?></strong></p>
            <?php endif; ?>
            <p>Atualize a página em alguns instantes.</p>
            <button onclick="window.location.reload();" class="btn btn-warning mt-4"><i class="fas fa-sync-alt"></i> Atualizar Página</button>
            <br>
            <a href="/loja" class="btn btn-link text-muted mt-3">Voltar à Loja</a>
        <?php endif; ?>
    </div>
</body>
</html>
