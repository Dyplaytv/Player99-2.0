<?php
// api/afiliados/click.php
header('Content-Type: application/json');

if (!isset($_GET['ref']) || empty($_GET['ref'])) {
    echo json_encode(['success' => false, 'message' => 'Código de referência não fornecido.']);
    exit;
}

$ref = strtoupper(trim($_GET['ref']));

try {
    $db = new SQLite3(__DIR__ . "/../db/studiolivecode_clientes.db");
    
    // Incrementa cliques para o afiliado
    $stmt = $db->prepare("UPDATE ibo SET affiliateClicks = affiliateClicks + 1 WHERE affiliateCode = :ref");
    $stmt->bindValue(':ref', $ref, SQLITE3_TEXT);
    $stmt->execute();

    if ($db->changes() > 0) {
        echo json_encode(['success' => true, 'message' => 'Clique registrado com sucesso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Afiliado não encontrado.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno.', 'error' => $e->getMessage()]);
}
?>
