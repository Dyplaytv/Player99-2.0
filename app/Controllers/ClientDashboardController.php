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
            "ALTER TABLE telas ADD COLUMN expire_date TEXT",
            "ALTER TABLE videos ADD COLUMN upload_date TEXT",
            "ALTER TABLE playlist_videos ADD COLUMN duration INTEGER DEFAULT 15",
            "ALTER TABLE ibo ADD COLUMN affiliateCode TEXT",
            "ALTER TABLE ibo ADD COLUMN affiliateClicks INTEGER DEFAULT 0",
            "ALTER TABLE ibo ADD COLUMN pixKey TEXT",
            "ALTER TABLE ibo ADD COLUMN commissionPercent REAL DEFAULT 20.0",
            "ALTER TABLE ibo ADD COLUMN referredById INTEGER",
            "ALTER TABLE ibo ADD COLUMN is_reseller INTEGER DEFAULT 0",
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

            if ($ajaxAction === 'validate_coupon') {
                $planId = (int)$request->input('plan_id', 0);
                $quantity = max(1, min(100, (int)$request->input('quantity', 1)));
                $code = strtoupper(preg_replace('/\s+/', '', trim((string)$request->input('coupon_code', ''))));
                $pdoFin = BillingController::getDb();
                $stmtPl = $pdoFin->prepare("SELECT * FROM planos WHERE id = :id LIMIT 1");
                $stmtPl->execute([':id' => $planId]);
                $plan = $stmtPl->fetch();
                if (!$plan) {
                    echo json_encode(['success' => false, 'msg' => 'Selecione um plano válido primeiro.']);
                    exit;
                }
                if ($code === '') {
                    echo json_encode(['success' => false, 'msg' => 'Digite o código do cupom.']);
                    exit;
                }
                $stmtCp = $pdoFin->prepare("SELECT * FROM cupons WHERE UPPER(codigo) = :c AND ativo = 1 LIMIT 1");
                $stmtCp->execute([':c' => $code]);
                $cp = $stmtCp->fetch();
                if (!$cp) {
                    echo json_encode(['success' => false, 'msg' => 'Cupom inválido ou não encontrado.']);
                    exit;
                }
                if (!empty($cp['validade']) && $cp['validade'] < date('Y-m-d')) {
                    echo json_encode(['success' => false, 'msg' => 'Este cupom já expirou.']);
                    exit;
                }
                if ((int)$cp['max_usos'] > 0 && (int)$cp['usos'] >= (int)$cp['max_usos']) {
                    echo json_encode(['success' => false, 'msg' => 'Este cupom atingiu o limite máximo de usos.']);
                    exit;
                }
                $unitPrice = (float)$plan['valor'];
                $orig = $unitPrice * $quantity;
                $descVal = ($cp['tipo'] === 'percent')
                    ? round($orig * ((float)$cp['desconto'] / 100.0), 2)
                    : (float)$cp['desconto'];
                if ($descVal > $orig) {
                    $descVal = $orig;
                }
                $finalVal = max(0.0, round($orig - $descVal, 2));
                $label = ($cp['tipo'] === 'percent')
                    ? number_format((float)$cp['desconto'], 0) . '% OFF'
                    : 'R$ ' . number_format((float)$cp['desconto'], 2, ',', '.') . ' OFF';

                echo json_encode([
                    'success' => true,
                    'coupon' => $cp['codigo'],
                    'label' => $label,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'original_price' => $orig,
                    'discount' => $descVal,
                    'discount_formatted' => '- R$ ' . number_format($descVal, 2, ',', '.'),
                    'final_price' => $finalVal,
                    'final_formatted' => 'R$ ' . number_format($finalVal, 2, ',', '.')
                ]);
                exit;
            }

            if ($ajaxAction === 'buy_plan') {
                $planId = (int)$request->input('plan_id', 0);
                $quantity = max(1, min(100, (int)$request->input('quantity', 1)));
                $code = strtoupper(preg_replace('/\s+/', '', trim((string)$request->input('coupon_code', ''))));
                $pdoFin = BillingController::getDb();
                $stmtPl = $pdoFin->prepare("SELECT * FROM planos WHERE id = :id LIMIT 1");
                $stmtPl->execute([':id' => $planId]);
                $plan = $stmtPl->fetch();
                if (!$plan) {
                    echo json_encode(['success' => false, 'msg' => 'Plano não encontrado.']);
                    exit;
                }

                $unitPrice = (float)$plan['valor'];
                $orig = $unitPrice * $quantity;
                $descVal = 0.0;
                $appliedCoupon = '';

                if ($code !== '') {
                    $stmtCp = $pdoFin->prepare("SELECT * FROM cupons WHERE UPPER(codigo) = :c AND ativo = 1 LIMIT 1");
                    $stmtCp->execute([':c' => $code]);
                    $cp = $stmtCp->fetch();
                    if ($cp && (empty($cp['validade']) || $cp['validade'] >= date('Y-m-d')) && ((int)$cp['max_usos'] === 0 || (int)$cp['usos'] < (int)$cp['max_usos'])) {
                        $descVal = ($cp['tipo'] === 'percent')
                            ? round($orig * ((float)$cp['desconto'] / 100.0), 2)
                            : (float)$cp['desconto'];
                        if ($descVal > $orig) $descVal = $orig;
                        $appliedCoupon = $cp['codigo'];
                        $pdoFin->prepare("UPDATE cupons SET usos = usos + 1 WHERE id = :id")->execute([':id' => $cp['id']]);
                    }
                }

                $finalVal = max(0.0, round($orig - $descVal, 2));
                $cliRow = Client::find($idCliente);
                $cliName = $cliRow['title'] ?? ($cliRow['username'] ?? ('Cliente #' . $idCliente));

                // Se o cupom deu 100% de desconto (ou plano gratuito), libera os créditos na hora!
                if ($finalVal <= 0.0) {
                    $pdoFin->prepare("INSERT INTO pedidos_planos (id_cliente, nome_cliente, id_plano, nome_plano, dias, quantidade, valor_original, desconto, valor_final, cupom, status, paid_at)
                        VALUES (:cid, :nc, :pid, :np, :d, :q, :vo, :desc, 0, :cp, 'PAGO', datetime('now', 'localtime'))")->execute([
                        ':cid' => $idCliente,
                        ':nc'  => $cliName,
                        ':pid' => (int)$plan['id'],
                        ':np'  => $plan['nome'],
                        ':d'   => (int)$plan['dias'],
                        ':q'   => $quantity,
                        ':vo'  => $orig,
                        ':desc'=> $descVal,
                        ':cp'  => $appliedCoupon
                    ]);

                    for ($i = 0; $i < $quantity; $i++) {
                        $pdoFin->prepare("INSERT INTO cliente_creditos (id_cliente, id_plano, nome_plano, dias, valor_pago, cupom_usado, status)
                            VALUES (:cid, :pid, :np, :d, 0, :cp, 'DISPONIVEL')")->execute([
                            ':cid' => $idCliente,
                            ':pid' => (int)$plan['id'],
                            ':np'  => $plan['nome'],
                            ':d'   => (int)$plan['dias'],
                            ':cp'  => $appliedCoupon
                        ]);
                    }

                    echo json_encode([
                        'success' => true,
                        'free_activated' => true,
                        'msg' => "Cupom de 100% aplicado! {$quantity} Crédito(s) do plano {$plan['nome']} ({$plan['dias']} dias cada) liberados imediatamente."
                    ]);
                    exit;
                }

                // Verifica se há token Mercado Pago configurado para gerar PIX dinâmico
                $mpToken = '';
                try {
                    $pdoUsers = Database::users();
                    $stmtMp = $pdoUsers->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'mercadopago_access_token' LIMIT 1");
                    $stmtMp->execute();
                    $mpToken = trim((string)$stmtMp->fetchColumn());
                } catch (\Throwable $e) {}

                $mpPaymentId = '';
                $pixQrCode = '';
                $pixQrBase64 = '';

                $planLabel = ($quantity > 1 ? "{$quantity}x " : "") . 'Plano ' . $plan['nome'] . ' (' . $plan['dias'] . ' dias cada)';

                if ($mpToken !== '') {
                    $payload = [
                        'transaction_amount' => (float)$finalVal,
                        'description' => $planLabel . ' - Player99',
                        'payment_method_id' => 'pix',
                        'payer' => [
                            'email' => !empty($cliRow['email']) ? $cliRow['email'] : ('cliente' . $idCliente . '@player99.com.br'),
                            'first_name' => $cliName
                        ]
                    ];
                    $ch = curl_init('https://api.mercadopago.com/v1/payments');
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => json_encode($payload),
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Bearer ' . $mpToken,
                            'Content-Type: application/json',
                            'X-Idempotency-Key: p99_' . $idCliente . '_' . time() . '_' . mt_rand(100, 999)
                        ],
                        CURLOPT_TIMEOUT => 12,
                        CURLOPT_SSL_VERIFYPEER => false
                    ]);
                    $mpResRaw = curl_exec($ch);
                    curl_close($ch);
                    $mpData = json_decode((string)$mpResRaw, true);
                    if (!empty($mpData['id'])) {
                        $mpPaymentId = (string)$mpData['id'];
                        $pixQrCode = (string)($mpData['point_of_interaction']['transaction_data']['qr_code'] ?? '');
                        $pixQrBase64 = (string)($mpData['point_of_interaction']['transaction_data']['qr_code_base64'] ?? '');
                    }
                }

                $stmtFatAdm = $pdoFin->query("SELECT * FROM faturamento_admin WHERE id = 1");
                $fatAdm = $stmtFatAdm->fetch() ?: [];
                if ($pixQrCode === '' && !empty($fatAdm['chave_pix'])) {
                    $pixQrCode = trim((string)$fatAdm['chave_pix']);
                }

                $stmtOrd = $pdoFin->prepare("INSERT INTO pedidos_planos (id_cliente, nome_cliente, id_plano, nome_plano, dias, quantidade, valor_original, desconto, valor_final, cupom, mp_payment_id, pix_qr_code, pix_qr_base64, status)
                    VALUES (:cid, :nc, :pid, :np, :d, :q, :vo, :desc, :vf, :cp, :mpid, :qr, :qrb64, 'PENDENTE')");
                $stmtOrd->execute([
                    ':cid'   => $idCliente,
                    ':nc'    => $cliName,
                    ':pid'   => (int)$plan['id'],
                    ':np'    => $plan['nome'],
                    ':d'     => (int)$plan['dias'],
                    ':q'     => $quantity,
                    ':vo'    => $orig,
                    ':desc'  => $descVal,
                    ':vf'    => $finalVal,
                    ':cp'    => $appliedCoupon,
                    ':mpid'  => $mpPaymentId,
                    ':qr'    => $pixQrCode,
                    ':qrb64' => $pixQrBase64
                ]);
                $orderId = (int)$pdoFin->lastInsertId();

                echo json_encode([
                    'success' => true,
                    'free_activated' => false,
                    'order_id' => $orderId,
                    'plan_name' => $planLabel,
                    'quantity' => $quantity,
                    'days' => (int)$plan['dias'],
                    'final_price' => 'R$ ' . number_format($finalVal, 2, ',', '.'),
                    'pix_qr_code' => $pixQrCode,
                    'pix_qr_base64' => $pixQrBase64,
                    'support_whats' => preg_replace('/\D/', '', (string)($fatAdm['whats'] ?? ''))
                ]);
                exit;
            }

            if ($ajaxAction === 'check_order_payment') {
                $orderId = (int)$request->input('order_id', 0);
                $pdoFin = BillingController::getDb();
                $stmtOrd = $pdoFin->prepare("SELECT * FROM pedidos_planos WHERE id = :id AND id_cliente = :cid LIMIT 1");
                $stmtOrd->execute([':id' => $orderId, ':cid' => $idCliente]);
                $ord = $stmtOrd->fetch();
                if (!$ord) {
                    echo json_encode(['paid' => false]);
                    exit;
                }
                if (($ord['status'] ?? '') === 'PAGO') {
                    echo json_encode(['paid' => true]);
                    exit;
                }
                if (!empty($ord['mp_payment_id'])) {
                    try {
                        $pdoUsers = Database::users();
                        $stmtMp = $pdoUsers->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'mercadopago_access_token' LIMIT 1");
                        $stmtMp->execute();
                        $mpToken = trim((string)$stmtMp->fetchColumn());
                        if ($mpToken !== '') {
                            $ch = curl_init('https://api.mercadopago.com/v1/payments/' . urlencode($ord['mp_payment_id']));
                            curl_setopt_array($ch, [
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $mpToken],
                                CURLOPT_TIMEOUT => 8,
                                CURLOPT_SSL_VERIFYPEER => false
                            ]);
                            $res = json_decode((string)curl_exec($ch), true);
                            curl_close($ch);
                            if (($res['status'] ?? '') === 'approved') {
                                $pdoFin->prepare("UPDATE pedidos_planos SET status = 'PAGO', paid_at = datetime('now', 'localtime') WHERE id = :id")
                                    ->execute([':id' => $orderId]);

                                $qtd = max(1, (int)($ord['quantidade'] ?? 1));
                                $valUnit = round((float)$ord['valor_final'] / $qtd, 2);

                                for ($i = 0; $i < $qtd; $i++) {
                                    $pdoFin->prepare("INSERT INTO cliente_creditos (id_cliente, id_plano, nome_plano, dias, valor_pago, cupom_usado, status)
                                        VALUES (:cid, :pid, :np, :d, :vp, :cp, 'DISPONIVEL')")->execute([
                                        ':cid' => $idCliente,
                                        ':pid' => (int)$ord['id_plano'],
                                        ':np'  => $ord['nome_plano'],
                                        ':d'   => (int)$ord['dias'],
                                        ':vp'  => $valUnit,
                                        ':cp'  => (string)$ord['cupom']
                                    ]);
                                }

                                Client::creditAffiliateCommission($idCliente, (float)$ord['valor_final'], 'Comissão sobre recarga: ' . ($qtd > 1 ? "{$qtd}x " : "") . 'plano ' . $ord['nome_plano'] . ' (' . ($ord['nome_cliente'] ?: ('#' . $idCliente)) . ')');
                                echo json_encode(['paid' => true, 'quantity' => $qtd]);
                                exit;
                            }
                        }
                    } catch (\Throwable $e) {}
                }
                echo json_encode(['paid' => false]);
                exit;
            }
            }
        }

        $client = Client::find($idCliente) ?: [
            'id' => $idCliente,
            'title' => 'Motorista / Parceiro',
            'username' => $_SESSION['cliente_user'] ?? 'motorista',
            'expire_date' => Screen::defaultTrialExpireAt(),
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
                    $trialHours = Screen::getNewScreenTrialHours();
                    $trialExpire = Screen::defaultTrialExpireAt($trialHours);
                    Screen::create([
                        'id_cliente' => $idCliente,
                        'player_code' => $code,
                        'title' => $nomeTela,
                        'nome_tela' => $nomeTela,
                        'rotation' => $rotation,
                        'audio_mode' => 'video',
                        'expire_date' => $trialExpire,
                        'ultimo_ping' => date('Y-m-d H:i:s')
                    ]);
                    if ($trialHours > 0) {
                        $msg = "Nova tela ({$code}) vinculada! Válida por {$trialHours} hora(s) até " . date('d/m/Y H:i', strtotime($trialExpire)) . ".";
                    } else {
                        $msg = "Nova tela ({$code}) vinculada! Faça uma recarga para liberar a transmissão.";
                    }
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

            // Recarregar Tela usando Crédito de Plano Adquirido
            if (isset($_POST['recarregar_tela_credito'])) {
                $tab = 'telas';
                $idTela = (int)$request->input('id_tela', 0);
                $idCredito = (int)$request->input('id_credito', 0);

                $stmtTela = $pdo->prepare("SELECT * FROM telas WHERE id = :id AND id_cliente = :cid LIMIT 1");
                $stmtTela->execute([':id' => $idTela, ':cid' => $idCliente]);
                $telaRow = $stmtTela->fetch();

                $pdoFin = BillingController::getDb();
                $stmtCred = $pdoFin->prepare("SELECT * FROM cliente_creditos WHERE id = :id AND id_cliente = :cid AND status = 'DISPONIVEL' LIMIT 1");
                $stmtCred->execute([':id' => $idCredito, ':cid' => $idCliente]);
                $credRow = $stmtCred->fetch();

                if (!$telaRow) {
                    $err = 'Tela não encontrada.';
                } elseif (!$credRow) {
                    $err = 'Crédito inválido ou já utilizado.';
                } else {
                    $diasAdd = max(1, (int)$credRow['dias']);
                    $curExp = !empty($telaRow['expire_date']) ? (string)$telaRow['expire_date'] : '';
                    $baseDate = ($curExp !== '' && !Screen::isExpiredValue($curExp)) ? substr($curExp, 0, 10) : date('Y-m-d');
                    $newExp = date('Y-m-d', strtotime($baseDate . " +{$diasAdd} days"));

                    $pdo->prepare("UPDATE telas SET expire_date = :exp, force_reload = 1 WHERE id = :id")
                        ->execute([':exp' => $newExp, ':id' => $idTela]);

                    // Também mantém o vencimento geral do motorista sincronizado se for maior
                    if (empty($client['expire_date']) || $newExp > $client['expire_date']) {
                        $pdo->prepare("UPDATE ibo SET expire_date = :exp, active = 1 WHERE id = :cid")
                            ->execute([':exp' => $newExp, ':cid' => $idCliente]);
                        $client['expire_date'] = $newExp;
                    }

                    $nomeTelaUsada = $telaRow['nome_tela'] ?: ($telaRow['title'] ?: ('Tela #' . $idTela));
                    $pdoFin->prepare("UPDATE cliente_creditos SET status = 'USADO', id_tela_usada = :tid, nome_tela_usada = :nt, used_at = datetime('now', 'localtime') WHERE id = :cid")
                        ->execute([
                            ':tid' => $idTela,
                            ':nt'  => $nomeTelaUsada,
                            ':cid' => $idCredito
                        ]);

                    $msg = "Tela '{$nomeTelaUsada}' recarregada com sucesso (+{$diasAdd} dias)! Nova validade: " . date('d/m/Y', strtotime($newExp)) . ".";
                }
            }

            // Revendedor: criar usuário motorista vinculado abaixo dele
            if (isset($_POST['criar_motorista'])) {
                $tab = 'revenda';
                if (empty($client['is_reseller'])) {
                    $err = 'Seu perfil não possui permissão de revendedor.';
                } else {
                    $mNome = trim((string)$request->input('motorista_nome', ''));
                    $mUser = trim((string)$request->input('motorista_usuario', ''));
                    $mPass = trim((string)$request->input('motorista_senha', ''));
                    $mWhats = trim((string)$request->input('motorista_whatsapp', ''));
                    if ($mNome === '' || $mUser === '' || strlen($mPass) < 4) {
                        $err = 'Preencha nome, usuário e uma senha de pelo menos 4 caracteres.';
                    } elseif (Client::findByLogin($mUser)) {
                        $err = 'Este usuário já existe. Escolha outro.';
                    } else {
                        $mac = sprintf('%02X:%02X:%02X:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
                        $pdo->prepare("INSERT INTO ibo (mac_address, username, password, title, whatsapp, expire_date, active, referredById)
                            VALUES (:mac, :u, :p, :t, :w, :exp, 1, :ref)")->execute([
                            ':mac' => $mac,
                            ':u'   => $mUser,
                            ':p'   => Auth::hashPassword($mPass),
                            ':t'   => $mNome,
                            ':w'   => $mWhats,
                            ':exp' => date('Y-m-d'),
                            ':ref' => $idCliente
                        ]);
                        \App\Services\WhatsAppWelcomeService::sendOnRegistration($mNome, $mUser, $mWhats);
                        $msg = "Motorista '{$mNome}' criado! Usuário: {$mUser}. Ele precisa de uma recarga para liberar as telas.";
                    }
                }
            }

            // Área do Afiliado: Atualizar Chave PIX
            if (isset($_POST['update_pix'])) {
                $tab = 'afiliado';
                $pixKey = trim((string)$request->input('pixKey', ''));
                $pdo->prepare("UPDATE ibo SET pixKey = :pk WHERE id = :id")->execute([':pk' => $pixKey, ':id' => $idCliente]);
                $client['pixKey'] = $pixKey;
                $msg = 'Chave PIX atualizada com sucesso!';
            }

            // Área do Afiliado / Revenda: Solicitar Saque
            if (isset($_POST['withdraw'])) {
                $returnTab = trim((string)$request->input('return_tab', ''));
                $tab = in_array($returnTab, ['afiliado', 'revenda'], true) ? $returnTab : ($tab === 'revenda' ? 'revenda' : 'afiliado');
                $saldoAtual = $this->getAffiliateBalance($pdo, $idCliente);
                if ($saldoAtual > 0) {
                    if (empty($client['pixKey'])) {
                        $err = 'Configure sua chave PIX antes de solicitar o saque.';
                    } else {
                        $stmtSq = $pdo->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description, createdAt) VALUES (:id, :amt, 'WITHDRAW_PENDING', 'Saque Solicitado', datetime('now', 'localtime'))");
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

        // Planos Ativos, Créditos Disponíveis do Motorista e Histórico de Recargas
        $planosAtivos = [];
        $creditosDisponiveis = [];
        $creditosUsados = [];
        $pedidosCliente = [];
        $faturamentoAdmin = [];
        $dealsScansCount = 0;
        try {
            $pdoFin = BillingController::getDb();
            $planosAtivos = $pdoFin->query("SELECT * FROM planos WHERE ativo = 1 ORDER BY valor ASC, dias ASC")->fetchAll();

            $stmtCrDisp = $pdoFin->prepare("SELECT * FROM cliente_creditos WHERE id_cliente = :cid AND status = 'DISPONIVEL' ORDER BY id ASC");
            $stmtCrDisp->execute([':cid' => $idCliente]);
            $creditosDisponiveis = $stmtCrDisp->fetchAll();

            $stmtCrUsed = $pdoFin->prepare("SELECT * FROM cliente_creditos WHERE id_cliente = :cid AND status = 'USADO' ORDER BY used_at DESC LIMIT 20");
            $stmtCrUsed->execute([':cid' => $idCliente]);
            $creditosUsados = $stmtCrUsed->fetchAll();

            $stmtPedCli = $pdoFin->prepare("SELECT * FROM pedidos_planos WHERE id_cliente = :cid ORDER BY id DESC LIMIT 15");
            $stmtPedCli->execute([':cid' => $idCliente]);
            $pedidosCliente = $stmtPedCli->fetchAll();

            $faturamentoAdmin = $pdoFin->query("SELECT * FROM faturamento_admin WHERE id = 1")->fetch() ?: [];

            \App\Models\Deal::ensureSchema();
            $stmtDeals = \App\Core\Database::deals()->prepare("SELECT COUNT(*) FROM deal_clicks WHERE driver_user_id = :id");
            $stmtDeals->execute([':id' => $idCliente]);
            $dealsScansCount = (int)$stmtDeals->fetchColumn();
        } catch (\Throwable $e) {}

        // Painel do Revendedor: motoristas abaixo dele e total de telas
        $isReseller = !empty($client['is_reseller']);
        $resellerDrivers = [];
        $resellerScreensTotal = 0;
        if ($isReseller) {
            try {
                $stmtRd = $pdo->prepare("SELECT i.id, i.title, i.username, i.whatsapp, i.expire_date, i.active,
                        (SELECT COUNT(*) FROM telas t WHERE t.id_cliente = i.id) AS telas_count
                    FROM ibo i WHERE i.referredById = :id ORDER BY i.id DESC");
                $stmtRd->execute([':id' => $idCliente]);
                $resellerDrivers = $stmtRd->fetchAll();
                foreach ($resellerDrivers as $rd) {
                    $resellerScreensTotal += (int)$rd['telas_count'];
                }
            } catch (\Throwable $e) {}
        }

        $this->render('client/dashboard', [
            'isReseller' => $isReseller,
            'resellerDrivers' => $resellerDrivers,
            'resellerScreensTotal' => $resellerScreensTotal,
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
            'planosAtivos' => $planosAtivos,
            'creditosDisponiveis' => $creditosDisponiveis,
            'creditosUsados' => $creditosUsados,
            'pedidosCliente' => $pedidosCliente,
            'faturamentoAdmin' => $faturamentoAdmin,
            'affiliateBalance' => $affiliateBalance,
            'affiliateReferrals' => $affiliateReferrals,
            'affiliateTransactions' => $affiliateTransactions,
            'dealsScansCount' => $dealsScansCount,
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