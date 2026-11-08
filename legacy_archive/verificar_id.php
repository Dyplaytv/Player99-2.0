<?php
// Conectar ao banco de dados SQLite
$databaseFileClientes = './api/db/studiolivecode_clientes.db';

try {
    $connClientes = new PDO("sqlite:$databaseFileClientes");
    $connClientes->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obter o ID enviado via POST
    $id = $_POST['id'];

    // Verificar se o ID existe no banco de dados
    $stmt = $connClientes->prepare("SELECT COUNT(*) as count FROM ibo WHERE key = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // Retornar 'true' se o ID existir, caso contrário, 'false'
    if ($row['count'] > 0) {
        echo "true";
    } else {
        echo "false";
    }
} catch (PDOException $e) {
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage();
}
?>
