<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Screen;
use App\Models\Client;

class ScreenController extends Controller
{
    public function list(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Screen::ensureSchema();
        Client::ensureSchema();

        $pdo = Database::clients();

        // Sincroniza clientes existentes em 'ibo' que ainda não possuam tela gerada
        try {
            $clientsWithoutScreen = $pdo->query("
                SELECT i.id, i.title, i.username 
                FROM ibo i 
                LEFT JOIN telas t ON t.id_cliente = i.id 
                WHERE t.id IS NULL
            ")->fetchAll();

            foreach ($clientsWithoutScreen as $cli) {
                $code = strtoupper(substr(md5('p99_' . $cli['id'] . uniqid()), 0, 6));
                $stmtIns = $pdo->prepare("INSERT INTO telas (id_cliente, nome_tela, title, player_code, audio_mode, rotation) VALUES (:cid, :nt, :t, :code, 'video', 0)");
                $stmtIns->execute([
                    ':cid' => $cli['id'],
                    ':nt' => $cli['title'] ?: $cli['username'],
                    ':t' => $cli['title'] ?: ('Tela ' . $cli['username']),
                    ':code' => $code
                ]);
            }
        } catch (\Throwable $e) {}

        $screens = [];
        try {
            $stmt = $pdo->query("SELECT t.*, i.username as client_username, i.title as client_name, i.whatsapp as client_whatsapp, i.expire_date 
                                 FROM telas t 
                                 LEFT JOIN ibo i ON t.id_cliente = i.id 
                                 ORDER BY t.id DESC");
            $screens = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $screens = [];
        }

        $this->render('admin/screens/index', [
            'pageTitle' => 'Gerenciar Telas & Carros - Player99',
            'pageHeading' => 'Telas em Operação (DOOH)',
            'currentRoute' => '/admin/telas',
            'currentUser' => Auth::user(),
            'screens' => $screens
        ], 'layouts/master');
    }

    public function create(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Screen::ensureSchema();
        Client::ensureSchema();

        $pdo = Database::clients();
        $error = '';

        if ($request->isPost()) {
            $title = trim($request->input('title', ''));
            $username = trim($request->input('username', ''));
            $password = trim($request->input('password', '123456'));
            $whatsapp = trim($request->input('whatsapp', ''));
            $expireDate = trim($request->input('expire_date', date('Y-m-d', strtotime('+30 days'))));
            $playerCode = strtoupper(trim($request->input('player_code', '')));
            $rotation = (int)$request->input('rotation', 0);
            $audioMode = trim($request->input('audio_mode', 'video'));

            if (empty($playerCode)) {
                $playerCode = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
            }

            if (empty($title)) {
                $error = 'Informe a identificação da tela ou nome do motorista.';
            } else {
                try {
                    // 1. Cria ou localiza o cliente/motorista na tabela ibo
                    if (empty($username)) {
                        $username = 'tela_' . strtolower($playerCode);
                    }

                    $mac = sprintf('%02X:%02X:%02X:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                    $stmtCli = $pdo->prepare("INSERT INTO ibo (mac_address, username, password, title, whatsapp, expire_date, active) VALUES (:mac, :u, :p, :t, :w, :exp, 1)");
                    $stmtCli->execute([
                        ':mac' => $mac,
                        ':u' => $username,
                        ':p' => Auth::hashPassword($password),
                        ':t' => $title,
                        ':w' => $whatsapp,
                        ':exp' => $expireDate
                    ]);
                    $clientId = (int)$pdo->lastInsertId();

                    // 2. Cria a tela vinculada
                    $stmtTela = $pdo->prepare("INSERT INTO telas (id_cliente, nome_tela, title, player_code, rotation, audio_mode) VALUES (:cid, :nt, :t, :code, :rot, :aud)");
                    $stmtTela->execute([
                        ':cid' => $clientId,
                        ':nt' => $title,
                        ':t' => $title,
                        ':code' => $playerCode,
                        ':rot' => $rotation,
                        ':aud' => $audioMode
                    ]);

                    $this->redirect('/admin/telas?msg=created');
                    return;
                } catch (\Throwable $e) {
                    $error = 'Erro ao cadastrar tela: ' . $e->getMessage();
                }
            }
        }

        $suggestedCode = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));

        $this->render('admin/screens/create', [
            'pageTitle' => 'Cadastrar Nova Tela - Player99',
            'pageHeading' => 'Cadastrar Nova Tela / Carro DOOH',
            'currentRoute' => '/admin/telas',
            'currentUser' => Auth::user(),
            'suggestedCode' => $suggestedCode,
            'error' => $error
        ], 'layouts/master');
    }

    public function media(Request $request, $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Screen::ensureSchema();
        $pdo = Database::clients();

        $screen = Screen::find((int)$id);
        if (!$screen) {
            $this->redirect('/admin/telas');
            return;
        }

        $clientId = (int)($screen['id_cliente'] ?: $screen['id']);
        $uploadDir = dirname(__DIR__, 2) . '/uploads/clientes/' . $clientId . '/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $msg = '';

        // Excluir mídia
        if ($request->input('delete_media') !== null) {
            $mediaId = (int)$request->input('delete_media');
            $stmtM = $pdo->prepare("SELECT * FROM videos WHERE id = :id AND id_cliente = :cid");
            $stmtM->execute([':id' => $mediaId, ':cid' => $clientId]);
            $mRow = $stmtM->fetch();
            if ($mRow) {
                @unlink($uploadDir . $mRow['filename']);
                $pdo->prepare("DELETE FROM videos WHERE id = :id")->execute([':id' => $mediaId]);
                $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id = :tid")->execute([':tid' => $screen['id']]);
                $msg = "<div class='alert alert-info d-flex align-items-center gap-2 mb-4'><i class='fa fa-trash'></i> Mídia removida da programação!</div>";
            }
        }

        // Atualizar Configurações da Tela
        if ($request->isPost() && $request->input('action') === 'update_settings') {
            $newTitle = trim($request->input('title', $screen['title'] ?? ''));
            $newRot = (int)$request->input('rotation', 0);
            $newAudio = trim($request->input('audio_mode', 'video'));

            $stmtUp = $pdo->prepare("UPDATE telas SET title = :t, nome_tela = :t, rotation = :r, audio_mode = :a, force_reload = 1 WHERE id = :id");
            $stmtUp->execute([
                ':t' => $newTitle,
                ':r' => $newRot,
                ':a' => $newAudio,
                ':id' => $screen['id']
            ]);

            $screen = Screen::find((int)$id);
            $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle'></i> Configurações da tela atualizadas e sinal enviado ao player!</div>";
        }

        // Upload de Nova Mídia (Vídeo ou Imagem)
        if ($request->isPost() && isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media_file'];
            $mediaTitle = trim($request->input('media_title', pathinfo($file['name'], PATHINFO_FILENAME)));
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['mp4', 'webm', 'ogg', 'jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (!in_array($ext, $allowed)) {
                $msg = "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4'><i class='fa fa-times-circle'></i> Formato não suportado. Use MP4, WEBM, JPG ou PNG.</div>";
            } else {
                $fileName = 'ad_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                    $stmtV = $pdo->prepare("INSERT INTO videos (id_cliente, id_tela, title, filename, upload_date) VALUES (:cid, :tid, :t, :f, datetime('now', 'localtime'))");
                    $stmtV->execute([
                        ':cid' => $clientId,
                        ':tid' => $screen['id'],
                        ':t' => $mediaTitle,
                        ':f' => $fileName
                    ]);
                    $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id = :tid")->execute([':tid' => $screen['id']]);
                    $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle'></i> Mídia adicionada à programação da tela com sucesso!</div>";
                } else {
                    $msg = "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4'><i class='fa fa-times-circle'></i> Falha ao salvar arquivo no diretório de uploads.</div>";
                }
            }
        }

        $stmtVideos = $pdo->prepare("SELECT * FROM videos WHERE id_cliente = :cid ORDER BY id DESC");
        $stmtVideos->execute([':cid' => $clientId]);
        $videos = $stmtVideos->fetchAll();

        $this->render('admin/screens/media', [
            'pageTitle' => 'Programação da Tela - Player99',
            'pageHeading' => 'Programação & Mídias: ' . htmlspecialchars($screen['title'] ?: $screen['player_code']),
            'currentRoute' => '/admin/telas',
            'currentUser' => Auth::user(),
            'screen' => $screen,
            'videos' => $videos,
            'clientId' => $clientId,
            'msg' => $msg
        ], 'layouts/master');
    }

