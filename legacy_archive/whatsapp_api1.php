<?php
function enviarMensagemWasender($numero, $mensagem) {
    $apiKey = 'SUA-API-WASENDER';
    $url = 'https://wasenderapi.com/api/send-message';

    $ch = curl_init($url);
    $payload = json_encode([
        'to' => $numero,
        'text' => $mensagem
    ]);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return ['error' => $error];

    return json_decode($response, true);
}
?>
