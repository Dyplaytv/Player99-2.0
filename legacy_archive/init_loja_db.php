<?php
$dbPath = dirname(__DIR__) . '/api/db/studiolivecode_loja.db';

try {
    $db = new SQLite3($dbPath);
    
    // Tabela de Produtos
    $db->exec("CREATE TABLE IF NOT EXISTS produtos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        titulo TEXT NOT NULL,
        descricao TEXT,
        preco REAL NOT NULL,
        imagem TEXT,
        parceiro_id INTEGER NOT NULL,
        ativo INTEGER DEFAULT 1,
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Tabela de Pedidos
    $db->exec("CREATE TABLE IF NOT EXISTS pedidos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        produto_id INTEGER NOT NULL,
        cliente_email TEXT,
        cliente_nome TEXT,
        status_pagamento TEXT DEFAULT 'pendente',
        mp_payment_id TEXT,
        voucher TEXT,
        data_pedido DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
} catch (Exception $e) {
    error_log("Erro ao iniciar DB loja: " . $e->getMessage());
}
?>
