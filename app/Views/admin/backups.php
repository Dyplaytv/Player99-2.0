<?php
use App\Services\BackupService;

$statsLive = $liveInspection['stats'] ?? [];
$checkLogs = $vaultIndex['check_logs'] ?? [];
$lastCheckTs = (int)($vaultIndex['last_check_timestamp'] ?? 0);
$nextCheckTs = $lastCheckTs > 0 ? ($lastCheckTs + BackupService::INTERVAL_SECONDS) : time();
$minutesToNext = max(0, (int)ceil(($nextCheckTs - time()) / 60));
?>

<style>
    .vault-path-box {
        background: rgba(10, 12, 20, 0.85);
        border: 1px solid rgba(52, 226, 228, 0.25);
        border-radius: 12px;
        padding: 12px 16px;
        font-family: monospace;
        font-size: 0.86rem;
        color: #34e2e4;
        word-break: break-all;
    }
    .badge-type-auto {
        background: rgba(52, 226, 228, 0.14);
        border: 1px solid rgba(52, 226, 228, 0.35);
        color: #34e2e4;
    }
    .badge-type-manual {
        background: rgba(59, 130, 246, 0.18);
        border: 1px solid rgba(59, 130, 246, 0.45);
        color: #93c5fd;
    }
    .badge-type-prerestore {
        background: rgba(245, 158, 11, 0.18);
        border: 1px solid rgba(245, 158, 11, 0.45);
        color: #fcd34d;
    }
    .snapshot-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 8px;
        padding: 3px 8px;
        font-size: 0.76rem;
        color: #d8e0f5;
        margin: 2px;
    }
    .form-control-dark, .form-select-dark {
        background: rgba(12, 14, 22, 0.9) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #fff !important;
        border-radius: 10px;
    }
    .form-control-dark:focus, .form-select-dark:focus {
        border-color: #34e2e4 !important;
        box-shadow: 0 0 0 3px rgba(52, 226, 228, 0.15) !important;
    }
    .check-log-item {
        border-left: 3px solid rgba(255, 255, 255, 0.15);
        padding: 8px 12px;
        margin-bottom: 8px;
        background: rgba(16, 18, 28, 0.65);
        border-radius: 0 10px 10px 0;
        font-size: 0.83rem;
    }
    .check-log-item.created {
        border-left-color: #10b981;
    }
    .check-log-item.skipped {
        border-left-color: #64748b;
    }
</style>

<?php if (!empty($alertSuccess)): ?>
    <div class="alert alert-success border-0 shadow-sm mb-4" style="background: rgba(16, 185, 129, 0.18); border: 1px solid rgba(16, 185, 129, 0.4) !important; color: #6ee7b7; border-radius: 14px;">
        <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($alertSuccess) ?>
    </div>
<?php endif; ?>

<?php if (!empty($alertInfo)): ?>
    <div class="alert alert-info border-0 shadow-sm mb-4" style="background: rgba(52, 226, 228, 0.14); border: 1px solid rgba(52, 226, 228, 0.35) !important; color: #7dd3fc; border-radius: 14px;">
        <i class="fa fa-info-circle me-2"></i> <?= htmlspecialchars($alertInfo) ?>
    </div>
<?php endif; ?>

<?php if (!empty($alertError)): ?>
    <div class="alert alert-danger border-0 shadow-sm mb-4" style="background: rgba(239, 68, 68, 0.18); border: 1px solid rgba(239, 68, 68, 0.4) !important; color: #fca5a5; border-radius: 14px;">
        <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($alertError) ?>
    </div>
<?php endif; ?>

