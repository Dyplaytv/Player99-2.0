<?php
try {
    // Usaremos o banco de clientes para as tabelas financeiras para manter a integridade relacional.
    $dbFinanceiro = new SQLite3(__DIR__ . "/../api/db/studiolivecode_clientes.db");
    $dbFinanceiro->busyTimeout(5000);
} catch (Exception $e) {
    die('Unable to open database: ' . $e->getMessage());
}

// Criação da tabela de Faturas
$dbFinanceiro->exec("CREATE TABLE IF NOT EXISTS faturas(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    id_cliente INTEGER, 
    data_gerada TEXT, 
    data_vencimento TEXT, 
    valor_total REAL, 
    status TEXT,
    link_pagamento TEXT,
    id_preferencia TEXT
)");

// Criação da tabela de Transações de Afiliados
$dbFinanceiro->exec("CREATE TABLE IF NOT EXISTS affiliate_transactions(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    userId INTEGER, 
    amount REAL, 
    type TEXT, 
    description TEXT, 
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Migração das colunas link_pagamento e id_preferencia caso já exista
$resFatInfo = $dbFinanceiro->query("PRAGMA table_info(faturas)");
$fatCols = [];
while ($rowF = $resFatInfo->fetchArray(SQLITE3_ASSOC)) {
    $fatCols[] = $rowF['name'];
}
if (!in_array('link_pagamento', $fatCols)) {
    $dbFinanceiro->exec("ALTER TABLE faturas ADD COLUMN link_pagamento TEXT");
}
if (!in_array('id_preferencia', $fatCols)) {
    $dbFinanceiro->exec("ALTER TABLE faturas ADD COLUMN id_preferencia TEXT");
}

// Criação da tabela de Dados de Faturamento do Cliente
$dbFinanceiro->exec("CREATE TABLE IF NOT EXISTS faturamento_cliente(
    id_cliente INTEGER PRIMARY KEY, 
    nome_razao TEXT, 
    cpf_cnpj TEXT, 
    endereco TEXT, 
    whats TEXT
)");

// Criação da tabela de Dados de Faturamento do Admin
$dbFinanceiro->exec("CREATE TABLE IF NOT EXISTS faturamento_admin(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    nome_razao TEXT, 
    cpf_cnpj TEXT, 
    endereco TEXT, 
    whats TEXT,
    chave_pix TEXT
)");
?>
