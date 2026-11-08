<?php
$dbPath = __DIR__ . '/api/db/studiolivecode_radios.db';
$db = new SQLite3($dbPath);

$db->exec("CREATE TABLE IF NOT EXISTS radios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    url TEXT NOT NULL,
    is_visible INTEGER DEFAULT 1
)");

// Verifica se já existem rádios
$count = $db->querySingle("SELECT COUNT(*) FROM radios");
if ($count == 0) {
    $defaultRadios = [
        ['Antena 1', 'https://antena1.newradio.it/stream'],
        ['Alpha FM', 'https://26583.live.streamtheworld.com/ALPHA_FMAAC.aac'],
        ['CBN SP', 'https://22453.live.streamtheworld.com/CBN_SPAAC.aac'],
        ['Transamérica SP', 'https://19313.live.streamtheworld.com/TRANSAMERICA_SPAAC.aac'],
        ['Mix FM', 'https://21303.live.streamtheworld.com/MIXFM_SPAAC.aac']
    ];

    $stmt = $db->prepare("INSERT INTO radios (nome, url) VALUES (:nome, :url)");
    foreach ($defaultRadios as $radio) {
        $stmt->bindValue(':nome', $radio[0], SQLITE3_TEXT);
        $stmt->bindValue(':url', $radio[1], SQLITE3_TEXT);
        $stmt->execute();
    }
    echo "Banco de radios criado e populado com sucesso.\n";
} else {
    echo "Banco de radios já existia e está populado.\n";
}
?>
