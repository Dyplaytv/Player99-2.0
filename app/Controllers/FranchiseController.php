<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Client;
use App\Models\Screen;

class FranchiseController extends Controller
{
    public function index(Request $request): void
    {
        if (empty($_SESSION['cliente_logado']) && !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $pdo = Database::clients();
        Client::ensureSchema();
        Screen::ensureSchema();

        $isAdmin = Auth::isAdmin();
        $allFranchisees = [];
        $resellersCount = 0;

        if ($isAdmin) {
            try {
                $allFranchisees = $pdo->query("SELECT id, title, username, email, whatsapp, is_reseller, commissionPercent FROM ibo ORDER BY is_reseller DESC, id DESC")->fetchAll();
                foreach ($allFranchisees as $f) {
                    if (!empty($f['is_reseller'])) {
                        $resellersCount++;
                    }
                }
            } catch (\Throwable $e) {}
        }

        $idCliente = $this->resolveFranchiseeId($request, $isAdmin, $allFranchisees);
        $client = $idCliente > 0 ? Client::find($idCliente) : null;

        if (!$isAdmin) {
            if (!$client || (isset($client['active']) && (int)$client['active'] === 0)) {
                Auth::logout();
                $this->redirect('/login?err=blocked');
            }
            if (empty($client['is_reseller'])) {
                unset($_SESSION['is_franqueado']);
                $this->redirect('/painel-cliente');
            }
            $_SESSION['is_franqueado'] = true;
        }

        if ($client && empty($client['affiliateCode'])) {
            $affCode = 'REF-' . strtoupper(substr(md5('p99_' . $client['id']), 0, 6));
            try {
                $pdo->prepare("UPDATE ibo SET affiliateCode = :c WHERE id = :id")->execute([':c' => $affCode, ':id' => $client['id']]);
                $client['affiliateCode'] = $affCode;
            } catch (\Throwable $e) {}
        }

        $tab = trim((string)$request->input('tab', 'resumo'));
        if (!in_array($tab, ['resumo', 'motoristas', 'financeiro', 'config'], true)) {
            $tab = 'resumo';
        }

        $msg = '';
        $err = '';
        $createdDriver = null;

        if ($request->isPost() && $client) {
            [$tab, $msg, $err, $createdDriver] = $this->handlePost($request, $pdo, $client, $isAdmin, $tab);
            $client = Client::find((int)$client['id']) ?: $client;
        }

        $commissionPercent = isset($client['commissionPercent']) ? (float)$client['commissionPercent'] : 20.0;

        $drivers = [];
        $driverScreens = [];
        $totalScreens = 0;
        $onlineScreens = 0;
        $activeDrivers = 0;

        if ($client) {
            [$drivers, $driverScreens, $totalScreens, $onlineScreens, $activeDrivers] = $this->loadDriversAndScreens($pdo, (int)$client['id']);
        }

        $driverIds = array_map(static fn(array $d): int => (int)$d['id'], $drivers);
        $recharges = $this->loadRecharges($driverIds, $commissionPercent);
        $financials = $client ? $this->computeFinancials($pdo, (int)$client['id']) : [
            'balance' => 0.0,
            'total_earned' => 0.0,
            'pending_withdraw' => 0.0,
            'paid_withdraw' => 0.0,
            'transactions' => []
        ];

        $this->render('franchise/dashboard', [
            'client' => $client,
            'isAdmin' => $isAdmin,
            'allFranchisees' => $allFranchisees,
            'resellersCount' => $resellersCount,
            'selectedFranchiseeId' => $client ? (int)$client['id'] : 0,
            'tab' => $tab,
            'msg' => $msg,
            'err' => $err,
            'createdDriver' => $createdDriver,
            'commissionPercent' => $commissionPercent,
            'drivers' => $drivers,
            'driverScreens' => $driverScreens,
            'totalDrivers' => count($drivers),
            'activeDrivers' => $activeDrivers,
            'totalScreens' => $totalScreens,
            'onlineScreens' => $onlineScreens,
            'recharges' => $recharges,
            'affiliateBalance' => $financials['balance'],
            'totalEarned' => $financials['total_earned'],
            'pendingWithdraw' => $financials['pending_withdraw'],
            'paidWithdraw' => $financials['paid_withdraw'],
            'affiliateTransactions' => $financials['transactions'],
        ], null);
    }

    private function resolveFranchiseeId(Request $request, bool $isAdmin, array $allFranchisees): int
    {
        if (!$isAdmin) {
            return (int)($_SESSION['cliente_id'] ?? 0);
        }

        $reqId = (int)($request->input('franqueado_id') ?: $request->input('client_id', 0));
        if ($reqId > 0) {
            $_SESSION['spy_franqueado_id'] = $reqId;
            return $reqId;
        }

        if (!empty($_SESSION['spy_franqueado_id'])) {
            return (int)$_SESSION['spy_franqueado_id'];
        }

        foreach ($allFranchisees as $f) {
            if (!empty($f['is_reseller'])) {
                return (int)$f['id'];
            }
        }

        return !empty($allFranchisees) ? (int)$allFranchisees[0]['id'] : 0;
    }

    private function handlePost(Request $request, \PDO $pdo, array $client, bool $isAdmin, string $tab): array
    {
        $idCliente = (int)$client['id'];
        $msg = '';
        $err = '';
        $createdDriver = null;

        if (isset($_POST['promote_reseller']) && $isAdmin) {
            $pdo->prepare("UPDATE ibo SET is_reseller = 1 WHERE id = :id")->execute([':id' => $idCliente]);
            $msg = 'Perfil ativado como Franqueado / Revendedor com sucesso!';
            return [$tab, $msg, $err, null];
        }

        if (isset($_POST['criar_motorista'])) {
            $tab = trim((string)$request->input('return_tab', $tab));
            $mNome = trim((string)$request->input('motorista_nome', ''));
            $mUser = trim((string)$request->input('motorista_usuario', ''));
            $mPass = trim((string)$request->input('motorista_senha', ''));
            $mWhats = trim((string)$request->input('motorista_whatsapp', ''));
            $mEmail = strtolower(trim((string)$request->input('motorista_email', '')));

            if ($mNome === '' || $mUser === '' || strlen($mPass) < 4) {
                $err = 'Preencha nome completo, usuário de login e senha com pelo menos 4 caracteres.';
            } elseif (Client::findByLogin($mUser)) {
                $err = 'Este nome de usuário já está em uso. Escolha outro.';
            } elseif ($mEmail !== '' && Client::findByLogin($mEmail)) {
                $err = 'Este e-mail já está cadastrado em outra conta.';
            } else {
                $mac = sprintf(
                    '%02X:%02X:%02X:%02X:%02X:%02X',
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255),
                    mt_rand(0, 255)
                );
                $driverAffCode = 'REF-' . strtoupper(substr(md5(uniqid('p99_drv_', true)), 0, 6));
                $pdo->prepare("INSERT INTO ibo (mac_address, username, password, email, title, whatsapp, expire_date, active, referredById, affiliateCode, affiliateClicks, commissionPercent)
                    VALUES (:mac, :u, :p, :e, :t, :w, :exp, 1, :ref, :aff, 0, 20.0)")->execute([
                    ':mac' => $mac,
                    ':u'   => $mUser,
                    ':p'   => Auth::hashPassword($mPass),
                    ':e'   => $mEmail,
                    ':t'   => $mNome,
                    ':w'   => $mWhats,
                    ':exp' => date('Y-m-d'),
                    ':ref' => $idCliente,
                    ':aff' => $driverAffCode
                ]);

                $createdDriver = [
                    'nome' => $mNome,
                    'usuario' => $mUser,
                    'senha' => $mPass,
                    'whatsapp' => $mWhats
                ];
                $welcomeRes = \App\Services\WhatsAppWelcomeService::sendOnRegistration($mNome, $mUser, $mWhats);
                $msg = "Motorista <b>" . htmlspecialchars($mNome) . "</b> criado e vinculado à sua rede!";
                if (!empty($welcomeRes['sent'])) {
                    $msg .= " Mensagem de boas-vindas enviada via WhatsApp.";
                }
            }
            return [$tab, $msg, $err, $createdDriver];
        }

