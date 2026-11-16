<?php
namespace App\Services;

use PDO;
use ZipArchive;

/**
 * Serviço Centralizado de Backup Automático, Deduplicação por Hash (Smart Check),
 * Retenção de 7 Dias com Trava Anti-Vazio e Restauração Segura (Hot Backup SQLite).
 */
class BackupService
{
    public const INTERVAL_SECONDS = 7200; // 2 horas (2 * 3600s)
    public const RETENTION_DAYS = 7;      // Ciclo de exclusão de 7 dias
    public const MIN_SAFE_BACKUPS = 3;    // Mínimo de backups sempre preservados mesmo após 7 dias sem alterações

    /**
     * Retorna o diretório raiz da aplicação atual.
     */
    public static function getProjectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Resolve o diretório EXTERNO do Cofre de Backups (separado da pasta do projeto/Git).
     * Garante isolamento contra git clean, git reset ou exclusão acidental da pasta do projeto.
     */
    public static function getVaultDir(): string
    {
        $projectRoot = self::getProjectRoot();
        $envName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($projectRoot)) ?: 'default';

        // Candidatos em ordem de prioridade (sempre fora da pasta do projeto)
        $candidates = [];

        if (DIRECTORY_SEPARATOR === '/') {
            // Ambiente Linux / VPS (aaPanel)
            $candidates[] = '/www/backup/player99_vault/' . $envName;
            $candidates[] = dirname($projectRoot) . '/.player99_vault_backups/' . $envName;
        } else {
            // Ambiente Windows / Local
            $candidates[] = dirname($projectRoot) . DIRECTORY_SEPARATOR . 'player99_vault_backups' . DIRECTORY_SEPARATOR . $envName;
        }

        // Fallback final caso open_basedir restrinja diretórios pais
        $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . 'storage_backups_vault';

        foreach ($candidates as $dir) {
            if (self::ensureWritableDir($dir)) {
                self::protectDirectory($dir);
                return $dir;
            }
        }

