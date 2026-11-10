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
        $this->instance = trim($instance) ?: 'player99';
    }

    public function request(string $endpoint, string $method = 'GET', ?array $data = null, int $timeout = 15): array
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $ch = curl_init();

        $headers = [
            'apikey: ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error, 'http_code' => $httpCode];
        }

        $decoded = json_decode((string)$response, true);
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded !== null ? $decoded : $response
        ];
    }

    /**
     * Normaliza o destinatário (suporta @s.whatsapp.net, :device, @lid e números puros)
     */
    public static function normalizeRecipient(string $rawJidOrNumber, string $altJid = ''): string
    {
        $candidate = trim($rawJidOrNumber);
        $alt = trim($altJid);

        if (str_ends_with($candidate, '@lid') && !empty($alt) && str_contains($alt, '@s.whatsapp.net')) {
            $candidate = $alt;
        }

        if (str_ends_with($candidate, '@lid')) {
            return $candidate;
        }

        if (str_contains($candidate, '@')) {
            $parts = explode('@', $candidate);
            $userPart = $parts[0];
            if (str_contains($userPart, ':')) {
                $userPart = explode(':', $userPart)[0];
            }
            $digits = preg_replace('/\D/', '', $userPart);
            return $digits ?: $candidate;
        }

        if (str_contains($candidate, ':')) {
            $candidate = explode(':', $candidate)[0];
        }

        return preg_replace('/\D/', '', $candidate) ?: $candidate;
    }

    public function getConnectionState(): array
    {
        return $this->request("instance/connectionState/{$this->instance}");
    }

    /**
     * Configura / Atualiza o Webhook em uma instância já existente (compatível com Evolution v1 e v2)
     */
    public function setWebhook(string $webhookUrl): array
    {
        if (empty($webhookUrl)) {
            return ['success' => false, 'error' => 'Webhook URL vazia'];
        }

        $v2Payload = [
            'webhook' => [
                'enabled' => true,
                'url' => $webhookUrl,
                'byEvents' => false,
                'base64' => false,
                'events' => [
                    'MESSAGES_UPSERT'
                ]
            ]
        ];

        $res = $this->request("webhook/set/{$this->instance}", 'POST', $v2Payload, 8);
        if ($res['success']) {
            return $res;
        }

        $v1Payload = [
            'enabled' => true,
            'url' => $webhookUrl,
            'webhook_by_events' => false,
            'webhook_base64' => false,
            'events' => [
                'MESSAGES_UPSERT'
            ]
        ];

        return $this->request("webhook/set/{$this->instance}", 'POST', $v1Payload, 8);
    }

    public function findWebhook(): array
    {
        return $this->request("webhook/find/{$this->instance}", 'GET', null, 8);
    }

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
                'base64' => false,
                'events' => ['MESSAGES_UPSERT']
            ];
        }

        $res = $this->request('instance/create', 'POST', $body, 10);

        if (!empty($webhookUrl)) {
            $this->setWebhook($webhookUrl);
        }

        return $res;
    }

    public function connect(): array
    {
        return $this->request("instance/connect/{$this->instance}");
    }

    public function logout(): array
    {
        return $this->request("instance/logout/{$this->instance}", 'DELETE');
    }

    public function sendPresence(string $number, string $presence = 'composing'): array
    {
        $target = self::normalizeRecipient($number);
        $res = $this->request("chat/sendPresence/{$this->instance}", 'POST', [
            'number' => $target,
            'presence' => $presence,
            'delay' => 1200
        ], 6);

        if (!$res['success']) {
            $res = $this->request("chat/sendPresence/{$this->instance}", 'POST', [
                'number' => $target,
                'options' => [
                    'delay' => 1200,
                    'presence' => $presence
                ]
            ], 6);
        }
        return $res;
    }

    public function sendText(string $number, string $text, string $altJid = '', int $timeout = 15): array
    {
        $target = self::normalizeRecipient($number, $altJid);

        // 1. Tenta formato Evolution API v2
        $res = $this->request("message/sendText/{$this->instance}", 'POST', [
            'number' => $target,
            'text' => $text,
            'delay' => 1000,
            'linkPreview' => false
        ], $timeout);

        if ($res['success']) {
            return $res;
        }

        // Em falha de conexão (cURL timeout/unreachable), aborta imediatamente sem multiplicar atrasos
        if (!empty($res['error']) && (int)($res['http_code'] ?? 0) === 0) {
            return $res;
        }

        // 2. Fallback para formato Evolution API v1 (com bloco textMessage e options)
        $resV1 = $this->request("message/sendText/{$this->instance}", 'POST', [
            'number' => $target,
            'options' => [
                'delay' => 1000,
                'presence' => 'composing',
                'linkPreview' => false
            ],
            'textMessage' => [
                'text' => $text
            ]
        ], $timeout);

        if ($resV1['success']) {
            return $resV1;
        }

        // 3. Se falhou e o número não tinha @s.whatsapp.net, tenta com JID explícito
        if (!str_contains($target, '@')) {
            return $this->request("message/sendText/{$this->instance}", 'POST', [
                'number' => $target . '@s.whatsapp.net',
                'text' => $text,
                'textMessage' => ['text' => $text]
            ], $timeout);
        }

        return $res;
    }

    public function fetchRecentMessages(int $limit = 20): array
    {
        $res = $this->request("chat/findMessages/{$this->instance}", 'POST', [
            'where' => (object)[],
            'limit' => $limit
        ], 8);

        if (!$res['success'] || empty($res['data'])) {
            return [];
        }

        $data = $res['data'];
        if (isset($data['messages']['records']) && is_array($data['messages']['records'])) {
            return $data['messages']['records'];
        }
        if (isset($data['messages']) && is_array($data['messages'])) {
            return $data['messages'];
        }
        if (isset($data['records']) && is_array($data['records'])) {
            return $data['records'];
        }
        if (is_array($data) && isset($data[0])) {
            return $data;
        }

        return [];
    }
}
