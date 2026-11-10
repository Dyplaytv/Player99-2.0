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
            $stmt = $pdo->query("SELECT t.*, i.username as client_username, i.title as client_name, i.whatsapp as client_whatsapp, i.expire_date as client_expire_date
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

    public function updateExpire(Request $request, $id): void
    {
        // Somente administradores podem alterar a validade de uma tela
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->json(['status' => 'forbidden'], 403);
        }

        Screen::ensureSchema();
        $pdo = Database::clients();
        $date = trim((string)$request->input('expire_date', ''));

        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->json(['status' => 'error', 'message' => 'Data inválida'], 422);
        }

        $pdo->prepare("UPDATE telas SET expire_date = :d, force_reload = 1 WHERE id = :id")
            ->execute([':d' => $date !== '' ? $date : null, ':id' => (int)$id]);

        $screen = Screen::find((int)$id);
        $locked = $screen ? $this->isScreenLocked($pdo, $screen) : false;

        $this->json([
            'status' => 'success',
            'expire_date' => $date,
            'locked' => $locked ? 1 : 0
        ]);
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

    private function resolveRadioInfo(\PDO $pdo, array $screen): array
    {
        $audioMode = $screen['audio_mode'] ?? 'video';
        if ($audioMode === 'video' && !empty($screen['muted'])) {
            $audioMode = 'mute';
        }
        $idRadio = (int)($screen['id_radio'] ?? 0);
        $radioUrl = '';
        $radioName = '';

        if ($audioMode === 'radio' && $idRadio !== 0) {
            if ($idRadio > 0) {
                try {
                    $pdoR = Database::radios();
                    $stmtR = $pdoR->prepare("SELECT nome, url FROM radios WHERE id = :id LIMIT 1");
                    $stmtR->execute([':id' => $idRadio]);
                    $rRow = $stmtR->fetch();
                    if ($rRow) {
                        $radioName = (string)$rRow['nome'];
                        $radioUrl = (string)$rRow['url'];
                        if (preg_match('/(MIXFM_SPAAC|ALPHA_FMAAC|CBN_SPAAC|TRANSAMERICA_SPAAC)\.aac/i', $radioUrl, $m)) {
                            $radioUrl = 'https://playerservices.streamtheworld.com/api/livestream-redirect/' . strtoupper($m[1]) . '.aac';
                        }
                    }
                } catch (\Throwable $e) {}
            } else {
                try {
                    $stmtFav = $pdo->prepare("SELECT title, filename FROM videos WHERE id = :id LIMIT 1");
                    $stmtFav->execute([':id' => abs($idRadio)]);
                    $vRow = $stmtFav->fetch();
                    if ($vRow) {
                        $radioName = trim(str_replace('📻', '', (string)$vRow['title']));
                        $radioUrl = (string)$vRow['filename'];
                    }
                } catch (\Throwable $e) {}
            }
        }

        return [
            'audio_mode' => $audioMode,
            'id_radio' => $idRadio,
            'radio_url' => $radioUrl,
            'radio_name' => $radioName
        ];
    }

    /**
     * Tela vencida/bloqueada: vencimento da própria tela (recarga por crédito) tem prioridade;
     * sem vencimento próprio, vale o vencimento do motorista. Motorista inativo (bloqueado) também trava.
     */
    private function isScreenLocked(\PDO $pdo, array $screen): bool
    {
        $today = date('Y-m-d');
        try {
            $cid = (int)($screen['id_cliente'] ?? 0);
            $cli = null;
            if ($cid > 0) {
                $st = $pdo->prepare("SELECT active, expire_date FROM ibo WHERE id = :id LIMIT 1");
                $st->execute([':id' => $cid]);
                $cli = $st->fetch() ?: null;
            }
            if ($cli && isset($cli['active']) && (int)$cli['active'] === 0) {
                return true;
            }
            $exp = trim((string)($screen['expire_date'] ?? ''));
            if ($exp === '' && $cli) {
                $exp = trim((string)($cli['expire_date'] ?? ''));
            }
            return ($exp !== '' && substr($exp, 0, 10) < $today);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getLockMedia(): array
    {
        $items = [];
        try {
            $dbPath = dirname(__DIR__, 2) . '/api/db/studiolivecode_bloqueio.db';
            if (is_file($dbPath)) {
                $pdoL = new \PDO("sqlite:{$dbPath}", null, null, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]);
                foreach ($pdoL->query("SELECT * FROM bloqueio ORDER BY id ASC")->fetchAll() as $row) {
                    $path = '/' . ltrim((string)$row['caminho'], '/');
                    $items[] = ['id' => 0, 'title' => $row['title'] ?? 'Bloqueio', 'filename' => $path, 'duration' => 15];
                }
            }
        } catch (\Throwable $e) {}
        return $items;
    }

    public function player(Request $request, string $codigo = ''): void
    {
        if ($codigo === '') {
            $codigo = trim((string)$request->input('codigo', ''));
        }
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
        $cid = (int)($screen['id_cliente'] ?: $screen['id']);
        $plId = (int)($screen['id_playlist'] ?? 0);
        try {
            if ($plId <= 0) {
                $stmtAct = $pdo->prepare("SELECT id FROM playlists WHERE id_cliente = :cid AND is_active = 1 LIMIT 1");
                $stmtAct->execute([':cid' => $cid]);
                $plId = (int)$stmtAct->fetchColumn();
            }
            if ($plId > 0) {
                $stmtPl = $pdo->prepare("
                    SELECT v.*, pv.duration 
                    FROM playlist_videos pv 
                    JOIN videos v ON pv.id_video = v.id 
                    WHERE pv.id_playlist = :pl 
                      AND v.filename NOT LIKE 'http%'
                    ORDER BY pv.sequence ASC
                ");
                $stmtPl->execute([':pl' => $plId]);
                $videos = $stmtPl->fetchAll();
            }
            if (empty($videos)) {
                $stmt = $pdo->prepare("SELECT * FROM videos WHERE id_cliente = :cid AND filename NOT LIKE '/widgets/%' AND filename NOT LIKE 'http%' ORDER BY id ASC");
                $stmt->execute([':cid' => $cid]);
                $videos = $stmt->fetchAll();
            }
        } catch (\Throwable $e) {}

        $radioInfo = $this->resolveRadioInfo($pdo, $screen);
        $deals = \App\Models\Deal::getActiveDeals(10);

        $locked = $this->isScreenLocked($pdo, $screen);
        if ($locked) {
            $videos = $this->getLockMedia();
            $radioInfo = ['audio_mode' => 'video', 'id_radio' => 0, 'radio_url' => '', 'radio_name' => ''];
        }

        $this->render('player/index', [
            'screen' => $screen,
            'videos' => $videos,
            'codigo' => $codigo,
            'radioInfo' => $radioInfo,
            'locked' => $locked,
            'deals' => $deals
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
            $pdo = Database::clients();
            $radioInfo = $this->resolveRadioInfo($pdo, $screen);
            $locked = $this->isScreenLocked($pdo, $screen);
            if ($locked) {
                $radioInfo = ['audio_mode' => 'video', 'id_radio' => 0, 'radio_url' => '', 'radio_name' => ''];
            }

            register_shutdown_function(function () {
                try {
                    if (function_exists('fastcgi_finish_request')) {
                        @fastcgi_finish_request();
                    }
                    \App\Controllers\EvolutionWebhookController::pollUnreadMessages(false);
                } catch (\Throwable $e) {}
            });

            $this->json([
                'status' => 'ok',
                'active' => $locked ? 0 : 1,
                'locked' => $locked ? 1 : 0,
                'force_reload' => $forceReload,
                'rotation' => (int)($screen['rotation'] ?? 0),
                'audio_mode' => $radioInfo['audio_mode'],
                'radio_url' => $radioInfo['radio_url'],
                'radio_name' => $radioInfo['radio_name']
            ]);
        }

        $this->json(['status' => 'not_found'], 404);
    }

    public function widget(Request $request, string $slug = ''): void
    {
        $slug = str_replace('.php', '', trim($slug));
        $presets = [
            'hora' => [
                'title' => 'Hora Certa',
                'icon' => 'fas fa-clock',
                'rss' => '',
                'mode' => 'clock',
                'bg' => 'linear-gradient(135deg, #090d16 0%, #0f172a 100%)',
                'accent' => '#00b4d8'
            ],
            'frases' => [
                'title' => 'Pensamento do Dia',
                'icon' => 'fas fa-quote-left',
                'rss' => '',
                'mode' => 'quotes',
                'bg' => 'linear-gradient(135deg, #1f1c2c 0%, #4a3f6b 100%)',
                'accent' => '#9b5de5'
            ],
            'noticias' => [
                'title' => 'Últimas Notícias • G1',
                'icon' => 'fas fa-newspaper',
                'rss' => 'https://g1.globo.com/rss/g1/',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #4a0404 0%, #140505 100%)',
                'accent' => '#c4170c'
            ],
            'carros' => [
                'title' => 'Carros & Motos • AutoEsporte',
                'icon' => 'fas fa-car',
                'rss' => 'https://g1.globo.com/dynamo/carros/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #2b0909 0%, #0d1117 100%)',
                'accent' => '#e63946'
            ],
            'rss_esportes' => [
                'title' => 'Esportes & Futebol • GE',
                'icon' => 'fas fa-futbol',
                'rss' => 'https://ge.globo.com/rss/ge/',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #063b16 0%, #07170c 100%)',
                'accent' => '#06aa48'
            ],
            'rss_economia' => [
                'title' => 'Economia & Negócios • G1',
                'icon' => 'fas fa-chart-line',
                'rss' => 'https://g1.globo.com/dynamo/economia/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #0a2540 0%, #07111e 100%)',
                'accent' => '#0077b6'
            ],
            'rss_tecnologia' => [
                'title' => 'Tecnologia & Inovação • G1',
                'icon' => 'fas fa-microchip',
                'rss' => 'https://g1.globo.com/dynamo/tecnologia/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%)',
                'accent' => '#3b82f6'
            ],
            'rss_pop' => [
                'title' => 'Pop, Música & Cinema • G1',
                'icon' => 'fas fa-music',
                'rss' => 'https://g1.globo.com/dynamo/pop-arte/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #3b0764 0%, #11052c 100%)',
                'accent' => '#d946ef'
            ],
            'rss_mundo' => [
                'title' => 'Mundo & Internacional • G1',
                'icon' => 'fas fa-globe-americas',
                'rss' => 'https://g1.globo.com/dynamo/mundo/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #172554 0%, #090d16 100%)',
                'accent' => '#2563eb'
            ],
            'rss_saude' => [
                'title' => 'Saúde & Bem-Estar • G1',
                'icon' => 'fas fa-heartbeat',
                'rss' => 'https://g1.globo.com/dynamo/ciencia-e-saude/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #064e3b 0%, #091512 100%)',
                'accent' => '#10b981'
            ],
            'rss_turismo' => [
                'title' => 'Turismo & Viagem • G1',
                'icon' => 'fas fa-plane',
                'rss' => 'https://g1.globo.com/dynamo/turismo-e-viagem/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #0c4a6e 0%, #081420 100%)',
                'accent' => '#0ea5e9'
            ],
            'rss_loterias' => [
                'title' => 'Loterias & Curiosidades',
                'icon' => 'fas fa-star',
                'rss' => 'https://g1.globo.com/dynamo/loterias/rss2.xml',
                'mode' => 'rss',
                'bg' => 'linear-gradient(135deg, #14532d 0%, #091910 100%)',
                'accent' => '#22c55e'
            ]
        ];

        $cfg = $presets[$slug] ?? null;
        if (!$cfg) {
            // Verifica no banco studiolivecode_radios.db
            try {
                $pdoR = Database::radios();
                $urlTarget = '/widgets/' . $slug . '.php';
                $stmt = $pdoR->prepare("SELECT * FROM entretenimentos WHERE url = :u LIMIT 1");
                $stmt->execute([':u' => $urlTarget]);
                $row = $stmt->fetch();
                if ($row) {
                    $cfg = [
                        'title' => $row['nome'],
                        'icon' => $row['icon'] ?: 'fas fa-rss',
                        'rss' => $row['rss_url'] ?? '',
                        'mode' => 'rss',
                        'bg' => 'linear-gradient(135deg, #0f172a 0%, #1e293b 100%)',
                        'accent' => '#00b4d8'
                    ];
                }
            } catch (\Throwable $e) {}
        }

        if (!$cfg) {
            $cfg = $presets['noticias'];
        }

        $items = [];
        $bgImage = '';

        if ($cfg['mode'] === 'clock') {
            $items = [['title' => date('H:i'), 'desc' => date('d/m/Y')]];
        } elseif ($cfg['mode'] === 'quotes') {
            $quotes = [
                ['title' => '“O sucesso é a soma de pequenos esforços repetidos dia após dia.”', 'desc' => 'Robert Collier'],
                ['title' => '“A persistência é o caminho mais curto para o êxito.”', 'desc' => 'Charles Chaplin'],
                ['title' => '“A melhor maneira de prever o futuro é criá-lo.”', 'desc' => 'Peter Drucker'],
                ['title' => '“Tudo o que um sonho precisa para ser realizado é alguém que acredite nele.”', 'desc' => 'Roberto Shinyashiki'],
                ['title' => '“Acredite em si próprio e chegará um dia em que os outros não terão outra escolha senão acreditar com você.”', 'desc' => 'Cynthia Kersey'],
                ['title' => '“Desejamos a você uma excelente viagem com a Player99!”', 'desc' => 'Boa viagem']
            ];
            $items = $quotes;
        } elseif (!empty($cfg['rss'])) {
            $items = $this->fetchRssItems($cfg['rss'], $slug);
            foreach ($items as $it) {
                if (!empty($it['image'])) {
                    $bgImage = $it['image'];
                    break;
                }
            }
        }

        if (empty($items)) {
            $items = [
                ['title' => 'Acompanhe as principais notícias e atualizações em tempo real na tela Player99.', 'desc' => $cfg['title']]
            ];
        }

        $this->render('player/widget', [
            'slug' => $slug,
            'title' => $cfg['title'],
            'icon' => $cfg['icon'],
            'mode' => $cfg['mode'],
            'bgGradient' => $cfg['bg'],
            'accentColor' => $cfg['accent'],
            'bgImage' => $bgImage,
            'items' => $items
        ], null);
    }

    private function fetchRssItems(string $rssUrl, string $cacheKey): array
    {
        $cacheDir = dirname(__DIR__, 2) . '/api/db';
        $cacheFile = $cacheDir . '/rss_cache_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $cacheKey) . '.json';

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 900) {
            $cached = json_decode((string)@file_get_contents($cacheFile), true);
            if (!empty($cached) && is_array($cached)) {
                return $cached;
            }
        }

        $items = [];
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $rssUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 4);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 Player99-DOOH/2.3');
            $xmlRaw = curl_exec($ch);
            curl_close($ch);

            if ($xmlRaw) {
                $xml = @simplexml_load_string($xmlRaw, 'SimpleXMLElement', LIBXML_NOCDATA);
                if ($xml && isset($xml->channel->item)) {
                    $count = 0;
                    foreach ($xml->channel->item as $item) {
                        if ($count >= 6) break;
                        $title = trim(strip_tags((string)$item->title));
                        $descRaw = (string)($item->description ?? '');
                        $img = '';
                        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $descRaw, $m)) {
                            $img = $m[1];
                        }
                        if (empty($img) && isset($item->enclosure['url'])) {
                            $img = (string)$item->enclosure['url'];
                        }
                        $desc = trim( strip_tags($descRaw) );
                        if (mb_strlen($desc) > 180) {
                            $desc = mb_substr($desc, 0, 177) . '...';
                        }
                        if (!empty($title)) {
                            $items[] = [
                                'title' => $title,
                                'desc' => $desc,
                                'image' => $img
                            ];
                            $count++;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}

        if (!empty($items)) {
            @file_put_contents($cacheFile, json_encode($items, JSON_UNESCAPED_UNICODE));
        }

        return $items;
    }

    public function tv(Request $request): void
    {
        $this->render('player/tv', [], null);
    }

    public function apiPairing(Request $request): void
    {
        $code = strtoupper(trim((string)$request->input('code', '')));
        if ($code === '') {
            $this->json(['paired' => false]);
        }

        Screen::ensureSchema();
        $screen = Screen::findByCode($code);
        $this->json(['paired' => !empty($screen)]);
    }

    public function manifest(Request $request): void
    {
        header('Content-Type: application/manifest+json; charset=utf-8');
        $manifestFile = dirname(__DIR__, 2) . '/manifest.json';
        if (file_exists($manifestFile)) {
            echo file_get_contents($manifestFile);
        } else {
            echo json_encode([
                'id' => '/tv',
                'name' => 'Player99 TV',
                'short_name' => 'Player99',
                'start_url' => '/tv',
                'scope' => '/',
                'display' => 'fullscreen',
                'display_override' => ['fullscreen', 'standalone'],
                'background_color' => '#0b132b',
                'theme_color' => '#0b132b'
            ]);
        }
        exit;
    }

    public function serviceWorker(Request $request): void
    {
        header('Content-Type: application/javascript; charset=utf-8');
        header('Service-Worker-Allowed: /');
        $swFile = dirname(__DIR__, 2) . '/sw.js';
        if (file_exists($swFile)) {
            echo file_get_contents($swFile);
        }
        exit;
    }
}