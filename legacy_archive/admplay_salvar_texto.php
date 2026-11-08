<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $data = [
        'mensagem' => isset($_POST['mensagem']) ? $_POST['mensagem'] : '',
        'cor' => isset($_POST['cor']) ? $_POST['cor'] : '',
        'bgcolor' => isset($_POST['bgcolor']) ? $_POST['bgcolor'] : '',
        'opacity' => isset($_POST['opacity']) ? $_POST['opacity'] : '',
        'fontSize' => isset($_POST['fontSize']) ? $_POST['fontSize'] : '',
        'efeito' => isset($_POST['efeito']) ? $_POST['efeito'] : ''
    ];

    $filePath = 'mensagem.txt';

    if (file_put_contents($filePath, json_encode($data)) === false) {
        echo "<script>alert('Erro ao salvar o arquivo. Verifique as permissões.'); window.history.back();</script>";
    } else {
        header('Location: admplay_texto.php?r=atualizado');
        exit;
    }
} else {
    echo "Método de solicitação não permitido.";
}
?>
