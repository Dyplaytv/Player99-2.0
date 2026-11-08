<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
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
                data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS pedidos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id INTEGER NOT NULL,
                cliente_email TEXT,
                cliente_nome TEXT,
                status_pagamento TEXT DEFAULT 'pendente',
                mp_payment_id TEXT,
                voucher TEXT,
                data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
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

        $this->render('store/public', [
            'pageTitle' => 'Loja de Serviços Digitais - Player99',
            'pageDescription' => 'Adquira serviços e receba seu Voucher instantaneamente.',
            'produtos' => $produtos
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

        // Salvar / Adicionar
        if ($request->isPost() && $request->input('action') === 'save') {
            $id = (int)$request->input('id', 0);
            $titulo = trim($request->input('titulo', ''));
            $descricao = trim($request->input('descricao', ''));
            $preco = (float)str_replace(['R$', ' ', '.'], '', str_replace(',', '.', $request->input('preco', '0')));
            $imagem = '';

            if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
                $imagem = 'prod_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['imagem']['tmp_name'], $targetDir . $imagem);
                @chmod($targetDir . $imagem, 0666);
            }

            if ($id > 0) {
                $sql = "UPDATE produtos SET titulo = :t, descricao = :d, preco = :p" . ($imagem ? ", imagem = :img" : "") . " WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':id', $id);
            } else {
                $sql = "INSERT INTO produtos (titulo, descricao, preco, imagem, parceiro_id) VALUES (:t, :d, :p, :img, 0)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':img', $imagem);
            }

            $stmt->bindValue(':t', $titulo);
            $stmt->bindValue(':d', $descricao);
            $stmt->bindValue(':p', $preco);
            if ($imagem && $id > 0) {
                $stmt->bindValue(':img', $imagem);
            }

            $stmt->execute();
            $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Produto salvo com sucesso!</div>";
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
