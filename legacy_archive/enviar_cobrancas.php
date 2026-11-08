<?php
// enviar_cobrancas.php
// Envia mensagens do JSON clientes_vencidos.json via Evolution API v2

set_time_limit(0); // Evitar timeout no envio em massa
include './whatsapp_api.php';

$arquivo = __DIR__.'/clientes_vencidos.json';

if(!file_exists($arquivo)) {
    die("❌ Arquivo clientes_vencidos.json não encontrado!");
}

$clientes = json_decode(file_get_contents($arquivo), true);

foreach($clientes as $mac => &$cliente) {
    // Pula se já enviou hoje
    $hoje = date('Y-m-d');
    if(isset($cliente['enviado_em']) && strpos($cliente['enviado_em'], $hoje) === 0) {
        continue;
    }

    $resposta = enviarMensagemWasender($cliente['numero'], $cliente['mensagem']);
    
    file_put_contents(__DIR__ . '/whatsapp_debug.log', "Tentativa para {$cliente['numero']}:\n" . print_r($resposta, true) . "\n", FILE_APPEND);

    if(isset($resposta['key']) || isset($resposta['messageId']) || (is_array($resposta) && !isset($resposta['error']))) {
        $cliente['enviado_em'] = date('Y-m-d H:i');
        echo "✅ Mensagem enviada para {$mac}\n";
    } else {
        $err = isset($resposta['error']) ? print_r($resposta['error'], true) : print_r($resposta, true);
        echo "⚠️ Erro ao enviar para {$mac}: {$err}\n";
    }

    // Previne banimento variando o tempo entre 4 a 8 segundos
    sleep(rand(4, 8));
}

// Salva JSON atualizado
file_put_contents($arquivo, json_encode($clientes, JSON_PRETTY_PRINT));

echo "🚀 Envio automático concluído: " . date('Y-m-d H:i');
?>
