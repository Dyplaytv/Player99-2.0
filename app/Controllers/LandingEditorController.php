<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Services\LandingPages;

class LandingEditorController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isAdmin()) {
            $this->redirect('/login');
        }

        $msg = '';
        $baseDir = dirname(__DIR__, 2);

        // Pasta padrão de uploads (já garantida com permissão de escrita para o Nginx/PHP)
        $landingDir = LandingPages::dir();

        // Página ativa no editor (home | parceiros | afiliados)
        $activePage = (string)$request->input('page', 'home');
        if (!LandingPages::isValidPage($activePage)) {
            $activePage = 'home';
        }

        if ($request->isPost()) {
            $action = (string)$request->input('action', '');
            $postPage = (string)$request->input('page', $activePage);
            if (LandingPages::isValidPage($postPage)) {
                $activePage = $postPage;
            }

            switch ($action) {
                case 'logo':
                    $msg = $this->handleLogo($landingDir, $baseDir);
                    break;

                case 'app_apk':
                    $msg = $this->handleAppApk($baseDir);
                    break;

                case 'add':
                    $added = LandingPages::add(
                        $activePage,
                        $_FILES['images'] ?? [],
                        (string)$request->input('link', '')
                    );
                    $msg = $added > 0
                        ? $this->alert('success', "{$added} container(s) adicionado(s) com sucesso! Já estão ativos na página.")
                        : $this->alert('warning', 'Nenhuma imagem válida enviada. Use JPG, PNG, WEBP ou GIF de até 15MB.');
                    break;

                case 'update':
                    $ok = LandingPages::update(
                        $activePage,
                        (string)$request->input('id', ''),
                        $_FILES['image'] ?? null,
                        (string)$request->input('link', '')
                    );
                    $msg = $ok
                        ? $this->alert('success', 'Container atualizado com sucesso!')
                        : $this->alert('warning', 'Nada foi alterado neste container.');
                    break;

                case 'delete':
                    $ok = LandingPages::remove($activePage, (string)$request->input('id', ''));
                    $msg = $ok
                        ? $this->alert('success', 'Container removido da página.')
                        : $this->alert('warning', 'Não foi possível remover o container.');
                    break;

                case 'move_up':
                case 'move_down':
                    LandingPages::move(
                        $activePage,
                        (string)$request->input('id', ''),
                        $action === 'move_up' ? 'up' : 'down'
                    );
                    break;
            }
        }

        $this->render('admin/landing_editor', [
            'pageTitle' => 'Editar Páginas - Player99',
            'pageHeading' => 'Editor das Páginas Públicas (Home, Parceiros & Afiliados)',
            'currentRoute' => '/admin/landing-editor',
            'currentUser' => Auth::user(),
            'msg' => $msg,
            'activePage' => $activePage,
            'pages' => LandingPages::PAGES,
            'itemsByPage' => [
                'home' => LandingPages::items('home'),
                'parceiros' => LandingPages::items('parceiros'),
                'afiliados' => LandingPages::items('afiliados'),
            ],
            'appInfo' => $this->getAppInfo(),
        ], 'layouts/master');
    }

    private function handleLogo(string $landingDir, string $baseDir): string
    {
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tmpLogo = $_FILES['logo']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

            if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                // Grava na pasta uploads/landing/logo.png (onde as permissões são 100% livres)
                $destLogo = $landingDir . 'logo.png';

                if (move_uploaded_file($tmpLogo, $destLogo) || @copy($tmpLogo, $destLogo)) {
                    @chmod($destLogo, 0666);

                    // Tenta replicar silenciosamente nas outras pastas se possível
                    @copy($destLogo, $baseDir . '/banners/logo.png');
                    @copy($destLogo, $baseDir . '/img/logo.png');
                    @copy($destLogo, $baseDir . '/assets/images/logo.png');

                    return $this->alert('success', 'Logo atualizada com sucesso! Já está ativa em toda a plataforma.');
                }
            }
            return $this->alert('warning', 'Arquivo de logo inválido. Use PNG, JPG, WEBP ou SVG.');
        }
        return $this->alert('warning', 'Selecione um arquivo de logo para enviar.');
    }

    private function handleAppApk(string $baseDir): string
    {
        if (!isset($_FILES['app_apk']) || $_FILES['app_apk']['error'] !== UPLOAD_ERR_OK) {
            return $this->alert('warning', 'Selecione um arquivo .APK válido para enviar.');
        }

        $origName = (string)($_FILES['app_apk']['name'] ?? 'player99-tablet.apk');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext !== 'apk') {
            return $this->alert('warning', 'Formato inválido! Envie apenas arquivos com extensão .apk.');
        }

        $appDir = $baseDir . '/uploads/app';
        if (!is_dir($appDir)) {
            @mkdir($appDir, 0777, true);
        }
        @chmod($appDir, 0777);

        $destName = 'player99-tablet.apk';
        $destPath = $appDir . '/' . $destName;
        $tmpPath = $_FILES['app_apk']['tmp_name'];

        if (move_uploaded_file($tmpPath, $destPath) || @copy($tmpPath, $destPath)) {
            @chmod($destPath, 0666);
            $bytes = (int)@filesize($destPath);
            $sizeLabel = $bytes >= 1048576
                ? number_format($bytes / 1048576, 2, ',', '.') . ' MB'
                : number_format(max(1, $bytes) / 1024, 0, ',', '.') . ' KB';

            try {
                $pdo = Database::users();
                $pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (setting_key TEXT PRIMARY KEY, setting_value TEXT)");
                $stmt = $pdo->prepare("INSERT OR REPLACE INTO system_settings (setting_key, setting_value) VALUES (:k, :v)");
                $stmt->execute([':k' => 'tablet_app_apk_url', ':v' => '/uploads/app/' . $destName]);
                $stmt->execute([':k' => 'tablet_app_apk_name', ':v' => $origName]);
                $stmt->execute([':k' => 'tablet_app_apk_size', ':v' => $sizeLabel]);
                $stmt->execute([':k' => 'tablet_app_apk_updated_at', ':v' => date('d/m/Y H:i')]);
            } catch (\Throwable $e) {}

            return $this->alert('success', "Aplicativo ({$origName} • {$sizeLabel}) enviado com sucesso! Já está disponível para download no Portal do Motorista.");
        }

        return $this->alert('warning', 'Erro ao salvar o arquivo .APK no servidor. Verifique as permissões de uploads/app.');
    }

    private function getAppInfo(): array
    {
        $info = ['url' => '', 'name' => '', 'size' => '', 'updated_at' => ''];
        try {
            $pdo = Database::users();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('tablet_app_apk_url', 'tablet_app_apk_name', 'tablet_app_apk_size', 'tablet_app_apk_updated_at')");
            if ($stmt) {
                $map = [];
                while ($r = $stmt->fetch()) {
                    $map[$r['setting_key']] = $r['setting_value'];
                }
                $info['url'] = trim((string)($map['tablet_app_apk_url'] ?? ''));
                $info['name'] = trim((string)($map['tablet_app_apk_name'] ?? 'player99-tablet.apk'));
                $info['size'] = trim((string)($map['tablet_app_apk_size'] ?? ''));
                $info['updated_at'] = trim((string)($map['tablet_app_apk_updated_at'] ?? ''));
            }
        } catch (\Throwable $e) {}
        return $info;
    }

    private function alert(string $type, string $text): string
    {
        $icon = $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        return "<div class='alert alert-{$type} d-flex align-items-center gap-2 mb-4'><i class='fa {$icon} fa-lg'></i> "
            . htmlspecialchars($text) . "</div>";
    }
}
