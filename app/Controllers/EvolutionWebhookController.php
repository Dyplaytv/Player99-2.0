<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Services\EvolutionService;
use App\Services\AIService;

class EvolutionWebhookController extends Controller
{
    private function getSettings(): array
    {
        $pdo = Database::users();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }

    private function ensureChatTable(): void
    {
        $pdo = Database::users();
        $pdo->exec("CREATE TABLE IF NOT EXISTS ai_conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            remote_jid TEXT,
            role TEXT,
            message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ai_jid ON ai_conversations(remote_jid)");
    }

    public function handle(Request $request): void
    {
        $settings = $this->getSettings();

        // Se o agente estiver desativado no painel, ignora
        if (($settings['ai_agent_enabled'] ?? '0') !== '1') {
            echo json_encode(['status' => 'ai_disabled']);
            return;
        }

        $raw = file_get_contents('php://input');
        if (empty($raw)) {
            echo json_encode(['status' => 'empty_payload']);
            return;
        }

        $payload = json_decode($raw, true);
        if (!$payload || ($payload['event'] ?? '') !== 'messages.upsert') {
            echo json_encode(['status' => 'ignored_event']);
            return;
        }

        $msgData = $payload['data'] ?? [];
        $key = $msgData['key'] ?? [];

        // Ignora mensagens enviadas pelo próprio bot ou de grupos
        $fromMe = $key['fromMe'] ?? false;
        $remoteJid = $key['remoteJid'] ?? '';
        $isGroup = str_ends_with($remoteJid, '@g.us');

        if ($fromMe || $isGroup || empty($remoteJid)) {
            echo json_encode(['status' => 'ignored_sender']);
            return;
        }

        // Extrai texto da mensagem
        $messageContent = $msgData['message'] ?? [];
        $userText = $messageContent['conversation'] 
            ?? $messageContent['extendedTextMessage']['text'] 
            ?? '';

        $userText = trim($userText);
        if (empty($userText)) {
            echo json_encode(['status' => 'non_text_message']);
            return;
        }

        $this->ensureChatTable();
        $pdo = Database::users();

        // Salva mensagem do cliente no histórico
        $stmtIns = $pdo->prepare("INSERT INTO ai_conversations (remote_jid, role, message) VALUES (:jid, 'user', :msg)");
        $stmtIns->execute([':jid' => $remoteJid, ':msg' => $userText]);

        // Carrega as últimas 6 mensagens desse contato para contexto
        $stmtHist = $pdo->prepare("SELECT role, message FROM ai_conversations WHERE remote_jid = :jid ORDER BY id DESC LIMIT 6");
        $stmtHist->execute([':jid' => $remoteJid]);
        $history = array_reverse($stmtHist->fetchAll());

        // Instancia Evolution
        $evoUrl = $settings['evolution_api_url'] ?? '';
        $evoKey = $settings['evolution_api_key'] ?? '';
        $evoInst = $settings['evolution_instance_name'] ?? 'player99';

        if (empty($evoUrl) || empty($evoKey)) {
            echo json_encode(['status' => 'evolution_not_configured']);
            return;
        }

        $evo = new EvolutionService($evoUrl, $evoKey, $evoInst);

        // 1. Simula que está digitando (Humanização)
        $delaySec = (int)($settings['ai_human_delay'] ?? 3);
        $evo->sendPresence($remoteJid, 'composing');

        // 2. Gera resposta com a IA
        $aiReply = AIService::reply($settings, $history, $userText);

        if (!empty($aiReply)) {
            // Delay natural de digitação
            if ($delaySec > 0 && $delaySec <= 10) {
                sleep($delaySec);
            }

            // 3. Envia resposta pelo WhatsApp
            $evo->sendText($remoteJid, $aiReply);

            // Salva a resposta no histórico da conversa
            $stmtBot = $pdo->prepare("INSERT INTO ai_conversations (remote_jid, role, message) VALUES (:jid, 'assistant', :msg)");
            $stmtBot->execute([':jid' => $remoteJid, ':msg' => $aiReply]);
        }

        echo json_encode(['status' => 'success', 'replied' => !empty($aiReply)]);
    }
}
