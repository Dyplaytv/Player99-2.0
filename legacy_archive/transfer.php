<?php
$dbUsers = new SQLite3(__DIR__ . '/api/db/studiolivecode_users.db');
$stmt = $dbUsers->prepare("SELECT id FROM users WHERE username = 'tu' COLLATE NOCASE");
$res = $stmt->execute();
$user = $res->fetchArray(SQLITE3_ASSOC);

if ($user) {
    $tu_id = $user['id'];
    $dbClientes = new SQLite3(__DIR__ . '/api/db/studiolivecode_clientes.db');
    $dbClientes->exec("UPDATE ibo SET id_user = $tu_id");
    echo "Sucesso! Todas as telas foram transferidas para o usuario 'tu' (ID: $tu_id).";
} else {
    echo "Erro: Usuario 'tu' não encontrado no banco de dados.";
}

// Auto exclusão por segurança
@unlink(__FILE__);
?>