    public function delete(Request $request, $id): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Screen::ensureSchema();
        Screen::deleteById((int)$id);
        $this->redirect('/admin/telas?msg=deleted');
    }

    public function player(Request $request, string $codigo): void
    {
        Screen::ensureSchema();
        $screen = Screen::findByCode($codigo);
        if (!$screen) {
            http_response_code(404);
            die("<div style='background:#08090e;color:#fff;font-family:sans-serif;height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;'>
                    <h2 style='color:#34e2e4;'>Player99 DOOH</h2>
                    <p>Código de tela <b>" . htmlspecialchars($codigo) . "</b> não encontrado.</p>
                 </div>");
        }

        Screen::updatePing((int)$screen['id']);

        $pdo = Database::clients();
        $videos = [];
        try {
            $stmt = $pdo->prepare("SELECT * FROM videos WHERE id_cliente = :cid ORDER BY id ASC");
            $stmt->bindValue(':cid', $screen['id_cliente'] ?: $screen['id']);
            $stmt->execute();
            $videos = $stmt->fetchAll();
        } catch (\Throwable $e) {}

        $this->render('player/index', [
            'screen' => $screen,
            'videos' => $videos,
            'codigo' => $codigo
        ], null);
    }

    public function ping(Request $request): void
    {
        $code = $request->input('codigo');
        if (empty($code)) {
            $this->json(['status' => 'error', 'message' => 'Código ausente'], 400);
        }

        $screen = Screen::findByCode($code);
        if ($screen) {
            $forceReload = (int)($screen['force_reload'] ?? 0);
            Screen::updatePing((int)$screen['id']);
            $this->json([
                'status' => 'ok',
                'active' => 1,
                'force_reload' => $forceReload,
                'rotation' => (int)($screen['rotation'] ?? 0),
                'audio_mode' => $screen['audio_mode'] ?? 'video'
            ]);
        }

        $this->json(['status' => 'not_found'], 404);
    }
}
