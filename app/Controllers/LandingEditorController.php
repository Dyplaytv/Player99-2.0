<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;

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
        $landingDir = $baseDir . '/uploads/landing/';
        if (!is_dir($landingDir)) {
            @mkdir($landingDir, 0777, true);
        }
        @chmod($landingDir, 0777);

        if ($request->isPost()) {
            $updated = 0;

            // 1. Upload da LOGO Oficial do Sistema
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $tmpLogo = $_FILES['logo']['tmp_name'];
                $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'])) {
                    // Grava na pasta uploads/landing/logo.png (onde as permissões são 100% livres)
                    $destLogo = $landingDir . 'logo.png';
                    
                    if (move_uploaded_file($tmpLogo, $destLogo) || @copy($tmpLogo, $destLogo)) {
                        @chmod($destLogo, 0666);
                        
                        // Tenta replicar silenciosamente nas outras pastas se possível
                        @copy($destLogo, $baseDir . '/banners/logo.png');
                        @copy($destLogo, $baseDir . '/img/logo.png');
                        @copy($destLogo, $baseDir . '/assets/images/logo.png');
                        
                        $updated++;
                    }
                }
            }

            // 2. Upload dos 3 Banners Sequenciais da Landing Page
            $banners = ['img1' => 'img1.jpg', 'img2' => 'img2.jpg', 'img3' => 'img3.jpg'];
            foreach ($banners as $inputName => $fileName) {
                if (isset($_FILES[$inputName]) && $_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                    $tmp = $_FILES[$inputName]['tmp_name'];
                    $dest = $landingDir . $fileName;

                    if (extension_loaded('gd') && function_exists('imagecreatefromstring')) {
                        $imgData = @file_get_contents($tmp);
                        if ($imgData !== false) {
                            $src = @imagecreatefromstring($imgData);
                            if ($src !== false) {
                                imagejpeg($src, $dest, 85);
                                imagedestroy($src);
                                @chmod($dest, 0666);
                                $updated++;
                                continue;
                            }
                        }
                    }

                    if (move_uploaded_file($tmp, $dest) || @copy($tmp, $dest)) {
                        @chmod($dest, 0666);
                        $updated++;
                    }
                }
            }

            if ($updated > 0) {
                $msg = "<div class='alert alert-success d-flex align-items-center gap-2 mb-4'><i class='fa fa-check-circle fa-lg'></i> Arquivo(s) atualizado(s) com sucesso! A nova imagem já está ativa em toda a plataforma.</div>";
            }
        }

        $this->render('admin/landing_editor', [
            'pageTitle' => 'Editar Home & Logo - Player99',
            'pageHeading' => 'Editor da Página Inicial (Logo & Banners)',
            'currentRoute' => '/admin/landing-editor',
            'currentUser' => Auth::user(),
            'msg' => $msg
        ], 'layouts/master');
    }
}
