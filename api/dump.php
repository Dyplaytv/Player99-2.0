<?php
$db = new SQLite3("db/studiolivecode_clientes.db");
$res = $db->query("SELECT id, mac_address, title, expire_date, id_user FROM ibo");
echo "=== IBO TABLE ===\n";
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    print_r($row);
}
$dbv = new SQLite3("db/studiolivecode_vencimento.db");
$resv = $dbv->query("SELECT * FROM vencimento");
echo "=== VENCIMENTO TABLE ===\n";
while ($row = $resv->fetchArray(SQLITE3_ASSOC)) {
    print_r($row);
}
?>
