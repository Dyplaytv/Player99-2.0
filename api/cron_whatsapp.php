<?php
// api/cron_whatsapp.php
// Configurar no Cron Job para rodar a cada 1 minuto ( * * * * * )

date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/../whatsapp_api.php';

$logFile = __DIR__ . '/cron_whatsapp.log';
function writeLog($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

$dbPath = __DIR__ . '/db/studiolivecode_clientes.db';
if (!file_exists($dbPath)) {
    writeLog("ERRO: Banco de dados não encontrado.");
    exit;
}

$db = new SQLite3($dbPath);
$whatsFile = __DIR__ . '/../whats.json';
$whatsData = file_exists($whatsFile) ? json_decode(file_get_contents($whatsFile), true) : [];

$current_time = date('H:i');
writeLog("CRON EXECUTADO. Hora atual: $current_time");

// Puxar todos os templates ativos
$resTemplates = $db->query("SELECT * FROM whatsapp_templates WHERE ativo = 1");

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$baseUrl = $protocol . $_SERVER['HTTP_HOST'];
if (strpos($baseUrl, 'localhost') === false && strpos($baseUrl, 'api/cron_whatsapp.php') !== false) {
    // Tratamento basico caso o HTTP_HOST falhe no CLI
    $baseUrl = "https://painel.player99.com.br";
}

while ($template = $resTemplates->fetchArray(SQLITE3_ASSOC)) {
    // Verifica se a hora do template é igual a hora atual
    // Como roda a cada 1 minuto, só vai disparar 1x quando for exatamente a hora.
    if (trim($template['hora']) !== $current_time) {
        continue;
    }
    
    writeLog("Processando template: {$template['tipo']} | Dias: {$template['dias']}");
    
    // Calcula a data alvo
    $target_date = null;
    $dias = (int)$template['dias'];
    
    if ($template['tipo'] === 'A vencer') {
        $target_date = date('Y-m-d', strtotime("+$dias days"));
    } elseif ($template['tipo'] === 'Cobrança no dia') {
        $target_date = date('Y-m-d');
    } elseif ($template['tipo'] === 'Cobrança mensalidade vencida') {
        $target_date = date('Y-m-d', strtotime("-$dias days"));
    } else {
        // Tipos manuais não entram no Cron automático
        continue;
    }
    
    // Busca clientes
    // No player99 original 'active' não é 100% confiavel se o expire_date for a métrica, mas buscamos todos não deletados
    // Filtrando pela data_alvo (target_date)
    $stmt = $db->prepare("SELECT * FROM ibo WHERE expire_date = :target_date AND active = 1");
    $stmt->bindValue(':target_date', $target_date, SQLITE3_TEXT);
    $resClientes = $stmt->execute();
    
    $count = 0;
    while ($cliente = $resClientes->fetchArray(SQLITE3_ASSOC)) {
        $mac = strtoupper(trim($cliente['mac_address']));
        $rawWhats = !empty($cliente['whatsapp']) ? $cliente['whatsapp'] : ($whatsData[$mac]['whatsapp'] ?? '');
        if (!empty($rawWhats)) {
            $numero = preg_replace('/[^0-9]/', '', $rawWhats);
            
            if (strlen($numero) >= 10) {
                // Link pagamento
                $link_payment = $cliente['link_payment'] ?? ($baseUrl . "/api/renovar/consulta_mac.php?mac=" . $mac);
                
                // Variáveis
                $mensagem = str_replace(
                    ['{nome}', '{vencimento}', '{link}'], 
                    [$cliente['title'], date('d/m/Y', strtotime($cliente['expire_date'])), $link_payment], 
                    $template['mensagem']
                );
                
                // Envia
                enviarMensagemWasender($numero, $mensagem);
                writeLog("Mensagem '{$template['tipo']}' enviada para $numero (MAC: $mac)");
                $count++;
            }
        }
    }
    
    writeLog("Fim do template '{$template['tipo']}'. $count mensagens enviadas.");
}

writeLog("CRON FINALIZADO.");
?>
