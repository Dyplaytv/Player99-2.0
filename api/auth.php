<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

ini_set("display_errors", 1);
include __DIR__ . "/../includes/functions.php";

$jsonIn = file_get_contents("php://input");
$resonse = json_decode($jsonIn, true);
$decoded = base64_decode($resonse["data"]);
$authData = json_decode($decoded, true);
$portal = [];

// Caminho do arquivo JSON onde as device_keys serão armazenadas
$jsonFilePath = __DIR__ . '/device_keys.json';

// Caminho do arquivo de log
$logFilePath = __DIR__ . '/connection_log.json';

// Função para ler o arquivo JSON e retornar os dados
function readDeviceKeys($filePath) {
    if (file_exists($filePath)) {
        $jsonData = file_get_contents($filePath);
        return json_decode($jsonData, true);
    }
    return [];
}

// Função para salvar as device_keys no arquivo JSON
function saveDeviceKeys($filePath, $data) {
    file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

// Função para registrar a conexão
function logConnection($filePath, $macAddress, $url) {
    $logData = [];
    
    // Verificar se o arquivo de log existe
    if (file_exists($filePath)) {
        $jsonLog = file_get_contents($filePath);
        $logData = json_decode($jsonLog, true);
    }

    // Dados da conexão atual
    $newLogEntry = [
        "mac_address" => $macAddress,
        "date" => date("Y-m-d H:i:s"), // Data e hora atual
        "url_accessed" => $url
    ];

    // Adicionar a nova entrada ao array de log
    $logData[] = $newLogEntry;

    // Salvar o log atualizado no arquivo JSON
    file_put_contents($filePath, json_encode($logData, JSON_PRETTY_PRINT));
}

// Carregar as device_keys existentes
$deviceKeys = readDeviceKeys($jsonFilePath);

// Verificar se o MAC está no authData
if ($authData["app_device_id"]) {
    $macAddress = base64_decode($authData["app_device_id"]);
    $macAddress = substr($macAddress, 0, 12);
    $formattedMac = strtoupper(preg_replace("/..(?!\$)/", "\$0:", $macAddress));
} else {
    $formattedMac = strtoupper($authData["mac_address"]);
}

// Verificar se já existe uma device_key para o endereço MAC
if (isset($deviceKeys[$formattedMac])) {
    // Usar a chave existente
    $device_key = $deviceKeys[$formattedMac];
} else {
    // Gerar uma nova chave se não existir
    $device_key = substr(md5($formattedMac), 0, 8);
    
    // Salvar a nova chave no arquivo JSON
    $deviceKeys[$formattedMac] = $device_key;
    saveDeviceKeys($jsonFilePath, $deviceKeys);
}

// Verificar bloqueios (Vencimento ou Admin) na tabela ibo
$dbIbo = new SQLite3(__DIR__ . '/db/studiolivecode_clientes.db'); $dbIbo->busyTimeout(5000);
$resIbo = $dbIbo->prepare("SELECT * FROM ibo WHERE mac_address = :mac_address");
$resIbo->bindValue(':mac_address', $formattedMac, SQLITE3_TEXT);
$result_ibo = $resIbo->execute();
$row_ibo = $result_ibo->fetchArray();

$isBlocked = false;
if ($row_ibo !== false) {
    if (isset($row_ibo['active']) && $row_ibo['active'] == 0) {
        $isBlocked = true;
    }
    if (isset($row_ibo['expire_date']) && !empty($row_ibo['expire_date'])) {
        $hoje = date('Y-m-d');
        if ($row_ibo['expire_date'] <= $hoje) {
            $isBlocked = true;
        }
    }
}

if ($isBlocked) {
    $backdropUrl = getBaseUrl() . "/backdrop.php";
    $link_payment = getBaseUrl() . "/mercado_pago_gold/consulta.php?mac=" . urlencode($formattedMac);
    $link_vencido = getBaseUrl() . "/vencido.php?mac=" . urlencode($formattedMac);
    
    $lang_str = file_get_contents("language.json");
    $lang_str = str_replace('https://iboproapp.com/manage-playlists/login/', $link_vencido, $lang_str);
    $lang_str = str_replace('iboproapp.com', $link_vencido, $lang_str);
    
    $response = [
        "android_version_code" => "1.0.0",
        "apk_url" => "",
        "device_key" => $device_key,
        "expire_date" => date('Y-m-d', strtotime("-1 DAYS")),
        "is_google_paid" => false,
        "is_trial" => 0,
        "languages" => json_decode($lang_str, true),
        "mac_registered" => true,
        "themes" => [],
        "trial_days" => 0,
        "plan_id" => "",
        "mac_address" => $formattedMac,
        "pin" => "0000",
        "price" => "",
        "apk_link" => "",
        "urls" => [
            [
                "is_protected" => "0",
                "id" => "vencido",
                "url" => getBaseUrl() . "/bloqueado.m3u",
                "name" => "SISTEMA VENCIDO-BLOQUEADO",
                "playlist_name" => "SISTEMA VENCIDO-BLOQUEADO",
                "type" => "m3u"
            ]
        ],
        "note_title" => "Acesso Bloqueado",
        "note_content" => "Seu acesso venceu ou foi bloqueado. Escaneie o QR Code na tela com o seu celular ou acesse nosso site para renovar via PIX e liberar seu acesso na hora!",
        "qr_url" => $link_payment,
        "qr_url_short" => $link_payment,
        "home_mode" => 1,
        "home_url1" => $link_vencido,
        "home_url2" => $link_vencido,
        "theme" => "1"
    ];
    echo Encryption::run(json_encode($response), "IBO_38");
    exit;
}

// Consultas de banco de dados
$res = $db->select("dns", "*", "", "");
foreach ($res as $row) {
    $result = $db->select("playlist", "*", "dns_id = :dns_id AND mac_address = :mac_address", "", [":dns_id" => $row["id"], ":mac_address" => $formattedMac]);
    if (!empty($result)) {
        $urlAccessed = $row["url"] . "/get.php?username=" . $result[0]["username"] . "&password=" . $result[0]["password"] . "&type=m3u_plus&output=ts";
        $portal[] = [
            "is_protected" => 0,
            "id" => $row["id"],
            "url" => $urlAccessed,
            "name" => $row["title"],
            "playlist_name" => $row["title"],
            "type" => "xc",
            "created_at" => "2023-03-26 16:42:48",
            "updated_at" => "2023-03-26 16:42:48"
        ];

        // Registrar a conexão no log
        logConnection($logFilePath, $formattedMac, $urlAccessed);
    }
}

// Outros dados
$backdropUrl = getbaseurl() . "/backdrop.php";
$settings = $db->select("settings", "*", "id = :id", "", [":id" => 1]);
$theme = $db->select("themes", "*", "id = :id", "", [":id" => 1]);
$theme_id = !empty($theme) ? $theme[0]["theme_id"] : "1";
$result_pin = $db->select("playlist", "*", "mac_address = :mac_address", "", [":mac_address" => $formattedMac]);

// Resposta final
$response = [
    "android_version_code" => "1.0.0",
    "apk_url" => "",
    "device_key" => $device_key, // Utiliza a chave gerada ou carregada
    "expire_date" => "2034-03-26",
    "is_google_paid" => true,
    "is_trial" => 0,
    "languages" => json_decode(file_get_contents("language.json"), true),
    "mac_registered" => true,
    "themes" => [],
    "trial_days" => 7,
    "plan_id" => "36269518",
    "mac_address" => $formattedMac,
    "pin" => $result_pin[0]["pin"] ?? "0000",
    "price" => "7.99",
    "apk_link" => "",
    "urls" => $portal,
    "note_title" => $settings[0]["note_title"],
    "note_content" => $settings[0]["note_content"],
    "qr_url" => "",
    "qr_url_short" => "",
    "home_mode" => null,
    "home_url1" => $backdropUrl,
    "home_url2" => $backdropUrl,
    "theme" => $theme_id
];

echo Encryption::run(json_encode($response), "IBO_38");

// Função para obter a URL base
function getBaseUrl() {
    $protocol = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off" || $_SERVER["SERVER_PORT"] == 443 ? "https://" : "http://";
    $domainName = $_SERVER["HTTP_HOST"];
    $folderPath = dirname($_SERVER["PHP_SELF"]);
    return $protocol . $domainName . $folderPath;
}

?>
