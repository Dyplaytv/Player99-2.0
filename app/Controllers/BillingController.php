<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use PDO;

class BillingController extends Controller
{
    public static function getDb(): PDO
    {
        $dbPath = dirname(__DIR__, 2) . '/api/db/studiolivecode_financeiro.db';
        $pdo = new PDO("sqlite:{$dbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);

        $pdo->exec("CREATE TABLE IF NOT EXISTS faturamento_admin (
            id INTEGER PRIMARY KEY,
            nome_razao TEXT,
            cpf_cnpj TEXT,
            endereco TEXT,
            whats TEXT,
            chave_pix TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS planos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            valor REAL NOT NULL DEFAULT 0,
            dias INTEGER NOT NULL DEFAULT 30,
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS cupons (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            codigo TEXT NOT NULL UNIQUE,
            tipo TEXT NOT NULL DEFAULT 'percent',
            desconto REAL NOT NULL DEFAULT 0,
            usos INTEGER NOT NULL DEFAULT 0,
            max_usos INTEGER NOT NULL DEFAULT 0,
            validade TEXT,
            ativo INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS cliente_creditos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_cliente INTEGER NOT NULL,
            id_plano INTEGER DEFAULT 0,
            nome_plano TEXT NOT NULL,
            dias INTEGER NOT NULL DEFAULT 30,
            valor_pago REAL DEFAULT 0,
            cupom_usado TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'DISPONIVEL',
            id_tela_usada INTEGER DEFAULT 0,
            nome_tela_usada TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            used_at DATETIME
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS pedidos_planos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_cliente INTEGER NOT NULL,
            nome_cliente TEXT DEFAULT '',
            id_plano INTEGER NOT NULL,
            nome_plano TEXT NOT NULL,
            dias INTEGER NOT NULL DEFAULT 30,
            valor_original REAL NOT NULL DEFAULT 0,
            desconto REAL NOT NULL DEFAULT 0,
            valor_final REAL NOT NULL DEFAULT 0,
            cupom TEXT DEFAULT '',
            mp_payment_id TEXT DEFAULT '',
            pix_qr_code TEXT DEFAULT '',
            pix_qr_base64 TEXT DEFAULT '',
            status TEXT NOT NULL DEFAULT 'PENDENTE',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            paid_at DATETIME
        )");

        return $pdo;
    }

    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $pdo = self::getDb();
        $msg = '';

        if ($request->isPost()) {
            $action = trim((string)$request->input('action', 'save_faturamento'));

            // 1. Criar Novo Plano
            if ($action === 'create_plan') {
                $nome = trim((string)$request->input('nome_plano', ''));
                $valorRaw = str_replace(',', '.', trim((string)$request->input('valor_plano', '0')));
                $valor = max(0, (float)$valorRaw);
                $dias = max(1, (int)$request->input('dias_plano', 30));

                if ($nome !== '') {
                    $stmt = $pdo->prepare("INSERT INTO planos (nome, valor, dias, ativo) VALUES (:n, :v, :d, 1)");
                    $stmt->execute([
                        ':n' => $nome,
                        ':v' => $valor,
                        ':d' => $dias
                    ]);
                    $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Plano <b>" . htmlspecialchars($nome) . "</b> (R$ " . number_format($valor, 2, ',', '.') . " - {$dias} dias) criado com sucesso!</div>";
                } else {
                    $msg = "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4'><i class='fa fa-exclamation-triangle fa-lg'></i> Informe o nome do plano.</div>";
                }
            }

            // 2. Excluir Plano
            elseif ($action === 'delete_plan') {
                $planId = (int)$request->input('plan_id', 0);
                if ($planId > 0) {
                    $pdo->prepare("DELETE FROM planos WHERE id = :id")->execute([':id' => $planId]);
                    $msg = "<div class='alert alert-info d-flex align-items-center gap-2 mb-4'><i class='fa fa-trash fa-lg'></i> Plano removido com sucesso!</div>";
                }
            }

            // 3. Cadastrar Cupom de Desconto
            elseif ($action === 'create_coupon') {
                $codigo = strtoupper(preg_replace('/\s+/', '', trim((string)$request->input('codigo_cupom', ''))));
                $tipo = in_array($request->input('tipo_cupom'), ['percent', 'fixed'], true) ? $request->input('tipo_cupom') : 'percent';
                $descontoRaw = str_replace(',', '.', trim((string)$request->input('desconto_cupom', '0')));
                $desconto = max(0.01, (float)$descontoRaw);
                if ($tipo === 'percent' && $desconto > 100) {
                    $desconto = 100;
                }
                $maxUsos = max(0, (int)$request->input('max_usos', 0));
                $validade = trim((string)$request->input('validade_cupom', ''));

                if ($codigo !== '') {
                    $stmt = $pdo->prepare("INSERT INTO cupons (codigo, tipo, desconto, max_usos, validade, ativo)
                        VALUES (:c, :t, :d, :m, :v, 1)
                        ON CONFLICT(codigo) DO UPDATE SET tipo = :t, desconto = :d, max_usos = :m, validade = :v, ativo = 1");
                    $stmt->execute([
                        ':c' => $codigo,
                        ':t' => $tipo,
                        ':d' => $desconto,
                        ':m' => $maxUsos,
                        ':v' => $validade
                    ]);
                    $descFmt = $tipo === 'percent' ? number_format($desconto, 0) . '%' : 'R$ ' . number_format($desconto, 2, ',', '.');
                    $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-tag fa-lg'></i> Cupom <b>" . htmlspecialchars($codigo) . "</b> ({$descFmt} de desconto) cadastrado com sucesso!</div>";
                } else {
                    $msg = "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4'><i class='fa fa-exclamation-triangle fa-lg'></i> Informe o código do cupom.</div>";
                }
            }

            // 4. Excluir Cupom
            elseif ($action === 'delete_coupon') {
                $couponId = (int)$request->input('coupon_id', 0);
                if ($couponId > 0) {
                    $pdo->prepare("DELETE FROM cupons WHERE id = :id")->execute([':id' => $couponId]);
                    $msg = "<div class='alert alert-info d-flex align-items-center gap-2 mb-4'><i class='fa fa-trash fa-lg'></i> Cupom de desconto removido!</div>";
                }
            }

            // 5. Aprovar Pedido de Plano Pendente Manualmente (gera o crédito para o motorista)
            elseif ($action === 'approve_order') {
                $orderId = (int)$request->input('order_id', 0);
                $stmtOrd = $pdo->prepare("SELECT * FROM pedidos_planos WHERE id = :id LIMIT 1");
                $stmtOrd->execute([':id' => $orderId]);
                $ord = $stmtOrd->fetch();
                if ($ord && ($ord['status'] ?? '') !== 'PAGO') {
                    $pdo->prepare("UPDATE pedidos_planos SET status = 'PAGO', paid_at = datetime('now', 'localtime') WHERE id = :id")
                        ->execute([':id' => $orderId]);
                    $pdo->prepare("INSERT INTO cliente_creditos (id_cliente, id_plano, nome_plano, dias, valor_pago, cupom_usado, status)
                        VALUES (:cid, :pid, :np, :d, :vp, :cp, 'DISPONIVEL')")->execute([
                        ':cid' => (int)$ord['id_cliente'],
                        ':pid' => (int)$ord['id_plano'],
                        ':np'  => $ord['nome_plano'],
                        ':d'   => (int)$ord['dias'],
                        ':vp'  => (float)$ord['valor_final'],
                        ':cp'  => (string)$ord['cupom']
                    ]);
                    $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Pagamento aprovado! O crédito de <b>{$ord['dias']} dias ({$ord['nome_plano']})</b> foi liberado para o motorista.</div>";
                }
            }

            // 6. Liberar Crédito Manualmente para um Motorista
            elseif ($action === 'grant_credit') {
                $idCliente = (int)$request->input('id_cliente', 0);
                $idPlano = (int)$request->input('id_plano', 0);
                $stmtPl = $pdo->prepare("SELECT * FROM planos WHERE id = :id LIMIT 1");
                $stmtPl->execute([':id' => $idPlano]);
                $pl = $stmtPl->fetch();
                if ($idCliente > 0 && $pl) {
                    $pdo->prepare("INSERT INTO cliente_creditos (id_cliente, id_plano, nome_plano, dias, valor_pago, cupom_usado, status)
                        VALUES (:cid, :pid, :np, :d, :vp, 'LIBERACAO_ADMIN', 'DISPONIVEL')")->execute([
                        ':cid' => $idCliente,
                        ':pid' => (int)$pl['id'],
                        ':np'  => $pl['nome'],
                        ':d'   => (int)$pl['dias'],
                        ':vp'  => (float)$pl['valor']
                    ]);
                    $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-bolt fa-lg'></i> Crédito do plano <b>" . htmlspecialchars($pl['nome']) . " ({$pl['dias']} dias)</b> adicionado com sucesso à conta do motorista!</div>";
                }
            }

            // 7. Salvar Dados Fiscais e PIX da Empresa
            elseif ($action === 'save_faturamento') {
                $nome = trim((string)$request->input('nome_razao', ''));
                $cpf = trim((string)$request->input('cpf_cnpj', ''));
                $endereco = trim((string)$request->input('endereco', ''));
                $whats = trim((string)$request->input('whats', ''));
                $pix = trim((string)$request->input('chave_pix', ''));

                $stmt = $pdo->prepare("INSERT INTO faturamento_admin (id, nome_razao, cpf_cnpj, endereco, whats, chave_pix)
                                       VALUES (1, :n, :c, :e, :w, :p)
                                       ON CONFLICT(id) DO UPDATE SET nome_razao = :n, cpf_cnpj = :c, endereco = :e, whats = :w, chave_pix = :p");
                $stmt->bindValue(':n', $nome);
                $stmt->bindValue(':c', $cpf);
                $stmt->bindValue(':e', $endereco);
                $stmt->bindValue(':w', $whats);
                $stmt->bindValue(':p', $pix);
                $stmt->execute();

                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Dados de faturamento e PIX da empresa atualizados com sucesso!</div>";
            }
        }

        $stmt = $pdo->query("SELECT * FROM faturamento_admin WHERE id = 1");
        $dados = $stmt->fetch() ?: [
            'nome_razao' => '',
            'cpf_cnpj' => '',
            'endereco' => '',
            'whats' => '',
            'chave_pix' => ''
        ];

        $planos = $pdo->query("SELECT * FROM planos ORDER BY valor ASC, dias ASC")->fetchAll();
        $cupons = $pdo->query("SELECT * FROM cupons ORDER BY id DESC")->fetchAll();
        $pedidos = $pdo->query("SELECT * FROM pedidos_planos ORDER BY id DESC LIMIT 30")->fetchAll();

        $clientes = [];
        try {
            $pdoCli = Database::clients();
            $clientes = $pdoCli->query("SELECT id, title, username FROM ibo ORDER BY id DESC")->fetchAll();
        } catch (\Throwable $e) {}

        $this->render('admin/billing', [
            'pageTitle' => 'Planos e Faturamentos - Player99',
            'pageHeading' => 'Planos de Telas, Cupons & Faturamento',
            'currentRoute' => '/admin/faturamento',
            'currentUser' => Auth::user(),
            'dados' => $dados,
            'planos' => $planos,
            'cupons' => $cupons,
            'pedidos' => $pedidos,
            'clientes' => $clientes,
            'msg' => $msg
        ], 'layouts/master');
    }
}
