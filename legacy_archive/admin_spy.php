<?php
session_start();
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/includes/auth_helper.php';

// Verifica se o painel está logado
if (!isset($_SESSION['id']) || !isset($_SESSION['U'])) {
    header("Location: login.php");
    exit;
}

$admin_id = intval($_SESSION['id']);

// Validação estrita de administrador
try {
    $dbUsers = new SQLite3("./api/db/studiolivecode_users.db");
    $stmtAdmin = $dbUsers->prepare("SELECT admin, email, username FROM users WHERE id = :id");
    $stmtAdmin->bindValue(':id', $admin_id, SQLITE3_INTEGER);
    $resAdmin = $stmtAdmin->execute();
    $adminData = $resAdmin->fetchArray(SQLITE3_ASSOC);
    $dbUsers->close();

    if (!$adminData || ($adminData['admin'] != '1' && !isRootUser($adminData))) {
        header("Location: login.php");
        exit;
    }
} catch (Exception $e) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['cliente_id'])) {
    header("Location: all_users.php");
    exit;
}

$cliente_id = intval($_GET['cliente_id']);

try {
    $db = new SQLite3("./api/db/studiolivecode_clientes.db");
} catch (Exception $e) {
    die('Unable to open database: ' . $e->getMessage());
}

// Verifica se o cliente existe
$stmt = $db->prepare("SELECT id, username, email FROM ibo WHERE id = :id");
$stmt->bindValue(':id', $cliente_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$row = $result->fetchArray(SQLITE3_ASSOC);

if ($row) {
    // Entrar como cliente (Modo Espião Autorizado)
    $_SESSION["cliente_logado"] = true;
    $_SESSION["cliente_id"] = $row["id"];
    $_SESSION["cliente_user"] = $row["username"];
    $_SESSION["cliente_email"] = $row["email"] ?? '';
    $_SESSION["admin_spy_origin"] = $admin_id;
    
    // Redireciona para o painel do cliente
    header("Location: cliente_dashboard.php");
    exit();
} else {
    // Cliente não encontrado
    header("Location: all_users.php?r=erro");
    exit();
}
?>
