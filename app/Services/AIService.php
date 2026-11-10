<?php
namespace App\Services;

class AIService
{
    public static string $lastError = '';

    public static function generateOpenAI(string $apiKey, string $systemPrompt, array $history, string $userMessage): ?string
    {
        self::$lastError = '';
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            self::$lastError = 'Chave OpenAI vazia.';
            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        $lastMsgText = null;
        foreach ($history as $h) {
            $role = ($h['role'] ?? '') === 'user' ? 'user' : 'assistant';
            $text = (string)($h['message'] ?? '');
            if ($text !== '') {
                $messages[] = [
                    'role' => $role,
                    'content' => $text
                ];
                if ($role === 'user') {
                    $lastMsgText = $text;
                }
            }
        }

        if ($lastMsgText !== $userMessage && trim($userMessage) !== '') {
            $messages[] = ['role' => 'user', 'content' => $userMessage];
        }

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 450
        ], JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr || !$res) {
            self::$lastError = 'Erro cURL OpenAI: ' . ($curlErr ?: 'Resposta vazia');
            return null;
        }

        $data = json_decode((string)$res, true);
        if ($httpCode >= 400 || isset($data['error'])) {
            $errCode = $data['error']['code'] ?? '';
            $errMsg = $data['error']['message'] ?? ("HTTP " . $httpCode);
            self::$lastError = "OpenAI ({$errCode}): {$errMsg}";
            return null;
        }

        $reply = $data['choices'][0]['message']['content'] ?? null;
        return is_string($reply) ? trim($reply) : null;
    }

    public static function generateGemini(string $apiKey, string $systemPrompt, array $history, string $userMessage): ?string
    {
        self::$lastError = '';
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            self::$lastError = 'Chave Gemini vazia.';
            return null;
        }

        $contents = [];
        $lastMsgText = null;
        foreach ($history as $h) {
            $role = ($h['role'] ?? '') === 'user' ? 'user' : 'model';
            $text = (string)($h['message'] ?? '');
            if ($text !== '') {
                $contents[] = [
                    'role' => $role,
                    'parts' => [['text' => $text]]
                ];
                if ($role === 'user') {
                    $lastMsgText = $text;
                }
            }
        }
        if ($lastMsgText !== $userMessage && trim($userMessage) !== '') {
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]]
            ];
        }

        $body = [
            'system_instruction' => [
                'parts' => [['text' => $systemPrompt]]
            ],
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 450
            ]
        ];

        $models = ['gemini-2.0-flash', 'gemini-1.5-flash'];
        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $res = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($res && $httpCode === 200) {
                $data = json_decode((string)$res, true);
                $reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if (is_string($reply) && trim($reply) !== '') {
                    self::$lastError = '';
                    return trim($reply);
                }
            } else {
                $data = json_decode((string)$res, true);
                self::$lastError = 'Gemini: ' . ($data['error']['message'] ?? ("HTTP " . $httpCode));
            }
        }

        return null;
    }

    public static function reply(array $settings, array $history, string $userMessage): ?string
    {
        $provider = trim((string)($settings['ai_provider'] ?? 'openai'));
        $systemPrompt = trim((string)($settings['ai_system_prompt'] ?? ''));
        if ($systemPrompt === '') {
            $systemPrompt = "Você é a assistente virtual oficial da Player99, especializada em mídia indoor e DOOH em telas de carros de aplicativo.";
        }

        $openAiKey = trim((string)($settings['openai_api_key'] ?? ''));
        $geminiKey = trim((string)($settings['gemini_api_key'] ?? ''));

        if ($provider === 'openai' && $openAiKey !== '') {
            $reply = self::generateOpenAI($openAiKey, $systemPrompt, $history, $userMessage);
            if ($reply !== null) {
                return $reply;
            }
            if ($geminiKey !== '') {
                $openAiErr = self::$lastError;
                $fallback = self::generateGemini($geminiKey, $systemPrompt, $history, $userMessage);
                if ($fallback !== null) {
                    return $fallback;
                }
                self::$lastError = $openAiErr . ' | ' . self::$lastError;
            }
            return null;
        }

        if ($provider === 'gemini' && $geminiKey !== '') {
            $reply = self::generateGemini($geminiKey, $systemPrompt, $history, $userMessage);
            if ($reply !== null) {
                return $reply;
            }
            if ($openAiKey !== '') {
                return self::generateOpenAI($openAiKey, $systemPrompt, $history, $userMessage);
            }
            return null;
        }

        if ($openAiKey !== '') {
            return self::generateOpenAI($openAiKey, $systemPrompt, $history, $userMessage);
        }
        if ($geminiKey !== '') {
            return self::generateGemini($geminiKey, $systemPrompt, $history, $userMessage);
        }

        self::$lastError = 'Nenhuma API Key (OpenAI ou Gemini) está salva no banco de dados.';
        return null;
    }
}
