<?php
// backup_rotina.php
// Script para fazer backup automático dos bancos de dados e imagens do Player99

date_default_timezone_set('America/Sao_Paulo');

$backup_dir = '/www/wwwroot/backups_player99/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

$date_str = date('Y-m-d_H-i-s');
$zip_filename = $backup_dir . 'backup_player99_' . $date_str . '.zip';

$directories_to_backup = [
    dirname(__DIR__) . '/api/db',
    __DIR__ . '/uploads',
    __DIR__ . '/banners'
];

$zip = new ZipArchive();
if ($zip->open($zip_filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    
    foreach ($directories_to_backup as $dir) {
        if (is_dir($dir)) {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $file_path = $file->getRealPath();
                    // O caminho relativo para manter a estrutura dentro do ZIP
                    $relative_path = substr($file_path, strlen(__DIR__) + 1);
                    $zip->addFile($file_path, $relative_path);
                }
            }
        }
    }
    
    // Backup de arquivos soltos importantes na raiz (JSONs)
    $extra_files = ['whats.json', 'vencimento.json', 'clientes_vencidos.json'];
    foreach ($extra_files as $f) {
        if (file_exists(__DIR__ . '/' . $f)) {
            $zip->addFile(__DIR__ . '/' . $f, $f);
        }
    }

    $zip->close();
    echo "Backup criado com sucesso: " . basename($zip_filename) . "<br>";
} else {
    echo "Erro ao criar arquivo ZIP.<br>";
}

// Limpeza de backups antigos (mantém apenas os últimos 7 dias)
$days_to_keep = 7;
$files = glob($backup_dir . '*.zip');
$now = time();

foreach ($files as $file) {
    if (is_file($file)) {
        if ($now - filemtime($file) >= 60 * 60 * 24 * $days_to_keep) {
            unlink($file);
            echo "Backup antigo removido: " . basename($file) . "<br>";
        }
    }
}
?>
