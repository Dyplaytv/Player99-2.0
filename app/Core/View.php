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
