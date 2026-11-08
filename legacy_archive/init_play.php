<?php
// Script para inicializar os arquivos do ADM Play automaticamente

$img_dir = __DIR__ . '/img/';
$banners_dir = __DIR__ . '/banners/';

// 1. Criar diretório img se não existir
if (!file_exists($img_dir)) {
    mkdir($img_dir, 0777, true);
}

// 2. Copiar logo e fundo padrão se existirem
if (file_exists($banners_dir . 'logo.png')) {
    copy($banners_dir . 'logo.png', $img_dir . 'logo.png');
}
if (file_exists($banners_dir . 'bg.png')) {
    copy($banners_dir . 'bg.png', $img_dir . 'bg.jpg'); // renomeado para .jpg pois é o que o script espera
}

// 3. Inicializar os bancos de dados
$db_logo = new SQLite3(dirname(__DIR__) . '/api/.db_logo.db');
$db_logo->exec("CREATE TABLE IF NOT EXISTS ads(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(100), caminho TEXT)");
$resL = $db_logo->query("SELECT COUNT(*) as count FROM ads");
$rowL = $resL->fetchArray();
if ($rowL['count'] == 0) {
    $db_logo->exec("INSERT INTO ads(title, caminho) VALUES('Logo Padrao', 'img/logo.png')");
}
$db_logo->close();

$db_bg = new SQLite3(dirname(__DIR__) . '/api/.db_bg.db');
$db_bg->exec("CREATE TABLE IF NOT EXISTS ads(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(100), caminho TEXT)");
$resB = $db_bg->query("SELECT COUNT(*) as count FROM ads");
$rowB = $resB->fetchArray();
if ($rowB['count'] == 0) {
    $db_bg->exec("INSERT INTO ads(title, caminho) VALUES('Fundo Padrao', 'img/bg.jpg')");
}
$db_bg->close();

// 4. Criar mensagem inicial
$mensagem_path = __DIR__ . '/mensagem.txt';
if (!file_exists($mensagem_path)) {
    $data = [
        'mensagem' => 'BEM-VINDO AO ADM PLAY',
        'cor' => '#ffffff',
        'bgcolor' => '#ff0000',
        'opacity' => '1',
        'fontSize' => '16',
        'efeito' => 'scroll'
    ];
    file_put_contents($mensagem_path, json_encode($data));
}

echo "Sucesso! Arquivos copiados e banco de dados inicializado.";
?>
