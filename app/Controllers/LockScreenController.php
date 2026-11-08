<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use PDO;

class LockScreenController extends Controller
{
    private function getDb(): PDO
    {
        // Centraliza banco na pasta api/db/ com permissões seguras
        $dbDir = dirname(__DIR__, 2) . '/api/db';
        if (!is_dir($dbDir)) {
            @mkdir($dbDir, 0777, true);
        }
        @chmod($dbDir, 0777);

        $dbPath = $dbDir . '/studiolivecode_bloqueio.db';
        
        $pdo = new PDO("sqlite:{$dbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        try {
            $pdo->exec("PRAGMA busy_timeout = 5000;");
            $pdo->exec("PRAGMA journal_mode = WAL;");
        } catch (\Throwable $e) {}

        $pdo->exec("CREATE TABLE IF NOT EXISTS bloqueio (
            id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
            title VARCHAR(100),
            caminho TEXT
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

        $targetDir = dirname(__DIR__, 2) . '/uploads/bloqueio/';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0777, true);
        }
        @chmod($targetDir, 0777);

        // Deletar
        if ($request->input('delete') !== null) {
            $delId = (int)$request->input('delete');
            $stmt = $pdo->prepare("SELECT caminho FROM bloqueio WHERE id = :id");
            $stmt->bindValue(':id', $delId);
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row) {
                $fullPath = dirname(__DIR__, 2) . '/' . ltrim($row['caminho'], '/');
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                $delStmt = $pdo->prepare("DELETE FROM bloqueio WHERE id = :id");
                $delStmt->bindValue(':id', $delId);
                $delStmt->execute();
                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Mídia de bloqueio removida com sucesso!</div>";
            }
        }

        // Upload / Cadastro
        if ($request->isPost() && isset($_FILES['fileToUpload']) && $_FILES['fileToUpload']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['fileToUpload'];
            $title = trim($request->input('title', 'Mídia de Bloqueio'));
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'mkv', 'webp'];

            if (!in_array($ext, $allowed)) {
                $msg = "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4'><i class='fa fa-times-circle fa-lg'></i> Formato não permitido. Envie JPG, PNG, GIF, WEBP ou MP4/WEBM.</div>";
            } else {
                $fileName = uniqid() . '.' . $ext;
                $targetFile = $targetDir . $fileName;
                $relativeFile = 'uploads/bloqueio/' . $fileName;

                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    @chmod($targetFile, 0666);
                    $stmt = $pdo->prepare("INSERT INTO bloqueio (title, caminho) VALUES (:t, :c)");
                    $stmt->bindValue(':t', $title);
                    $stmt->bindValue(':c', $relativeFile);
                    $stmt->execute();
                    $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Mídia enviada com sucesso!</div>";
                } else {
                    $msg = "<div class='alert alert-danger d-flex align-items-center gap-2 mb-4'><i class='fa fa-times-circle fa-lg'></i> Erro ao mover arquivo enviado. Verifique permissões da pasta uploads/bloqueio.</div>";
                }
            }
        }

        // Lista mídias
        $items = $pdo->query("SELECT * FROM bloqueio ORDER BY id DESC")->fetchAll();

        $this->render('admin/lockscreen', [
            'pageTitle' => 'Tela de Bloqueio - Player99',
            'pageHeading' => 'Gerenciador da Tela de Bloqueio / Standby',
            'currentRoute' => '/admin/bloqueio',
            'currentUser' => Auth::user(),
            'items' => $items,
            'msg' => $msg
        ], 'layouts/master');
    }
}
