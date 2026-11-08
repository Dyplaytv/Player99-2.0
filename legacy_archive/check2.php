<?php
$db = new SQLite3('./api/db/studiolivecode_clientes.db');
$res = $db->query('SELECT id, username, link_payment FROM ibo LIMIT 1');
print_r($res->fetchArray(SQLITE3_ASSOC));
?>
