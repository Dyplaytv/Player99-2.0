<?php
namespace App\Services;

class EvolutionService
{
    private string $baseUrl;
    private string $apiKey;
    private string $instance;

    public function __construct(string $baseUrl, string $apiKey, string $instance)
    {
        $baseUrl = trim($baseUrl);
        // Remove barras extras no início acidentais (ex: //127.0.0.1)
        $baseUrl = preg_replace('#^/+#', '', $baseUrl);
        if (!empty($baseUrl) && !preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'http://' . $baseUrl;
        }
        // Garante exatamente duas barras após o protocolo (corrige http://// -> http://)
        $baseUrl = preg_replace('#^(https?:)/+#i', '$1//', $baseUrl);

        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = trim($apiKey);
        $this->instance = trim($instance);
    }

    private function request(string $endpoint, string $method = 'GET', ?array $data = null): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $ch = curl_init();

        $headers = [
            'apikey: ' . $this->apiKey,
            'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error, 'http_code' => $httpCode];
        }

        $decoded = json_decode($response, true);
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded ?: $response
        ];
    }

    /**
     * Verifica o status da conexão da instância
     */
    public function getConnectionState(): array
    {
        return $this->request("instance/connectionState/{$this->instance}");
    }

    /**
     * Cria a instância se não existir
     */
    public function createInstance(string $webhookUrl = ''): array
    {
        $body = [
            'instanceName' => $this->instance,
            'token' => '',
            'qrcode' => true,
            'integration' => 'WHATSAPP-BAILEYS'
        ];

        if (!empty($webhookUrl)) {
            $body['webhook'] = [
                'enabled' => true,
                'url' => $webhookUrl,
                'byEvents' => false,
                'events' => ['MESSAGES_UPSERT']
            ];
        }

        return $this->request('instance/create', 'POST', $body);
    }

    /**
     * Conecta a instância e retorna o QR Code (base64)
     */
    public function connect(): array
    {
        return $this->request("instance/connect/{$this->instance}");
    }

    /**
     * Desconecta (Logout)
     */
    public function logout(): array
    {
        return $this->request("instance/logout/{$this->instance}", 'DELETE');
    }

    /**
     * Simula presença digitando (composing)
     */
    public function sendPresence(string $number, string $presence = 'composing'): array
    {
        $cleanNumber = preg_replace('/\D/', '', $number);
        return $this->request("chat/sendPresence/{$this->instance}", 'POST', [
            'number' => $cleanNumber,
            'presence' => $presence,
            'delay' => 1200
        ]);
    }

    /**
     * Envia mensagem de texto simples
     */
    public function sendText(string $number, string $text): array
    {
        $cleanNumber = preg_replace('/\D/', '', $number);
        return $this->request("message/sendText/{$this->instance}", 'POST', [
            'number' => $cleanNumber,
            'text' => $text,
            'options' => [
                'delay' => 1200,
                'presence' => 'composing'
            ]
        ]);
    }
}
