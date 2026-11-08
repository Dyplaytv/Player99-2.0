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
    private function ensureTables(\PDO $pdo): void
    {
        Client::ensureSchema();
        Screen::ensureSchema();

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

        $pdo->exec("CREATE TABLE IF NOT EXISTS playlists (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_cliente INTEGER,
            title TEXT,
            creation_date TEXT,
            is_active INTEGER DEFAULT 0
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS playlist_videos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_playlist INTEGER,
            id_video INTEGER,
            sequence INTEGER DEFAULT 1,
            duration INTEGER DEFAULT 15
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS faturamento_cliente (
            id_cliente INTEGER PRIMARY KEY,
            nome_razao TEXT,
            cpf_cnpj TEXT,
            endereco TEXT,
            whats TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS faturas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_cliente INTEGER,
            data_gerada TEXT,
            data_vencimento TEXT,
            valor_total REAL DEFAULT 0,
            status TEXT DEFAULT 'PENDENTE',
            link_pagamento TEXT,
            id_preferencia TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS affiliate_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            userId INTEGER,
            amount REAL,
            type TEXT,
            description TEXT,
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Garante colunas extras em telas, videos e ibo
        $alterQueries = [
            "ALTER TABLE telas ADD COLUMN id_playlist INTEGER DEFAULT 0",
            "ALTER TABLE telas ADD COLUMN id_radio INTEGER DEFAULT 0",
            "ALTER TABLE telas ADD COLUMN muted INTEGER DEFAULT 0",
            "ALTER TABLE videos ADD COLUMN upload_date TEXT",
            "ALTER TABLE playlist_videos ADD COLUMN duration INTEGER DEFAULT 15",
            "ALTER TABLE ibo ADD COLUMN affiliateCode TEXT",
            "ALTER TABLE ibo ADD COLUMN affiliateClicks INTEGER DEFAULT 0",
            "ALTER TABLE ibo ADD COLUMN pixKey TEXT",
            "ALTER TABLE ibo ADD COLUMN commissionPercent REAL DEFAULT 20.0",
            "ALTER TABLE ibo ADD COLUMN referredById INTEGER",
            "ALTER TABLE ibo ADD COLUMN max_videos INTEGER DEFAULT 10",
            "ALTER TABLE ibo ADD COLUMN max_video_size_mb INTEGER DEFAULT 50"
        ];
        foreach ($alterQueries as $q) {
            try { $pdo->exec($q); } catch (\Throwable $e) {}
        }
    }

    private function countClientVideos(\PDO $pdo, int $idCliente): int
    {
        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM videos 
                WHERE id_cliente = :cid
                AND filename NOT LIKE '/widgets/%'
                AND filename NOT LIKE 'http://%'
                AND filename NOT LIKE 'https://%'
                AND (
                    LOWER(filename) LIKE '%.mp4' OR 
                    LOWER(filename) LIKE '%.webm' OR 
                    LOWER(filename) LIKE '%.ogg' OR
                    LOWER(filename) LIKE '%.avi' OR
                    LOWER(filename) LIKE '%.mov' OR
                    LOWER(filename) LIKE '%.mkv'
                )
            ");
            $stmt->execute([':cid' => $idCliente]);
            return (int)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function index(Request $request): void
    {
        if (empty($_SESSION['cliente_logado']) && !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $pdo = Database::clients();
        $this->ensureTables($pdo);

        $allClients = [];
        $isAdmin = Auth::isAdmin();

        if ($isAdmin) {
            try {
                $stmtAll = $pdo->query("SELECT id, title, username, email, whatsapp FROM ibo ORDER BY id DESC");
                $allClients = $stmtAll->fetchAll();
            } catch (\Throwable $e) {}
        }

        $targetId = (int)$request->input('client_id', 0);
        if ($targetId > 0 && $isAdmin) {
            $idCliente = $targetId;
            $_SESSION['spy_client_id'] = $targetId;
        } elseif (!empty($_SESSION['spy_client_id']) && $isAdmin && empty($_SESSION['cliente_id'])) {
            $idCliente = (int)$_SESSION['spy_client_id'];
        } else {
            $idCliente = (int)($_SESSION['cliente_id'] ?? 0);
            if ($idCliente <= 0 && $isAdmin) {
                $idCliente = !empty($allClients) ? (int)$allClients[0]['id'] : 1;
            }
        }

        // Proxy Backend para Busca de Rádios (GET ?action=search_radios&q=...)
        if ($request->input('action') === 'search_radios' && $request->input('q') !== null) {
            header('Content-Type: application/json; charset=utf-8');
            $q = urlencode(trim((string)$request->input('q')));
            $servers = ['de1.api.radio-browser.info', 'at1.api.radio-browser.info', 'nl1.api.radio-browser.info'];
            $response = '[]';
            foreach ($servers as $server) {
                $url = "https://$server/json/stations/search?name=$q&limit=15";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Player99/2.3');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $result = curl_exec($ch);
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                if ($result !== false && $httpcode == 200) {
                    $response = $result;
                    break;
                }
            }
            echo $response;
            exit;
        }

        // Ações AJAX de Telas, Rádios e Playlists (POST ajax_action)
        if ($request->isPost() && $request->input('ajax_action') !== null) {
            header('Content-Type: application/json; charset=utf-8');
            $ajaxAction = $request->input('ajax_action');

            if ($ajaxAction === 'vincular_playlist') {
                $idTela = (int)$request->input('id_tela', 0);
                $idPlaylist = (int)$request->input('id_playlist', 0);
                $stmt = $pdo->prepare("UPDATE telas SET id_playlist = :pl, force_reload = 1 WHERE id = :id AND (id_cliente = :cid OR :adm = 1)");
                $ok = $stmt->execute([':pl' => $idPlaylist, ':id' => $idTela, ':cid' => $idCliente, ':adm' => $isAdmin ? 1 : 0]);
                echo json_encode(['success' => $ok]);
                exit;
            }

            if ($ajaxAction === 'set_audio') {
                $idTela = (int)$request->input('id_tela', 0);
                $audioMode = trim((string)$request->input('audio_mode', 'video'));
                $idRadio = (int)$request->input('id_radio', 0);
                $muted = ($audioMode === 'mute' || $audioMode === 'radio') ? 1 : 0;
                $stmt = $pdo->prepare("UPDATE telas SET audio_mode = :am, id_radio = :ir, muted = :m, force_reload = 1 WHERE id = :id AND (id_cliente = :cid OR :adm = 1)");
                $ok = $stmt->execute([':am' => $audioMode, ':ir' => $idRadio, ':m' => $muted, ':id' => $idTela, ':cid' => $idCliente, ':adm' => $isAdmin ? 1 : 0]);
                echo json_encode(['success' => $ok]);
                exit;
            }

            if ($ajaxAction === 'toggle_rotation') {
                $idTela = (int)$request->input('id_tela', 0);
                $rotation = (int)$request->input('rotation', 0);
                $stmt = $pdo->prepare("UPDATE telas SET rotation = :rot, force_reload = 1 WHERE id = :id AND (id_cliente = :cid OR :adm = 1)");
                $ok = $stmt->execute([':rot' => $rotation, ':id' => $idTela, ':cid' => $idCliente, ':adm' => $isAdmin ? 1 : 0]);
                echo json_encode(['success' => $ok, 'rotation' => $rotation, 'id_tela' => $idTela]);
                exit;
            }

            if ($ajaxAction === 'add_favorite_radio') {
                $nomeRadio = trim((string)$request->input('radio_name', ''));
                $urlRadio = trim((string)$request->input('radio_url', ''));
                if (!empty($nomeRadio) && !empty($urlRadio)) {
                    $stmtR = $pdo->prepare("INSERT INTO videos (id_cliente, title, filename, upload_date) VALUES (:cid, :t, :f, :dt)");
                    $stmtR->execute([
                        ':cid' => $idCliente,
                        ':t' => '📻 ' . $nomeRadio,
                        ':f' => $urlRadio,
                        ':dt' => date('Y-m-d H:i:s')
                    ]);
                    $newFavId = -(int)$pdo->lastInsertId();
                    echo json_encode(['success' => true, 'radio_id' => $newFavId, 'name' => $nomeRadio, 'url' => $urlRadio]);
                } else {
                    echo json_encode(['success' => false, 'msg' => 'Nome ou URL da rádio inválidos.']);
                }
                exit;
            }

            if ($ajaxAction === 'save_order') {
                $idPlaylist = (int)$request->input('id_playlist', 0);
                $videoDataStr = (string)$request->input('video_data', '[]');
                $stmtChk = $pdo->prepare("SELECT id FROM playlists WHERE id = :id AND id_cliente = :cid");
                $stmtChk->execute([':id' => $idPlaylist, ':cid' => $idCliente]);
                if ($stmtChk->fetch()) {
                    $pdo->prepare("DELETE FROM playlist_videos WHERE id_playlist = :id")->execute([':id' => $idPlaylist]);
                    $dataArr = json_decode($videoDataStr, true);
                    if (is_array($dataArr)) {
                        $seq = 1;
                        $stmtIns = $pdo->prepare("INSERT INTO playlist_videos (id_playlist, id_video, sequence, duration) VALUES (:pl, :vid, :seq, :dur)");
                        foreach ($dataArr as $item) {
                            $stmtIns->execute([
                                ':pl' => $idPlaylist,
                                ':vid' => (int)($item['id'] ?? 0),
                                ':seq' => $seq++,
                                ':dur' => (int)($item['duration'] ?? 15)
                            ]);
                        }
                    }
                    $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id_cliente = :cid")->execute([':cid' => $idCliente]);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'msg' => 'Playlist não encontrada.']);
                }
                exit;
            }
        }

        $client = Client::find($idCliente) ?: [
            'id' => $idCliente,
            'title' => 'Motorista / Parceiro',
            'username' => $_SESSION['cliente_user'] ?? 'motorista',
            'expire_date' => date('Y-m-d', strtotime('+30 days')),
            'max_videos' => 10,
            'max_video_size_mb' => 50,
            'commissionPercent' => 20,
            'affiliateClicks' => 0,
            'pixKey' => ''
        ];

        // Garante código de afiliado do cliente
        if (empty($client['affiliateCode'])) {
            $affCode = 'REF-' . strtoupper(substr(md5('p99_' . $idCliente), 0, 6));
            try {
                $pdo->prepare("UPDATE ibo SET affiliateCode = :c WHERE id = :id")->execute([':c' => $affCode, ':id' => $idCliente]);
                $client['affiliateCode'] = $affCode;
            } catch (\Throwable $e) {}
        }

        $maxVideosAllowed = (int)($client['max_videos'] ?? 10) ?: 10;
        $maxVideoSizeMb = (int)($client['max_video_size_mb'] ?? 50) ?: 50;
        $maxVideoSizeBytes = $maxVideoSizeMb * 1024 * 1024;
        $currentVideoCount = $this->countClientVideos($pdo, $idCliente);

        $uploadDir = dirname(__DIR__, 2) . '/uploads/clientes/' . $idCliente . '/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        @chmod($uploadDir, 0777);

        $tab = trim((string)$request->input('tab', 'telas'));
        $editListId = (int)$request->input('edit_list', 0);
        $msg = '';
        $err = '';

        if ($request->input('msg') === 'vinculada') {
            $msg = 'Nova tela vinculada com sucesso! O Player começará a tocar automaticamente.';
        } elseif ($request->input('msg') === 'reloaded') {
            $msg = 'Sinal de recarregamento enviado para a tela!';
        }
        if ($request->input('err') === 'uso') {
            $err = 'Erro: Este código já está em uso.';
        } elseif ($request->input('err') === 'vazio') {
            $err = 'Erro: O código da tela não pode ser vazio.';
        }
        if (!empty($_SESSION['sys_msg'])) {
            $msg = $_SESSION['sys_msg'];
            unset($_SESSION['sys_msg']);
        }

        // Processamento de formulários POST
        if ($request->isPost()) {
            $action = $request->input('action', '');

            // 1. Upload de Arquivos (múltiplos ou único)
            if (isset($_POST['upload_video']) || !empty($_FILES['video_files']) || $action === 'upload_media') {
                $tab = 'arquivos';
                if (!empty($_FILES['video_files']['name'][0])) {
                    $allowed = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
                    $videoExts = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv'];
                    $successCount = 0;
                    $errorCount = 0;
                    $totalFiles = count($_FILES['video_files']['name']);
                    $currentVideoCount = $this->countClientVideos($pdo, $idCliente);

                    for ($i = 0; $i < $totalFiles; $i++) {
                        if ($_FILES['video_files']['error'][$i] === UPLOAD_ERR_OK) {
                            $origName = $_FILES['video_files']['name'][$i];
                            $fileSize = $_FILES['video_files']['size'][$i];
                            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                            $isVideo = in_array($ext, $videoExts);

                            if ($isVideo && $currentVideoCount >= $maxVideosAllowed) {
                                $errorCount++;
                                $err = "Limite atingido: Você já possui {$currentVideoCount} vídeos (limite: {$maxVideosAllowed}).";
                                continue;
                            }
                            if ($fileSize > $maxVideoSizeBytes) {
                                $errorCount++;
                                $err = "O arquivo {$origName} ultrapassa o limite permitido de {$maxVideoSizeMb}MB.";
                                continue;
                            }
                            if (in_array($ext, $allowed)) {
                                $newFilename = uniqid() . '_' . $i . '.' . $ext;
                                if (@move_uploaded_file($_FILES['video_files']['tmp_name'][$i], $uploadDir . $newFilename)) {
                                    @chmod($uploadDir . $newFilename, 0666);
                                    $title = pathinfo($origName, PATHINFO_FILENAME);
                                    $stmtIns = $pdo->prepare("INSERT INTO videos (id_cliente, title, filename, upload_date) VALUES (:cid, :t, :f, :dt)");
                                    $stmtIns->execute([
                                        ':cid' => $idCliente,
                                        ':t' => $title,
                                        ':f' => $newFilename,
                                        ':dt' => date('Y-m-d H:i:s')
                                    ]);
                                    $successCount++;
                                    if ($isVideo) {
                                        $currentVideoCount++;
                                    }
                                } else {
                                    $errorCount++;
                                    $err = "Falha ao salvar o arquivo {$origName}.";
                                }
                            } else {
                                $errorCount++;
                                $err = "Extensão não permitida (.{$ext}).";
                            }
                        } else {
                            $errorCount++;
                            $err = "Erro no envio do arquivo.";
                        }
                    }

                    if ($successCount > 0) {
                        $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id_cliente = :cid")->execute([':cid' => $idCliente]);
                        $msg = "{$successCount} arquivo(s) enviado(s) com sucesso!";
                        $_SESSION['sys_msg'] = $msg;
                    }

                    if (isset($_POST['ajax_upload'])) {
                        header('Content-Type: application/json; charset=utf-8');
                        echo json_encode([
                            'success' => ($successCount > 0 && $errorCount === 0),
                            'successCount' => $successCount,
                            'errorCount' => $errorCount,
                            'msg' => $err ?: $msg
                        ]);
                        exit;
                    }
                }
            }

            // Apagar Arquivo Individual
            if (isset($_POST['apagar_video']) || $action === 'delete_media') {
                $tab = 'arquivos';
                $idVideo = (int)($request->input('id_video') ?: $request->input('video_id', 0));
                $stmtV = $pdo->prepare("SELECT filename FROM videos WHERE id = :id AND id_cliente = :cid");
                $stmtV->execute([':id' => $idVideo, ':cid' => $idCliente]);
                $rowV = $stmtV->fetch();
                if ($rowV) {
                    $file = $uploadDir . $rowV['filename'];
                    if (is_file($file)) @unlink($file);
                    $pdo->prepare("DELETE FROM videos WHERE id = :id AND id_cliente = :cid")->execute([':id' => $idVideo, ':cid' => $idCliente]);
                    $pdo->prepare("DELETE FROM playlist_videos WHERE id_video = :id")->execute([':id' => $idVideo]);
                    $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id_cliente = :cid")->execute([':cid' => $idCliente]);
                    $msg = 'Arquivo apagado com sucesso!';
                }
            }

            // Apagar Múltiplos Arquivos
            if (isset($_POST['apagar_multiplos'])) {
                $tab = 'arquivos';
                $selected = $_POST['selected_files'] ?? [];
                if (!empty($selected) && is_array($selected)) {
                    $apagados = 0;
                    foreach ($selected as $idVideo) {
                        $idVideo = (int)$idVideo;
                        $stmtV = $pdo->prepare("SELECT filename FROM videos WHERE id = :id AND id_cliente = :cid");
                        $stmtV->execute([':id' => $idVideo, ':cid' => $idCliente]);
                        $rowV = $stmtV->fetch();
                        if ($rowV) {
                            $file = $uploadDir . $rowV['filename'];
                            if (is_file($file)) @unlink($file);
                            $pdo->prepare("DELETE FROM videos WHERE id = :id AND id_cliente = :cid")->execute([':id' => $idVideo, ':cid' => $idCliente]);
                            $pdo->prepare("DELETE FROM playlist_videos WHERE id_video = :id")->execute([':id' => $idVideo]);
                            $apagados++;
                        }
                    }
                    $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id_cliente = :cid")->execute([':cid' => $idCliente]);
                    $msg = "{$apagados} arquivo(s) apagado(s) com sucesso!";
                } else {
                    $err = 'Nenhum arquivo selecionado.';
                }
            }

            // Adicionar Rádio via Buscador Automático
            if (isset($_POST['add_radio_submit'])) {
                $tab = 'arquivos';
                $nomeRadio = trim((string)$request->input('add_radio_name', ''));
                $urlRadio = trim((string)$request->input('add_radio_url', ''));
                if (!empty($nomeRadio) && !empty($urlRadio)) {
                    $stmtR = $pdo->prepare("INSERT INTO videos (id_cliente, title, filename, upload_date) VALUES (:cid, :t, :f, :dt)");
                    $stmtR->execute([
                        ':cid' => $idCliente,
                        ':t' => '📻 ' . $nomeRadio,
                        ':f' => $urlRadio,
                        ':dt' => date('Y-m-d H:i:s')
                    ]);
                    $msg = 'Rádio adicionada com sucesso aos seus arquivos!';
                }
            }

            // Criar Nova Playlist
            if (isset($_POST['criar_lista'])) {
                $nomeLista = trim((string)$request->input('nome_lista', 'Nova Lista'));
                $stmtPl = $pdo->prepare("INSERT INTO playlists (id_cliente, title, creation_date, is_active) VALUES (:cid, :t, :dt, 0)");
                $stmtPl->execute([
                    ':cid' => $idCliente,
                    ':t' => $nomeLista,
                    ':dt' => date('Y-m-d H:i:s')
                ]);
                $newListId = (int)$pdo->lastInsertId();

                $stmtCnt = $pdo->prepare("SELECT COUNT(*) FROM playlists WHERE id_cliente = :cid");
                $stmtCnt->execute([':cid' => $idCliente]);
                if ((int)$stmtCnt->fetchColumn() === 1) {
                    $pdo->prepare("UPDATE playlists SET is_active = 1 WHERE id = :id")->execute([':id' => $newListId]);
                }

                $this->redirect('/painel-cliente?client_id=' . $idCliente . '&tab=lista&edit_list=' . $newListId);
                return;
            }

            // Tornar Playlist Ativa
            if (isset($_POST['set_active'])) {
                $tab = 'lista';
                $idActive = (int)$request->input('id_playlist', 0);
                $pdo->prepare("UPDATE playlists SET is_active = 0 WHERE id_cliente = :cid")->execute([':cid' => $idCliente]);
                $pdo->prepare("UPDATE playlists SET is_active = 1 WHERE id = :id AND id_cliente = :cid")->execute([':id' => $idActive, ':cid' => $idCliente]);
                $pdo->prepare("UPDATE telas SET id_playlist = :pl, force_reload = 1 WHERE id_cliente = :cid")->execute([':pl' => $idActive, ':cid' => $idCliente]);
                $msg = 'Lista definida como ativa! O Player tocará esta lista.';
            }

            // Apagar Playlist
            if (isset($_POST['apagar_lista'])) {
                $tab = 'lista';
                $idApagar = (int)$request->input('id_playlist', 0);
                $pdo->prepare("DELETE FROM playlists WHERE id = :id AND id_cliente = :cid")->execute([':id' => $idApagar, ':cid' => $idCliente]);
                $pdo->prepare("DELETE FROM playlist_videos WHERE id_playlist = :id")->execute([':id' => $idApagar]);
                $msg = 'Playlist apagada com sucesso.';
            }

            // Adicionar Novo Canal RSS em Entretenimentos
            if (isset($_POST['add_custom_rss'])) {
                $tab = 'lista';
                $rssNome = trim((string)$request->input('rss_nome', ''));
                $rssUrl = trim((string)$request->input('rss_url', ''));
                if (!empty($rssNome) && !empty($rssUrl)) {
                    try {
                        $pdoRadios = Database::radios();
                        try { $pdoRadios->exec("ALTER TABLE entretenimentos ADD COLUMN rss_url TEXT"); } catch (\Throwable $e) {}
                        $stmtRss = $pdoRadios->prepare("INSERT INTO entretenimentos (nome, url, icon, is_visible, rss_url) VALUES (:n, '/widgets/temp', 'fas fa-rss', 1, :r)");
                        $stmtRss->execute([':n' => $rssNome, ':r' => $rssUrl]);
                        $rssId = (int)$pdoRadios->lastInsertId();
                        $widgetUrl = '/widgets/rss_' . $rssId . '.php';
                        $pdoRadios->prepare("UPDATE entretenimentos SET url = :u WHERE id = :id")->execute([':u' => $widgetUrl, ':id' => $rssId]);
                        $msg = 'Canal RSS (' . htmlspecialchars($rssNome) . ') adicionado aos Entretenimentos!';
                    } catch (\Throwable $e) {
                        $err = 'Erro ao salvar canal RSS.';
                    }
                }
            }

            // Vincular Nova Tela
            if (isset($_POST['vincular_tela']) || $action === 'add_screen') {
                $tab = 'telas';
                $nomeTela = trim((string)($request->input('nome_tela') ?: $request->input('title', 'Nova Tela')));
                $code = strtoupper(trim((string)($request->input('codigo_tela') ?: $request->input('player_code', ''))));
                if (empty($code)) {
                    $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
                }
                $rotation = (int)$request->input('rotation', 0);

                $stmtChk = $pdo->prepare("SELECT id FROM telas WHERE player_code = :c");
                $stmtChk->execute([':c' => $code]);
                if ($stmtChk->fetch()) {
                    $err = 'Erro: Este código de player já está em uso.';
                } else {
                    Screen::create([
                        'id_cliente' => $idCliente,
                        'player_code' => $code,
                        'title' => $nomeTela,
                        'nome_tela' => $nomeTela,
                        'rotation' => $rotation,
                        'audio_mode' => 'video',
                        'ultimo_ping' => date('Y-m-d H:i:s')
                    ]);
                    $msg = "Nova tela ({$code}) vinculada com sucesso!";
                }
            }

            // Forçar recarregamento da tela
            if (isset($_POST['force_reload'])) {
                $tab = 'telas';
                $idTela = (int)$request->input('id_tela', 0);
                $pdo->prepare("UPDATE telas SET force_reload = 1 WHERE id = :id AND id_cliente = :cid")->execute([':id' => $idTela, ':cid' => $idCliente]);
                $msg = 'Sinal de recarregamento enviado para a tela!';
            }

            // Apagar Tela
            if (isset($_POST['apagar_tela'])) {
                $tab = 'telas';
                $idTela = (int)$request->input('id_tela', 0);
                $pdo->prepare("DELETE FROM telas WHERE id = :id AND id_cliente = :cid")->execute([':id' => $idTela, ':cid' => $idCliente]);
                $msg = 'Tela removida com sucesso.';
            }

            // Área do Afiliado: Atualizar Chave PIX
            if (isset($_POST['update_pix'])) {
                $tab = 'afiliado';
                $pixKey = trim((string)$request->input('pixKey', ''));
                $pdo->prepare("UPDATE ibo SET pixKey = :pk WHERE id = :id")->execute([':pk' => $pixKey, ':id' => $idCliente]);
                $client['pixKey'] = $pixKey;
                $msg = 'Chave PIX atualizada com sucesso!';
            }

            // Área do Afiliado: Solicitar Saque
            if (isset($_POST['withdraw'])) {
                $tab = 'afiliado';
                $saldoAtual = $this->getAffiliateBalance($pdo, $idCliente);
                if ($saldoAtual > 0) {
                    if (empty($client['pixKey'])) {
                        $err = 'Configure sua chave PIX antes de solicitar o saque.';
                    } else {
                        $stmtSq = $pdo->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description) VALUES (:id, :amt, 'WITHDRAW_PENDING', 'Saque Solicitado')");
                        $stmtSq->execute([':id' => $idCliente, ':amt' => $saldoAtual]);
                        $msg = 'Saque de R$ ' . number_format($saldoAtual, 2, ',', '.') . ' solicitado com sucesso!';
                    }
                }
            }

            // Configurações: Atualizar Senha
            if (isset($_POST['atualizar_senha'])) {
                $tab = 'config';
                $novaSenha = trim((string)$request->input('nova_senha', ''));
                if (!empty($novaSenha)) {
                    $pdo->prepare("UPDATE ibo SET password = :p WHERE id = :id")->execute([
                        ':p' => Auth::hashPassword($novaSenha),
                        ':id' => $idCliente
                    ]);
                    $msg = 'Senha atualizada com sucesso!';
                }
            }

            // Configurações: Atualizar Dados de Faturamento
            if (isset($_POST['atualizar_faturamento'])) {
                $tab = 'config';
                $stmtFat = $pdo->prepare("INSERT OR REPLACE INTO faturamento_cliente (id_cliente, nome_razao, cpf_cnpj, endereco, whats) VALUES (:id, :n, :c, :e, :w)");
                $stmtFat->execute([
                    ':id' => $idCliente,
                    ':n' => trim((string)$request->input('nome_razao', '')),
                    ':c' => trim((string)$request->input('cpf_cnpj', '')),
                    ':e' => trim((string)$request->input('endereco', '')),
                    ':w' => trim((string)$request->input('whats', ''))
                ]);
                $msg = 'Dados de faturamento atualizados com sucesso!';
            }

            // Configurações: Salvar Customização da Franquia
            if ($action === 'save_customization') {
                $tab = 'config';
                $brandName = trim((string)$request->input('brand_name', ''));
                $primaryColor = trim((string)$request->input('primary_color', '#34e2e4'));
                $supportWhatsapp = trim((string)$request->input('support_whatsapp', ''));
                $supportEmail = trim((string)$request->input('support_email', ''));
                $customAnnouncement = trim((string)$request->input('custom_announcement', ''));

                $logoPath = null;
                if (!empty($_FILES['custom_logo']['name']) && $_FILES['custom_logo']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['custom_logo']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                        $logoName = 'logo_custom_' . time() . '.' . $ext;
                        if (@move_uploaded_file($_FILES['custom_logo']['tmp_name'], $uploadDir . $logoName)) {
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

                $stmtUpsert = $pdo->prepare("INSERT OR REPLACE INTO cliente_customizacoes (id_cliente, brand_name, logo_path, primary_color, support_whatsapp, support_email, custom_announcement, updated_at)
                    VALUES (:cid, :bname, :logo, :color, :whats, :email, :ann, datetime('now'))");
                $stmtUpsert->execute([
                    ':cid' => $idCliente,
                    ':bname' => $brandName,
                    ':logo' => $logoPath,
                    ':color' => $primaryColor,
                    ':whats' => $supportWhatsapp,
                    ':email' => $supportEmail,
                    ':ann' => $customAnnouncement
                ]);
                $msg = 'Personalização salva com sucesso!';
            }
        }

        // Sincroniza entretenimentos globais (widgets) para os vídeos do cliente
        $this->syncEntertainments($pdo, $idCliente);

        // Carrega Telas
        $stmtTelas = $pdo->prepare("SELECT * FROM telas WHERE id_cliente = :cid ORDER BY id DESC");
        $stmtTelas->execute([':cid' => $idCliente]);
        $screens = $stmtTelas->fetchAll();

        // Carrega Vídeos / Arquivos / Entretenimentos / Rádios
        $stmtVids = $pdo->prepare("SELECT * FROM videos WHERE id_cliente = :cid ORDER BY id DESC");
        $stmtVids->execute([':cid' => $idCliente]);
        $videos = $stmtVids->fetchAll();

        $arquivosOnly = [];
        $entOnly = [];
        $radiosCliente = [];
        foreach ($videos as $v) {
            $fn = (string)($v['filename'] ?? '');
            if (strpos($fn, '/widgets/') === 0 || strpos($fn, 'http') === 0) {
                $entOnly[] = $v;
                if (strpos($fn, 'http') === 0) {
                    $radiosCliente[] = $v;
                }
            } else {
                $arquivosOnly[] = $v;
            }
        }

        // Carrega Playlists
        $stmtPl = $pdo->prepare("
            SELECT p.*, (SELECT COUNT(*) FROM playlist_videos pv WHERE pv.id_playlist = p.id) as qtd_videos 
            FROM playlists p WHERE p.id_cliente = :cid ORDER BY p.id DESC
        ");
        $stmtPl->execute([':cid' => $idCliente]);
        $playlistsList = $stmtPl->fetchAll();

        // Se estiver editando uma playlist
        $playlistEdit = null;
        $playlistVideosData = [];
        if ($editListId > 0) {
            $stmtPe = $pdo->prepare("SELECT * FROM playlists WHERE id = :id AND id_cliente = :cid");
            $stmtPe->execute([':id' => $editListId, ':cid' => $idCliente]);
            $playlistEdit = $stmtPe->fetch();
            if ($playlistEdit) {
                $stmtPv = $pdo->prepare("
                    SELECT v.*, pv.sequence, pv.duration 
                    FROM playlist_videos pv 
                    JOIN videos v ON pv.id_video = v.id 
                    WHERE pv.id_playlist = :pl 
                    ORDER BY pv.sequence ASC
                ");
                $stmtPv->execute([':pl' => $editListId]);
                $playlistVideosData = $stmtPv->fetchAll();
            }
        }

        // Rádios Globais (garante streams HTTPS funcionais e carrega URL p/ preview)
        $radiosGlobais = [];
        try {
            $pdoRadios = Database::radios();
            $this->ensureDefaultRadios($pdoRadios);
            $radiosGlobais = $pdoRadios->query("SELECT id, nome, url FROM radios WHERE is_visible = 1 ORDER BY nome ASC")->fetchAll();
        } catch (\Throwable $e) {}

        // Faturamento e Faturas
        $faturamento = ['nome_razao' => '', 'cpf_cnpj' => '', 'endereco' => '', 'whats' => ''];
        $stmtFat = $pdo->prepare("SELECT * FROM faturamento_cliente WHERE id_cliente = :id");
        $stmtFat->execute([':id' => $idCliente]);
        if ($rowFat = $stmtFat->fetch()) {
            $faturamento = $rowFat;
        }

        $faturas = [];
        $totalAcumulado = 0.0;
        $stmtFaturas = $pdo->prepare("SELECT * FROM faturas WHERE id_cliente = :id ORDER BY id DESC");
        $stmtFaturas->execute([':id' => $idCliente]);
        foreach ($stmtFaturas->fetchAll() as $rowF) {
            $faturas[] = $rowF;
            if (($rowF['status'] ?? '') === 'PAGA' && date('m', strtotime($rowF['data_gerada'] ?? 'now')) === date('m')) {
                $totalAcumulado += (float)$rowF['valor_total'];
            }
        }
        $totalPrevisto = count($screens) * 100.0;

        // Saldo Afiliado, Indicados e Extrato
        $affiliateBalance = $this->getAffiliateBalance($pdo, $idCliente);
        $affiliateReferrals = [];
        $affiliateTransactions = [];
        try {
            $stmtRefList = $pdo->prepare("SELECT id, title, username, active, expire_date FROM ibo WHERE referredById = :id ORDER BY id DESC");
            $stmtRefList->execute([':id' => $idCliente]);
            $affiliateReferrals = $stmtRefList->fetchAll();

            $stmtTxList = $pdo->prepare("SELECT * FROM affiliate_transactions WHERE userId = :id ORDER BY id DESC LIMIT 30");
            $stmtTxList->execute([':id' => $idCliente]);
            $affiliateTransactions = $stmtTxList->fetchAll();
        } catch (\Throwable $e) {}

        // Customização da Franquia
        $stmtC = $pdo->prepare("SELECT * FROM cliente_customizacoes WHERE id_cliente = :cid");
        $stmtC->execute([':cid' => $idCliente]);
        $customization = $stmtC->fetch();
        $defaultLogo = \App\Core\View::logoUrl();
        if (!$customization) {
            $customization = [
                'brand_name' => $client['title'] ?? 'Player99',
                'logo_path' => $defaultLogo,
                'primary_color' => '#00f3ff',
                'support_whatsapp' => $client['whatsapp'] ?? '',
                'support_email' => $client['email'] ?? '',
                'custom_announcement' => ''
            ];
        } elseif (empty($customization['logo_path']) || !file_exists(dirname(__DIR__, 2) . '/' . ltrim($customization['logo_path'], '/'))) {
            $customization['logo_path'] = $defaultLogo;
        }

        $this->render('client/dashboard', [
            'client' => $client,
            'customization' => $customization,
            'screens' => $screens,
            'videos' => $videos,
            'arquivosOnly' => $arquivosOnly,
            'entOnly' => $entOnly,
            'radiosCliente' => $radiosCliente,
            'radiosGlobais' => $radiosGlobais,
            'playlistsList' => $playlistsList,
            'playlistEdit' => $playlistEdit,
            'playlistVideosData' => $playlistVideosData,
            'editListId' => $editListId,
            'faturamento' => $faturamento,
            'faturas' => $faturas,
            'totalAcumulado' => $totalAcumulado,
            'totalPrevisto' => $totalPrevisto,
            'affiliateBalance' => $affiliateBalance,
            'affiliateReferrals' => $affiliateReferrals,
            'affiliateTransactions' => $affiliateTransactions,
            'currentVideoCount' => $currentVideoCount,
            'maxVideosAllowed' => $maxVideosAllowed,
            'maxVideoSizeMb' => $maxVideoSizeMb,
            'maxVideoSizeBytes' => $maxVideoSizeBytes,
            'allClients' => $allClients,
            'isAdmin' => $isAdmin,
            'selectedClientId' => $idCliente,
            'tab' => $tab,
            'msg' => $msg,
            'err' => $err
        ], null);
    }

    private function getAffiliateBalance(\PDO $pdo, int $idCliente): float
    {
        $saldo = 0.0;
        try {
            $stmt = $pdo->prepare("SELECT amount, type FROM affiliate_transactions WHERE userId = :id");
            $stmt->execute([':id' => $idCliente]);
            foreach ($stmt->fetchAll() as $tx) {
                if ($tx['type'] === 'COMMISSION') {
                    $saldo += (float)$tx['amount'];
                } elseif ($tx['type'] === 'PAYOUT' || $tx['type'] === 'WITHDRAW_PENDING') {
                    $saldo -= (float)$tx['amount'];
                }
            }
        } catch (\Throwable $e) {}
        return max(0.0, $saldo);
    }

    private function syncEntertainments(\PDO $pdo, int $idCliente): void
    {
        try {
            $pdoRadios = Database::radios();
            $pdoRadios->exec("CREATE TABLE IF NOT EXISTS entretenimentos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                url TEXT NOT NULL,
                icon TEXT DEFAULT 'fas fa-globe',
                is_visible INTEGER DEFAULT 1,
                rss_url TEXT,
                bg_image TEXT
            )");
            try { $pdoRadios->exec("ALTER TABLE entretenimentos ADD COLUMN rss_url TEXT"); } catch (\Throwable $e) {}
            try { $pdoRadios->exec("ALTER TABLE entretenimentos ADD COLUMN bg_image TEXT"); } catch (\Throwable $e) {}

            $defaults = [
                ['Hora Atual', '/widgets/hora.php', 'fas fa-clock', ''],
                ['Frases e Pensamentos', '/widgets/frases.php', 'fas fa-quote-left', ''],
                ['RSS: Notícias (Últimas - G1)', '/widgets/noticias.php', 'fas fa-newspaper', 'https://g1.globo.com/rss/g1/'],
                ['RSS: Carros & Motos (AutoEsporte)', '/widgets/carros.php', 'fas fa-car', 'https://g1.globo.com/dynamo/carros/rss2.xml'],
                ['RSS: Esportes & Futebol (GE Globo)', '/widgets/rss_esportes.php', 'fas fa-futbol', 'https://ge.globo.com/rss/ge/'],
                ['RSS: Economia & Negócios (G1)', '/widgets/rss_economia.php', 'fas fa-chart-line', 'https://g1.globo.com/dynamo/economia/rss2.xml'],
                ['RSS: Tecnologia & Inovação (G1)', '/widgets/rss_tecnologia.php', 'fas fa-microchip', 'https://g1.globo.com/dynamo/tecnologia/rss2.xml'],
                ['RSS: Pop, Música & Cinema (G1)', '/widgets/rss_pop.php', 'fas fa-music', 'https://g1.globo.com/dynamo/pop-arte/rss2.xml'],
                ['RSS: Mundo & Internacional (G1)', '/widgets/rss_mundo.php', 'fas fa-globe-americas', 'https://g1.globo.com/dynamo/mundo/rss2.xml'],
                ['RSS: Saúde & Bem-Estar (G1)', '/widgets/rss_saude.php', 'fas fa-heartbeat', 'https://g1.globo.com/dynamo/ciencia-e-saude/rss2.xml'],
                ['RSS: Turismo & Viagem (G1)', '/widgets/rss_turismo.php', 'fas fa-plane', 'https://g1.globo.com/dynamo/turismo-e-viagem/rss2.xml'],
                ['RSS: Loterias & Curiosidades', '/widgets/rss_loterias.php', 'fas fa-star', 'https://g1.globo.com/dynamo/loterias/rss2.xml']
            ];

            $stmtChkEnt = $pdoRadios->prepare("SELECT id FROM entretenimentos WHERE url = :u");
            $stmtInsEnt = $pdoRadios->prepare("INSERT INTO entretenimentos (nome, url, icon, is_visible, rss_url) VALUES (:n, :u, :i, 1, :r)");
            $stmtUpdEnt = $pdoRadios->prepare("UPDATE entretenimentos SET rss_url = :r WHERE url = :u AND (rss_url IS NULL OR rss_url = '')");

            foreach ($defaults as $d) {
                $stmtChkEnt->execute([':u' => $d[1]]);
                if (!$stmtChkEnt->fetch()) {
                    $stmtInsEnt->execute([':n' => $d[0], ':u' => $d[1], ':i' => $d[2], ':r' => $d[3]]);
                } elseif (!empty($d[3])) {
                    $stmtUpdEnt->execute([':r' => $d[3], ':u' => $d[1]]);
                }
            }

            $ents = $pdoRadios->query("SELECT * FROM entretenimentos WHERE is_visible = 1 ORDER BY id DESC")->fetchAll();
            foreach ($ents as $ent) {
                $prefix = !empty($ent['rss_url']) ? '📡 ' : '⭐ ';
                $cleanName = preg_replace('/^(⭐|📡)\s*/u', '', $ent['nome']);
                $fullTitle = $prefix . $cleanName;

                $stmtChk = $pdo->prepare("SELECT id, title FROM videos WHERE id_cliente = :cid AND filename = :fn");
                $stmtChk->execute([':cid' => $idCliente, ':fn' => $ent['url']]);
                $existing = $stmtChk->fetch();
                if (!$existing) {
                    $stmtIns = $pdo->prepare("INSERT INTO videos (id_cliente, title, filename, upload_date) VALUES (:cid, :t, :fn, :dt)");
                    $stmtIns->execute([
                        ':cid' => $idCliente,
                        ':t' => $fullTitle,
                        ':fn' => $ent['url'],
                        ':dt' => date('Y-m-d H:i:s')
                    ]);
                }
            }
        } catch (\Throwable $e) {}
    }

    private function ensureDefaultRadios(\PDO $pdoRadios): void
    {
        try {
            $pdoRadios->exec("CREATE TABLE IF NOT EXISTS radios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                url TEXT NOT NULL,
                is_visible INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            // Corrige URLs antigas do StreamTheWorld com IP/servidor expirado para o balanceador oficial HTTPS
            $fixes = [
                'MIXFM_SPAAC.aac' => 'https://playerservices.streamtheworld.com/api/livestream-redirect/MIXFM_SPAAC.aac',
                'ALPHA_FMAAC.aac' => 'https://playerservices.streamtheworld.com/api/livestream-redirect/ALPHA_FMAAC.aac',
                'CBN_SPAAC.aac' => 'https://playerservices.streamtheworld.com/api/livestream-redirect/CBN_SPAAC.aac',
                'TRANSAMERICA_SPAAC.aac' => 'https://playerservices.streamtheworld.com/api/livestream-redirect/TRANSAMERICA_SPAAC.aac'
            ];
            foreach ($fixes as $suffix => $newUrl) {
                $stmtFix = $pdoRadios->prepare("UPDATE radios SET url = :newu WHERE url LIKE :like");
                $stmtFix->execute([':newu' => $newUrl, ':like' => '%' . $suffix]);
            }

            $defaults = [
                ['Antena 1 FM', 'https://antena1.newradio.it/stream'],
                ['Mix FM', 'https://playerservices.streamtheworld.com/api/livestream-redirect/MIXFM_SPAAC.aac'],
                ['Alpha FM', 'https://playerservices.streamtheworld.com/api/livestream-redirect/ALPHA_FMAAC.aac'],
                ['Jovem Pan FM', 'https://playerservices.streamtheworld.com/api/livestream-redirect/JP_SP_FMAAC.aac'],
                ['89 FM A Rádio Rock', 'https://playerservices.streamtheworld.com/api/livestream-redirect/RADIO_89FM_ADP.aac'],
                ['CBN São Paulo', 'https://playerservices.streamtheworld.com/api/livestream-redirect/CBN_SPAAC.aac'],
                ['Transamérica FM', 'https://playerservices.streamtheworld.com/api/livestream-redirect/TRANSAMERICA_SPAAC.aac'],
                ['Hunter FM - Sertanejo', 'https://live.hunter.fm/sertanejo_normal'],
                ['Hunter FM - Pop Hits', 'https://live.hunter.fm/pop_normal'],
                ['Hunter FM - Pagode', 'https://live.hunter.fm/pagode_normal'],
                ['Hunter FM - Anos 80', 'https://live.hunter.fm/80s_normal']
            ];

            $stmtChk = $pdoRadios->prepare("SELECT id FROM radios WHERE url = :u OR LOWER(nome) = LOWER(:n) LIMIT 1");
            $stmtIns = $pdoRadios->prepare("INSERT INTO radios (nome, url, is_visible) VALUES (:n, :u, 1)");
            foreach ($defaults as $r) {
                $stmtChk->execute([':u' => $r[1], ':n' => $r[0]]);
                if (!$stmtChk->fetch()) {
                    $stmtIns->execute([':n' => $r[0], ':u' => $r[1]]);
                }
            }
        } catch (\Throwable $e) {}
    }
}