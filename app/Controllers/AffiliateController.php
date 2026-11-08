<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use PDO;

class AffiliateController extends Controller
{
    private function ensureSchema(PDO $pdo): void
    {
        $cols = $pdo->query("PRAGMA table_info(ibo)")->fetchAll();
        $colNames = array_column($cols, 'name');

        if (!in_array('affiliateCode', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN affiliateCode TEXT"); } catch (\Throwable $e) {}
        }
        if (!in_array('affiliateClicks', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN affiliateClicks INTEGER DEFAULT 0"); } catch (\Throwable $e) {}
        }
        if (!in_array('pixKey', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN pixKey TEXT"); } catch (\Throwable $e) {}
        }
        if (!in_array('commissionPercent', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN commissionPercent REAL DEFAULT 20.0"); } catch (\Throwable $e) {}
        }
        if (!in_array('referredById', $colNames)) {
            try { $pdo->exec("ALTER TABLE ibo ADD COLUMN referredById INTEGER"); } catch (\Throwable $e) {}
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS affiliate_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            userId INTEGER, 
            amount REAL, 
            type TEXT, 
            description TEXT, 
            createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $pdo = Database::clients();
        $this->ensureSchema($pdo);
        $msg = '';

        if ($request->isPost()) {
            // Atualizar Porcentagem de Comissão
            if ($request->input('update_percent') !== null) {
                $userId = (int)$request->input('user_id');
                $percent = (float)$request->input('percent', 20.0);

                $stmt = $pdo->prepare("UPDATE ibo SET commissionPercent = :p WHERE id = :id");
                $stmt->bindValue(':p', $percent);
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Porcentagem de comissão atualizada com sucesso!</div>";
            }
            // Pagar Saque
            elseif ($request->input('pay_withdraw') !== null) {
                $userId = (int)$request->input('user_id');
                $stmt = $pdo->prepare("UPDATE affiliate_transactions SET type = 'PAYOUT', description = 'Saque Pago' WHERE userId = :id AND type = 'WITHDRAW_PENDING'");
                $stmt->bindValue(':id', $userId);
                $stmt->execute();
                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Pagamento de saque registrado com sucesso!</div>";
            }
        }

        // Saques Pendentes
        $saques = [];
        try {
            $stmtSaques = $pdo->query("
                SELECT t.userId, SUM(t.amount) as total, i.title, i.pixKey 
                FROM affiliate_transactions t
                JOIN ibo i ON t.userId = i.id
                WHERE t.type = 'WITHDRAW_PENDING'
                GROUP BY t.userId
            ");
            $saques = $stmtSaques->fetchAll();
        } catch (\Throwable $e) {}

        // Afiliados Cadastrados
        $afiliados = [];
        try {
            $stmtAfil = $pdo->query("SELECT id, title, username, affiliateCode, affiliateClicks, commissionPercent FROM ibo ORDER BY id DESC LIMIT 100");
            $afiliados = $stmtAfil->fetchAll();
        } catch (\Throwable $e) {}

        $this->render('admin/affiliates', [
            'pageTitle' => 'Afiliados - Player99',
            'pageHeading' => 'Gestão do Programa de Afiliados & Comissões',
            'currentRoute' => '/admin/afiliados',
            'currentUser' => Auth::user(),
            'saques' => $saques,
            'afiliados' => $afiliados,
            'msg' => $msg
        ], 'layouts/master');
    }
}
