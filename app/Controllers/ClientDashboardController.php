<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Client;
use App\Models\Screen;

class ClientDashboardController extends Controller
{
    private function ensureClientCustomizationTable(): void
    {
        $pdo = Database::clients();
        $pdo->exec("CREATE TABLE IF NOT EXISTS cliente_customizacoes (
            id_cliente INTEGER PRIMARY KEY,
            brand_name TEXT,
            logo_path TEXT,
            primary_color TEXT DEFAULT '#34e2e4',
            support_whatsapp TEXT,
            support_email TEXT,
            custom_announcement TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function index(Request $request): void
    {
        if (empty($_SESSION['cliente_logado']) && !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Client::ensureSchema();
        Screen::ensureSchema();
        $this->ensureClientCustomizationTable();
        $pdo = Database::clients();

        $allClients = [];
        $isAdmin = Auth::isAdmin();

        // Se for Admin/Root, carrega todos os clientes para permitir busca instantânea (Dropdown Live Search)
        if ($isAdmin) {
            $stmtAll = $pdo->query("SELECT id, title, username, email, whatsapp FROM ibo ORDER BY id DESC");
            $allClients = $stmtAll->fetchAll();
        }

        // Determina qual cliente está sendo visualizado / personalizado
        $targetId = (int)$request->input('client_id', 0);
        if ($targetId > 0 && $isAdmin) {
            $idCliente = $targetId;
        } else {
            $idCliente = (int)($_SESSION['cliente_id'] ?? 0);
            if ($idCliente <= 0 && $isAdmin) {
                $idCliente = !empty($allClients) ? (int)$allClients[0]['id'] : 1;
            }
        }

        $msg = '';
        $err = '';

        if ($request->isPost()) {
            $action = $request->input('action', '');

            // 1. Salvar Customização da Página / Franquia
            if ($action === 'save_customization') {
                $brandName = trim($request->input('brand_name', ''));
                $primaryColor = trim($request->input('primary_color', '#34e2e4'));
                $supportWhatsapp = trim($request->input('support_whatsapp', ''));
                $supportEmail = trim($request->input('support_email', ''));
                $customAnnouncement = trim($request->input('custom_announcement', ''));

                $logoPath = null;
                if (!empty($_FILES['custom_logo']['name']) && $_FILES['custom_logo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['custom_logo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                        $upDir = dirname(__DIR__, 2) . '/uploads/clientes/' . $idCliente;
                        if (!is_dir($upDir)) @mkdir($upDir, 0777, true);
                        $logoName = 'logo_custom_' . time() . '.' . $ext;
                        if (move_uploaded_file($_FILES['custom_logo']['tmp_name'], $upDir . '/' . $logoName)) {
                            $logoPath = '/uploads/clientes/' . $idCliente . '/' . $logoName;
                        }
                    }
                }

                $stmtCur = $pdo->prepare("SELECT logo_path FROM cliente_customizacoes WHERE id_cliente = :cid");
                $stmtCur->execute([':cid' => $idCliente]);
                $currCustom = $stmtCur->fetch();

                if ($logoPath === null && $currCustom) {
                    $logoPath = $currCustom['logo_path'];
                }

                $stmtUpsert = $pdo->prepare("INSERT INTO cliente_customizacoes (id_cliente, brand_name, logo_path, primary_color, support_whatsapp, support_email, custom_announcement, updated_at)
                    VALUES (:cid, :bname, :logo, :color, :whats, :email, :ann, datetime('now'))
                    ON CONFLICT(id_cliente) DO UPDATE SET 
                        brand_name = excluded.brand_name,
                        logo_path = coalesce(excluded.logo_path, cliente_customizacoes.logo_path),
                        primary_color = excluded.primary_color,
                        support_whatsapp = excluded.support_whatsapp,
                        support_email = excluded.support_email,
                        custom_announcement = excluded.custom_announcement,
                        updated_at = excluded.updated_at");

                $stmtUpsert->execute([
                    ':cid' => $idCliente,
                    ':bname' => $brandName,
                    ':logo' => $logoPath,
                    ':color' => $primaryColor,
                    ':whats' => $supportWhatsapp,
                    ':email' => $supportEmail,
                    ':ann' => $customAnnouncement
                ]);

                $msg = 'Personalização do portal e franquia salva com sucesso!';
            }

            // 2. Vincular Nova Tela
            elseif ($action === 'add_screen') {
                $title = trim($request->input('title', 'Meu Carro'));
                $code = strtoupper(trim($request->input('player_code', '')));
                if (empty($code)) {
                    $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
                }
                $rotation = (int)$request->input('rotation', 0);

                Screen::create([
                    'id_cliente' => $idCliente,
                    'player_code' => $code,
                    'title' => $title,
                    'nome_tela' => $title,
                    'rotation' => $rotation,
                    'audio_mode' => 'video',
                    'ultimo_ping' => date('Y-m-d H:i:s')
                ]);
                $msg = "Nova tela ({$code}) vinculada com sucesso!";
            }

            // 3. Upload de Mídia
            elseif ($action === 'upload_media') {
                if (!empty($_FILES['media_file']['name']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
                    $allowed = ['mp4', 'webm', 'jpg', 'jpeg', 'png', 'webp', 'gif'];
                    if (in_array($ext, $allowed)) {
                        $uploadDir = dirname(__DIR__, 2) . '/uploads/clientes/' . $idCliente;
                        if (!is_dir($uploadDir)) {
                            @mkdir($uploadDir, 0777, true);
                        }
                        @chmod($uploadDir, 0777);
                        $filename = 'ad_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
                        if (move_uploaded_file($_FILES['media_file']['tmp_name'], $uploadDir . '/' . $filename)) {
                            @chmod($uploadDir . '/' . $filename, 0666);
                            $title = trim($request->input('media_title', ''));
                            if (empty($title)) {
                                $title = pathinfo($_FILES['media_file']['name'], PATHINFO_FILENAME);
                            }
                            
                            // Insere de forma compatível
                            try {
                                $stmtIns = $pdo->prepare("INSERT INTO videos (id_cliente, title, filename, sequence) VALUES (:cid, :t, :f, 0)");
                                $stmtIns->execute([':cid' => $idCliente, ':t' => $title, ':f' => $filename]);
                            } catch (\Throwable $e) {
                                $stmtIns = $pdo->prepare("INSERT INTO videos (id_cliente, title, filename) VALUES (:cid, :t, :f)");
                                $stmtIns->execute([':cid' => $idCliente, ':t' => $title, ':f' => $filename]);
                            }

                            $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id_cliente = :cid")->execute([':cid' => $idCliente]);
                            $msg = 'Mídia enviada e sincronizada nas suas telas!';
                        } else {
                            $err = 'Erro ao salvar o arquivo na pasta de uploads.';
                        }
                    } else {
                        $err = 'Formato inválido. Envie MP4, WEBM, JPG ou PNG.';
                    }
                }
            }

            // 4. Deletar Mídia
            elseif ($action === 'delete_media') {
                $vidId = (int)$request->input('video_id', 0);
                $stmtV = $pdo->prepare("SELECT * FROM videos WHERE id = :id AND id_cliente = :cid");
                $stmtV->execute([':id' => $vidId, ':cid' => $idCliente]);
                $v = $stmtV->fetch();
                if ($v) {
                    $filePath = dirname(__DIR__, 2) . '/uploads/clientes/' . $idCliente . '/' . $v['filename'];
                    if (is_file($filePath)) @unlink($filePath);
                    $pdo->prepare("DELETE FROM videos WHERE id = :id")->execute([':id' => $vidId]);
                    $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id_cliente = :cid")->execute([':cid' => $idCliente]);
                    $msg = 'Mídia removida com sucesso.';
                }
            }
        }

        $client = Client::find($idCliente) ?: [
            'id' => $idCliente,
            'title' => 'Parceiro Player99',
            'username' => 'parceiro',
            'expire_date' => date('Y-m-d', strtotime('+30 days'))
        ];

        // Carrega personalização do cliente
        $stmtC = $pdo->prepare("SELECT * FROM cliente_customizacoes WHERE id_cliente = :cid");
        $stmtC->execute([':cid' => $idCliente]);
        $customization = $stmtC->fetch();

        $defaultLogo = '/banners/logo.png';
        if (!file_exists(dirname(__DIR__, 2) . '/banners/logo.png') && file_exists(dirname(__DIR__, 2) . '/assets/images/logo.png')) {
            $defaultLogo = '/assets/images/logo.png';
        }

        if (!$customization) {
            $customization = [
                'brand_name' => $client['title'] ?? 'Player99 Franquia',
                'logo_path' => $defaultLogo,
                'primary_color' => '#34e2e4',
                'support_whatsapp' => $client['whatsapp'] ?? '',
                'support_email' => $client['email'] ?? '',
                'custom_announcement' => 'Bem-vindo ao portal oficial da nossa rede de mídia indoor!'
            ];
        } else {
            // Se o logo_path estiver vazio ou apontar para arquivo inexistente, usa a nossa logo padrão
            if (empty($customization['logo_path'])) {
                $customization['logo_path'] = $defaultLogo;
            } else {
                $realPath = dirname(__DIR__, 2) . '/' . ltrim($customization['logo_path'], '/');
                if (!file_exists($realPath)) {
                    $customization['logo_path'] = $defaultLogo;
                }
            }
        }

        $stmtTelas = $pdo->prepare("SELECT * FROM telas WHERE id_cliente = :cid ORDER BY id DESC");
        $stmtTelas->execute([':cid' => $idCliente]);
        $screens = $stmtTelas->fetchAll();

        $stmtVids = $pdo->prepare("SELECT * FROM videos WHERE id_cliente = :cid ORDER BY id DESC");
        $stmtVids->execute([':cid' => $idCliente]);
        $videos = $stmtVids->fetchAll();

        $this->render('client/dashboard', [
            'client' => $client,
            'customization' => $customization,
            'screens' => $screens,
            'videos' => $videos,
            'allClients' => $allClients,
            'isAdmin' => $isAdmin,
            'selectedClientId' => $idCliente,
            'msg' => $msg,
            'err' => $err
        ], null);
    }
}