        if (isset($_POST['update_pix'])) {
            $tab = trim((string)$request->input('return_tab', 'financeiro'));
            $pixKey = trim((string)$request->input('pixKey', ''));
            $pdo->prepare("UPDATE ibo SET pixKey = :pk WHERE id = :id")->execute([':pk' => $pixKey, ':id' => $idCliente]);
            $msg = 'Chave PIX salva com sucesso!';
            return [$tab, $msg, $err, null];
        }

        if (isset($_POST['withdraw'])) {
            $tab = trim((string)$request->input('return_tab', 'financeiro'));
            $financials = $this->computeFinancials($pdo, $idCliente);
            $saldoAtual = $financials['balance'];
            if ($saldoAtual <= 0) {
                $err = 'Não há saldo disponível para saque no momento.';
            } elseif (empty(trim((string)($client['pixKey'] ?? '')))) {
                $err = 'Cadastre sua chave PIX antes de solicitar o saque.';
            } else {
                $stmtSq = $pdo->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description, createdAt) VALUES (:id, :amt, 'WITHDRAW_PENDING', 'Saque Solicitado (Franqueado)', datetime('now', 'localtime'))");
                $stmtSq->execute([':id' => $idCliente, ':amt' => $saldoAtual]);
                $msg = 'Saque de <b>R$ ' . number_format($saldoAtual, 2, ',', '.') . '</b> solicitado! Acompanhe a aprovação no extrato.';
            }
            return [$tab, $msg, $err, null];
        }

        if (isset($_POST['atualizar_senha'])) {
            $tab = 'config';
            $novaSenha = trim((string)$request->input('nova_senha', ''));
            if (strlen($novaSenha) < 4) {
                $err = 'A nova senha deve ter pelo menos 4 caracteres.';
            } else {
                $newHash = Auth::hashPassword($novaSenha);
                $pdo->prepare("UPDATE ibo SET password = :p WHERE id = :id")->execute([':p' => $newHash, ':id' => $idCliente]);

                // Mantém senha sincronizada caso o franqueado também possua conta na tabela users (nível 2)
                try {
                    $pdoUsers = Database::users();
                    $pdoUsers->prepare("UPDATE users SET password = :p WHERE LOWER(username) = LOWER(:u) AND COALESCE(admin, '0') != '1' AND COALESCE(nivel, '2') != '1'")
                        ->execute([':p' => $newHash, ':u' => (string)$client['username']]);
                } catch (\Throwable $e) {}

                $msg = 'Senha de acesso atualizada com sucesso!';
            }
            return [$tab, $msg, $err, null];
        }

        return [$tab, $msg, $err, null];
    }

    private function loadDriversAndScreens(\PDO $pdo, int $franchiseeId): array
    {
        $drivers = [];
        $driverScreens = [];
        $totalScreens = 0;
        $onlineScreens = 0;
        $activeDrivers = 0;
        $today = date('Y-m-d');

        try {
            $stmt = $pdo->prepare("
                SELECT i.id, i.title, i.username, i.email, i.whatsapp, i.expire_date, i.active,
                       (SELECT COUNT(*) FROM telas t WHERE t.id_cliente = i.id) AS telas_count,
                       (SELECT COUNT(*) FROM telas t WHERE t.id_cliente = i.id AND datetime(t.ultimo_ping) >= datetime('now', '-3 minutes', 'localtime')) AS telas_online
                FROM ibo i
                WHERE i.referredById = :fid
                ORDER BY i.id DESC
            ");
            $stmt->execute([':fid' => $franchiseeId]);
            $drivers = $stmt->fetchAll();

            $driverIds = [];
            foreach ($drivers as $d) {
                $did = (int)$d['id'];
                $driverIds[] = $did;
                $totalScreens += (int)$d['telas_count'];
                $onlineScreens += (int)$d['telas_online'];

                $isActive = ((int)($d['active'] ?? 1) === 1) && (empty($d['expire_date']) || substr((string)$d['expire_date'], 0, 10) >= $today);
                if ($isActive) {
                    $activeDrivers++;
                }
            }

            if (!empty($driverIds)) {
                $placeholders = implode(',', array_fill(0, count($driverIds), '?'));
                $stmtSc = $pdo->prepare("
                    SELECT id, id_cliente, nome_tela, title, player_code, expire_date, ultimo_ping, rotation, audio_mode,
                           CASE WHEN datetime(ultimo_ping) >= datetime('now', '-3 minutes', 'localtime') THEN 1 ELSE 0 END AS is_online
                    FROM telas WHERE id_cliente IN ({$placeholders}) ORDER BY id DESC
                ");
                $stmtSc->execute($driverIds);
                foreach ($stmtSc->fetchAll() as $sc) {
                    $cid = (int)$sc['id_cliente'];
                    $driverScreens[$cid][] = $sc;
                }
            }
        } catch (\Throwable $e) {}

        return [$drivers, $driverScreens, $totalScreens, $onlineScreens, $activeDrivers];
    }

    private function loadRecharges(array $driverIds, float $commissionPercent): array
    {
        if (empty($driverIds)) {
            return [];
        }

        try {
            $pdoFin = BillingController::getDb();
            $placeholders = implode(',', array_fill(0, count($driverIds), '?'));
            $stmt = $pdoFin->prepare("SELECT id, id_cliente, nome_cliente, nome_plano, dias, valor_final, cupom, status, created_at, paid_at FROM pedidos_planos WHERE id_cliente IN ({$placeholders}) ORDER BY id DESC LIMIT 50");
            $stmt->execute($driverIds);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$r) {
                $r['comissao_estimada'] = round(((float)$r['valor_final'] * $commissionPercent) / 100.0, 2);
            }
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function computeFinancials(\PDO $pdo, int $franchiseeId): array
    {
        $balance = 0.0;
        $totalEarned = 0.0;
        $pendingWithdraw = 0.0;
        $paidWithdraw = 0.0;
        $transactions = [];

        try {
            $stmtAll = $pdo->prepare("SELECT amount, type FROM affiliate_transactions WHERE userId = :id");
            $stmtAll->execute([':id' => $franchiseeId]);
            foreach ($stmtAll->fetchAll() as $tx) {
                $amt = (float)($tx['amount'] ?? 0);
                $type = (string)($tx['type'] ?? '');
                if ($type === 'COMMISSION') {
                    $balance += $amt;
                    $totalEarned += $amt;
                } elseif ($type === 'WITHDRAW_PENDING') {
                    $balance -= $amt;
                    $pendingWithdraw += $amt;
                } elseif ($type === 'PAYOUT') {
                    $balance -= $amt;
                    $paidWithdraw += $amt;
                }
            }

            $stmtList = $pdo->prepare("SELECT * FROM affiliate_transactions WHERE userId = :id ORDER BY id DESC LIMIT 50");
            $stmtList->execute([':id' => $franchiseeId]);
            $transactions = $stmtList->fetchAll();
        } catch (\Throwable $e) {}

        return [
            'balance' => max(0.0, round($balance, 2)),
            'total_earned' => round($totalEarned, 2),
            'pending_withdraw' => round($pendingWithdraw, 2),
            'paid_withdraw' => round($paidWithdraw, 2),
            'transactions' => $transactions
        ];
    }
}
