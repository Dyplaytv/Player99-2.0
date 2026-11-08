<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
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

    private function alert(string $type, string $text): string
    {
        $icon = $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        return "<div class='alert alert-{$type} d-flex align-items-center gap-2 mb-4'><i class='fa {$icon} fa-lg'></i> "
            . htmlspecialchars($text) . "</div>";
    }
}
