<?php
session_start();
require_once __DIR__ . '/../../init_loja_db.php';

if (!isset($_GET['prod_id'])) {
    die("Produto não informado.");
}

$prod_id = (int)$_GET['prod_id'];
$dbLoja = new SQLite3(__DIR__ . '/../db/studiolivecode_loja.db');

$stmt = $dbLoja->prepare("SELECT * FROM produtos WHERE id = :id AND ativo = 1");
$stmt->bindValue(':id', $prod_id, SQLITE3_INTEGER);
$res = $stmt->execute();
$produto = $res->fetchArray(SQLITE3_ASSOC);

if (!$produto) {
    die("Produto não encontrado ou indisponível.");
}

// Processa o Checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];

    // Insere pedido pendente no banco
    $stmtPed = $dbLoja->prepare("INSERT INTO pedidos (produto_id, cliente_nome, cliente_email) VALUES (:pid, :nome, :email)");
    $stmtPed->bindValue(':pid', $prod_id, SQLITE3_INTEGER);
    $stmtPed->bindValue(':nome', $nome, SQLITE3_TEXT);
    $stmtPed->bindValue(':email', $email, SQLITE3_TEXT);
    $stmtPed->execute();
    
    $pedido_id = $dbLoja->lastInsertRowID();

    // Integração Mercado Pago Gold
    $config_file = __DIR__ . '/../mercado_pago_gold/config.json';
    if (!file_exists($config_file)) {
        die("Configuração do Mercado Pago não encontrada.");
    }
    
    $config = json_decode(file_get_contents($config_file), true);
    $access_token = $config['access_token'];

    $external_reference = "LOJA|" . $pedido_id;

    $host = $_SERVER['HTTP_HOST'];
    if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, '192.168.') !== false) {
        $host = "teste-local.com"; // workaround local
    }
    $base_url = "https://" . $host . "/api/loja";

    $data = [
        "items" => [
            [
                "title" => "Serviço Digital: " . $produto['titulo'],
                "quantity" => 1,
                "currency_id" => "BRL",
                "unit_price" => round($produto['preco'], 2)
            ]
        ],
        "payer" => [
            "name" => $nome,
            "email" => $email
        ],
        "back_urls" => [
            "success" => $base_url . "/sucesso.php",
            "failure" => "https://" . $host . "/loja.php",
            "pending" => "https://" . $host . "/loja.php"
        ],
        "notification_url" => $base_url . "/webhook.php",
        "auto_return" => "approved",
        "external_reference" => $external_reference
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/checkout/preferences");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $access_token,
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $response = curl_exec($ch);
    curl_close($ch);

    $mp_response = json_decode($response, true);

    if (isset($mp_response['init_point'])) {
        header("Location: " . $mp_response['init_point']);
        exit;
    } else {
        die("Erro ao gerar pagamento com Mercado Pago. Verifique o Access Token.");
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
    <title>Checkout - <?php echo htmlspecialchars($produto['titulo']); ?></title>
    <style>
        body { background-color: #181828; color: #fff; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .checkout-box { background: rgba(0, 0, 0, 0.5); padding: 40px; border-radius: 20px; border-top: 4px solid #34e2e4; box-shadow: 0 10px 30px rgba(0,0,0,0.5); max-width: 500px; width: 100%; }
        .prod-info { text-align: center; margin-bottom: 30px; }
        .prod-img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; margin-bottom: 15px; border: 2px solid #34e2e4; }
        .price { font-size: 2rem; color: #34e2e4; font-weight: bold; }
        .btn-pay { background: linear-gradient(135deg, #0A38D1, #4721fb); color: #fff; font-weight: bold; border: none; padding: 15px; border-radius: 10px; transition: all 0.3s; width: 100%; }
        .btn-pay:hover { background: linear-gradient(135deg, #4721fb, #34e2e4); box-shadow: 0 5px 15px rgba(52, 226, 228, 0.4); }
    </style>
</head>
<body>
    <div class="checkout-box">
        <a href="../../loja.php" class="text-light mb-3 d-inline-block"><i class="fas fa-arrow-left"></i> Voltar</a>
        <div class="prod-info">
            <img src="../../uploads/loja/<?php echo $produto['imagem'] ?: 'default.jpg'; ?>" class="prod-img" onerror="this.style.display='none'">
            <h3><?php echo htmlspecialchars($produto['titulo']); ?></h3>
            <div class="price">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></div>
        </div>

        <form action="" method="post">
            <div class="form-group">
                <label>Seu Nome Completo</label>
                <input type="text" name="nome" class="form-control form-control-lg bg-dark text-light border-secondary" required placeholder="João da Silva">
            </div>
            <div class="form-group">
                <label>Seu Melhor E-mail</label>
                <input type="email" name="email" class="form-control form-control-lg bg-dark text-light border-secondary" required placeholder="joao@email.com">
                <small class="form-text text-muted">Você receberá o voucher neste email (fictício).</small>
            </div>
            <button type="submit" class="btn-pay mt-4"><i class="fas fa-lock"></i> Ir para Pagamento Seguro</button>
        </form>
    </div>
</body>
</html>
