<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Deal;
use App\Services\LinkConverterService;
use App\Services\EvolutionService;
use PDO;

class DealsController extends Controller
{
    /**
     * Painel de Gestão de Ofertas e Afiliados (/admin/achadinhos)
     */
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Deal::ensureSchema();
        $pdo = Database::deals();
        $msg = '';

        if ($request->isPost()) {
            // 1. Salvar Tags de Afiliado e Splits
            if ($request->input('action') === 'save_settings') {
                $tags = $request->input('tags') ?? [];
                $splits = $request->input('splits') ?? [];
                $actives = $request->input('actives') ?? [];
                $groupJid = trim($request->input('group_jid', ''));

                $stmt = $pdo->prepare("UPDATE affiliate_settings SET 
                    affiliate_tag = :tag, 
                    driver_split_percent = :split, 
                    active = :act, 
                    group_jid = :gjid,
                    updated_at = datetime('now')
                    WHERE platform_key = :k");

                foreach (Deal::getPlatforms() as $p) {
                    $k = $p['platform_key'];
                    $stmt->execute([
                        ':tag' => trim($tags[$k] ?? ''),
                        ':split' => (float)($splits[$k] ?? 40.0),
                        ':act' => isset($actives[$k]) ? 1 : 0,
                        ':gjid' => $groupJid,
                        ':k' => $k
                    ]);
                }

                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Configurações de Afiliados salvas com sucesso!</div>";
            }
            // 2. Adicionar Oferta Manual
            elseif ($request->input('action') === 'add_manual') {
                $rawUrl = trim($request->input('product_url', ''));
                $customTitle = trim($request->input('title', ''));
                $priceOld = (float)str_replace(',', '.', $request->input('price_old', '0'));
                $priceNew = (float)str_replace(',', '.', $request->input('price_new', '0'));
                $image = trim($request->input('image_url', ''));
                $category = trim($request->input('category', 'Geral'));

                if (!empty($rawUrl)) {
                    $conv = LinkConverterService::convertLink($rawUrl);
                    $disc = ($priceOld > $priceNew && $priceNew > 0) ? (int)round((($priceOld - $priceNew) / $priceOld) * 100) : 0;

                    $stmt = $pdo->prepare("INSERT INTO deals (platform, title, original_url, affiliate_url, image_url, price_old, price_new, discount_pct, category, active) 
                        VALUES (:plat, :title, :orig, :aff, :img, :pold, :pnew, :disc, :cat, 1)");
                    $stmt->execute([
                        ':plat' => $conv['platform'],
                        ':title' => !empty($customTitle) ? $customTitle : ('Oferta ' . ucfirst($conv['platform'])),
                        ':orig' => $conv['original_url'],
                        ':aff' => $conv['affiliate_url'],
                        ':img' => $image,
                        ':pold' => $priceOld,
                        ':pnew' => $priceNew,
                        ':disc' => $disc,
                        ':cat' => $category
                    ]);

                    $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Nova oferta cadastrada com sucesso!</div>";
                }
            }
            // 3. Alternar Ativo/Inativo
            elseif ($request->input('action') === 'toggle_active') {
                $id = (int)$request->input('deal_id');
                $pdo->prepare("UPDATE deals SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END WHERE id = :id")->execute([':id' => $id]);
                $msg = "<div class='alert alert-info d-flex align-items-center gap-2 mb-4'><i class='fa fa-sync-alt fa-lg'></i> Status da oferta atualizado!</div>";
            }
            // 4. Excluir Oferta
            elseif ($request->input('action') === 'delete') {
                $id = (int)$request->input('deal_id');
                $pdo->prepare("DELETE FROM deals WHERE id = :id")->execute([':id' => $id]);
                $msg = "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4'><i class='fa fa-trash fa-lg'></i> Oferta removida!</div>";
            }
        }

        $platforms = Deal::getPlatforms();
        $deals = $pdo->query("SELECT * FROM deals ORDER BY id DESC LIMIT 50")->fetchAll();

        // Estatísticas rápidas
        $totalClicks = (int)$pdo->query("SELECT COUNT(*) FROM deal_clicks")->fetchColumn();
        $totalDeals = (int)$pdo->query("SELECT COUNT(*) FROM deals WHERE active = 1")->fetchColumn();

        $this->render('admin/deals/index', [
            'pageTitle' => 'Vitrine de Afiliados E-commerce - Player99',
            'pageHeading' => 'Vitrine de Afiliados & Achadinhos (DOOH)',
            'currentRoute' => '/admin/achadinhos',
            'currentUser' => Auth::user(),
            'platforms' => $platforms,
            'deals' => $deals,
            'totalClicks' => $totalClicks,
            'totalDeals' => $totalDeals,
            'msg' => $msg
        ], 'layouts/master');
    }

    /**
     * Endpoint para testar conversão de link ao vivo via AJAX
     */
    public function testConvert(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            return;
        }

        header('Content-Type: application/json');
        $url = trim($request->input('url', ''));

        if (empty($url)) {
            echo json_encode(['success' => false, 'error' => 'URL vazia']);
            return;
        }

        $res = LinkConverterService::convertLink($url, 'carro_teste');
        echo json_encode(['success' => true, 'data' => $res]);
    }

