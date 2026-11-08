<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    var_dump($_POST); // Adicione esta linha para verificar os dados recebidos

    $data = [
        'mensagem' => isset($_POST['mensagem']) ? $_POST['mensagem'] : '',
        'cor' => isset($_POST['cor']) ? $_POST['cor'] : '',
        'bgcolor' => isset($_POST['bgcolor']) ? $_POST['bgcolor'] : '',
        'opacity' => isset($_POST['opacity']) ? $_POST['opacity'] : '',
        'fontSize' => isset($_POST['fontSize']) ? $_POST['fontSize'] : '',
        'efeito' => isset($_POST['efeito']) ? $_POST['efeito'] : ''
    ];

    // Verificar e garantir que o caminho está correto
    $filePath = '../mensagem.txt';
    if (file_put_contents($filePath, json_encode($data)) === false) {
        echo "Erro ao salvar o arquivo. Verifique as permissões e o caminho.";
    } else {
        header('Location: edit_texto.php');
        exit;
    }
} else {
    echo "Método de solicitação não permitido.";
}
?>
