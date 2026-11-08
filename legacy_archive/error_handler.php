<?php
// error_handler.php
// Sistema Global de Captura de Erros e Logs

// Função para registrar o erro no banco de dados
function logErrorToDatabase($message, $file, $line, $type) {
    try {
        $dbPath = __DIR__ . "/api/db/error_logs.db";
        $db = new SQLite3($dbPath);
        $db->exec("CREATE TABLE IF NOT EXISTS system_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            date_time TEXT,
            error_type TEXT,
            message TEXT,
            file TEXT,
            line INTEGER,
            url TEXT
        )");

        $stmt = $db->prepare("INSERT INTO system_logs (date_time, error_type, message, file, line, url) VALUES (:dt, :type, :msg, :file, :line, :url)");
        $stmt->bindValue(':dt', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->bindValue(':type', $type, SQLITE3_TEXT);
        $stmt->bindValue(':msg', $message, SQLITE3_TEXT);
        $stmt->bindValue(':file', $file, SQLITE3_TEXT);
        $stmt->bindValue(':line', $line, SQLITE3_INTEGER);
        $stmt->bindValue(':url', $_SERVER['REQUEST_URI'] ?? 'CLI', SQLITE3_TEXT);
        $stmt->execute();
        $db->close();
    } catch (Exception $e) {
        // Se falhar ao gravar no banco, grava em arquivo físico como fallback
        file_put_contents(__DIR__ . '/error_fallback.log', date('Y-m-d H:i:s') . " - $type: $message in $file:$line\n", FILE_APPEND);
    }
}

// Handler de Erros Fatais (Shutdown)
function globalShutdownHandler() {
    $error = error_get_last();
    // Pega apenas erros fatais que quebram o código
    $fatal_errors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
    
    if ($error !== null && in_array($error['type'], $fatal_errors)) {
        logErrorToDatabase($error['message'], $error['file'], $error['line'], 'FATAL_ERROR');
        displayErrorPage();
    }
}

// Handler de Exceções
function globalExceptionHandler($exception) {
    logErrorToDatabase($exception->getMessage(), $exception->getFile(), $exception->getLine(), 'EXCEPTION');
    displayErrorPage();
}

// Handler de Erros Normais (Warnings, Notices)
function globalErrorHandler($level, $message, $file, $line) {
    // Se o erro foi silenciado com @
    if (!(error_reporting() & $level)) {
        return false;
    }
    
    // Loga Warnings que podem ser importantes, mas ignora Notices
    $loggable_errors = [E_WARNING, E_USER_WARNING, E_USER_ERROR, E_RECOVERABLE_ERROR];
    if (in_array($level, $loggable_errors)) {
        $type = ($level === E_USER_ERROR || $level === E_RECOVERABLE_ERROR) ? 'FATAL_ERROR' : 'WARNING';
        logErrorToDatabase($message, $file, $line, $type);
        
        if ($type === 'FATAL_ERROR') {
            displayErrorPage();
        }
    }
    
    return false; // Permite que o PHP continue seu processo normal
}

function displayErrorPage() {
    $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api') !== false) || 
             (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
             
    if ($isApi) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["success" => false, "msg" => "Ocorreu um erro interno no servidor."]);
        exit;
    } else {
        http_response_code(500);
        if (file_exists(__DIR__ . '/error_page.php')) {
            // Limpa qualquer output anterior
            while (ob_get_level()) { ob_end_clean(); }
            include __DIR__ . '/error_page.php';
        } else {
            echo "<h1>Algo deu errado!</h1><p>Nossa equipe técnica já foi notificada. Tente novamente mais tarde.</p>";
        }
        exit;
    }
}

// Registra os handlers
register_shutdown_function('globalShutdownHandler');
set_exception_handler('globalExceptionHandler');
set_error_handler('globalErrorHandler');

// Desativa a exibição padrão do PHP para não aparecer telas brancas
ini_set('display_errors', 0);
?>
