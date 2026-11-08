<?php
session_start();
header('Content-Type: application/json');

if ((!isset($_SESSION['mpg_username']) && !isset($_SESSION['mpg_multi_codigo']) && !isset($_SESSION['cliente_logado'])) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$config_file = 'config.json';
$config = json_decode(file_get_contents($config_file), true);
$access_token = $config['access_token'];

$is_multi = isset($_POST['is_multi']) && $_POST['is_multi'] == '1';
$is_fatura = isset($_POST['is_fatura']) && $_POST['is_fatura'] == '1';
$id_pacote = $_POST['id_pacote'] ?? null;
$pacote_escolhido = null;

if ($is_fatura) {
    $id_fatura = $_POST['id_fatura'];
    $valor_final = floatval($_POST['valor_fatura']);
    $titulo_item = "Pagamento de Fatura #" . $id_fatura;
    $external_reference = "MPGFATURA|" . $id_fatura;
} else {
    if ($is_multi) {
        foreach ($config['multi_macs'] as $m) {
            if ($m['id'] === $id_pacote) {
                $pacote_escolhido = $m;
                break;
            }
        }
    } else {
        foreach ($config['pacotes'] as $p) {
            if ($p['id'] === $id_pacote) {
                $pacote_escolhido = $p;
                break;
            }
        }
    }

    if (!$pacote_escolhido) {
        echo json_encode(['error' => 'Pacote inválido!']);
        exit;
    }

    $valor_final = floatval($pacote_escolhido['valor']);

    if ($is_multi) {
        $codigo = $pacote_escolhido['codigo'];
        $external_reference = "MPGMULTI|" . $codigo;
        $titulo_item = "Pacote Multi MACs - " . $codigo;
    } else {
        $username = $_SESSION['mpg_username'];
        $dias = $pacote_escolhido['dias'] ?? $pacote_escolhido['creditos'];
        // A referência externa guarda informações cruciais para o webhook
        $external_reference = "MPG|" . $username . "|" . $dias;
        $titulo_item = "Recarga - " . $pacote_escolhido['nome'];
        
        // Verifica se há cupom ativo na sessão
        if (isset($_SESSION['mpg_cupom_desconto'])) {
            $desconto = $_SESSION['mpg_cupom_desconto'];
            $valor_final = $valor_final - (($valor_final * $desconto) / 100);
            $titulo_item .= " (Cupom: " . $_SESSION['mpg_cupom_codigo'] . " -".$desconto."%)";
        }
    }
}

$host = $_SERVER['HTTP_HOST'];
if ($host === 'localhost' || $host === '127.0.0.1' || strpos($host, '192.168.') !== false) {
    $host = "teste-local.com";
}
$base_url = "https://" . $host . "/api/mercado_pago_gold";

// Usa o uniqid() para garantir idempotência
$idempotency_key = uniqid();

$data = [
    "transaction_amount" => round($valor_final, 2),
    "description" => $titulo_item,
    "payment_method_id" => "pix",
    "payer" => [
        "email" => "pix" . time() . "@seudominio.com",
        "first_name" => "Cliente",
        "last_name" => "Renovacao"
    ],
    "notification_url" => $base_url . "/webhook.php",
    "external_reference" => $external_reference
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer " . $access_token,
    "Content-Type: application/json",
    "X-Idempotency-Key: " . $idempotency_key
]);

// Log request (opcional)
file_put_contents('mp_request_log.txt', date('Y-m-d H:i:s') . " (PIX)\n" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n", FILE_APPEND);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
curl_close($ch);

$mp_response = json_decode($response, true);

if (isset($mp_response['point_of_interaction']['transaction_data'])) {
    $tx_data = $mp_response['point_of_interaction']['transaction_data'];
    echo json_encode([
        'success' => true,
        'payment_id' => $mp_response['id'],
        'qr_code' => $tx_data['qr_code'],
        'qr_code_base64' => $tx_data['qr_code_base64']
    ]);
} else {
    $err_msg = $mp_response['message'] ?? 'Erro desconhecido';
    if (isset($mp_response['cause'][0]['description'])) {
        $err_msg .= " - " . $mp_response['cause'][0]['description'];
    }
    echo json_encode(['error' => 'Erro Mercado Pago: ' . $err_msg]);
}
?>
