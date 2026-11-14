<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Services\BackupService;

class BackupController extends Controller
{
    public function index(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            $this->redirect('/login');
        }

        $alertSuccess = '';
        $alertError = '';
        $alertInfo = '';

        if ($request->isPost()) {
            $action = (string)$request->input('action', '');
            $backupId = (string)$request->input('backup_id', '');

            if ($action === 'create_manual') {
                $notes = trim((string)$request->input('notes', 'Backup manual criado pelo administrador'));
                $res = BackupService::createBackup('manual', false, $notes);
                if (!empty($res['created'])) {
                    $alertSuccess = $res['message'];
                } else {
                    $alertError = $res['message'] ?? 'Erro ao criar backup manual.';
                }
            } elseif ($action === 'run_smart_check') {
                $res = BackupService::createBackup('auto_2h', true, 'Checagem inteligente de alteração');
                if (($res['status'] ?? '') === 'created') {
                    $alertSuccess = 'Alteração detectada nos bancos! ' . ($res['message'] ?? '');
                } elseif (($res['status'] ?? '') === 'skipped_unchanged') {
                    $alertInfo = $res['message'];
                } else {
                    $alertError = $res['message'] ?? 'Erro ao executar verificação inteligente.';
                }
            } elseif ($action === 'restore' && $backupId !== '') {
                $res = BackupService::restoreBackup($backupId);
                if (!empty($res['success'])) {
                    $alertSuccess = $res['message'];
                } else {
                    $alertError = $res['message'] ?? 'Falha ao restaurar backup.';
                }
            } elseif ($action === 'toggle_pin' && $backupId !== '') {
                $res = BackupService::togglePin($backupId);
                if (!empty($res['success'])) {
                    $alertSuccess = $res['message'];
                } else {
                    $alertError = $res['message'] ?? 'Erro ao alterar proteção do backup.';
                }
            } elseif ($action === 'delete' && $backupId !== '') {
                $res = BackupService::deleteBackup($backupId);
                if (!empty($res['success'])) {
                    $alertSuccess = $res['message'];
                } else {
                    $alertError = $res['message'] ?? 'Não foi possível excluir o backup.';
                }
            } elseif ($action === 'cleanup') {
                $deleted = BackupService::runRetentionCleanup();
                $alertInfo = $deleted > 0
                    ? "Limpeza concluída: {$deleted} backup(s) antigo(s) (> 7 dias) foram removidos."
                    : "Limpeza verificada: Nenhum backup elegível para exclusão (> 7 dias) foi encontrado.";
            }
        }

        // Carrega índice do cofre; se estiver vazio, gera o primeiro snapshot base automaticamente
        $vaultIndex = BackupService::getVaultIndex();
        if (empty($vaultIndex['backups'])) {
            $initRes = BackupService::createBackup('auto_2h', false, 'Snapshot Inicial Automático (Baseline)');
            if (!empty($initRes['created'])) {
                $alertInfo = $alertInfo ?: 'Cofre inicializado! O primeiro backup base (Baseline) foi criado automaticamente.';
            }
            $vaultIndex = BackupService::getVaultIndex();
        }

        // Inspeção ao vivo do estado atual dos bancos para mostrar comparativo na tela
        $liveInspection = BackupService::inspectDatabasesAndFingerprint(false);
        $latestBackup = $vaultIndex['backups'][0] ?? null;
        $hasPendingChanges = empty($latestBackup) || (($latestBackup['data_fingerprint'] ?? '') !== $liveInspection['fingerprint']);

        // Filtros de pesquisa (Data Inicial, Data Final, Tipo e Busca textual)
        $filterDateStart = trim((string)$request->input('date_start', ''));
        $filterDateEnd = trim((string)$request->input('date_end', ''));
        $filterType = trim((string)$request->input('type', ''));
        $filterQuery = trim((string)$request->input('q', ''));

        $allBackups = $vaultIndex['backups'] ?? [];
        $filteredBackups = array_filter($allBackups, function (array $b) use ($filterDateStart, $filterDateEnd, $filterType, $filterQuery): bool {
            $bDate = substr((string)($b['created_at'] ?? ''), 0, 10);

            if ($filterDateStart !== '' && $bDate < $filterDateStart) {
                return false;
            }
            if ($filterDateEnd !== '' && $bDate > $filterDateEnd) {
                return false;
            }
            if ($filterType !== '' && ($b['type'] ?? '') !== $filterType) {
                return false;
            }
            if ($filterQuery !== '') {
                $haystack = strtolower(
                    ($b['filename'] ?? '') . ' ' .
                    ($b['notes'] ?? '') . ' ' .
                    ($b['data_fingerprint'] ?? '') . ' ' .
                    ($b['created_at'] ?? '')
                );
                if (strpos($haystack, strtolower($filterQuery)) === false) {
                    return false;
                }
            }
            return true;
        });

        // Soma do tamanho em disco ocupado pelo cofre
        $totalVaultBytes = 0;
        foreach ($allBackups as $b) {
            $totalVaultBytes += (int)($b['size_bytes'] ?? 0);
        }

        $this->render('admin/backups', [
            'pageTitle' => 'Cofre de Backups & Recuperação - Player99',
            'pageHeading' => 'Cofre de Backups & Recuperação (Ciclo 2h / 7 Dias)',
            'currentRoute' => '/admin/backups',
            'currentUser' => Auth::user(),
            'alertSuccess' => $alertSuccess,
            'alertError' => $alertError,
            'alertInfo' => $alertInfo,
            'vaultDir' => BackupService::getVaultDir(),
            'vaultIndex' => $vaultIndex,
            'allBackupsCount' => count($allBackups),
            'backups' => array_values($filteredBackups),
            'totalVaultBytes' => $totalVaultBytes,
            'liveInspection' => $liveInspection,
            'hasPendingChanges' => $hasPendingChanges,
            'filterDateStart' => $filterDateStart,
            'filterDateEnd' => $filterDateEnd,
            'filterType' => $filterType,
            'filterQuery' => $filterQuery,
        ], 'master');
    }

    public function download(Request $request): void
    {
        if (!Auth::check() || !Auth::isRoot()) {
            $this->redirect('/login');
        }

        $backupId = (string)$request->input('id', '');
        $fileInfo = BackupService::getBackupFilePath($backupId);

        if (!$fileInfo || !is_file($fileInfo['path'])) {
            http_response_code(404);
            echo "Arquivo de backup não encontrado.";
            exit;
        }

        $path = $fileInfo['path'];
        $filename = $fileInfo['filename'];

        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public function cronWebhook(Request $request): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $force = $request->input('force') === '1';

        $res = BackupService::maybeRunScheduledCheck($force);
        if ($res === null) {
            $index = BackupService::getVaultIndex();
            $nextInSec = max(0, BackupService::INTERVAL_SECONDS - (time() - (int)($index['last_check_timestamp'] ?? 0)));
            echo json_encode([
                'status' => 'waiting_interval',
                'last_check_at' => $index['last_check_at'] ?? null,
                'next_check_in_seconds' => $nextInSec
            ]);
            exit;
        }

        echo json_encode($res);
        exit;
    }
}