    /**
     * Rota de Redirecionamento e Rastreio do QR Code (/o/{id} ou /promo/{id})
     */
    public function redirectDeal(Request $request, string $id): void
    {
        Deal::ensureSchema();
        $dealId = (int)$id;
        $screenId = (int)($request->input('carro') ?: ($request->input('s') ?: 0));

        $deal = Deal::find($dealId);

        if (!$deal) {
            http_response_code(404);
            die("Oferta não encontrada.");
        }

        // Descobre motorista dono da tela se screenId fornecido
        $driverUserId = 0;
        if ($screenId > 0) {
            try {
                $stmtScr = Database::clients()->prepare("SELECT user_id FROM screens WHERE id = :id");
                $stmtScr->execute([':id' => $screenId]);
                $driverUserId = (int)$stmtScr->fetchColumn();
            } catch (\Throwable $e) {}
        }

        // Registra o clique
        Deal::recordClick($dealId, $screenId, $driverUserId);

        // Se oferta inativa ou expirada, exibe fallback amigável
        if ($deal['active'] != 1) {
            $otherDeals = Deal::getActiveDeals(6);
            $this->render('store/deal_expired', [
                'deal' => $deal,
                'otherDeals' => $otherDeals
            ], null);
            return;
        }

        // Reconstrói URL de afiliado com sub_id do veículo
        $subId = 'carro_' . $screenId;
        $converted = LinkConverterService::convertLink($deal['original_url'], $subId);
        $finalTarget = !empty($converted['affiliate_url']) ? $converted['affiliate_url'] : $deal['affiliate_url'];

        // Redireciona para o e-commerce
        header("Location: " . $finalTarget, true, 302);
        exit;
    }

    /**
     * Endpoint JSON para o Player TV consumir ofertas ativas
     */
    public function apiActiveDeals(Request $request): void
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');

        $deals = Deal::getActiveDeals(20);
        echo json_encode([
            'success' => true,
            'count' => count($deals),
            'deals' => $deals
        ]);
        exit;
    }

    /**
     * Robô de verificação de links quebrados/expirados
     */
    public function checkLinksHealth(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            http_response_code(403);
            die("Acesso negado");
        }

        header('Content-Type: application/json');
        $pdo = Database::deals();
        $deals = $pdo->query("SELECT id, original_url, title FROM deals WHERE active = 1")->fetchAll();

        $checked = 0;
        $deactivated = 0;

        foreach ($deals as $d) {
            $ch = curl_init($d['original_url']);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $checked++;
            // Se der 404, 410, ou erro fatal
            if ($code === 404 || $code === 410) {
                $pdo->prepare("UPDATE deals SET active = 0 WHERE id = :id")->execute([':id' => $d['id']]);
                $deactivated++;
            }
        }

        echo json_encode([
            'success' => true,
            'checked' => $checked,
            'deactivated' => $deactivated,
            'msg' => "Verificação concluída: {$checked} ofertas checadas, {$deactivated} links quebrados desativados."
        ]);
        exit;
    }

    /**
     * Resolve o cliente Evolution API para instâncias de ofertas
     */
    private function getEvolutionService(string $instanceName = 'player99_ofertas'): EvolutionService
    {
        $pdo = Database::users();
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $settings = [];
        if ($stmt) {
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        }

        $url = !empty($settings['evolution_api_url']) ? $settings['evolution_api_url'] : 'http://172.17.0.1:8095';
        $key = !empty($settings['evolution_api_key']) ? $settings['evolution_api_key'] : 'PLAYER99_EVO_KEY_2026_94a73e51bc084d28f92b71c6e';

        return new EvolutionService($url, $key, $instanceName);
    }

    /**
     * Checa status da instância de ofertas
     */
    public function evolutionStatus(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }
        header('Content-Type: application/json');
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];
        $instance = trim($request->input('instance') ?: ($raw['instance'] ?? 'player99_ofertas'));
        if (empty($instance)) $instance = 'player99_ofertas';

        $evo = $this->getEvolutionService($instance);
        echo json_encode($evo->getConnectionState());
    }

    /**
     * Cria e gera QR Code para a instância de ofertas
     */
    public function evolutionConnect(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }
        header('Content-Type: application/json');
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];
        $instance = trim($request->input('instance') ?: ($raw['instance'] ?? 'player99_ofertas'));
        if (empty($instance)) $instance = 'player99_ofertas';

        $evo = $this->getEvolutionService($instance);

        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? '75.119.156.103';
        $webhookUrl = $protocol . '://' . $host . '/api/evolution/deals-webhook';

        $evo->createInstance($webhookUrl);
        $res = $evo->connect();
        echo json_encode($res);
    }

    /**
     * Desconecta WhatsApp de ofertas
     */
    public function evolutionLogout(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            echo json_encode(['success' => false, 'error' => 'Acesso negado']);
            return;
        }
        header('Content-Type: application/json');
        $raw = json_decode(file_get_contents('php://input'), true) ?? [];
        $instance = trim($request->input('instance') ?: ($raw['instance'] ?? 'player99_ofertas'));
        if (empty($instance)) $instance = 'player99_ofertas';

        $evo = $this->getEvolutionService($instance);
        echo json_encode($evo->logout());
    }
}
