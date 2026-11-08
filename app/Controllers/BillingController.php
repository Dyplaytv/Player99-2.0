<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use PDO;

class BillingController extends Controller
{
    private function getDb(): PDO
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
        return $pdo;
    }

    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $pdo = $this->getDb();
        $msg = '';

        if ($request->isPost()) {
            $nome = trim($request->input('nome_razao', ''));
            $cpf = trim($request->input('cpf_cnpj', ''));
            $endereco = trim($request->input('endereco', ''));
            $whats = trim($request->input('whats', ''));
            $pix = trim($request->input('chave_pix', ''));

            $stmt = $pdo->prepare("INSERT INTO faturamento_admin (id, nome_razao, cpf_cnpj, endereco, whats, chave_pix)
                                   VALUES (1, :n, :c, :e, :w, :p)
                                   ON CONFLICT(id) DO UPDATE SET nome_razao = :n, cpf_cnpj = :c, endereco = :e, whats = :w, chave_pix = :p");
            $stmt->bindValue(':n', $nome);
            $stmt->bindValue(':c', $cpf);
            $stmt->bindValue(':e', $endereco);
            $stmt->bindValue(':w', $whats);
            $stmt->bindValue(':p', $pix);
            $stmt->execute();

            $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Dados de faturamento da empresa atualizados com sucesso!</div>";
        }

        $stmt = $pdo->query("SELECT * FROM faturamento_admin WHERE id = 1");
        $dados = $stmt->fetch() ?: [
            'nome_razao' => '',
            'cpf_cnpj' => '',
            'endereco' => '',
            'whats' => '',
            'chave_pix' => ''
        ];

        $this->render('admin/billing', [
            'pageTitle' => 'Faturamento - Player99',
            'pageHeading' => 'Configurações de Faturamento & PIX da Empresa',
            'currentRoute' => '/admin/faturamento',
            'currentUser' => Auth::user(),
            'dados' => $dados,
            'msg' => $msg
        ], 'layouts/master');
    }
}
