<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\Client;
use PDO;

class AffiliateController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        Client::ensureSchema();
        $pdo = Database::clients();
        $msg = '';

        if ($request->isPost()) {
            // 1. Atualizar Porcentagem de Comissão
            if ($request->input('update_percent') !== null) {
                $userId = (int)$request->input('user_id');
                $percent = (float)$request->input('percent', 20.0);

                $stmt = $pdo->prepare("UPDATE ibo SET commissionPercent = :p WHERE id = :id");
                $stmt->bindValue(':p', $percent);
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Porcentagem de comissão atualizada com sucesso!</div>";
            }
            // 2. Pagar Saque Pendente
            elseif ($request->input('pay_withdraw') !== null) {
                $userId = (int)$request->input('user_id');
                $stmt = $pdo->prepare("UPDATE affiliate_transactions SET type = 'PAYOUT', description = 'Saque Pago via PIX' WHERE userId = :id AND type = 'WITHDRAW_PENDING'");
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Pagamento de saque registrado com sucesso!</div>";
            }
            // 3. Lançar Comissão sobre Pagamento de Cliente Indicado ou Bônus Direto
            elseif ($request->input('add_manual_commission') !== null) {
                $affiliateId = (int)$request->input('affiliate_id', 0);
                $baseValue = (float)str_replace(',', '.', (string)$request->input('base_value', '0'));
                $mode = trim((string)$request->input('calc_mode', 'percent'));
                $descInput = trim((string)$request->input('description', ''));

                if ($affiliateId > 0 && $baseValue > 0) {
                    $stmtAff = $pdo->prepare("SELECT id, title, username, COALESCE(commissionPercent, 20.0) as commissionPercent FROM ibo WHERE id = :id");
                    $stmtAff->execute([':id' => $affiliateId]);
                    $affRow = $stmtAff->fetch();

                    if ($affRow) {
                        $pct = (float)($affRow['commissionPercent'] ?: 20.0);
                        $commissionAmount = ($mode === 'direct')
                            ? round($baseValue, 2)
                            : round(($baseValue * $pct) / 100.0, 2);

                        $finalDesc = $descInput !== ''
                            ? $descInput
                            : ($mode === 'direct'
                                ? 'Bônus / Comissão Manual'
                                : "Comissão ({$pct}%) sobre mensalidade de R$ " . number_format($baseValue, 2, ',', '.'));

                        $stmtTx = $pdo->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description, createdAt) VALUES (:uid, :amt, 'COMMISSION', :desc, datetime('now', 'localtime'))");
                        $stmtTx->execute([
                            ':uid' => $affiliateId,
                            ':amt' => $commissionAmount,
                            ':desc' => $finalDesc
                        ]);

                        $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Comissão de <b>R$ " . number_format($commissionAmount, 2, ',', '.') . "</b> creditada para <b>" . htmlspecialchars($affRow['title'] ?: $affRow['username']) . "</b>!</div>";
                    }
                }
            }
        }

        // Saques Pendentes
        $saques = [];
        try {
            $stmtSaques = $pdo->query("
                SELECT t.userId, SUM(t.amount) as total, i.title, i.username, i.pixKey 
                FROM affiliate_transactions t
                JOIN ibo i ON t.userId = i.id
                WHERE t.type = 'WITHDRAW_PENDING'
                GROUP BY t.userId
            ");
            $saques = $stmtSaques->fetchAll();
        } catch (\Throwable $e) {}

        // Afiliados Cadastrados com contagem de Indicados e Saldo Disponível
        $afiliados = [];
        try {
            $stmtAfil = $pdo->query("
                SELECT 
                    i.id,
                    i.title,
                    i.username,
                    i.affiliateCode,
                    COALESCE(i.affiliateClicks, 0) as affiliateClicks,
                    COALESCE(i.commissionPercent, 20.0) as commissionPercent,
                    i.pixKey,
                    (SELECT COUNT(*) FROM ibo r WHERE r.referredById = i.id) as referralsCount,
                    COALESCE((SELECT SUM(CASE WHEN t.type = 'COMMISSION' THEN t.amount WHEN t.type IN ('PAYOUT', 'WITHDRAW_PENDING') THEN -t.amount ELSE 0 END) FROM affiliate_transactions t WHERE t.userId = i.id), 0) as balance
                FROM ibo i
                ORDER BY i.id DESC
                LIMIT 100
            ");
            $afiliados = $stmtAfil->fetchAll();
        } catch (\Throwable $e) {}

        // Últimas Transações de Comissões e Saques
        $transacoes = [];
        try {
            $stmtTx = $pdo->query("
                SELECT t.*, i.title as affiliateName, i.username as affiliateUser
                FROM affiliate_transactions t
                LEFT JOIN ibo i ON t.userId = i.id
                ORDER BY t.id DESC
                LIMIT 30
            ");
            $transacoes = $stmtTx->fetchAll();
        } catch (\Throwable $e) {}

        $this->render('admin/affiliates', [
            'pageTitle' => 'Afiliados - Player99',
            'pageHeading' => 'Gestão do Programa de Afiliados & Comissões',
            'currentRoute' => '/admin/afiliados',
            'currentUser' => Auth::user(),
            'saques' => $saques,
            'afiliados' => $afiliados,
            'transacoes' => $transacoes,
            'msg' => $msg
        ], 'layouts/master');
    }
}
