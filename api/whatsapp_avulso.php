<?php
// api/whatsapp_avulso.php
include '../whatsapp_api.php';
$db = new SQLite3('./db/studiolivecode_clientes.db');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$query = $db->query("SELECT * FROM ibo WHERE id = $id");
$cliente = $query->fetchArray(SQLITE3_ASSOC);

if (!$cliente) {
    echo json_encode(['error' => 'Cliente não encontrado']);
    exit;
}

$mac = strtoupper(trim($cliente['mac_address']));
$whatsFile = __DIR__ . '/../whats.json';
$whatsData = file_exists($whatsFile) ? json_decode(file_get_contents($whatsFile), true) : [];

$numero = !empty($cliente['whatsapp']) ? $cliente['whatsapp'] : ($whatsData[$mac]['whatsapp'] ?? '');

if (empty($numero)) {
    echo json_encode(['error' => 'Cliente não possui WhatsApp cadastrado']);
    exit;
}

$numero = preg_replace('/[^0-9]/', '', $numero); // Limpa o número
if (strlen($numero) < 10) {
    echo json_encode(['error' => 'Número de WhatsApp inválido']);
    exit;
}

$templateQuery = $db->query("SELECT mensagem FROM whatsapp_templates WHERE tipo = 'Cobrança Manual' AND ativo = 1 LIMIT 1");
$templateRow = $templateQuery->fetchArray(SQLITE3_ASSOC);

if ($templateRow && !empty($templateRow['mensagem'])) {
    $mensagem = str_replace(
        ['{nome}', '{vencimento}', '{link}'], 
        [$cliente['title'], date('d/m/Y', strtotime($cliente['expire_date'])), $cliente['link_payment']], 
        $templateRow['mensagem']
    );
} else {
    $mensagem = "Olá {$cliente['title']},\nvi que temos faturas em atraso. Evite bloqueios acertando o pagamento pelo link abaixo:\n{$cliente['link_payment']}";
}

$resposta = enviarMensagemWasender($numero, $mensagem);

if (isset($resposta['key']) || isset($resposta['messageId']) || (is_array($resposta) && !isset($resposta['error']))) {
    echo json_encode(['success' => true]);
} else {
    $err = isset($resposta['error']) ? (is_array($resposta['error']) ? json_encode($resposta['error']) : $resposta['error']) : 'Erro desconhecido';
    echo json_encode(['error' => $err]);
}
?>
