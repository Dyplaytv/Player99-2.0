<?php
function enviarMensagemWasender($numero, $mensagem) {
    $configFile = __DIR__ . '/evolution_config.json';
    if (!file_exists($configFile)) {
        return ['error' => 'Arquivo evolution_config.json não encontrado. Configure no painel.'];
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    
    $evolutionUrl = rtrim($config['evolution_url'] ?? '', '/');
    $apiKey = $config['api_key'] ?? '';
    $instancia = $config['instancia'] ?? '';
    // Se não tiver hub_token salvo no arquivo (ex: após git reset), usa o token oficial do Jogador99
    $hubToken = !empty($config['hub_token']) ? $config['hub_token'] : 'HUB_6A6039D8DE252ccbcac01';
    
    if(empty($evolutionUrl) && empty($hubToken)) {
        return ['error' => 'Acesse Configurar WhatsApp no painel e salve os dados da API Local ou do Hub primeiro.'];
    }

    // Tratamento de número para o Brasil (DDI 55)
    $numero = preg_replace('/[^0-9]/', '', (string)$numero);
    $numero = ltrim($numero, '0');
    
    // Se o número tiver exatos 10 ou 11 dígitos, é um número padrão do Brasil sem DDI.
    // Mesmo que o DDD seja 55 (ex: Rio Grande do Sul), o tamanho garante que falta o DDI.
    if (strlen($numero) == 10 || strlen($numero) == 11) {
        $numero = '55' . $numero;
    } 
    // Se for menor que 10 dígitos (sem DDD), não temos como adivinhar o DDD,
    // mas se a Evolution aceitar números curtos, adicionamos o 55 pelo menos.
    else if (strlen($numero) < 10) {
        if (strpos($numero, '55') !== 0) {
            $numero = '55' . $numero;
        }
    }
    
    file_put_contents(__DIR__ . '/whatsapp_debug.log', date('Y-m-d H:i:s') . " - Iniciando envio para: $numero\n", FILE_APPEND);
    
    $localConnected = false;
    
    // Verifica conexão local primeiro (Prioridade 1)
    if (!empty($evolutionUrl) && !empty($apiKey) && !empty($instancia)) {
        $chState = curl_init("$evolutionUrl/instance/connectionState/$instancia");
        curl_setopt($chState, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chState, CURLOPT_HTTPHEADER, ["apikey: $apiKey"]);
        curl_setopt($chState, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chState, CURLOPT_TIMEOUT, 5); // Timeout rápido para não travar
        $stateRes = curl_exec($chState);
        curl_close($chState);
        
        $stateData = json_decode($stateRes, true);
        if (isset($stateData['instance']['state']) && $stateData['instance']['state'] === 'open') {
            $localConnected = true;
        }
    }
    
    $localSuccess = false;
    
    if ($localConnected) {
        $url = $evolutionUrl . '/message/sendText/' . $instancia;
        
        $payload_v2 = json_encode([
            'number' => $numero,
            'text' => $mensagem,
            'delay' => 1200,
            'linkPreview' => true
        ]);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_v2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        $debugLog = "URL: $url\nPayload v2: $payload_v2\nResponse Code: $httpCode\ncURL Error: $curlError\nResponse Body: $response\n";
        
        if ($httpCode == 400 || (strpos(strtolower($response), 'bad request') !== false) || (strpos(strtolower($response), 'inválida') !== false) || (strpos(strtolower($response), 'invalid') !== false)) {
            $payload_v1 = json_encode([
                'number' => $numero,
                'options' => ['delay' => 1200, 'presence' => 'composing'],
                'textMessage' => ['text' => $mensagem]
            ]);

            $ch2 = curl_init($url);
            curl_setopt($ch2, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey, 'Content-Type: application/json']);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_POST, true);
            curl_setopt($ch2, CURLOPT_POSTFIELDS, $payload_v1);
            curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch2);
            $httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch2);
            curl_close($ch2);
            
            $debugLog .= "Fallback Response Code: $httpCode\nFallback cURL Error: $curlError\nFallback Response Body: $response\n";
        }
        
        file_put_contents(__DIR__ . '/whatsapp_debug.log', $debugLog, FILE_APPEND);
        
        if ($response !== false && $httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode($response, true);
            if (isset($decoded['response']['message'][0]['exists']) && empty($decoded['response']['message'][0]['exists'])) {
                file_put_contents(__DIR__ . '/whatsapp_debug.log', "Erro Local: Número sem WhatsApp.\n========================\n", FILE_APPEND);
                return ['error' => "O número {$numero} não possui uma conta no WhatsApp ativa!"];
            }
            $localSuccess = true;
            file_put_contents(__DIR__ . '/whatsapp_debug.log', "Sucesso Local.\n========================\n", FILE_APPEND);
            return $decoded;
        }
    }
    
    // Prioridade 2 (Fallback Hub)
    if (!$localSuccess && !empty($hubToken)) {
        file_put_contents(__DIR__ . '/whatsapp_debug.log', "Iniciando fallback via HUB Híbrido...\n", FILE_APPEND);
        
        $urlHub = 'https://admhibrido.cadtech.com.br/api/hub_receiver.php';
        $payloadHub = json_encode([
            'number' => $numero,
            'text' => $mensagem
        ]);
        
        $chHub = curl_init($urlHub);
        curl_setopt($chHub, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $hubToken
        ]);
        curl_setopt($chHub, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chHub, CURLOPT_POST, true);
        curl_setopt($chHub, CURLOPT_POSTFIELDS, $payloadHub);
        curl_setopt($chHub, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chHub, CURLOPT_TIMEOUT, 35);
        curl_setopt($chHub, CURLOPT_CONNECTTIMEOUT, 10);
        
        $responseHub = curl_exec($chHub);
        $httpCodeHub = curl_getinfo($chHub, CURLINFO_HTTP_CODE);
        $curlErrorHub = curl_error($chHub);
        curl_close($chHub);
        
        file_put_contents(__DIR__ . '/whatsapp_debug.log', "Hub Response Code: $httpCodeHub | Body: $responseHub | cURL Error: $curlErrorHub\n========================\n", FILE_APPEND);
        
        if ($responseHub === false) {
            return ['error' => 'Falha na conexão com Hub Híbrido: ' . $curlErrorHub];
        }
        
        $decodedHub = json_decode($responseHub, true);
        if ($httpCodeHub >= 400 || (isset($decodedHub['error']) && !empty($decodedHub['error']))) {
            return ['error' => $decodedHub['error'] ?? ("Erro no Hub Híbrido (HTTP $httpCodeHub): " . $responseHub)];
        }
        
        return $decodedHub ?: ['success' => true, 'hub' => true];
    }
    
    file_put_contents(__DIR__ . '/whatsapp_debug.log', "Falha total: Local offline e sem Token de Hub.\n========================\n", FILE_APPEND);
    return ['error' => 'WhatsApp local offline e nenhum Hub Híbrido configurado.'];
}
?>
