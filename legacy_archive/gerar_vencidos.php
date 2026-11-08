<?php
// gerar_clientes_vencidos.php

$db = new SQLite3('./api/db/studiolivecode_clientes.db');

$hoje = date('Y-m-d');
$cincoDias = date('Y-m-d', strtotime('+5 days'));

$query = "SELECT * FROM ibo WHERE expire_date <= '$cincoDias'";
$result = $db->query($query);

$whatsData = json_decode(file_get_contents(__DIR__.'/whats.json'), true);

$clientesVencidos = [];

while($cliente = $result->fetchArray(SQLITE3_ASSOC)) {
    $mac = $cliente['mac_address'];
    $rawWhats = !empty($cliente['whatsapp']) ? $cliente['whatsapp'] : ($whatsData[$mac]['whatsapp'] ?? '');

    if(!empty($rawWhats)) {
        $numero = $rawWhats;
        $clientesVencidos[$mac] = [
            'numero' => $numero,
            'vencimento' => $cliente['expire_date'],
            'mensagem' => "Olá {$cliente['title']},\nvi que temos faturas em atraso. Evite bloqueios acertando o pagamento pelo link abaixo:\n{$cliente['link_payment']}",
            'enviado_em' => null
        ];
    }
}

$arquivo = __DIR__.'/clientes_vencidos.json';
file_put_contents($arquivo, json_encode($clientesVencidos, JSON_PRETTY_PRINT));

echo "✅ clientes_vencidos.json criado com " . count($clientesVencidos) . " clientes.";
?>
