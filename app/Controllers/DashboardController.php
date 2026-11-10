<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\User;
use App\Models\Client;
use App\Models\Screen;

class DashboardController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        User::ensureSchema();
        Client::ensureSchema();
        Screen::ensureSchema();

        $currentUser = Auth::user();

        // 1. Estatísticas de Telas e Carros (banco clientes)
        $pdoClients = Database::clients();

        // Total de telas
        $totalTelas = 0;
        try {
            $res = $pdoClients->query("SELECT COUNT(*) FROM telas");
            $totalTelas = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // Telas Online nos últimos 3 minutos
        $telasOnline = 0;
        try {
            $res = $pdoClients->query("SELECT COUNT(*) FROM telas WHERE datetime(ultimo_ping) >= datetime('now', '-3 minutes', 'localtime')");
            $telasOnline = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // Total de Clientes / Anunciantes
        $totalClientes = 0;
        try {
            $res = $pdoClients->query("SELECT COUNT(*) FROM ibo");
            $totalClientes = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // 2. Estatísticas de Usuários / Revendedores (banco users)
        $pdoUsers = Database::users();
        $totalUsers = 0;
        try {
            $res = $pdoUsers->query("SELECT COUNT(*) FROM users");
            $totalUsers = (int)$res->fetchColumn();
        } catch (\Exception $e) {}

        // 3. Métricas Financeiras (Planos, Recargas, Comissões de Afiliados & Franqueados)
        $today = date('Y-m-d');
        $month = date('Y-m');
        $fin = [
            'ganhos_dia' => 0.0,
            'qtd_dia' => 0,
            'ganhos_mes' => 0.0,
            'qtd_mes' => 0,
            'ganhos_total' => 0.0,
            'qtd_total' => 0,
            'ticket_medio' => 0.0,
            'pendentes_valor' => 0.0,
            'pendentes_qtd' => 0,
            'creditos_disponiveis' => 0,
            'creditos_usados' => 0,
            'afiliados_saldo' => 0.0,
            'afiliados_total_gerado' => 0.0,
            'afiliados_pago' => 0.0,
            'afiliados_qtd' => 0,
            'franqueados_saldo' => 0.0,
            'franqueados_total_gerado' => 0.0,
            'franqueados_pago' => 0.0,
            'franqueados_qtd' => 0,
            'motoristas_franqueados' => 0,
            'comissoes_mes' => 0.0,
            'receita_liquida_mes' => 0.0,
            'saques_pendentes_valor' => 0.0,
            'saques_pendentes_qtd' => 0,
            'telas_ativas' => 0,
            'telas_vencidas' => 0,
            'ultimas_recargas' => []
        ];

        try {
            $pdoFin = BillingController::getDb();

            $stmtDia = $pdoFin->prepare("SELECT COALESCE(SUM(valor_final), 0) AS total, COUNT(*) AS qtd FROM pedidos_planos WHERE status = 'PAGO' AND substr(COALESCE(NULLIF(paid_at, ''), created_at), 1, 10) = :d");
            $stmtDia->execute([':d' => $today]);
            $rowDia = $stmtDia->fetch() ?: [];
            $fin['ganhos_dia'] = (float)($rowDia['total'] ?? 0);
            $fin['qtd_dia'] = (int)($rowDia['qtd'] ?? 0);

            $stmtMes = $pdoFin->prepare("SELECT COALESCE(SUM(valor_final), 0) AS total, COUNT(*) AS qtd FROM pedidos_planos WHERE status = 'PAGO' AND substr(COALESCE(NULLIF(paid_at, ''), created_at), 1, 7) = :m");
            $stmtMes->execute([':m' => $month]);
            $rowMes = $stmtMes->fetch() ?: [];
            $fin['ganhos_mes'] = (float)($rowMes['total'] ?? 0);
            $fin['qtd_mes'] = (int)($rowMes['qtd'] ?? 0);

            $rowTot = $pdoFin->query("SELECT COALESCE(SUM(valor_final), 0) AS total, COUNT(*) AS qtd FROM pedidos_planos WHERE status = 'PAGO'")->fetch() ?: [];
            $fin['ganhos_total'] = (float)($rowTot['total'] ?? 0);
            $fin['qtd_total'] = (int)($rowTot['qtd'] ?? 0);
            $fin['ticket_medio'] = $fin['qtd_total'] > 0 ? round($fin['ganhos_total'] / $fin['qtd_total'], 2) : 0.0;

            $rowPend = $pdoFin->query("SELECT COALESCE(SUM(valor_final), 0) AS total, COUNT(*) AS qtd FROM pedidos_planos WHERE status = 'PENDENTE'")->fetch() ?: [];
            $fin['pendentes_valor'] = (float)($rowPend['total'] ?? 0);
            $fin['pendentes_qtd'] = (int)($rowPend['qtd'] ?? 0);

            $fin['creditos_disponiveis'] = (int)$pdoFin->query("SELECT COUNT(*) FROM cliente_creditos WHERE status = 'DISPONIVEL'")->fetchColumn();
            $fin['creditos_usados'] = (int)$pdoFin->query("SELECT COUNT(*) FROM cliente_creditos WHERE status = 'USADO'")->fetchColumn();

            $fin['ultimas_recargas'] = $pdoFin->query("SELECT id, id_cliente, nome_cliente, nome_plano, dias, valor_final, paid_at, created_at FROM pedidos_planos WHERE status = 'PAGO' ORDER BY id DESC LIMIT 5")->fetchAll();
        } catch (\Throwable $e) {}

        // Soma também faturas legadas pagas caso existam na base de clientes
        try {
            $stmtFatDia = $pdoClients->prepare("SELECT COALESCE(SUM(valor_total), 0) AS total, COUNT(*) AS qtd FROM faturas WHERE status = 'PAGA' AND substr(COALESCE(data_gerada, ''), 1, 10) = :d");
            $stmtFatDia->execute([':d' => $today]);
            $rFd = $stmtFatDia->fetch() ?: [];
            $fin['ganhos_dia'] += (float)($rFd['total'] ?? 0);
            $fin['qtd_dia'] += (int)($rFd['qtd'] ?? 0);

            $stmtFatMes = $pdoClients->prepare("SELECT COALESCE(SUM(valor_total), 0) AS total, COUNT(*) AS qtd FROM faturas WHERE status = 'PAGA' AND substr(COALESCE(data_gerada, ''), 1, 7) = :m");
            $stmtFatMes->execute([':m' => $month]);
            $rFm = $stmtFatMes->fetch() ?: [];
            $fin['ganhos_mes'] += (float)($rFm['total'] ?? 0);
            $fin['qtd_mes'] += (int)($rFm['qtd'] ?? 0);
        } catch (\Throwable $e) {}

        // Comissões de Afiliados vs Franqueados
        try {
            $fin['franqueados_qtd'] = (int)$pdoClients->query("SELECT COUNT(*) FROM ibo WHERE COALESCE(is_reseller, 0) = 1")->fetchColumn();
            $fin['afiliados_qtd'] = (int)$pdoClients->query("SELECT COUNT(DISTINCT i.id) FROM ibo i WHERE COALESCE(i.is_reseller, 0) = 0 AND (EXISTS (SELECT 1 FROM ibo r WHERE r.referredById = i.id) OR EXISTS (SELECT 1 FROM affiliate_transactions t WHERE t.userId = i.id))")->fetchColumn();
            $fin['motoristas_franqueados'] = (int)$pdoClients->query("SELECT COUNT(*) FROM ibo m JOIN ibo f ON m.referredById = f.id WHERE COALESCE(f.is_reseller, 0) = 1")->fetchColumn();

            $stmtTx = $pdoClients->query("
                SELECT t.type, t.amount, t.createdAt, COALESCE(i.is_reseller, 0) AS is_reseller
                FROM affiliate_transactions t
                LEFT JOIN ibo i ON t.userId = i.id
            ");
            foreach ($stmtTx->fetchAll() as $tx) {
                $amt = (float)($tx['amount'] ?? 0);
                $type = (string)($tx['type'] ?? '');
                $isFrq = ((int)($tx['is_reseller'] ?? 0) === 1);
                $prefix = $isFrq ? 'franqueados' : 'afiliados';

                if ($type === 'COMMISSION') {
                    $fin[$prefix . '_total_gerado'] += $amt;
                    $fin[$prefix . '_saldo'] += $amt;
                    if (substr((string)($tx['createdAt'] ?? ''), 0, 7) === $month) {
                        $fin['comissoes_mes'] += $amt;
                    }
                } elseif ($type === 'PAYOUT') {
                    $fin[$prefix . '_pago'] += $amt;
                    $fin[$prefix . '_saldo'] -= $amt;
                } elseif ($type === 'WITHDRAW_PENDING') {
                    $fin[$prefix . '_saldo'] -= $amt;
                    $fin['saques_pendentes_valor'] += $amt;
                    $fin['saques_pendentes_qtd']++;
                }
            }
            $fin['afiliados_saldo'] = max(0.0, round($fin['afiliados_saldo'], 2));
            $fin['franqueados_saldo'] = max(0.0, round($fin['franqueados_saldo'], 2));
            $fin['receita_liquida_mes'] = round($fin['ganhos_mes'] - $fin['comissoes_mes'], 2);
        } catch (\Throwable $e) {}

        // Saúde financeira das telas (ativas vs vencidas/bloqueadas)
        try {
            $stmtScrHealth = $pdoClients->query("SELECT t.expire_date, i.expire_date AS client_expire_date, COALESCE(i.active, 1) AS client_active FROM telas t LEFT JOIN ibo i ON t.id_cliente = i.id");
            foreach ($stmtScrHealth->fetchAll() as $sh) {
                $exp = trim((string)($sh['expire_date'] ?? ''));
                if ($exp === '') {
                    $exp = trim((string)($sh['client_expire_date'] ?? ''));
                }
                $blocked = ((int)$sh['client_active'] === 0) || ($exp !== '' && substr($exp, 0, 10) < $today);
                if ($blocked) {
                    $fin['telas_vencidas']++;
                } else {
                    $fin['telas_ativas']++;
                }
            }
        } catch (\Throwable $e) {}

        // Telas recentes com status
        $recentScreens = [];
        try {
            $stmt = $pdoClients->query("SELECT id, player_code, title, nome_tela, ultimo_ping, audio_mode, rotation FROM telas ORDER BY id DESC LIMIT 8");
            $recentScreens = $stmt->fetchAll();
        } catch (\Exception $e) {}

        $this->render('admin/dashboard', [
            'pageTitle' => 'Estatísticas - Player99',
            'pageHeading' => 'Painel de Estatísticas & DOOH',
            'currentRoute' => '/admin/dashboard',
            'currentUser' => $currentUser,
            'totalTelas' => $totalTelas,
            'telasOnline' => $telasOnline,
            'totalClientes' => $totalClientes,
            'totalUsers' => $totalUsers,
            'recentScreens' => $recentScreens,
            'fin' => $fin
        ], 'layouts/master');
    }
}
