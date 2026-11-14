<?php
// Script CLI de Backup Automático (Ciclo de 2 em 2 horas + Retenção de 7 dias)
// Agendamento Cron (a cada 2 horas):
//   php /www/wwwroot/player99.prod/cli/backup_cron.php
// Para forçar backup imediato mesmo sem alterações:
//   php cli/backup_cron.php --force

$projectRoot = dirname(__DIR__);
$appConfig = @include $projectRoot . '/config/app.php';
date_default_timezone_set($appConfig['timezone'] ?? 'America/Sao_Paulo');

require_once $projectRoot . '/app/Core/Autoloader.php';
\App\Core\Autoloader::register($projectRoot);

use App\Services\BackupService;

$args = $argv ?? [];
$force = in_array('--force', $args, true) || in_array('-f', $args, true);

$onlyIfChanged = !$force;
$type = $force ? 'manual' : 'auto_2h';
$notes = $force ? 'Backup forçado via terminal (CLI)' : 'Verificação agendada de 2h (Cron)';

$result = BackupService::createBackup($type, $onlyIfChanged, $notes);

$status = strtoupper($result['status'] ?? 'UNKNOWN');
$msg = $result['message'] ?? '';
$vaultDir = BackupService::getVaultDir();

echo sprintf(
    "[%s] [%s] %s | Cofre: %s\n",
    date('Y-m-d H:i:s'),
    $status,
    $msg,
    $vaultDir
);