<!-- 1. KPIs & Status de Proteção -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="glass-card h-100 mb-0">
            <div class="stat-card">
                <div class="stat-icon <?= $hasPendingChanges ? 'orange' : 'green' ?>">
                    <i class="fa <?= $hasPendingChanges ? 'fa-sync-alt' : 'fa-shield-alt' ?>"></i>
                </div>
                <div>
                    <div class="stat-val" style="font-size: 1.2rem; color: <?= $hasPendingChanges ? '#fbbf24' : '#34d399' ?>;">
                        <?= $hasPendingChanges ? 'Alterações Novas' : '100% Sincronizado' ?>
                    </div>
                    <div class="stat-label">
                        <?= $hasPendingChanges ? 'Banco mudou após último backup' : 'Idêntico ao último backup' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="glass-card h-100 mb-0">
            <div class="stat-card">
                <div class="stat-icon cyan">
                    <i class="fa fa-clock"></i>
                </div>
                <div>
                    <div class="stat-val" style="font-size: 1.25rem;">
                        <?= $minutesToNext > 0 ? "Em {$minutesToNext} min" : "Pronto agora" ?>
                    </div>
                    <div class="stat-label">
                        Ciclo 2h • Último: <?= !empty($vaultIndex['last_check_at']) ? date('d/m H:i', strtotime($vaultIndex['last_check_at'])) : '-' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="glass-card h-100 mb-0">
            <div class="stat-card">
                <div class="stat-icon blue">
                    <i class="fa fa-database"></i>
                </div>
                <div>
                    <div class="stat-val" style="font-size: 1.4rem;">
                        <?= (int)$allBackupsCount ?> <small class="fs-6 fw-normal text-muted">snapshots</small>
                    </div>
                    <div class="stat-label">
                        Ocupado: <?= BackupService::formatBytes((int)$totalVaultBytes) ?> (Ciclo 7d)
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="glass-card h-100 mb-0">
            <div class="stat-card">
                <div class="stat-icon green">
                    <i class="fa fa-check-double"></i>
                </div>
                <div>
                    <div class="stat-val" style="font-size: 1.25rem;">
                        <?= (int)($statsLive['clientes'] ?? 0) ?> Cli / <?= (int)($statsLive['telas'] ?? 0) ?> Telas
                    </div>
                    <div class="stat-label">
                        <?= (int)($statsLive['databases_count'] ?? 0) ?> bancos SQLite monitorados
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 2. Barra de Controles e Pasta Externa Isolada -->
<div class="glass-card mb-4">
    <div class="row align-items-center g-3">
        <div class="col-lg-5">
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">
                    <i class="fa fa-folder-open me-1"></i> Cofre Externo Isolado do Git
                </span>
                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                    Deduplicação SHA-256 Ativa
                </span>
            </div>
            <div class="vault-path-box mt-2" title="Pasta física separada do repositório Git">
                <i class="fa fa-hdd me-1"></i> <?= htmlspecialchars($vaultDir) ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                <!-- Botão 1: Checagem Inteligente (Só grava se houver mudança) -->
                <form method="POST" action="/admin/backups" class="d-inline">
                    <input type="hidden" name="action" value="run_smart_check">
                    <button type="submit" class="btn btn-outline-info fw-bold" style="border-radius: 12px; padding: 10px 16px;">
                        <i class="fa fa-fingerprint me-1"></i> Checar Alteração Agora (Smart 2h)
                    </button>
                </form>

                <!-- Botão 2: Criar Backup Manual Forçado (com modal de nota) -->
                <button type="button" class="btn btn-cyan" data-bs-toggle="modal" data-bs-target="#modalManualBackup">
                    <i class="fa fa-plus-circle me-1"></i> Criar Backup Manual Agora
                </button>

                <!-- Botão 3: Limpeza de 7 dias -->
                <form method="POST" action="/admin/backups" class="d-inline">
                    <input type="hidden" name="action" value="cleanup">
                    <button type="submit" class="btn btn-outline-secondary text-light" style="border-radius: 12px; padding: 10px 14px;" title="Remove backups com mais de 7 dias (preservando os fixados e mínimo de 3 backups)">
                        <i class="fa fa-broom me-1"></i> Limpar > 7d
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 3. Filtro e Pesquisa por Data -->
<div class="glass-card mb-4">
    <form method="GET" action="/admin/backups" id="filterForm">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">
                    <i class="fa fa-calendar-alt me-1"></i> Data Inicial
                </label>
                <input type="date" name="date_start" id="date_start" value="<?= htmlspecialchars($filterDateStart) ?>" class="form-control form-control-dark">
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold text-muted mb-1">
                    <i class="fa fa-calendar-check me-1"></i> Data Final
                </label>
                <input type="date" name="date_end" id="date_end" value="<?= htmlspecialchars($filterDateEnd) ?>" class="form-control form-control-dark">
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">
                    <i class="fa fa-tag me-1"></i> Tipo
                </label>
                <select name="type" class="form-select form-select-dark">
                    <option value="">Todos os Tipos</option>
                    <option value="auto_2h" <?= $filterType === 'auto_2h' ? 'selected' : '' ?>>Automático (2h)</option>
                    <option value="manual" <?= $filterType === 'manual' ? 'selected' : '' ?>>Manual</option>
                    <option value="pre_restore" <?= $filterType === 'pre_restore' ? 'selected' : '' ?>>Pré-Restauração</option>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label small fw-bold text-muted mb-1">
                    <i class="fa fa-search me-1"></i> Buscar Hash / Nota
                </label>
                <input type="text" name="q" value="<?= htmlspecialchars($filterQuery) ?>" placeholder="Ex: nota, hash..." class="form-control form-control-dark">
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-cyan w-100">
                    <i class="fa fa-filter me-1"></i> Filtrar
                </button>
                <?php if ($filterDateStart !== '' || $filterDateEnd !== '' || $filterType !== '' || $filterQuery !== ''): ?>
                    <a href="/admin/backups" class="btn btn-outline-light" style="border-radius: 12px;" title="Limpar Filtros">
                        <i class="fa fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Atalhos rápidos de data -->
        <div class="d-flex flex-wrap align-items-center gap-2 mt-3 pt-2 border-top border-secondary border-opacity-25">
            <span class="small text-muted me-1"><i class="fa fa-bolt text-warning me-1"></i> Período rápido:</span>
            <button type="button" class="btn btn-sm btn-outline-secondary text-light py-1 px-2" onclick="setQuickDate(0)">Hoje</button>
            <button type="button" class="btn btn-sm btn-outline-secondary text-light py-1 px-2" onclick="setQuickDate(1)">Ontem e Hoje</button>
            <button type="button" class="btn btn-sm btn-outline-secondary text-light py-1 px-2" onclick="setQuickDate(3)">Últimos 3 Dias</button>
            <button type="button" class="btn btn-sm btn-outline-secondary text-light py-1 px-2" onclick="setQuickDate(7)">Ciclo 7 Dias</button>
            <span class="ms-auto small text-muted">
                Exibindo <strong><?= count($backups) ?></strong> de <strong><?= (int)$allBackupsCount ?></strong> backups
            </span>
        </div>
    </form>
