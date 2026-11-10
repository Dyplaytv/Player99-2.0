<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Services\EvolutionService;
use App\Services\AIService;
use App\Services\WhatsAppWelcomeService;

class SettingsController extends Controller
{
    public static function buildWebhookUrl(): string
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'teste.player99.com.br';
        return $scheme . '://' . $host . '/api/evolution/webhook';
    }

    private function normalizeEvoUrl(string $evoUrl): string
    {
        $evoUrl = trim($evoUrl);
        $evoUrl = preg_replace('#^/+#', '', $evoUrl);
        if (!empty($evoUrl) && !preg_match('#^https?://#i', $evoUrl)) {
            $evoUrl = 'http://' . $evoUrl;
        }
        $evoUrl = preg_replace('#^(https?:)/+#i', '$1//', $evoUrl);
        return rtrim($evoUrl, '/');
    }

    private function ensureSettingsTable(\PDO $pdo): void
    {
        $cols = $pdo->query("PRAGMA table_info(system_settings)")->fetchAll();
        $colNames = array_column($cols, 'name');

        if (empty($colNames) || !in_array('setting_key', $colNames)) {
            $pdo->exec("DROP TABLE IF EXISTS system_settings");
            $pdo->exec("CREATE TABLE system_settings (
                setting_key VARCHAR(100) PRIMARY KEY,
                setting_value TEXT,
                description VARCHAR(255),
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
        }
    }

    private function upsertSetting(\PDO $pdo, string $key, string $value): void
    {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, datetime('now'))
                               ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = excluded.updated_at");
        $stmt->execute([':k' => $key, ':v' => $value]);
    }

    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            http_response_code(403);
            die("Acesso Restrito: Apenas usuários Root podem acessar as configurações gerais do sistema.");
        }

        $pdo = Database::users();
        $this->ensureSettingsTable($pdo);

        $msg = '';

        if ($request->isPost()) {
            $evoUrl = $this->normalizeEvoUrl((string)$request->input('evolution_api_url', ''));

            $settingsToSave = [
                'google_login_enabled' => $request->input('google_login_enabled') === '1' ? '1' : '0',
                'google_client_id' => trim((string)$request->input('google_client_id', '')),
                'google_client_secret' => trim((string)$request->input('google_client_secret', '')),
                'evolution_api_url' => $evoUrl,
                'evolution_api_key' => trim((string)$request->input('evolution_api_key', '')),
                'evolution_instance_name' => trim((string)$request->input('evolution_instance_name', 'player99')) ?: 'player99',
                'welcome_whatsapp_enabled' => $request->input('welcome_whatsapp_enabled') === '1' ? '1' : '0',
                'welcome_whatsapp_message' => trim((string)$request->input('welcome_whatsapp_message', '')),
                'ai_agent_enabled' => $request->input('ai_agent_enabled') === '1' ? '1' : '0',
                'ai_provider' => trim((string)$request->input('ai_provider', 'openai')),
                'ai_human_delay' => (string)(int)$request->input('ai_human_delay', 3),
                'ai_system_prompt' => trim((string)$request->input('ai_system_prompt', '')),
                'gemini_api_key' => trim((string)$request->input('gemini_api_key', '')),
                'openai_api_key' => trim((string)$request->input('openai_api_key', '')),
                'anthropic_api_key' => trim((string)$request->input('anthropic_api_key', '')),
                'tmdb_api_key' => trim((string)$request->input('tmdb_api_key', '')),
                'openweather_api_key' => trim((string)$request->input('openweather_api_key', '')),
                'mercadopago_access_token' => trim((string)$request->input('mercadopago_access_token', ''))
            ];

            foreach ($settingsToSave as $k => $v) {
                $this->upsertSetting($pdo, $k, (string)$v);
            }

            $webhookSynced = false;
            if (!empty($settingsToSave['evolution_api_url']) && !empty($settingsToSave['evolution_api_key'])) {
                try {
                    $evo = new EvolutionService(
                        $settingsToSave['evolution_api_url'],
                        $settingsToSave['evolution_api_key'],
                        $settingsToSave['evolution_instance_name']
                    );
                    $whRes = $evo->setWebhook(self::buildWebhookUrl());
                    $webhookSynced = !empty($whRes['success']);
                } catch (\Throwable $e) {}
            }

            if ($request->input('ajax') === '1') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'ok' => true,
                    'webhook_synced' => $webhookSynced,
                    'ai_agent_enabled' => $settingsToSave['ai_agent_enabled'],
                    'ai_provider' => $settingsToSave['ai_provider'],
                    'msg' => 'Configurações salvas e Agente I.A. sincronizado com sucesso!'
                ]);
                return;
            }

            $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Configurações, Webhook Evolution e Treinamento do Agente de I.A. salvos e sincronizados com sucesso!</div>";
        }

        $stmtAll = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $savedSettings = [];
        while ($row = $stmtAll->fetch()) {
            $savedSettings[$row['setting_key']] = $row['setting_value'];
        }

        if (!isset($savedSettings['welcome_whatsapp_enabled'])) {
            $savedSettings['welcome_whatsapp_enabled'] = '1';
        }
        if (empty($savedSettings['welcome_whatsapp_message'])) {
            $savedSettings['welcome_whatsapp_message'] = WhatsAppWelcomeService::DEFAULT_TEMPLATE;
        }

        if (empty($savedSettings['ai_system_prompt'])) {
            $savedSettings['ai_system_prompt'] = "Você é a assistente virtual e consultora oficial da Player99 (plataforma inovadora de DOOH - Digital Out-Of-Home, mídia indoor em telas de carros de aplicativo como Uber e 99).\n\n"
                . "SEU OBJETIVO:\n"
                . "1. Fazer o primeiro atendimento de forma calorosa, educada, empática e humana (como uma atendente real no WhatsApp).\n"
                . "2. Identificar rapidamente o perfil de quem está falando:\n"
                . "   - MOTORISTA DE APP: Quer saber como colocar a tela/tablet no carro para ter renda extra passiva.\n"
                . "   - ANUNCIANTE / EMPRESA: Quer divulgar seus produtos/serviços para milhares de passageiros que andam de aplicativo na cidade todos os dias.\n"
                . "   - REVENDA / AGÊNCIA: Quer comprar telas para gerenciar a sua própria rede de mídia.\n"
                . "3. Explique os benefícios resumidamente de acordo com o interesse.\n"
                . "4. Peça o Nome, a Cidade e o WhatsApp da pessoa para direcionar ao especialista.\n"
                . "5. Se a pessoa pedir 'falar com humano' ou negociar valores específicos, diga gentilmente que vai transferir para o especialista responsável agora mesmo.\n\n"
                . "DIRETRIZES DE ESTILO:\n"
                . "- Seja concisa: no WhatsApp as pessoas preferem mensagens diretas de 2 a 4 linhas, evite blocos gigantes de texto.\n"
                . "- Use emojis de forma moderada e profissional (🚗, 📱, 🎯, ✨).\n"
                . "- NUNCA invente preços que você não sabe; ofereça transferir para a equipe comercial.";
        }

        $this->render('admin/settings', [
            'pageTitle' => 'Configurações Root - Player99',
            'pageHeading' => 'Configurações Gerais, WhatsApp & Agente I.A. (Root)',
            'currentRoute' => '/admin/configuracoes',
            'currentUser' => Auth::user(),
            'settings' => $savedSettings,
            'webhookUrl' => self::buildWebhookUrl(),
            'msg' => $msg
        ], 'layouts/master');
    }

    public function evolutionConnect(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $pdo = Database::users();
        $this->ensureSettingsTable($pdo);
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $rawBody = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $url = $request->input('url') ?: ($rawBody['url'] ?? ($settings['evolution_api_url'] ?? ''));
        $key = $request->input('key') ?: ($rawBody['key'] ?? ($settings['evolution_api_key'] ?? ''));
        $inst = $request->input('instance') ?: ($request->input('inst') ?: ($rawBody['instance'] ?? ($settings['evolution_instance_name'] ?? 'player99')));

        $url = $this->normalizeEvoUrl((string)$url);
        $key = trim((string)$key);
        $inst = trim((string)$inst) ?: 'player99';

        if (empty($url) || empty($key)) {
            echo json_encode(['success' => false, 'error' => 'Por favor, preencha a URL da Evolution API e a API Key antes de conectar.']);
            return;
        }

        $this->upsertSetting($pdo, 'evolution_api_url', $url);
        $this->upsertSetting($pdo, 'evolution_api_key', $key);
        $this->upsertSetting($pdo, 'evolution_instance_name', $inst);

        $evo = new EvolutionService($url, $key, $inst);

        $webhookUrl = self::buildWebhookUrl();
        $evo->createInstance($webhookUrl);
        $evo->setWebhook($webhookUrl);

        $res = $evo->connect();

        echo json_encode($res);
    }

    public function evolutionStatus(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $pdo = Database::users();
        $this->ensureSettingsTable($pdo);
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $rawBody = json_decode((string)file_get_contents('php://input'), true) ?? [];
        $url = $request->input('url') ?: ($rawBody['url'] ?? ($settings['evolution_api_url'] ?? ''));
        $key = $request->input('key') ?: ($rawBody['key'] ?? ($settings['evolution_api_key'] ?? ''));
        $inst = $request->input('instance') ?: ($request->input('inst') ?: ($rawBody['instance'] ?? ($settings['evolution_instance_name'] ?? 'player99')));

        $url = $this->normalizeEvoUrl((string)$url);
        $key = trim((string)$key);
        $inst = trim((string)$inst) ?: 'player99';

        if (empty($url) || empty($key)) {
            echo json_encode(['success' => false, 'error' => 'Evolution não configurada']);
            return;
        }

        $this->upsertSetting($pdo, 'evolution_api_url', $url);
        $this->upsertSetting($pdo, 'evolution_api_key', $key);
        $this->upsertSetting($pdo, 'evolution_instance_name', $inst);

        $evo = new EvolutionService($url, $key, $inst);
        $res = $evo->getConnectionState();

        $state = $res['data']['instance']['state'] ?? $res['data']['state'] ?? '';
        if ($state === 'open') {
            $evo->setWebhook(self::buildWebhookUrl());
        }

        echo json_encode($res);
    }

    public function evolutionLogout(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $pdo = Database::users();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $url = $settings['evolution_api_url'] ?? '';
        $key = $settings['evolution_api_key'] ?? '';
        $inst = $settings['evolution_instance_name'] ?? 'player99';

        $evo = new EvolutionService($url, $key, $inst);
        $res = $evo->logout();

        echo json_encode($res);
    }

    public function testApi(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['ok' => false, 'msg' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        $api = $request->input('api', '');
        $val = trim((string)$request->input('val', ''));

        $pdo = Database::users();
        $this->ensureSettingsTable($pdo);
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $saved = [];
        while ($row = $stmt->fetch()) {
            $saved[$row['setting_key']] = $row['setting_value'];
        }

        switch ($api) {
            case 'evolution':
                $url = $val ?: ($saved['evolution_api_url'] ?? '');
                $key = trim((string)$request->input('key', '')) ?: ($saved['evolution_api_key'] ?? '');
                $inst = trim((string)$request->input('inst', '')) ?: ($saved['evolution_instance_name'] ?? 'player99');
                $result = \App\Services\APITesterService::testEvolution($url, $key, $inst);
                break;

            case 'gemini':
                $key = $val ?: ($saved['gemini_api_key'] ?? '');
                $result = \App\Services\APITesterService::testGemini($key);
                if (!empty($result['ok']) && $key !== '') {
                    $this->upsertSetting($pdo, 'gemini_api_key', $key);
                }
                break;

            case 'openai':
                $key = $val ?: ($saved['openai_api_key'] ?? '');
                $result = \App\Services\APITesterService::testOpenAI($key);
                if (!empty($result['ok']) && $key !== '') {
                    $this->upsertSetting($pdo, 'openai_api_key', $key);
                }
                break;

            case 'ai_simulation':
                $testPrompt = trim((string)$request->input('prompt', '')) ?: ($saved['ai_system_prompt'] ?? '');
                $provider = trim((string)$request->input('provider', '')) ?: ($saved['ai_provider'] ?? 'openai');
                $openAiKey = trim((string)$request->input('openai_key', '')) ?: ($saved['openai_api_key'] ?? '');
                $geminiKey = trim((string)$request->input('gemini_key', '')) ?: ($saved['gemini_api_key'] ?? '');
                $sampleQuestion = trim((string)$request->input('question', 'Olá! Vi sobre a Player99 e gostaria de saber como funciona.'));

                $simSettings = [
                    'ai_provider' => $provider,
                    'ai_system_prompt' => $testPrompt,
                    'openai_api_key' => $openAiKey,
                    'gemini_api_key' => $geminiKey
                ];

                $reply = AIService::reply($simSettings, [], $sampleQuestion);
                if ($reply !== null) {
                    $result = ['ok' => true, 'msg' => $reply];
                } else {
                    $result = ['ok' => false, 'msg' => AIService::$lastError ?: 'Falha ao gerar resposta da IA.'];
                }
                break;

            case 'mercadopago':
                $token = $val ?: ($saved['mercadopago_access_token'] ?? '');
                $result = \App\Services\APITesterService::testMercadoPago($token);
                if (!empty($result['ok']) && $token !== '') {
                    $this->upsertSetting($pdo, 'mercadopago_access_token', $token);
                }
                break;

            case 'weather':
                $key = $val ?: ($saved['openweather_api_key'] ?? '');
                $result = \App\Services\APITesterService::testWeather($key);
                if (!empty($result['ok']) && $key !== '') {
                    $this->upsertSetting($pdo, 'openweather_api_key', $key);
                }
                break;

            case 'welcome_whatsapp':
                $phoneRaw = trim((string)$request->input('phone', ''));
                $phone = WhatsAppWelcomeService::formatBrazilPhone($phoneRaw);
                if ($phone === null) {
                    $result = ['ok' => false, 'msg' => 'Informe um número com DDD válido (ex: 11999998888).'];
                    break;
                }
                $evoUrl = $this->normalizeEvoUrl((string)($request->input('url') ?: ($saved['evolution_api_url'] ?? '')));
                $evoKey = trim((string)($request->input('key') ?: ($saved['evolution_api_key'] ?? '')));
                $evoInst = trim((string)($request->input('inst') ?: ($saved['evolution_instance_name'] ?? 'player99'))) ?: 'player99';
                $tpl = trim((string)$request->input('template', '')) ?: ($saved['welcome_whatsapp_message'] ?? '');

                if ($evoUrl === '' || $evoKey === '') {
                    $result = ['ok' => false, 'msg' => 'Configure a URL e API Key da Evolution API acima primeiro.'];
                    break;
                }

                $previewMsg = WhatsAppWelcomeService::buildMessage('Cliente Teste', 'clienteteste', $tpl);
                $evo = new EvolutionService($evoUrl, $evoKey, $evoInst);
                $sendRes = $evo->sendText($phone, $previewMsg, '', 8);
                if (!empty($sendRes['success'])) {
                    $result = ['ok' => true, 'msg' => "Mensagem de boas-vindas enviada com sucesso para {$phone}!"];
                } else {
                    $result = ['ok' => false, 'msg' => 'Falha ao enviar (' . ($sendRes['error'] ?? ('HTTP ' . ($sendRes['http_code'] ?? 0))) . '). Verifique se o WhatsApp está conectado.'];
                }
                break;

            default:
                $result = ['ok' => false, 'msg' => 'API não reconhecida'];
                break;
        }

        echo json_encode($result);
    }
}
