<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Models\Deal;
use PDO;

class StoreController extends Controller
{
    private function getDb(): PDO
    {
        $dbDir = dirname(__DIR__, 2) . '/api/db';
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0777, true);
        }
        @chmod($dbDir, 0777);

        $dbPath = $dbDir . '/studiolivecode_loja.db';
        $pdo = new PDO("sqlite:{$dbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        @chmod($dbPath, 0666);

        try {
            $pdo->exec("PRAGMA busy_timeout = 5000;");
            $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                titulo TEXT NOT NULL,
                descricao TEXT,
                preco REAL NOT NULL,
                imagem TEXT,
                parceiro_id INTEGER NOT NULL DEFAULT 0,
                ativo INTEGER DEFAULT 1,
                observacoes_pos_compra TEXT DEFAULT '',
                whatsapp_vendedor TEXT DEFAULT '',
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS pedidos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id INTEGER NOT NULL,
                cliente_email TEXT,
                cliente_nome TEXT,
                cliente_whatsapp TEXT DEFAULT '',
                numero_venda TEXT DEFAULT '',
                status_pagamento TEXT DEFAULT 'pendente',
                mp_payment_id TEXT,
                voucher TEXT,
                whatsapp_notificado INTEGER DEFAULT 0,
                data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $alterQueries = [
                "ALTER TABLE produtos ADD COLUMN observacoes_pos_compra TEXT DEFAULT ''",
                "ALTER TABLE produtos ADD COLUMN whatsapp_vendedor TEXT DEFAULT ''",
                "ALTER TABLE pedidos ADD COLUMN cliente_whatsapp TEXT DEFAULT ''",
                "ALTER TABLE pedidos ADD COLUMN numero_venda TEXT DEFAULT ''",
                "ALTER TABLE pedidos ADD COLUMN whatsapp_notificado INTEGER DEFAULT 0"
            ];
            foreach ($alterQueries as $aq) {
                try {
                    $pdo->exec($aq);
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $e) {}

        return $pdo;
    }

    /** Vitrine pública da Loja (/loja e /loja.php) */
    public function publicStore(Request $request): void
    {
        $pdo = $this->getDb();
        try {
            $stmt = $pdo->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY id DESC");
            $produtos = $stmt ? $stmt->fetchAll() : [];
        } catch (\Throwable $e) {
            $produtos = [];
        }

        try {
            Deal::ensureSchema();
            $deals = Deal::getActiveDeals(50);
        } catch (\Throwable $e) {
            $deals = [];
        }

        $this->render('store/public', [
            'pageTitle' => 'Loja de Serviços Digitais - Player99',
            'pageDescription' => 'Adquira serviços, receba seu Voucher instantaneamente e aproveite os melhores achadinhos.',
            'produtos' => $produtos,
            'deals' => $deals
        ], null);
    }

    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $pdo = $this->getDb();
        $msg = '';

        $targetDir = dirname(__DIR__, 2) . '/uploads/loja/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        @chmod($targetDir, 0777);

        // Deletar
        if ($request->input('delete') !== null) {
            $delId = (int)$request->input('delete');
            $stmt = $pdo->prepare("SELECT imagem FROM produtos WHERE id = :id");
            $stmt->bindValue(':id', $delId);
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row && !empty($row['imagem'])) {
                @unlink($targetDir . $row['imagem']);
            }
            $stmtDel = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
            $stmtDel->bindValue(':id', $delId);
            $stmtDel->execute();
            $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Produto excluído com sucesso!</div>";
        }

        // Salvar / Adicionar / Editar
        if ($request->isPost() && $request->input('action') === 'save') {
            $id = (int)$request->input('id', 0);
            $titulo = trim($request->input('titulo', ''));
            $descricao = trim($request->input('descricao', ''));
            $observacoesPosCompra = trim((string)$request->input('observacoes_pos_compra', ''));
            $whatsappVendedor = preg_replace('/\D/', '', trim((string)$request->input('whatsapp_vendedor', '')));

            $rawPreco = str_replace(['R$', ' '], '', trim((string)$request->input('preco', '0')));
            if (strpos($rawPreco, ',') !== false && strpos($rawPreco, '.') !== false) {
                $rawPreco = str_replace('.', '', $rawPreco);
                $rawPreco = str_replace(',', '.', $rawPreco);
            } elseif (strpos($rawPreco, ',') !== false) {
                $rawPreco = str_replace(',', '.', $rawPreco);
            }
            $preco = max(0, (float)$rawPreco);

            $imagem = '';

            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                $imagem = 'prod_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['imagem']['tmp_name'], $targetDir . $imagem)) {
                    @chmod($targetDir . $imagem, 0666);
                    if ($id > 0) {
                        $stmtOld = $pdo->prepare("SELECT imagem FROM produtos WHERE id = :id");
                        $stmtOld->bindValue(':id', $id);
                        $stmtOld->execute();
                        $oldRow = $stmtOld->fetch();
                        if ($oldRow && !empty($oldRow['imagem']) && $oldRow['imagem'] !== $imagem) {
                            @unlink($targetDir . $oldRow['imagem']);
                        }
                    }
                } else {
                    $imagem = '';
                }
            }

            if ($id > 0) {
                $sql = "UPDATE produtos SET titulo = :t, descricao = :d, preco = :p, observacoes_pos_compra = :obs, whatsapp_vendedor = :wv" . ($imagem ? ", imagem = :img" : "") . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id', $id);
            } else {
                $sql = "INSERT INTO produtos (titulo, descricao, preco, imagem, observacoes_pos_compra, whatsapp_vendedor, parceiro_id) VALUES (:t, :d, :p, :img, :obs, :wv, 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':img', $imagem);
            }

            $stmt->bindValue(':t', $titulo);
            $stmt->bindValue(':d', $descricao);
            $stmt->bindValue(':p', $preco);
            $stmt->bindValue(':obs', $observacoesPosCompra);
            $stmt->bindValue(':wv', $whatsappVendedor);
            if ($imagem && $id > 0) {
                $stmt->bindValue(':img', $imagem);
            }

            $stmt->execute();
            $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> " . ($id > 0 ? 'Produto atualizado com sucesso!' : 'Produto cadastrado com sucesso!') . "</div>";
        }

        $produtos = $pdo->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();

        $this->render('admin/store', [
            'pageTitle' => 'Minha Loja - Player99',
            'pageHeading' => 'Gerenciamento da Loja de Produtos & Dispositivos',
            'currentRoute' => '/admin/loja',
            'currentUser' => Auth::user(),
            'produtos' => $produtos,
            'msg' => $msg
        ], 'layouts/master');
    }
}
