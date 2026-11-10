<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Services\EvolutionService;
use App\Services\AIService;

class EvolutionWebhookController extends Controller
{
    public static function getSettings(): array
    {
        $pdo = Database::users();
        $settings = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (\Throwable $e) {}
        return $settings;
    }

    public static function ensureTables(): void
    {
        $pdo = Database::users();
        $pdo->exec("CREATE TABLE IF NOT EXISTS ai_conversations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            remote_jid TEXT,
            role TEXT,
            message TEXT,
            msg_id TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_ai_jid ON ai_conversations(remote_jid)");

        $pdo->exec("CREATE TABLE IF NOT EXISTS ai_processed_messages (
            msg_id VARCHAR(120) PRIMARY KEY,
            remote_jid TEXT,
            user_text TEXT,
            ai_reply TEXT,
            status VARCHAR(50),
            error_msg TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public static function extractMessageText(array $msgData): string
    {
        $m = $msgData['message'] ?? $msgData;
        if (!is_array($m)) {
            return '';
        }

        if (isset($m['ephemeralMessage']['message']) && is_array($m['ephemeralMessage']['message'])) {
            $m = $m['ephemeralMessage']['message'];
        } elseif (isset($m['viewOnceMessage']['message']) && is_array($m['viewOnceMessage']['message'])) {
            $m = $m['viewOnceMessage']['message'];
        } elseif (isset($m['viewOnceMessageV2']['message']) && is_array($m['viewOnceMessageV2']['message'])) {
            $m = $m['viewOnceMessageV2']['message'];
        } elseif (isset($m['documentWithCaptionMessage']['message']) && is_array($m['documentWithCaptionMessage']['message'])) {
            $m = $m['documentWithCaptionMessage']['message'];
        }

        $candidates = [
            $m['conversation'] ?? null,
            $m['extendedTextMessage']['text'] ?? null,
            $m['imageMessage']['caption'] ?? null,
            $m['videoMessage']['caption'] ?? null,
            $m['documentMessage']['caption'] ?? null,
            $m['buttonsResponseMessage']['selectedDisplayText'] ?? null,
            $m['listResponseMessage']['title'] ?? null,
            $m['templateButtonReplyMessage']['selectedDisplayText'] ?? null,
            $msgData['body'] ?? null,
            $msgData['text'] ?? null
        ];

        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
        }

        return '';
    }

    public static function processSingleMessage(array $msgData, array $settings): array
    {
        $key = $msgData['key'] ?? [];
        if (empty($key) || !is_array($key)) {
            return ['status' => 'invalid_key'];
        }

        $fromMe = !empty($key['fromMe']);
        $remoteJid = trim((string)($key['remoteJid'] ?? ''));
        $altJid = trim((string)($key['remoteJidAlt'] ?? $key['senderPn'] ?? $msgData['senderPn'] ?? $key['participant'] ?? ''));

        if ($fromMe || $remoteJid === '') {
            return ['status' => 'ignored_sender'];
        }

        if (
            str_ends_with($remoteJid, '@g.us') ||
            str_ends_with($remoteJid, '@broadcast') ||
            str_contains($remoteJid, 'status@broadcast') ||
            str_ends_with($remoteJid, '@newsletter')
        ) {
            return ['status' => 'ignored_group_or_broadcast'];
        }

        $userText = self::extractMessageText($msgData);
        if ($userText === '') {
            return ['status' => 'non_text_message'];
        }

        self::ensureTables();
        $pdo = Database::users();

        $msgId = trim((string)($key['id'] ?? ''));
        if ($msgId === '') {
            $ts = (string)($msgData['messageTimestamp'] ?? time());
            $msgId = 'gen_' . md5($remoteJid . '|' . $userText . '|' . $ts);
        }

        $stmtChk = $pdo->prepare("SELECT status FROM ai_processed_messages WHERE msg_id = :id LIMIT 1");
        $stmtChk->execute([':id' => $msgId]);
        if ($stmtChk->fetch()) {
            return ['status' => 'already_processed', 'msg_id' => $msgId];
        }

        try {
            $stmtLock = $pdo->prepare("INSERT INTO ai_processed_messages (msg_id, remote_jid, user_text, status) VALUES (:id, :jid, :txt, 'processing')");
            $stmtLock->execute([
                ':id' => $msgId,
                ':jid' => $remoteJid,
                ':txt' => $userText
            ]);
        } catch (\Throwable $e) {
            return ['status' => 'already_locked', 'msg_id' => $msgId];
        }

        $normalizedJid = EvolutionService::normalizeRecipient($remoteJid, $altJid);

        $stmtHist = $pdo->prepare("SELECT role, message FROM ai_conversations WHERE remote_jid = :jid ORDER BY id DESC LIMIT 6");
        $stmtHist->execute([':jid' => $normalizedJid]);
        $history = array_reverse($stmtHist->fetchAll());

        $stmtIns = $pdo->prepare("INSERT INTO ai_conversations (remote_jid, role, message, msg_id) VALUES (:jid, 'user', :msg, :mid)");
        $stmtIns->execute([
            ':jid' => $normalizedJid,
            ':msg' => $userText,
            ':mid' => $msgId
        ]);

        $evoUrl = trim((string)($settings['evolution_api_url'] ?? ''));
        $evoKey = trim((string)($settings['evolution_api_key'] ?? ''));
        $evoInst = trim((string)($settings['evolution_instance_name'] ?? 'player99'));

        if ($evoUrl === '' || $evoKey === '') {
            $pdo->prepare("UPDATE ai_processed_messages SET status = 'error', error_msg = 'Evolution não configurada' WHERE msg_id = :id")
                ->execute([':id' => $msgId]);
            return ['status' => 'evolution_not_configured'];
        }

        $evo = new EvolutionService($evoUrl, $evoKey, $evoInst);

        $delaySec = (int)($settings['ai_human_delay'] ?? 2);
        $evo->sendPresence($remoteJid, 'composing');

        $aiReply = AIService::reply($settings, $history, $userText);

        if (empty($aiReply)) {
            $err = AIService::$lastError ?: 'Resposta vazia da API de IA';
            $pdo->prepare("UPDATE ai_processed_messages SET status = 'ai_error', error_msg = :err WHERE msg_id = :id")
                ->execute([':err' => $err, ':id' => $msgId]);
            return ['status' => 'ai_error', 'error' => $err];
        }

        if ($delaySec > 0) {
            sleep(min($delaySec, 5));
        }

        $sendRes = $evo->sendText($remoteJid, $aiReply, $altJid);

        if (!empty($sendRes['success'])) {
            $stmtBot = $pdo->prepare("INSERT INTO ai_conversations (remote_jid, role, message, msg_id) VALUES (:jid, 'assistant', :msg, :mid)");
            $stmtBot->execute([
                ':jid' => $normalizedJid,
                ':msg' => $aiReply,
                ':mid' => $msgId . '_reply'
            ]);

            $pdo->prepare("UPDATE ai_processed_messages SET ai_reply = :rep, status = 'replied', error_msg = '' WHERE msg_id = :id")
                ->execute([':rep' => $aiReply, ':id' => $msgId]);

            return [
                'status' => 'replied',
                'msg_id' => $msgId,
                'remote_jid' => $normalizedJid,
                'reply' => $aiReply
            ];
        } else {
            $sendErr = is_array($sendRes['data'] ?? null)
                ? json_encode($sendRes['data'], JSON_UNESCAPED_UNICODE)
                : (string)($sendRes['error'] ?? ('HTTP ' . ($sendRes['http_code'] ?? 0)));

            $pdo->prepare("UPDATE ai_processed_messages SET ai_reply = :rep, status = 'send_error', error_msg = :err WHERE msg_id = :id")
                ->execute([':rep' => $aiReply, ':err' => $sendErr, ':id' => $msgId]);

            return [
                'status' => 'send_error',
                'error' => $sendErr
            ];
        }
    }

    public function handle(Request $request, string $eventParam = ''): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $settings = self::getSettings();

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
        if (!is_array($payload)) {
            echo json_encode(['status' => 'invalid_json']);
            return;
        }

        $rawEvent = (string)($payload['event'] ?? $eventParam ?? '');
        $normEvent = strtolower(str_replace(['_', '-'], '.', trim($rawEvent)));

        $data = $payload['data'] ?? $payload;
        $hasKeyDirectly = isset($data['key']) || isset($data['messages']) || (is_array($data) && isset($data[0]['key']));

        if ($normEvent !== '' && $normEvent !== 'messages.upsert' && !$hasKeyDirectly) {
            echo json_encode(['status' => 'ignored_event', 'event' => $rawEvent]);
            return;
        }

        $messagesList = [];
        if (isset($data['key'])) {
            $messagesList[] = $data;
        } elseif (isset($data['messages']) && is_array($data['messages'])) {
            foreach ($data['messages'] as $mItem) {
                if (is_array($mItem)) {
                    $messagesList[] = $mItem;
                }
            }
        } elseif (is_array($data) && isset($data[0]['key'])) {
            $messagesList = $data;
        }

        if (empty($messagesList)) {
            echo json_encode(['status' => 'no_messages_in_payload']);
            return;
        }

        $results = [];
        foreach ($messagesList as $msgItem) {
            $results[] = self::processSingleMessage($msgItem, $settings);
        }

        echo json_encode(['status' => 'ok', 'results' => $results]);
    }

    public static function pollUnreadMessages(bool $force = false): array
    {
        $lockFile = dirname(__DIR__, 2) . '/api/db/evo_ai_poll.lock';
        if (!$force && is_file($lockFile) && (time() - (int)@filemtime($lockFile)) < 5) {
            return ['status' => 'throttled'];
        }
        @file_put_contents($lockFile, (string)time());

        $settings = self::getSettings();
        if (($settings['ai_agent_enabled'] ?? '0') !== '1') {
            return ['status' => 'ai_disabled'];
        }

        $evoUrl = trim((string)($settings['evolution_api_url'] ?? ''));
        $evoKey = trim((string)($settings['evolution_api_key'] ?? ''));
        $evoInst = trim((string)($settings['evolution_instance_name'] ?? 'player99'));

        if ($evoUrl === '' || $evoKey === '') {
            return ['status' => 'evolution_not_configured'];
        }

        self::ensureTables();
        $evo = new EvolutionService($evoUrl, $evoKey, $evoInst);

        $recentMessages = $evo->fetchRecentMessages(25);
        if (empty($recentMessages)) {
            return ['status' => 'no_recent_messages', 'checked' => 0];
        }

        $latestByJid = [];
        $now = time();

        foreach ($recentMessages as $m) {
            if (!is_array($m) || empty($m['key'])) {
                continue;
            }
            $rJid = trim((string)($m['key']['remoteJid'] ?? ''));
            if ($rJid === '' || str_ends_with($rJid, '@g.us') || str_ends_with($rJid, '@broadcast') || str_contains($rJid, 'status@broadcast')) {
                continue;
            }
            $ts = (int)($m['messageTimestamp'] ?? 0);
            if ($ts > 0 && ($now - $ts) > 900) {
                continue;
            }
            if (!isset($latestByJid[$rJid]) || $ts >= (int)($latestByJid[$rJid]['messageTimestamp'] ?? 0)) {
                $latestByJid[$rJid] = $m;
            }
        }

        $processed = [];
        foreach ($latestByJid as $jid => $lastMsg) {
            $fromMe = !empty($lastMsg['key']['fromMe']);
            if ($fromMe) {
                continue;
            }
            $res = self::processSingleMessage($lastMsg, $settings);
            if (($res['status'] ?? '') !== 'already_processed') {
                $processed[] = $res;
            }
        }

        return [
            'status' => 'ok',
            'checked' => count($recentMessages),
            'conversations_inspected' => count($latestByJid),
            'processed' => $processed
        ];
    }

    public function poll(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $force = $request->input('force') === '1';
        $pollResult = self::pollUnreadMessages($force);

        self::ensureTables();
        $pdo = Database::users();
        $logs = [];
        try {
            $stmt = $pdo->query("SELECT msg_id, remote_jid, user_text, ai_reply, status, error_msg, created_at FROM ai_processed_messages ORDER BY created_at DESC LIMIT 10");
            $logs = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        echo json_encode([
            'poll' => $pollResult,
            'recent_logs' => $logs
        ]);
    }
}
