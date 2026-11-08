<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
try {
    $db = new SQLite3("./api/db/studiolivecode_clientes.db");
    $res = $db->query("PRAGMA table_info(ibo)");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        echo $row['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
