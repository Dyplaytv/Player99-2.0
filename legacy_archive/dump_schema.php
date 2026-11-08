<?php
$db = new SQLite3('C:/Users/CA_ Tech/Documents/Projetos GOOGLE Antigravity/player99/api/db/studiolivecode_clientes.db');
$res = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='faturas'");
$row = $res->fetchArray();
echo $row['sql'];
?>
