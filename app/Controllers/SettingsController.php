<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Services\EvolutionService;

class SettingsController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            http_response_code(403);
            die("Acesso Restrito: Apenas usuários Root podem acessar as configurações gerais do sistema.");
        }

        $pdo = Database::users();

        // Garante tabela system_settings com schema seguro
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

        $msg = '';

        if ($request->isPost()) {
            $evoUrl = trim($request->input('evolution_api_url', ''));
            // Remove barras soltas no início e normaliza http://
            $evoUrl = preg_replace('#^/+#', '', $evoUrl);
            if (!empty($evoUrl) && !preg_match('#^https?://#i', $evoUrl)) {
                $evoUrl = 'http://' . $evoUrl;
            }
            $evoUrl = preg_replace('#^(https?:)/+#i', '$1//', $evoUrl);

            $settingsToSave = [
                'google_login_enabled' => $request->input('google_login_enabled') === '1' ? '1' : '0',
                'google_client_id' => trim($request->input('google_client_id', '')),
                'google_client_secret' => trim($request->input('google_client_secret', '')),
                'evolution_api_url' => $evoUrl,
                'evolution_api_key' => trim($request->input('evolution_api_key', '')),
                'evolution_instance_name' => trim($request->input('evolution_instance_name', 'player99')),
                'ai_agent_enabled' => $request->input('ai_agent_enabled') === '1' ? '1' : '0',
                'ai_provider' => trim($request->input('ai_provider', 'gemini')),
                'ai_human_delay' => (int)$request->input('ai_human_delay', 3),
                'ai_system_prompt' => trim($request->input('ai_system_prompt', '')),
                'gemini_api_key' => trim($request->input('gemini_api_key', '')),
                'openai_api_key' => trim($request->input('openai_api_key', '')),
                'anthropic_api_key' => trim($request->input('anthropic_api_key', '')),
                'tmdb_api_key' => trim($request->input('tmdb_api_key', '')),
                'openweather_api_key' => trim($request->input('openweather_api_key', '')),
                'mercadopago_access_token' => trim($request->input('mercadopago_access_token', ''))
            ];

            $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, datetime('now'))
                                   ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = excluded.updated_at");

            foreach ($settingsToSave as $k => $v) {
                $stmt->bindValue(':k', $k);
                $stmt->bindValue(':v', (string)$v);
                $stmt->execute();
            }

            $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Configurações e Treinamento do Agente de I.A. salvos com sucesso!</div>";
        }

        // Carrega configurações existentes
        $stmtAll = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $savedSettings = [];
        while ($row = $stmtAll->fetch()) {
            $savedSettings[$row['setting_key']] = $row['setting_value'];
        }

        // Prompt Padrão de Treinamento se estiver vazio
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
            'msg' => $msg
        ], 'layouts/master');
    }

    /**
     * Endpoint AJAX para Gerar QR Code ou Conectar à Evolution API
     */
    public function evolutionConnect(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json');

        $pdo = Database::users();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $rawBody = json_decode(file_get_contents('php://input'), true) ?? [];
        $url = $request->input('url') ?: ($rawBody['url'] ?? ($settings['evolution_api_url'] ?? ''));
        $key = $request->input('key') ?: ($rawBody['key'] ?? ($settings['evolution_api_key'] ?? ''));
        $inst = $request->input('instance') ?: ($rawBody['instance'] ?? ($settings['evolution_instance_name'] ?? 'player99'));

        // Normaliza a URL (remove barras soltas e garante http://)
        $url = trim($url);
        $url = preg_replace('#^/+#', '', $url);
        if (!empty($url) && !preg_match('#^https?://#i', $url)) {
            $url = 'http://' . $url;
        }
        $url = preg_replace('#^(https?:)/+#i', '$1//', $url);

        if (empty($url) || empty($key)) {
            echo json_encode(['success' => false, 'error' => 'Por favor, preencha a URL da Evolution API e a API Key antes de conectar.']);
            return;
        }

        // Salva automaticamente no banco para nunca perder a configuração
        $stmtSave = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (:k, :v, datetime('now'))
                                   ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value, updated_at = excluded.updated_at");
        $stmtSave->execute([':k' => 'evolution_api_url', ':v' => $url]);
        $stmtSave->execute([':k' => 'evolution_api_key', ':v' => $key]);
        $stmtSave->execute([':k' => 'evolution_instance_name', ':v' => $inst]);

        $evo = new EvolutionService($url, $key, $inst);

        // 1. Cria a instância se necessário, já configurando o webhook
        $webhookUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? '') . '/api/evolution/webhook';
        $evo->createInstance($webhookUrl);

        // 2. Solicita conexão e captura QR Code
        $res = $evo->connect();

        echo json_encode($res);
    }

    /**
     * Endpoint AJAX para verificar status da conexão
     */
    public function evolutionStatus(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json');

        $pdo = Database::users();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $rawBody = json_decode(file_get_contents('php://input'), true) ?? [];
        $url = $request->input('url') ?: ($rawBody['url'] ?? ($settings['evolution_api_url'] ?? ''));
        $key = $request->input('key') ?: ($rawBody['key'] ?? ($settings['evolution_api_key'] ?? ''));
        $inst = $request->input('instance') ?: ($rawBody['instance'] ?? ($settings['evolution_instance_name'] ?? 'player99'));

        $url = trim($url);
        $url = preg_replace('#^/+#', '', $url);
        if (!empty($url) && !preg_match('#^https?://#i', $url)) {
            $url = 'http://' . $url;
        }
        $url = preg_replace('#^(https?:)/+#i', '$1//', $url);

        if (empty($url) || empty($key)) {
            echo json_encode(['success' => false, 'error' => 'Evolution não configurada']);
            return;
        }

        $evo = new EvolutionService($url, $key, $inst);
        $res = $evo->getConnectionState();

        echo json_encode($res);
    }

    /**
     * Endpoint AJAX para Desconectar WhatsApp
     */
    public function evolutionLogout(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json');

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

    /**
     * Endpoint AJAX genérico para Teste de Conexão com qualquer API externa
     */
    public function testApi(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            echo json_encode(['ok' => false, 'msg' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json');

        $api = $request->input('api', '');
        $val = trim($request->input('val', ''));

        // Busca dados salvos para fallback
        $pdo = Database::users();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $saved = [];
        while ($row = $stmt->fetch()) {
            $saved[$row['setting_key']] = $row['setting_value'];
        }

        switch ($api) {
            case 'evolution':
                $url = $val ?: ($saved['evolution_api_url'] ?? '');
                $key = trim($request->input('key', '')) ?: ($saved['evolution_api_key'] ?? '');
                $inst = trim($request->input('inst', '')) ?: ($saved['evolution_instance_name'] ?? 'player99');
                $result = \App\Services\APITesterService::testEvolution($url, $key, $inst);
                break;

            case 'gemini':
                $key = $val ?: ($saved['gemini_api_key'] ?? '');
                $result = \App\Services\APITesterService::testGemini($key);
                break;

            case 'openai':
                $key = $val ?: ($saved['openai_api_key'] ?? '');
                $result = \App\Services\APITesterService::testOpenAI($key);
                break;

            case 'mercadopago':
                $token = $val ?: ($saved['mercadopago_access_token'] ?? '');
                $result = \App\Services\APITesterService::testMercadoPago($token);
                break;

            case 'weather':
                $key = $val ?: ($saved['openweather_api_key'] ?? '');
                $result = \App\Services\APITesterService::testWeather($key);
                break;

            default:
                $result = ['ok' => false, 'msg' => 'API não reconhecida'];
                break;
        }

        echo json_encode($result);
    }
}
