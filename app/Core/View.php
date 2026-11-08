<?php
namespace App\Core;

use Exception;

class View
{
    protected static string $viewsDir = '';

    public static function setViewsDir(string $dir): void
    {
        self::$viewsDir = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Retorna a URL da logomarca oficial:
     * 1. Se existir logo enviada pelo painel em uploads/landing/logo.png, usa ela.
     * 2. Caso contrário, usa a logo principal padrão em assets/images/logo.png.
     */
    public static function logoUrl(): string
    {
        $baseDir = dirname(__DIR__, 2);
        $customLogo = $baseDir . '/uploads/landing/logo.png';
        if (file_exists($customLogo) && filesize($customLogo) > 0) {
            return '/uploads/landing/logo.png?v=' . filemtime($customLogo);
        }
        $bannerLogo = $baseDir . '/banners/logo.png';
        if (file_exists($bannerLogo) && filesize($bannerLogo) > 0) {
            return '/banners/logo.png?v=' . filemtime($bannerLogo);
        }
        return '/assets/images/logo.png';
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/master'): void
    {
        if (empty(self::$viewsDir)) {
            self::$viewsDir = dirname(__DIR__) . '/Views/';
        }

        $viewFile = self::$viewsDir . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewFile)) {
            throw new Exception("View '{$view}' não foi encontrada em: {$viewFile}");
        }

        extract($data);

        // Renderiza conteúdo da view
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Se tem layout, envolve o conteúdo
        if ($layout) {
            $layoutFile = self::$viewsDir . str_replace('.', '/', $layout) . '.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
                return;
            }
        }

        echo $content;
    }
}
