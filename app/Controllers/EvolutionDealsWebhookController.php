<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Models\Deal;
use App\Services\LinkConverterService;

class EvolutionDealsWebhookController extends Controller
{
    /**
     * Endpoint Webhook dedicado para captação de ofertas de grupos WhatsApp
     * Rota: POST /api/evolution/deals-webhook
     */
    public function handle(Request $request): void
    {
        header('Content-Type: application/json');
        Deal::ensureSchema();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!$payload) {
            echo json_encode(['status' => 'ignored', 'reason' => 'Empty or invalid JSON']);
            return;
        }

        // Evento padrão da Evolution v2 é 'messages.upsert'
        $event = $payload['event'] ?? '';
        if ($event !== 'messages.upsert') {
            echo json_encode(['status' => 'ignored', 'reason' => 'Event not messages.upsert']);
            return;
        }

        $data = $payload['data'] ?? [];
        $key = $data['key'] ?? [];
        $remoteJid = $key['remoteJid'] ?? '';
        $fromMe = $key['fromMe'] ?? false;

        // Ignora mensagens enviadas por nós mesmos
        if ($fromMe) {
            echo json_encode(['status' => 'ignored', 'reason' => 'fromMe']);
            return;
        }

        // Verifica se há filtro de grupo configurado
        $pdo = Database::deals();
        $configuredJid = (string)$pdo->query("SELECT group_jid FROM affiliate_settings WHERE active = 1 AND group_jid != '' LIMIT 1")->fetchColumn();
        if (!empty($configuredJid) && strpos($remoteJid, $configuredJid) === false) {
            echo json_encode(['status' => 'ignored', 'reason' => 'Group not matching configured JID']);
            return;
        }

        // Extrai texto da mensagem
        $message = $data['message'] ?? [];
        $text = $message['conversation'] 
            ?? ($message['extendedTextMessage']['text'] 
            ?? ($message['imageMessage']['caption'] 
            ?? ($message['videoMessage']['caption'] ?? '')));

        if (empty($text)) {
            echo json_encode(['status' => 'ignored', 'reason' => 'No text found in message']);
            return;
        }

        // Processa o texto procurando ofertas e convertendo links
        $parsed = LinkConverterService::parseDealMessage($text);

        if (!$parsed) {
            echo json_encode(['status' => 'ignored', 'reason' => 'No supported e-commerce deal link detected']);
            return;
        }

        // Verifica se a oferta com esse link original já existe para evitar duplicatas
        $stmtCheck = $pdo->prepare("SELECT id FROM deals WHERE original_url = :url OR affiliate_url = :aff");
        $stmtCheck->execute([
            ':url' => $parsed['original_url'],
            ':aff' => $parsed['affiliate_url']
        ]);
        if ($stmtCheck->fetch()) {
            echo json_encode(['status' => 'ignored', 'reason' => 'Deal already exists']);
            return;
        }

        // Se tiver imagem na mensagem da Evolution, salva em /uploads/deals/
        $imageUrl = '';
        if (isset($message['imageMessage']['url'])) {
            $imageUrl = $message['imageMessage']['url'];
        }

        // Salva a nova oferta
        $stmtIns = $pdo->prepare("INSERT INTO deals (platform, title, original_url, affiliate_url, image_url, price_old, price_new, discount_pct, category, active) 
            VALUES (:plat, :title, :orig, :aff, :img, :pold, :pnew, :disc, :cat, 1)");
        
        $stmtIns->execute([
            ':plat' => $parsed['platform'],
            ':title' => $parsed['title'],
            ':orig' => $parsed['original_url'],
            ':aff' => $parsed['affiliate_url'],
            ':img' => $imageUrl,
            ':pold' => $parsed['price_old'],
            ':pnew' => $parsed['price_new'],
            ':disc' => $parsed['discount_pct'],
            ':cat' => $parsed['category']
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Deal captured and converted successfully',
            'deal' => $parsed
        ]);
    }
}
