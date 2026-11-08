<?php
require __DIR__ . '/vendor/autoload.php';

// Caminho do JSON
$jsonFile = __DIR__ . '/mp.json';

// Lê configurações
if (!file_exists($jsonFile)) {
  die('Erro: Configurações não encontradas.');
}

$configData = file_get_contents($jsonFile);
$config = json_decode($configData, true);

if (json_last_error() !== JSON_ERROR_NONE) {
  die('Erro: JSON inválido - ' . json_last_error_msg());
}

$requiredKeys = ['access_token', 'title', 'quantity', 'unit_price'];
foreach ($requiredKeys as $key) {
  if (!array_key_exists($key, $config)) {
    die("Erro: Chave '$key' ausente no JSON.");
  }
}

MercadoPago\SDK::setAccessToken($config['access_token']);

$mac = $_GET['mac'] ?? null;
if (!$mac) {
    die('Erro: MAC não fornecido.');
}

$preference = new MercadoPago\Preference();

$item = new MercadoPago\Item();
$item->title = $config['title'];
$item->quantity = $config['quantity'];
$item->unit_price = $config['unit_price'];
$preference->items = [$item];

$preference->external_reference = $mac;
$preference->notification_url = 'https://seupainel.com/api/renovar/webhook.php';
$preference->back_urls = [
    "success" => "https://seu-painel.com/api/renovar/sucesso.php?mac=" . urlencode($mac),
    "failure"https://seu-painel. co./api/renovar/consulta_mac.php" . urlencode($mac)
];
$preference->auto_return = "approved";

try {
    $preference->save();
} catch (Exception $e) {
    die('Erro ao salvar preferência: ' . $e->getMessage());
}

header("Location: " . $preference->init_point);
exit;
?>