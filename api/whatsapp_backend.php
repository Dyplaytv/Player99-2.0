<?php
session_start();
header('Content-Type: application/json');

// Proteção simples (precisa estar logado no painel admin, você pode reforçar dependendo do seu auth)
if (!isset($_SESSION['U'])) {
    echo json_encode(['error' => 'Acesso negado.']);
    exit;
}

$action = $_POST['action'] ?? '';
$configFile = __DIR__ . '/../evolution_config.json';

if ($action === 'save_config') {
    $data = [
        'evolution_url' => rtrim($_POST['evolution_url'] ?? '', '/'),
        'api_key' => $_POST['api_key'] ?? '',
        'instancia' => $_POST['instancia'] ?? 'instancia',
        'hub_token' => $_POST['hub_token'] ?? ''
    ];
    file_put_contents($configFile, json_encode($data, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'test_hub') {
    $hubToken = $_POST['hub_token'] ?? '';
    
    if (empty($hubToken)) {
        echo json_encode(['connected' => false, 'error' => 'Token não fornecido.']);
        exit;
    }
    
    $ch = curl_init('https://admhibrido.cadtech.com.br/api/hub_receiver.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => 'test']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $hubToken
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo json_encode(['connected' => true]);
    } else {
        echo json_encode(['connected' => false, 'error' => "Falha na conexão. HTTP Code: $httpCode"]);
    }
    exit;
}

if ($action === 'get_qrcode') {
    if (!file_exists($configFile)) {
        echo json_encode(['error' => 'Salve as configurações primeiro.']);
        exit;
    }
    $config = json_decode(file_get_contents($configFile), true);
    
    $url = $config['evolution_url'];
    $apikey = $config['api_key'];
    $instancia = $config['instancia'];
    
    if (empty($url) || empty($apikey)) {
        echo json_encode(['error' => 'URL ou API Key não preenchidos.']);
        exit;
    }
    
    // 1. Tenta criar a instância (se já existir, retorna erro tratado)
    $ch = curl_init("$url/instance/create");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'instanceName' => $instancia,
        'qrcode' => true,
        'integration' => 'WHATSAPP-BAILEYS'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "apikey: $apikey",
        "Content-Type: application/json"
    ]);
    $create_res = curl_exec($ch);
    curl_close($ch);
    $create_data = json_decode($create_res, true);
    
    // Se a API retornar a base64 logo na criação, ótimo.
    if (isset($create_data['qrcode']['base64']) && !empty($create_data['qrcode']['base64'])) {
        echo json_encode(['qr' => $create_data['qrcode']['base64']]);
        exit;
    }
    
    // 2. Se a instância já existe, buscamos o status de conexão
    $ch2 = curl_init("$url/instance/connectionState/$instancia");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ["apikey: $apikey"]);
    $state_res = curl_exec($ch2);
    curl_close($ch2);
    $state_data = json_decode($state_res, true);
    
    $state = $state_data['instance']['state'] ?? '';
    if ($state === 'open') {
        echo json_encode(['connected' => true]);
        exit;
    }
    
    // 3. Se não está conectada, pede o QR Code (endpoint de connect)
    $ch3 = curl_init("$url/instance/connect/$instancia");
    curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch3, CURLOPT_HTTPHEADER, ["apikey: $apikey"]);
    $qr_res = curl_exec($ch3);
    curl_close($ch3);
    $qr_data = json_decode($qr_res, true);
    
    if (isset($qr_data['base64']) && strpos($qr_data['base64'], 'data:image') === 0) {
        echo json_encode(['qr' => $qr_data['base64']]);
    } elseif (isset($qr_data['code'])) {
        echo json_encode(['error' => 'A API retornou texto em vez de base64.']);
    } else {
        echo json_encode(['error' => 'Não foi possível buscar o QR Code. Talvez a instância esteja conectando.', 'debug' => $qr_data]);
    }
    exit;
}

if ($action === 'check_status') {
    if (!file_exists($configFile)) {
        echo json_encode(['error' => 'Configure primeiro.']);
        exit;
    }
    $config = json_decode(file_get_contents($configFile), true);
    $url = $config['evolution_url'];
    $apikey = $config['api_key'];
    $instancia = $config['instancia'];

    $ch2 = curl_init("$url/instance/connectionState/$instancia");
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ["apikey: $apikey"]);
    $state_res = curl_exec($ch2);
    curl_close($ch2);
    $state_data = json_decode($state_res, true);
    
    $state = $state_data['instance']['state'] ?? '';
    if ($state === 'open') {
        echo json_encode(['connected' => true]);
    } else {
        echo json_encode(['connected' => false]);
    }
    exit;
}

if ($action === 'disconnect') {
    if (!file_exists($configFile)) {
        echo json_encode(['error' => 'Configure primeiro.']);
        exit;
    }
    $config = json_decode(file_get_contents($configFile), true);
    
    $url = $config['evolution_url'];
    $apikey = $config['api_key'];
    $instancia = $config['instancia'];
    
    $ch = curl_init("$url/instance/logout/$instancia");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["apikey: $apikey"]);
    curl_exec($ch);
    curl_close($ch);
    
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Ação inválida.']);
?>