</div>

<!-- 4. Lista de Backups + Histórico de Verificações de 2h -->
<div class="row g-4">
    <div class="col-xl-8">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="fa fa-archive text-info me-2"></i> Snapshots Disponíveis para Download ou Restauração
                </h6>
                <span class="small text-muted">
                    <i class="fa fa-thumbtack text-warning me-1"></i> Backups fixados nunca expiram no ciclo de 7 dias
                </span>
            </div>

            <?php if (empty($backups)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa fa-folder-open fa-3x mb-3 opacity-50"></i>
                    <p class="mb-0">Nenhum backup encontrado para os filtros selecionados.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Data / Hora</th>
                                <th>Tipo & Etiqueta</th>
                                <th>Dados no Snapshot</th>
                                <th>Tamanho / Hash</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $idx => $b):
                                $bId = $b['id'] ?? '';
                                $bType = $b['type'] ?? 'manual';
                                $isPinned = !empty($b['pinned']);
                                $bStats = $b['stats'] ?? [];
                                $shortHash = substr((string)($b['data_fingerprint'] ?? ''), 0, 10);

                                $typeBadgeClass = 'badge-type-auto';
                                $typeLabel = 'Automático 2h';
                                if ($bType === 'manual') {
                                    $typeBadgeClass = 'badge-type-manual';
                                    $typeLabel = 'Manual';
                                } elseif ($bType === 'pre_restore') {
                                    $typeBadgeClass = 'badge-type-prerestore';
                                    $typeLabel = 'Pré-Restauração';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white">
                                            <?php if ($isPinned): ?>
                                                <i class="fa fa-thumbtack text-warning me-1" title="Protegido contra exclusão de 7 dias"></i>
                                            <?php endif; ?>
                                            <?= date('d/m/Y', strtotime($b['created_at'])) ?>
                                            <span class="text-info ms-1"><?= date('H:i:s', strtotime($b['created_at'])) ?></span>
                                        </div>
                                        <div class="small text-muted">
                                            <?php
                                            $diffSec = max(0, time() - (int)($b['timestamp'] ?? time()));
                                            if ($diffSec < 60) {
                                                echo 'Agora mesmo';
                                            } elseif ($diffSec < 3600) {
                                                echo 'Há ' . floor($diffSec / 60) . ' min';
                                            } elseif ($diffSec < 86400) {
                                                echo 'Há ' . floor($diffSec / 3600) . 'h';
                                            } else {
                                                echo 'Há ' . floor($diffSec / 86400) . ' dia(s)';
                                            }
                                            ?>
                                        </div>
                                    </td>

                                    <td>
                                        <span class="badge <?= $typeBadgeClass ?> rounded-pill px-2 py-1">
                                            <?= htmlspecialchars($typeLabel) ?>
                                        </span>
                                        <?php if (!empty($b['notes'])): ?>
                                            <div class="small text-muted mt-1" style="max-width: 210px;">
                                                <?= htmlspecialchars($b['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="d-flex flex-wrap">
                                            <span class="snapshot-pill" title="Clientes na tabela ibo">
                                                <i class="fa fa-users text-info"></i> <?= (int)($bStats['clientes'] ?? 0) ?> cli
                                            </span>
                                            <span class="snapshot-pill" title="Telas cadastradas">
                                                <i class="fa fa-tv text-success"></i> <?= (int)($bStats['telas'] ?? 0) ?> telas
                                            </span>
                                            <span class="snapshot-pill" title="Pedidos pagos no financeiro">
                                                <i class="fa fa-receipt text-warning"></i> <?= (int)($bStats['pedidos_pagos'] ?? 0) ?> pgto
                                            </span>
                                            <span class="snapshot-pill" title="Produtos na loja">
                                                <i class="fa fa-shopping-bag text-primary"></i> <?= (int)($bStats['produtos_loja'] ?? 0) ?> prod
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-white">
                                            <?= BackupService::formatBytes((int)($b['size_bytes'] ?? 0)) ?>
                                        </div>
                                        <div class="small text-muted font-monospace" title="Assinatura SHA-256: <?= htmlspecialchars($b['data_fingerprint'] ?? '') ?>">
                                            #<?= htmlspecialchars($shortHash) ?> (<?= (int)($b['databases_count'] ?? 0) ?> DBs)
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex gap-1">
                                            <!-- Download .ZIP -->
                                            <a href="/admin/backups/download?id=<?= urlencode($bId) ?>" class="btn btn-sm btn-outline-info" title="Baixar arquivo .ZIP">
                                                <i class="fa fa-download"></i>
                                            </a>

                                            <!-- Fixar / Proteger contra limpeza de 7 dias -->
                                            <form method="POST" action="/admin/backups" class="d-inline">
                                                <input type="hidden" name="action" value="toggle_pin">
                                                <input type="hidden" name="backup_id" value="<?= htmlspecialchars($bId) ?>">
                                                <button type="submit" class="btn btn-sm <?= $isPinned ? 'btn-warning text-dark' : 'btn-outline-secondary text-light' ?>" title="<?= $isPinned ? 'Fixado (Não expira em 7 dias) - Clique para desafixar' : 'Fixar este backup para nunca expirar' ?>">
                                                    <i class="fa fa-thumbtack"></i>
                                                </button>
                                            </form>

                                            <!-- Restaurar Backup -->
                                            <form method="POST" action="/admin/backups" class="d-inline" onsubmit="return confirm('ATENÇÃO: Deseja restaurar os bancos de dados para o ponto de <?= htmlspecialchars($b['created_at']) ?>?\n\nFique tranquilo: o sistema criará automaticamente um Backup de Segurança do estado atual antes de restaurar.');">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="backup_id" value="<?= htmlspecialchars($bId) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Restaurar bancos para esta data/hora">
                                                    <i class="fa fa-undo"></i> Restaurar
                                                </button>
                                            </form>

                                            <!-- Excluir -->
                                            <?php if (!$isPinned): ?>
                                                <form method="POST" action="/admin/backups" class="d-inline" onsubmit="return confirm('Excluir permanentemente o arquivo de backup de <?= htmlspecialchars($b['created_at']) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="backup_id" value="<?= htmlspecialchars($bId) ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir este backup">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Coluna Lateral: Log de Checagens de 2h & Garantias da Arquitetura -->
    <div class="col-xl-4">
        <div class="glass-card mb-4">
            <h6 class="fw-bold mb-3">
                <i class="fa fa-history text-cyan me-2"></i> Diário de Verificações (2 em 2h)
            </h6>
            <p class="small text-muted mb-3">
                Mostra todas as checagens automáticas. Quando o banco não sofre alterações, o sistema registra a checagem e poupa armazenamento.
            </p>

            <div style="max-height: 360px; overflow-y: auto; padding-right: 4px;">
                <?php if (empty($checkLogs)): ?>
                    <div class="text-muted small text-center py-3">Nenhuma verificação registrada ainda.</div>
                <?php else: ?>
                    <?php foreach (array_slice($checkLogs, 0, 20) as $log):
                        $isCreated = ($log['result'] ?? '') === 'created';
                    ?>
                        <div class="check-log-item <?= $isCreated ? 'created' : 'skipped' ?>">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold <?= $isCreated ? 'text-success' : 'text-muted' ?>">
                                    <i class="fa <?= $isCreated ? 'fa-check-circle' : 'fa-minus-circle' ?> me-1"></i>
                                    <?= $isCreated ? 'Backup Gravado' : 'Poupado (Sem Alteração)' ?>
                                </span>
                                <span class="text-muted" style="font-size: 0.75rem;">
                                    <?= date('d/m H:i:s', strtotime($log['checked_at'] ?? 'now')) ?>
                                </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.78rem;">
                                <?= htmlspecialchars($log['message'] ?? '') ?>
                                <span class="opacity-75">(<?= (int)($log['elapsed_ms'] ?? 0) ?>ms)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="glass-card">
            <h6 class="fw-bold mb-2">
                <i class="fa fa-shield-virus text-success me-2"></i> Blindagens Ativas
            </h6>
            <ul class="small text-muted ps-3 mb-0" style="line-height: 1.7;">
                <li><strong>Hot Backup SQLite (`VACUUM INTO` + WAL Checkpoint):</strong> Garante que os dados em memória (`.db-wal`) sejam consolidados sem corromper o banco em uso.</li>
                <li><strong>Filtro Anti-Ping:</strong> Pings de 30s das TVs (`ultimo_ping`) são ignorados no cálculo do hash para não criar backups falsos a cada 2h.</li>
                <li><strong>Trava Anti-Vazio de 7 Dias:</strong> Mesmo se o sistema ficar mais de 7 dias sem alterações, os <strong>3 últimos backups</strong> jamais são apagados.</li>
                <li><strong>Snapshot Pré-Restauração:</strong> Antes de voltar qualquer backup antigo, o estado atual é salvo automaticamente.</li>
            </ul>
        </div>
    </div>
</div>

<!-- Modal: Criar Backup Manual -->
<div class="modal fade" id="modalManualBackup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #121521; border: 1px solid rgba(52, 226, 228, 0.3); border-radius: 18px; color: #fff;">
            <form method="POST" action="/admin/backups">
                <input type="hidden" name="action" value="create_manual">
                <div class="modal-header border-bottom border-secondary border-opacity-25">
                    <h5 class="modal-title fw-bold">
                        <i class="fa fa-save text-info me-2"></i> Criar Backup Manual Agora
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Este comando gera imediatamente um pacote <code>.zip</code> com todos os bancos SQLite na pasta externa do cofre, mesmo que não tenha havido alterações recentes.
                    </p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Observação / Etiqueta de Identificação (Opcional)</label>
                        <input type="text" name="notes" class="form-control form-control-dark" placeholder="Ex: Antes de alterar planos ou testar campanha">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary text-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-cyan">
                        <i class="fa fa-check me-1"></i> Gerar Snapshot Agora
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function setQuickDate(daysBack) {
    const today = new Date();
    const endStr = today.toISOString().split('T')[0];

    const start = new Date();
    start.setDate(today.getDate() - daysBack);
    const startStr = start.toISOString().split('T')[0];

    document.getElementById('date_start').value = startStr;
    document.getElementById('date_end').value = endStr;
    document.getElementById('filterForm').submit();
}
</script>
