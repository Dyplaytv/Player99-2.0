<?php
$db = new SQLite3('./api/db/studiolivecode_clientes.db');
$res = $db->query("PRAGMA table_info(ibo);");
while($row = $res->fetchArray(SQLITE3_ASSOC)) {
    print_r($row);
}
