<?php
namespace App\Core;

/**
 * Autoloader PSR-4 Nativo do Player99
 * Permite usar namespaces App\... sem precisar de dependências externas.
 */
class Autoloader
{
    protected static string $baseDir = '';

    public static function register(string $baseDir): void
    {
        self::$baseDir = rtrim($baseDir, '/\\') . DIRECTORY_SEPARATOR;

        spl_autoload_register(function (string $class) {
            $prefix = 'App\\';
            if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                return;
            }

            $relativeClass = substr($class, strlen($prefix));
            $file = self::$baseDir . 'app' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

            if (file_exists($file)) {
                require_once $file;
            }
        });
    }
}
