<?php
namespace App\Services;

class AIService
{
    /**
     * Gera resposta com OpenAI ChatGPT
     */
    public static function generateOpenAI(string $apiKey, string $systemPrompt, array $history, string $userMessage): ?string
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        foreach ($history as $h) {
            $messages[] = [
                'role' => $h['role'] === 'user' ? 'user' : 'assistant',
                'content' => $h['message']
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

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
            'max_tokens' => 400
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) return null;
        $data = json_decode($res, true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    /**
     * Gera resposta com Google Gemini
     */
    public static function generateGemini(string $apiKey, string $systemPrompt, array $history, string $userMessage): ?string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . urlencode($apiKey);

        $contents = [];
        foreach ($history as $h) {
            $contents[] = [
                'role' => $h['role'] === 'user' ? 'user' : 'model',
                'parts' => [['text' => $h['message']]]
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $userMessage]]
        ];

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

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) return null;
        $data = json_decode($res, true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    /**
     * Orquestrador de IA de acordo com as configurações salvas
     */
    public static function reply(array $settings, array $history, string $userMessage): ?string
    {
        $provider = $settings['ai_provider'] ?? 'gemini';
        $systemPrompt = $settings['ai_system_prompt'] ?? "Você é a assistente virtual oficial da Player99, especializada em mídia indoor e DOOH.";

        if ($provider === 'openai' && !empty($settings['openai_api_key'])) {
            return self::generateOpenAI($settings['openai_api_key'], $systemPrompt, $history, $userMessage);
        }

        if (!empty($settings['gemini_api_key'])) {
            return self::generateGemini($settings['gemini_api_key'], $systemPrompt, $history, $userMessage);
        }

        if (!empty($settings['openai_api_key'])) {
            return self::generateOpenAI($settings['openai_api_key'], $systemPrompt, $history, $userMessage);
        }

        return null;
    }
}
