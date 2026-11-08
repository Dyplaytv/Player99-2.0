<?php
include './whatsapp_api.php';
$numero = '5511999999999'; // Test number
$mensagem = 'Test Message';
$resposta = enviarMensagemWasender($numero, $mensagem);
echo "Result:\n";
print_r($resposta);
?>
