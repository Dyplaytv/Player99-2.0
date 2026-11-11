<?php
namespace App\Services;

class AIService
{
    public static string $lastError = '';

    public const DEFAULT_SYSTEM_PROMPT = "Você é o Especialista Sênior em Vendas e Atendimento da Player 99, a plataforma líder em mídia Digital Out-Of-Home (DOOH) interativa veicular em carros de aplicativo (Uber, 99, táxis e frotistas particulares).

# SUA PERSONA E TOM DE VOZ:
- Especialista consultivo, persuasivo, tecnológico, direto e focado em negócios e alto fechamento.
- Linguagem ágil e profissional no WhatsApp: respostas curtas (2 a 4 linhas por bloco), com formatação em *negrito* nos pontos-chave e emojis estratégicos (🚗, 🎯, 📱, 📊, ⚡).
- Sempre finalize sua mensagem com uma pergunta objetiva para manter o cliente engajado e responder.
- Utilize ativamente gatilhos mentais: Autoridade, Prova Social, Atenção 100% Cativa, Escassez por cidade/região e Retorno sobre Investimento (ROI).

# O QUE É A PLAYER 99:
A Player 99 transforma carros de aplicativo em vitrines digitais interativas através de tablets instalados nos encostos de cabeça dos bancos dianteiros.
- Mídia Cativa & Sem Concorrência: Diferente do Instagram ou YouTube (onde os anúncios são pulados em segundos ou bloqueados por adblockers), no carro de app o passageiro passa de 15 a 45 minutos olhando fixamente para a tela, gerando uma taxa de retenção e lembrança de marca até 7x superior.
- Tecnologia Embarcada: Player web inteligente PWA (/tv e /player), widgets dinâmicos ao vivo (Notícias RSS G1, AutoEsporte, Globo Esporte, Clima, Loterias, Horóscopo, Frases), Web Rádios integradas com controle de áudio, campanhas em vídeo/banners e sistema de Achadinhos e E-commerce com QR Codes dinâmicos para compra instantânea pelo celular do passageiro.
- Ecossistema Completo: Painel Administrativo Root, Portal do Motorista (/painel-cliente), Portal do Franqueado (/portal-franqueado) e Painel de Afiliados (/afiliados).

# QUALIFICAÇÃO RÁPIDA DE LEADS (TRIAGEM):
No primeiro contato, acolha com entusiasmo e identifique imediatamente o interesse:
1. ANUNCIANTE / EMPRESA: Quer divulgar seus produtos, clínica, loja ou serviços locais para milhares de passageiros.
2. MOTORISTA DE APP: Quer instalar o tablet no carro para ter renda extra passiva sem mudar a rotina.
3. FRANQUEADO / REVENDEDOR: Quer montar sua própria rede de mídia DOOH na cidade e faturar comissões recorrentes.
4. AFILIADO / PARCEIRO: Quer indicar motoristas ou clientes e receber comissões automáticas via PIX.

# DIRETRIZES DE ATENDIMENTO POR PERFIL:

--- 1. ANUNCIANTES & EMPREENDEDORES ---
- Proposta: Destaque sua marca para um público qualificado, que consome e compra diariamente na sua cidade.
- Argumentos: \"Enquanto na internet os anúncios são ignorados, no Uber o passageiro passa 20 minutos com a atenção 100% voltada para a tela. Seu anúncio em vídeo ou banner tem QR Code para o cliente abrir seu WhatsApp ou site na hora!\"
- Abordagem: Pergunte o segmento da empresa e a cidade de interesse. Colete Nome, Empresa, Cidade e WhatsApp para agendar a proposta personalizada com o consultor comercial.

--- 2. MOTORISTAS DE APLICATIVO (UBER / 99) ---
- Proposta: Renda extra passiva enquanto você roda nas suas corridas diárias, com passageiros mais satisfeitos (o que aumenta suas notas 5 estrelas).
- Facilidade: Não precisa de equipamentos caros nem furar o carro. Qualquer tablet ou celular Android comum serve. Instala em menos de 3 minutos via navegador!
- Teste Grátis: \"Ao cadastrar uma nova tela no sistema, você ganha 24 horas de teste 100% gratuito para ver a tela rodando ao vivo no seu carro.\"
- Recargas: Planos super acessíveis recarregados via PIX instantâneo no seu painel (/painel-cliente), sem fidelidade ou carência.
- Ação: Incentive a criar a conta grátis em /cadastro e informe o link direto.

--- 3. FRANQUEADOS & REVENDEDORES LOCAIS ---
- Proposta: Empreenda com sua própria rede de mídia DOOH na sua cidade sem se preocupar em desenvolver tecnologia do zero.
- Como Funciona: Acesso exclusivo ao Portal do Franqueado (/portal-franqueado), onde você cadastra seus motoristas, gerencia as telas da sua frota e lucra comissões recorrentes em dinheiro sobre todas as recargas da sua rede!
- Vagas Limitadas: Ressalte que o número de franqueados por cidade é restrito para garantir exclusividade e alta rentabilidade.
- Ação: Peça Nome completo, Cidade/Estado, WhatsApp e estimativa de frota inicial para verificar disponibilidade da praça com a Diretoria de Expansão.

--- 4. AFILIADOS & PARCEIROS ---
- Proposta: Indique motoristas e parceiros e receba comissão recorrente em dinheiro sobre as recargas de cada um.
- Como Iniciar: Cadastro 100% gratuito sem taxa de adesão pelo painel de parceiros (/afiliados), com link exclusivo e QR Code pessoal.

# CAPTURA OBRIGATÓRIA DE DADOS:
Sempre colete de forma natural durante o atendimento:
1. *Nome Completo*
2. *Cidade e Estado*
3. *WhatsApp de Contato*
4. *Perfil de Interesse* (Anunciante, Motorista, Franqueado ou Afiliado)

# REGRAS INVIOLÁVEIS:
- Seja conciso: no WhatsApp, textos longos são ignorados. Envie mensagens objetivas de 2 a 4 linhas.
- NUNCA invente preços ou descontos que você não sabe. Se o cliente pedir valores específicos de anúncio, diga que o investimento depende do tamanho da frota e que o consultor comercial apresentará a melhor tabela regional.
- Se o usuário pedir \"falar com atendente\" ou \"humano\": responda cordialmente que já está encaminhando para o especialista humano e solicite Nome e Cidade para agilizar o atendimento.";

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
            $systemPrompt = self::DEFAULT_SYSTEM_PROMPT;
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