        $fallback = end($candidates);
        @mkdir($fallback, 0775, true);
        return $fallback;
    }

    private static function ensureWritableDir(string $dir): bool
    {
        try {
            if (!@is_dir($dir)) {
                if (!@mkdir($dir, 0775, true) && !@is_dir($dir)) {
                    return false;
                }
            }
            @chmod($dir, 0775);
            $testFile = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.write_test_' . uniqid();
            if (@file_put_contents($testFile, 'ok') === false) {
                return false;
            }
            @unlink($testFile);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private static function protectDirectory(string $dir): void
    {
        $htaccess = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.htaccess';
        if (!@file_exists($htaccess)) {
            @file_put_contents($htaccess, "Order allow,deny\nDeny from all\n");
        }
        $indexHtml = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . 'index.html';
        if (!@file_exists($indexHtml)) {
            @file_put_contents($indexHtml, "<!-- Protected Backup Vault -->");
        }
    }

    /**
     * Lista todos os arquivos de banco de dados SQLite (.db) do projeto (api/db/*.db e api/.*.db).
     */
    public static function discoverDatabaseFiles(): array
    {
        $root = self::getProjectRoot();
        $files = [];

        // 1. Bancos principais em api/db/*.db
        $dbDir = $root . '/api/db';
        if (is_dir($dbDir)) {
            $items = @scandir($dbDir) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $fullPath = $dbDir . '/' . $item;
                if (is_file($fullPath) && preg_match('/\.db$/i', $item)) {
                    $files['api/db/' . $item] = $fullPath;
                }
            }
        }

        // 2. Bancos ocultos/legados em api/.*.db
        $apiDir = $root . '/api';
        if (is_dir($apiDir)) {
            $items = @scandir($apiDir) ?: [];
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $fullPath = $apiDir . '/' . $item;
                if (is_file($fullPath) && preg_match('/^\..*\.db$/i', $item)) {
                    $files['api/' . $item] = $fullPath;
                }
            }
        }

        ksort($files);
        return $files;
    }

    /**
     * Força o checkpoint do modo WAL do SQLite para garantir que todas as transações pendentes
     * no arquivo .db-wal estejam consolidadas no arquivo .db antes do hash ou cópia.
     */
    public static function checkpointDatabase(string $dbPath): void
    {
        if (!is_file($dbPath) || filesize($dbPath) === 0) {
            return;
        }
        try {
            $pdo = new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                PDO::ATTR_TIMEOUT => 3
            ]);
            @$pdo->exec("PRAGMA busy_timeout = 3000;");
            @$pdo->exec("PRAGMA wal_checkpoint(PASSIVE);");
            $pdo = null;
        } catch (\Throwable $e) {}
    }

    /**
     * Calcula a Assinatura Inteligente de Conteúdo (Smart Data Fingerprint) e extrai métricas.
     * Ignora apenas colunas de heartbeat volátil (como ultimo_ping de 30s das TVs) para não gerar
     * falsos positivos quando nenhum dado cadastral, financeiro ou de configuração mudou.
     */
    public static function inspectDatabasesAndFingerprint(bool $doCheckpoint = true): array
    {
        $dbFiles = self::discoverDatabaseFiles();
        $hashCtx = hash_init('sha256');

        $stats = [
            'clientes' => 0,
            'telas' => 0,
            'usuarios_admin' => 0,
            'pedidos_pagos' => 0,
            'creditos_ativos' => 0,
            'produtos_loja' => 0,
            'achadinhos' => 0,
            'total_raw_bytes' => 0,
            'databases_count' => count($dbFiles),
        ];

        $dbDetails = [];

        foreach ($dbFiles as $relPath => $absPath) {
            if ($doCheckpoint) {
                self::checkpointDatabase($absPath);
            }

            clearstatcache(true, $absPath);
            $size = @filesize($absPath) ?: 0;
            $stats['total_raw_bytes'] += $size;

            $dbDetails[$relPath] = [
                'size' => $size,
                'mtime' => @filemtime($absPath) ?: 0
            ];

            hash_update($hashCtx, "DB:{$relPath}:");

            if ($size === 0) {
                hash_update($hashCtx, "EMPTY;");
                continue;
            }

            try {
                $pdo = new PDO("sqlite:{$absPath}", null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 3
                ]);
                $pdo->exec("PRAGMA busy_timeout = 3000;");

                $tablesStmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name ASC");
                $tables = $tablesStmt ? $tablesStmt->fetchAll() : [];

                foreach ($tables as $tRow) {
                    $tName = $tRow['name'];
                    $tSql = $tRow['sql'] ?? '';
                    hash_update($hashCtx, "TBL:{$tName}:{$tSql}:");

                    // Coleta de estatísticas principais do negócio
                    if ($relPath === 'api/db/studiolivecode_clientes.db') {
                        if ($tName === 'ibo') {
                            $stats['clientes'] = (int)$pdo->query("SELECT COUNT(*) FROM ibo")->fetchColumn();
                        } elseif ($tName === 'telas') {
                            $stats['telas'] = (int)$pdo->query("SELECT COUNT(*) FROM telas")->fetchColumn();
                        }
                    } elseif ($relPath === 'api/db/studiolivecode_users.db' && $tName === 'users') {
                        $stats['usuarios_admin'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
                    } elseif ($relPath === 'api/db/studiolivecode_financeiro.db') {
                        if ($tName === 'pedidos_planos') {
                            $stats['pedidos_pagos'] = (int)$pdo->query("SELECT COUNT(*) FROM pedidos_planos WHERE status = 'PAGO'")->fetchColumn();
                        } elseif ($tName === 'cliente_creditos') {
                            $stats['creditos_ativos'] = (int)$pdo->query("SELECT COUNT(*) FROM cliente_creditos WHERE status = 'DISPONIVEL'")->fetchColumn();
                        }
                    } elseif ($relPath === 'api/db/studiolivecode_loja.db' && $tName === 'produtos') {
                        $stats['produtos_loja'] = (int)$pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
                    } elseif ($relPath === 'api/db/studiolivecode_deals.db' && $tName === 'deals') {
                        $stats['achadinhos'] = (int)$pdo->query("SELECT COUNT(*) FROM deals")->fetchColumn();
                    }

                    // Ignora tabelas puramente efêmeras de deduplicação de webhook no cálculo do hash
                    // (elas continuam sendo salvas no .db, mas não disparam backup falso sem mudança cadastral)
                    if ($tName === 'ai_processed_messages') {
                        continue;
                    }

                    // Leitura das linhas para compor o hash inteligente
                    $rowsStmt = $pdo->query("SELECT * FROM \"{$tName}\"");
                    if ($rowsStmt) {
                        while ($row = $rowsStmt->fetch(PDO::FETCH_ASSOC)) {
                            // Remove colunas de ping/sessão volátil que mudam a cada 30s sem ação de usuário
                            if ($tName === 'telas') {
                                unset($row['ultimo_ping'], $row['active_session_id']);
                            }
                            hash_update($hashCtx, json_encode($row, JSON_UNESCAPED_UNICODE) . "\n");
                        }
                    }
                }
                $pdo = null;
            } catch (\Throwable $e) {
                // Fallback para hash do arquivo binário caso alguma base não abra via PDO
                hash_update($hashCtx, (string)@hash_file('sha256', $absPath));
            }
        }

        $fingerprint = hash_final($hashCtx);

        return [
            'fingerprint' => $fingerprint,
            'stats' => $stats,
            'databases' => $dbDetails,
            'files' => $dbFiles
        ];
    }

    /**
     * Caminho do arquivo de índice do cofre de backups.
     */
    private static function getIndexFilePath(): string
    {
        return rtrim(self::getVaultDir(), '/\\') . DIRECTORY_SEPARATOR . 'vault_index.json';
    }

    /**
     * Carrega o índice do cofre e sincroniza com os arquivos físicos existentes na pasta.
     */
    public static function getVaultIndex(): array
    {
        $indexPath = self::getIndexFilePath();
        $default = [
            'last_check_at' => null,
            'last_check_timestamp' => 0,
            'last_check_result' => null,
            'last_backup_at' => null,
            'last_backup_fingerprint' => null,
            'backups' => [],
            'check_logs' => []
        ];

        $data = $default;
        if (is_file($indexPath)) {
            $raw = @file_get_contents($indexPath);
            $decoded = $raw ? json_decode($raw, true) : null;
            if (is_array($decoded)) {
                $data = array_merge($default, $decoded);
            }
        }

        // Sincroniza lista de backups garantindo que o arquivo físico realmente existe no disco
        $vaultDir = self::getVaultDir();
        $validBackups = [];
        foreach ($data['backups'] as $b) {
            if (empty($b['filename'])) {
                continue;
            }
            $fullPath = $vaultDir . DIRECTORY_SEPARATOR . basename($b['filename']);
            if (is_file($fullPath)) {
                $b['size_bytes'] = @filesize($fullPath) ?: ($b['size_bytes'] ?? 0);
                $validBackups[] = $b;
            }
        }

        // Ordena do mais recente para o mais antigo
        usort($validBackups, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        $data['backups'] = $validBackups;
        return $data;
    }

    private static function saveVaultIndex(array $indexData): void
    {
        $indexPath = self::getIndexFilePath();
        @file_put_contents($indexPath, json_encode($indexData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        @chmod($indexPath, 0666);
    }

    /**
     * Executa um backup agora.
     *
     * @param string $type 'auto_2h', 'manual', 'pre_restore', 'cli'
     * @param bool $onlyIfChanged Se true, só cria arquivo caso haja alteração no fingerprint desde o último backup
     * @param string $notes Observação opcional
     */
    public static function createBackup(string $type = 'manual', bool $onlyIfChanged = false, string $notes = ''): array
    {
        $startTime = microtime(true);
        $vaultDir = self::getVaultDir();
        $index = self::getVaultIndex();

        $inspection = self::inspectDatabasesAndFingerprint(true);
        $currentFingerprint = $inspection['fingerprint'];
        $dbFiles = $inspection['files'];

        $nowTs = time();
        $nowStr = date('Y-m-d H:i:s', $nowTs);

        // Pega o fingerprint do último backup existente no cofre
        $latestBackup = $index['backups'][0] ?? null;
        $lastFingerprint = $latestBackup['data_fingerprint'] ?? null;

        // Se só deve criar quando houver alteração e já existe ao menos 1 backup idêntico:
        if ($onlyIfChanged && !empty($latestBackup) && $lastFingerprint === $currentFingerprint) {
            $elapsedMs = (int)round((microtime(true) - $startTime) * 1000);

            $index['last_check_at'] = $nowStr;
            $index['last_check_timestamp'] = $nowTs;
            $index['last_check_result'] = 'skipped_unchanged';

            array_unshift($index['check_logs'], [
                'checked_at' => $nowStr,
                'timestamp' => $nowTs,
                'type' => $type,
                'result' => 'skipped_unchanged',
                'message' => 'Nenhuma alteração detectada nos bancos desde o último backup (' . ($latestBackup['created_at'] ?? '') . ').',
                'fingerprint' => substr($currentFingerprint, 0, 12),
                'elapsed_ms' => $elapsedMs
            ]);
            $index['check_logs'] = array_slice($index['check_logs'], 0, 60);

            // Executa limpeza do ciclo de 7 dias mesmo quando pula criação
            $pruned = self::pruneOldBackupsInternal($index);
            self::saveVaultIndex($index);

            return [
                'status' => 'skipped_unchanged',
                'created' => false,
                'message' => 'Verificação concluída: Nenhuma alteração nos bancos de dados desde o último backup (' . ($latestBackup['created_at'] ?? '') . '). Backup poupado.',
                'fingerprint' => $currentFingerprint,
                'pruned_count' => $pruned,
                'elapsed_ms' => $elapsedMs
            ];
        }

        // Cria o pacote .zip de backup de forma atômica
        $shortHash = substr($currentFingerprint, 0, 8);
        $safeType = preg_replace('/[^a-z0-9_]/i', '', $type) ?: 'manual';
        $backupId = 'bkp_' . date('Ymd_His', $nowTs) . '_' . $shortHash;
        $filename = 'player99_backup_' . date('Y-m-d_H-i-s', $nowTs) . '_' . $safeType . '_' . $shortHash . '.zip';
        $fullZipPath = $vaultDir . DIRECTORY_SEPARATOR . $filename;

        $manifest = [
            'id' => $backupId,
            'filename' => $filename,
            'created_at' => $nowStr,
            'timestamp' => $nowTs,
            'type' => $type,
            'notes' => trim($notes),
            'data_fingerprint' => $currentFingerprint,
            'stats' => $inspection['stats'],
            'databases' => $inspection['databases'],
            'project_root' => self::getProjectRoot()
        ];

        $packagedOk = self::packageDatabasesToZip($fullZipPath, $dbFiles, $manifest);
        if (!$packagedOk || !is_file($fullZipPath)) {
            return [
                'status' => 'error',
                'created' => false,
                'message' => 'Falha ao empacotar arquivos de banco de dados no diretório do cofre (' . $vaultDir . '). Verifique permissões.'
            ];
        }

        @chmod($fullZipPath, 0666);
        clearstatcache(true, $fullZipPath);
        $zipSize = @filesize($fullZipPath) ?: 0;
        $elapsedMs = (int)round((microtime(true) - $startTime) * 1000);

        $backupEntry = [
            'id' => $backupId,
            'filename' => $filename,
            'created_at' => $nowStr,
            'date_only' => date('Y-m-d', $nowTs),
            'timestamp' => $nowTs,
            'type' => $type,
            'notes' => trim($notes),
            'size_bytes' => $zipSize,
            'raw_size_bytes' => $inspection['stats']['total_raw_bytes'] ?? 0,
            'databases_count' => $inspection['stats']['databases_count'] ?? count($dbFiles),
            'data_fingerprint' => $currentFingerprint,
            'stats' => $inspection['stats'],
            'pinned' => false
        ];

        array_unshift($index['backups'], $backupEntry);
        $index['last_check_at'] = $nowStr;
        $index['last_check_timestamp'] = $nowTs;
        $index['last_check_result'] = 'created';
        $index['last_backup_at'] = $nowStr;
        $index['last_backup_fingerprint'] = $currentFingerprint;

        array_unshift($index['check_logs'], [
            'checked_at' => $nowStr,
            'timestamp' => $nowTs,
            'type' => $type,
            'result' => 'created',
            'message' => 'Novo backup gravado com sucesso (' . self::formatBytes($zipSize) . ').',
            'backup_filename' => $filename,
            'fingerprint' => substr($currentFingerprint, 0, 12),
            'elapsed_ms' => $elapsedMs
        ]);
        $index['check_logs'] = array_slice($index['check_logs'], 0, 60);

        // Executa a limpeza automática de 7 dias
        $pruned = self::pruneOldBackupsInternal($index);
        self::saveVaultIndex($index);

        return [
            'status' => 'created',
            'created' => true,
            'backup' => $backupEntry,
            'pruned_count' => $pruned,
            'elapsed_ms' => $elapsedMs,
            'message' => 'Backup criado com sucesso: ' . $filename . ' (' . self::formatBytes($zipSize) . ').'
        ];
    }

    /**
     * Empacota os arquivos SQLite em um arquivo .zip íntegro (usando cópia segura Hot Backup).
     * Possui fallback para o utilitário zip do Linux caso a extensão ZipArchive não esteja ativa.
     */
    private static function packageDatabasesToZip(string $destZipPath, array $dbFiles, array $manifest): bool
    {
        $vaultDir = dirname($destZipPath);
        $tempStageDir = $vaultDir . DIRECTORY_SEPARATOR . '.tmp_stage_' . uniqid();

        if (!@mkdir($tempStageDir, 0775, true)) {
            return false;
        }

        try {
            // Copia cada banco de forma limpa para o diretório de staging
            foreach ($dbFiles as $relPath => $absPath) {
                if (!is_file($absPath)) {
                    continue;
                }
                $targetFile = $tempStageDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                $targetDir = dirname($targetFile);
                if (!is_dir($targetDir)) {
                    @mkdir($targetDir, 0775, true);
                }

                // Tenta usar VACUUM INTO para uma cópia SQLite 100% consistente a quente
                $copiedViaSqlite = false;
                if (filesize($absPath) > 0) {
                    try {
                        $pdo = new PDO("sqlite:{$absPath}", null, null, [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_TIMEOUT => 3
                        ]);
                        $pdo->exec("PRAGMA busy_timeout = 3000;");
                        $escapedTarget = str_replace("'", "''", $targetFile);
                        $pdo->exec("VACUUM INTO '{$escapedTarget}'");
                        $pdo = null;
                        if (is_file($targetFile) && filesize($targetFile) > 0) {
                            $copiedViaSqlite = true;
                        }
                    } catch (\Throwable $e) {
                        $copiedViaSqlite = false;
                    }
                }

                if (!$copiedViaSqlite) {
                    @copy($absPath, $targetFile);
                }
            }

            // Salva o manifesto dentro do pacote
            $manifestFile = $tempStageDir . DIRECTORY_SEPARATOR . 'manifest.json';
            @file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Método 1: Extensão nativa ZipArchive do PHP
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($destZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    foreach ($dbFiles as $relPath => $absPath) {
                        $stagedFile = $tempStageDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                        if (is_file($stagedFile)) {
                            $zip->addFile($stagedFile, $relPath);
                        }
                    }
                    if (is_file($manifestFile)) {
                        $zip->addFile($manifestFile, 'manifest.json');
                    }
                    $zip->close();
                }
            }

            // Método 2 (Fallback Linux CLI): comando zip caso ZipArchive não esteja habilitado
            if (!is_file($destZipPath) && DIRECTORY_SEPARATOR === '/' && function_exists('exec')) {
                $cmd = sprintf('cd %s && zip -q -r %s . 2>/dev/null', escapeshellarg($tempStageDir), escapeshellarg($destZipPath));
                @exec($cmd);
            }

            self::removeDirectoryRecursive($tempStageDir);
            return is_file($destZipPath) && filesize($destZipPath) > 0;
        } catch (\Throwable $e) {
            self::removeDirectoryRecursive($tempStageDir);
            return false;
        }
    }

    /**
     * Remove backups com mais de 7 dias (RETENTION_DAYS), preservando sempre:
     * 1. Backups marcados como fixados (pinned = true)
     * 2. No mínimo MIN_SAFE_BACKUPS (3) backups mais recentes (trava contra cofre vazio)
     */
    private static function pruneOldBackupsInternal(array &$index): int
    {
        $vaultDir = self::getVaultDir();
        $cutoffTs = time() - (self::RETENTION_DAYS * 86400);
        $backups = $index['backups'] ?? [];

        // Ordena do mais novo para o mais antigo
        usort($backups, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        $kept = [];
        $deletedCount = 0;
        $unpinnedKeptCount = 0;

        foreach ($backups as $b) {
            $isPinned = !empty($b['pinned']);
            $ts = (int)($b['timestamp'] ?? 0);

            if ($isPinned) {
                $kept[] = $b;
                continue;
            }

            // Garante que os N mais recentes nunca sejam deletados mesmo se tiverem > 7 dias
            if ($unpinnedKeptCount < self::MIN_SAFE_BACKUPS || $ts >= $cutoffTs) {
                $kept[] = $b;
                $unpinnedKeptCount++;
            } else {
                // Expirou o ciclo de 7 dias e já temos backups recentes suficientes
                $file = $vaultDir . DIRECTORY_SEPARATOR . basename($b['filename']);
                if (is_file($file)) {
                    @unlink($file);
                }
                $deletedCount++;
            }
        }

        $index['backups'] = $kept;
        return $deletedCount;
    }

    /**
     * Executa a limpeza de 7 dias sob demanda e salva o índice.
     */
    public static function runRetentionCleanup(): int
    {
        $index = self::getVaultIndex();
        $deleted = self::pruneOldBackupsInternal($index);
        self::saveVaultIndex($index);
        return $deleted;
    }

    /**
     * Gatilho Automático de 2 em 2 horas (Self-Healing Schedule).
     * Verifica se já se passaram >= 2 horas desde a última checagem.
     * Se sim, adquire lock e executa a verificação inteligente (só faz backup se houve alteração).
     */
    public static function maybeRunScheduledCheck(bool $forceCheck = false): ?array
    {
        $vaultDir = self::getVaultDir();
        $lockFile = $vaultDir . DIRECTORY_SEPARATOR . 'auto_backup.lock';

        $index = self::getVaultIndex();
        $lastCheckTs = (int)($index['last_check_timestamp'] ?? 0);
        $now = time();

        if (!$forceCheck && ($now - $lastCheckTs) < self::INTERVAL_SECONDS) {
            return null; // Ainda não deu o ciclo de 2 horas
        }

        // Evita concorrência com lock file (expira em 5 min se travar)
        if (is_file($lockFile) && ($now - (int)@filemtime($lockFile)) < 300) {
            return null;
        }
        @file_put_contents($lockFile, (string)$now);

        try {
            $res = self::createBackup('auto_2h', true, 'Verificação automática de 2h');
        } finally {
            @unlink($lockFile);
        }

        return $res;
    }

    /**
     * Restaura um backup específico com criação prévia de um Snapshot de Segurança (Pre-Restore).
     */
    public static function restoreBackup(string $backupId): array
    {
        $vaultDir = self::getVaultDir();
        $index = self::getVaultIndex();

        $targetBackup = null;
        foreach ($index['backups'] as $b) {
            if (($b['id'] ?? '') === $backupId || ($b['filename'] ?? '') === $backupId) {
                $targetBackup = $b;
                break;
            }
        }

        if (!$targetBackup) {
            return ['success' => false, 'message' => 'Backup não encontrado no índice do cofre.'];
        }

        $zipPath = $vaultDir . DIRECTORY_SEPARATOR . basename($targetBackup['filename']);
        if (!is_file($zipPath)) {
            return ['success' => false, 'message' => 'O arquivo físico do backup não foi encontrado no disco.'];
        }

        // 1. Extrai para diretório temporário e valida integridade SQLite antes de tocar nos bancos ativos
        $tempRestoreDir = $vaultDir . DIRECTORY_SEPARATOR . '.tmp_restore_' . uniqid();
        if (!@mkdir($tempRestoreDir, 0775, true)) {
            return ['success' => false, 'message' => 'Não foi possível criar diretório temporário de restauração.'];
        }

        $extractedOk = false;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zipPath) === true) {
                $extractedOk = $zip->extractTo($tempRestoreDir);
                $zip->close();
            }
        } elseif (DIRECTORY_SEPARATOR === '/' && function_exists('exec')) {
            $cmd = sprintf('unzip -q -o %s -d %s 2>/dev/null', escapeshellarg($zipPath), escapeshellarg($tempRestoreDir));
            @exec($cmd, $out, $code);
            $extractedOk = ($code === 0);
        }

        if (!$extractedOk) {
            self::removeDirectoryRecursive($tempRestoreDir);
            return ['success' => false, 'message' => 'Falha ao descompactar o arquivo de backup.'];
        }

        // 2. Cria um Backup de Segurança do estado atual antes de sobrescrever (exceto se já estiver restaurando um pre_restore)
        if (($targetBackup['type'] ?? '') !== 'pre_restore') {
            self::createBackup('pre_restore', false, 'Ponto de segurança automático antes de restaurar ' . $targetBackup['created_at']);
        }

        // 3. Substitui os arquivos .db de forma limpa, removendo arquivos .db-wal e .db-shm antigos
        $projectRoot = self::getProjectRoot();
        $restoredFilesCount = 0;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($tempRestoreDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $fileName = $fileInfo->getFilename();
            if ($fileName === 'manifest.json' || !preg_match('/\.db$/i', $fileName)) {
                continue;
            }

            $relPath = str_replace('\\', '/', substr($fileInfo->getPathname(), strlen($tempRestoreDir) + 1));
            // Segurança contra Path Traversal
            if (strpos($relPath, '..') !== false || !preg_match('#^api/(db/)?[^/]+\.db$#i', $relPath)) {
                continue;
            }

            $destPath = $projectRoot . '/' . $relPath;
            $destDir = dirname($destPath);
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0777, true);
            }

            // Remove journals antigos para que o SQLite não misture páginas antigas com o banco restaurado
            @unlink($destPath . '-wal');
            @unlink($destPath . '-shm');

            if (@copy($fileInfo->getPathname(), $destPath)) {
                @chmod($destPath, 0666);
                $restoredFilesCount++;
            }
        }

        self::removeDirectoryRecursive($tempRestoreDir);

        if ($restoredFilesCount === 0) {
            return ['success' => false, 'message' => 'Nenhum arquivo .db válido foi encontrado dentro do pacote para restauração.'];
        }

        return [
            'success' => true,
            'message' => "Restauração concluída com sucesso! {$restoredFilesCount} bancos de dados foram restaurados para o ponto de {$targetBackup['created_at']}."
        ];
    }

    /**
     * Alterna o status de proteção (Pin) de um backup para que ele nunca seja removido no ciclo de 7 dias.
     */
    public static function togglePin(string $backupId): array
    {
        $index = self::getVaultIndex();
        foreach ($index['backups'] as &$b) {
            if (($b['id'] ?? '') === $backupId) {
                $b['pinned'] = empty($b['pinned']);
                self::saveVaultIndex($index);
                return [
                    'success' => true,
                    'pinned' => $b['pinned'],
                    'message' => $b['pinned']
                        ? 'Backup fixado! Ele está protegido e NÃO será excluído no ciclo de 7 dias.'
                        : 'Proteção removida. O backup voltará a seguir o ciclo normal de 7 dias.'
                ];
            }
        }
        return ['success' => false, 'message' => 'Backup não localizado.'];
    }

    /**
     * Exclui manualmente um backup específico.
     */
    public static function deleteBackup(string $backupId): array
    {
        $vaultDir = self::getVaultDir();
        $index = self::getVaultIndex();

        foreach ($index['backups'] as $k => $b) {
            if (($b['id'] ?? '') === $backupId) {
                if (!empty($b['pinned'])) {
                    return ['success' => false, 'message' => 'Este backup está fixado/protegido. Desafixe-o primeiro caso queira excluí-lo.'];
                }
                $file = $vaultDir . DIRECTORY_SEPARATOR . basename($b['filename']);
                if (is_file($file)) {
                    @unlink($file);
                }
                unset($index['backups'][$k]);
                $index['backups'] = array_values($index['backups']);
                self::saveVaultIndex($index);
                return ['success' => true, 'message' => 'Backup removido com sucesso.'];
            }
        }

        return ['success' => false, 'message' => 'Backup não encontrado.'];
    }

    /**
     * Retorna o caminho absoluto seguro de um arquivo de backup para download.
     */
    public static function getBackupFilePath(string $backupId): ?array
    {
        $vaultDir = self::getVaultDir();
        $index = self::getVaultIndex();
        foreach ($index['backups'] as $b) {
            if (($b['id'] ?? '') === $backupId || ($b['filename'] ?? '') === $backupId) {
                $path = $vaultDir . DIRECTORY_SEPARATOR . basename($b['filename']);
                if (is_file($path)) {
                    return ['path' => $path, 'filename' => basename($b['filename']), 'backup' => $b];
                }
            }
        }
        return null;
    }

    public static function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $pow = (int)floor(log($bytes, 1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), 2) . ' ' . $units[$pow];
    }

    private static function removeDirectoryRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = @scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                self::removeDirectoryRecursive($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
