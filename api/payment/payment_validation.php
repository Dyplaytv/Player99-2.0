<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Conectar ao banco de dados
    $db = new SQLite3('../db/studiolivecode_clientes.db');

    // Inicializar as variáveis de entrada e o link de pagamento
    $link_payment = '';
    $message = '';

    // Verificar se foi fornecido um nome de usuário ou uma Chave de Dispositivo
    $input = trim($_POST['input']);

    // Consulta para verificar o link de pagamento pela Chave de Dispositivo
    if (preg_match('/^[a-zA-Z0-9]+$/', $input)) {
        $stmt = $db->prepare("SELECT link_payment FROM ibo WHERE key = :device_key LIMIT 1");
        $stmt->bindValue(':device_key', $input, SQLITE3_TEXT);
        if (!$row = $stmt->execute()->fetchArray(SQLITE3_ASSOC)) {
            // Verificar se é um nome de usuário
            $stmt = $db->prepare("SELECT link_payment FROM ibo WHERE username = :username LIMIT 1");
            $stmt->bindValue(':username', $input, SQLITE3_TEXT);
        }
    } else {
        $message = "Por favor, forneça uma Chave de Dispositivo ou um nome de usuário válido.";
    }

    // Executar a consulta se um campo foi preenchido corretamente
    if (isset($stmt)) {
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row && !empty($row['link_payment'])) {
            // Se encontrado um link de pagamento, redireciona para o link
            $link_payment = $row['link_payment'];
            header("Location: $link_payment");
            exit();
        } else {
            $message = "Dados de cadastro não encontrados ou link de pagamento ausente.";
        }
    }

    // Se a execução chegou aqui, exibe a mensagem de erro
    if (!empty($message)) {
        echo "<div style='text-align: center; margin-top: 20px; color: red; font-weight: bold;'>$message</div>";
    }
}
?>
