<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;

class RadioController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $pdo = Database::radios();

        // Garante a tabela radios
        $pdo->exec("CREATE TABLE IF NOT EXISTS radios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome VARCHAR(150) NOT NULL,
            url TEXT NOT NULL,
            is_visible INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $msg = '';

        if ($request->isPost()) {
            // Adicionar Rádio
            if ($request->input('add_radio') !== null) {
                $nome = trim($request->input('nome', ''));
                $url = trim($request->input('url', ''));

                if (!empty($nome) && !empty($url)) {
                    $stmt = $pdo->prepare("INSERT INTO radios (nome, url, is_visible) VALUES (:nome, :url, 1)");
                    $stmt->bindValue(':nome', $nome);
                    $stmt->bindValue(':url', $url);
                    if ($stmt->execute()) {
                        $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Rádio '<b>" . htmlspecialchars($nome) . "</b>' adicionada com sucesso!</div>";
                    }
                }
            }
            // Deletar Rádio
            elseif ($request->input('delete_radio') !== null) {
                $id = (int)$request->input('id_radio');
                if ($id > 0) {
                    $stmt = $pdo->prepare("DELETE FROM radios WHERE id = :id");
                    $stmt->bindValue(':id', $id);
                    $stmt->execute();
                    $msg = "<div class='alert alert-info d-flex align-items-center gap-2 mb-4'><i class='fa fa-trash fa-lg'></i> Rádio removida com sucesso!</div>";
                }
            }
            // Alternar Visibilidade
            elseif ($request->input('toggle_visible') !== null) {
                $id = (int)$request->input('id_radio');
                $current = (int)$request->input('current_visible', 1);
                $newVal = ($current === 1) ? 0 : 1;

                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE radios SET is_visible = :v WHERE id = :id");
                    $stmt->bindValue(':v', $newVal);
                    $stmt->bindValue(':id', $id);
                    $stmt->execute();
                }
            }
        }

        // Buscar todas as rádios
        $stmt = $pdo->query("SELECT * FROM radios ORDER BY id DESC");
        $radios = $stmt->fetchAll();

        $this->render('admin/radios', [
            'pageTitle' => 'Web Rádios - Player99',
            'pageHeading' => 'Gerenciador de Web Rádios & Transmissões',
            'currentRoute' => '/admin/radios',
            'currentUser' => Auth::user(),
            'radios' => $radios,
            'msg' => $msg
        ], 'layouts/master');
    }
}
