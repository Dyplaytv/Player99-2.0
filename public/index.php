<?php
/**
 * Player99 - Front Controller MVC
 * Ponto de entrada único e seguro para todas as requisições.
 */

// Buffer de saída global para evitar envio prematuro de cabeçalhos HTTP
ob_start();

$appConfig = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'America/Sao_Paulo');

// Tratamento de ambiente: em produção desativa avisos visuais na tela
if (($appConfig['env'] ?? 'production') === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
}

// Configuração robusta de sessão (compatível com HTTP, HTTPS, Nginx e Apache)
if (session_status() === PHP_SESSION_NONE) {
    // 1. Garante diretório de sessões gravável com fallback seguro
    $sessDir = dirname(__DIR__) . '/api/db/sessions';
    if (!is_dir($sessDir)) {
        @mkdir($sessDir, 0777, true);
    }
    if (is_dir($sessDir)) {
        @chmod($sessDir, 0777);
    }

    // Testa se o diretório local tem permissão REAL de escrita
    $canUseLocalSession = false;
    if (is_dir($sessDir) && is_writable($sessDir)) {
        $testFile = $sessDir . '/.sess_test_' . uniqid();
        if (@file_put_contents($testFile, '1') !== false) {
            @unlink($testFile);
            $canUseLocalSession = true;
        }
    }

    if ($canUseLocalSession) {
        session_save_path($sessDir);
    } else {
        $sysTmp = sys_get_temp_dir();
        if (is_dir($sysTmp) && is_writable($sysTmp)) {
            session_save_path($sysTmp);
        }
    }

    // 2. Parâmetros de Cookie compatíveis com acesso via IP direto (HTTP) e HTTPS
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_secure', '0'); // Permite login em HTTP e HTTPS
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', 86400 * 7); // 7 dias de persistência
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');

    if (!@session_start()) {
        // Fallback garantido: usa diretório temporário padrão se o customizado falhar
        @session_save_path(sys_get_temp_dir());
        @session_start();
    }
}

// Registra Autoloader PSR-4 Nativo
require_once dirname(__DIR__) . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register(dirname(__DIR__));

try {
    // Inicializa Requisição e Roteador
    $request = new \App\Core\Request();
    $router = new \App\Core\Router($request);

    // Carrega rotas
    require_once dirname(__DIR__) . '/routes/web.php';

    // Despacha a requisição
    $router->dispatch();
} catch (\Throwable $e) {
    // Grava no log de erros para auditoria na raiz
    $logMsg = date('[Y-m-d H:i:s]') . " " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
    @file_put_contents(dirname(__DIR__) . '/error_debug.log', $logMsg, FILE_APPEND);

    // Nota: Mantemos status 200 intencionalmente para evitar que o Nginx / Cloudflare intercepte o erro e oculte o detalhe técnico
    echo "<div style='font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, sans-serif; padding: 30px; background: #0f1015; color: #fff; max-width: 850px; margin: 40px auto; border-radius: 12px; border: 1px solid #ef4444; box-shadow: 0 10px 40px rgba(0,0,0,0.8);'>";
    echo "<h2 style='color: #ef4444; margin-top: 0; display: flex; align-items: center; gap: 10px;'><span style='font-size: 24px;'>⚠️</span> Diagnóstico de Erro - Player99</h2>";
    echo "<p style='font-size: 16px; color: #fca5a5;'><b>Mensagem:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='font-size: 14px; color: #cbd5e1;'><b>Arquivo:</b> " . htmlspecialchars($e->getFile()) . " <b>(Linha " . $e->getLine() . ")</b></p>";
    echo "<pre style='background: #1a1e30; padding: 15px; border-radius: 8px; overflow-x: auto; color: #34e2e4; font-size: 13px; line-height: 1.5; border: 1px solid rgba(255,255,255,0.08);'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
    exit;
}
