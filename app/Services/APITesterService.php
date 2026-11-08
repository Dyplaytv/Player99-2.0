<?php
namespace App\Services;

class APITesterService
{
    /**
     * Testa comunicação com a Evolution API
     */
    public static function testEvolution(string $url, string $apiKey, string $instance = 'player99'): array
    {
        if (empty($url) || empty($apiKey)) {
            return ['ok' => false, 'msg' => 'URL ou API Key não preenchidas'];
        }

        $cleanUrl = rtrim($url, '/') . '/instance/connectionState/' . trim($instance);
        $ch = curl_init($cleanUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['apikey: ' . $apiKey]);

        $start = microtime(true);
        $res = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            return ['ok' => false, 'msg' => 'Falha de rede/timeout: ' . $err];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($res, true);
            $state = $data['instance']['state'] ?? $data['state'] ?? 'conectado';
            return ['ok' => true, 'msg' => "Online ({$state}) - {$latency}ms"];
        }

        return ['ok' => false, 'msg' => "HTTP {$httpCode} - Resposta inválida"];
    }

    /**
     * Testa Google Gemini API
     */
    public static function testGemini(string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['ok' => false, 'msg' => 'API Key vazia'];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $start = microtime(true);
        $res = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['ok' => true, 'msg' => "Autenticado com sucesso - {$latency}ms"];
        }

        $data = json_decode($res, true);
        $errMsg = $data['error']['message'] ?? "Erro HTTP {$httpCode}";
        return ['ok' => false, 'msg' => $errMsg];
    }

    /**
     * Testa OpenAI ChatGPT API
     */
    public static function testOpenAI(string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['ok' => false, 'msg' => 'API Key vazia'];
        }

        $ch = curl_init('https://api.openai.com/v1/models');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey]);

        $start = microtime(true);
        $res = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['ok' => true, 'msg' => "Autenticado com sucesso - {$latency}ms"];
        }

        $data = json_decode($res, true);
        $errMsg = $data['error']['message'] ?? "Erro HTTP {$httpCode}";
        return ['ok' => false, 'msg' => $errMsg];
    }

    /**
     * Testa Mercado Pago API
     */
    public static function testMercadoPago(string $accessToken): array
    {
        if (empty($accessToken)) {
            return ['ok' => false, 'msg' => 'Access Token vazio'];
        }

        $ch = curl_init('https://api.mercadopago.com/users/me');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);

        $start = microtime(true);
        $res = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($res, true);
            $user = $data['nickname'] ?? $data['email'] ?? 'Conta Válida';
            return ['ok' => true, 'msg' => "Conectado ({$user}) - {$latency}ms"];
        }

        return ['ok' => false, 'msg' => "Token inválido (HTTP {$httpCode})"];
    }

    /**
     * Testa OpenWeather API
     */
    public static function testWeather(string $apiKey): array
    {
        if (empty($apiKey)) {
            return ['ok' => false, 'msg' => 'API Key vazia'];
        }

        $url = "https://api.openweathermap.org/data/2.5/weather?q=Sao+Paulo&appid=" . urlencode($apiKey);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $start = microtime(true);
        $res = curl_exec($ch);
        $latency = round((microtime(true) - $start) * 1000);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return ['ok' => true, 'msg' => "Clima ativo - {$latency}ms"];
        }

        return ['ok' => false, 'msg' => "Chave inválida (HTTP {$httpCode})"];
    }
}
