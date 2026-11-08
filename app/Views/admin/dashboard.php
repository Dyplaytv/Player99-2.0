<!-- Estatísticas Cards -->
<div class="row g-4 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="glass-card stat-card">
            <div class="stat-icon cyan">
                <i class="fa fa-tablet-screen-button"></i>
            </div>
            <div>
                <div class="stat-val"><?= number_format($totalTelas, 0, ',', '.') ?></div>
                <div class="stat-label">Telas & Carros Cadastrados</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="glass-card stat-card">
            <div class="stat-icon green">
                <i class="fa fa-satellite-dish"></i>
            </div>
            <div>
                <div class="stat-val text-success"><?= number_format($telasOnline, 0, ',', '.') ?></div>
                <div class="stat-label">Telas Online Transmitindo</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="glass-card stat-card">
            <div class="stat-icon blue">
                <i class="fa fa-user-tie"></i>
            </div>
            <div>
                <div class="stat-val"><?= number_format($totalClientes, 0, ',', '.') ?></div>
                <div class="stat-label">Clientes & Motoristas</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="glass-card stat-card">
            <div class="stat-icon orange">
                <i class="fa fa-users-gear"></i>
            </div>
            <div>
                <div class="stat-val"><?= number_format($totalUsers, 0, ',', '.') ?></div>
                <div class="stat-label">Revendedores & Admins</div>
            </div>
        </div>
    </div>
</div>

<!-- Ações Rápidas e Banner -->
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fa fa-car-side text-info me-2"></i> Telas Recentes em Operação</h5>
                <a href="/admin/telas" class="btn btn-sm btn-outline-info">Ver Todas as Telas <i class="fa fa-arrow-right ms-1"></i></a>
            </div>

            <?php if (empty($recentScreens)): ?>
                <div class="text-center py-5">
                    <i class="fa fa-tv fa-3x mb-3 text-cyan" style="color: #34e2e4; opacity: 0.7;"></i>
                    <p class="fs-5 fw-semibold text-white mb-3">Nenhuma tela cadastrada no momento.</p>
                    <a href="/admin/telas/criar" class="btn btn-cyan px-4 py-2"><i class="fa fa-plus me-1"></i> Cadastrar Nova Tela</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Código / Carro</th>
                                <th>Identificação</th>
                                <th>Status</th>
                                <th>Áudio / Rotação</th>
                                <th>Player ao Vivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentScreens as $tela): 
                                $isOnline = false;
                                if (!empty($tela['ultimo_ping'])) {
                                    $diff = time() - strtotime($tela['ultimo_ping']);
                                    if ($diff <= 180) $isOnline = true;
                                }
                            ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-dark border border-secondary font-monospace px-2 py-1">
                                            <?= htmlspecialchars($tela['player_code'] ?? '---') ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars(!empty($tela['title']) ? $tela['title'] : 'Tela #' . $tela['id']) ?>
                                    </td>
                                    <td>
                                        <?php if ($isOnline): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1">
                                                <i class="fa fa-circle me-1" style="font-size: 8px;"></i> Online
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-50 px-2 py-1">
                                                <i class="fa fa-circle me-1" style="font-size: 8px;"></i> Offline
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <i class="fa fa-volume-high me-1"></i> <?= htmlspecialchars($tela['audio_mode'] ?? 'video') ?> |
                                            <i class="fa fa-rotate me-1"></i> <?= (int)($tela['rotation'] ?? 0) ?>°
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="/admin/telas/<?= $tela['id'] ?>/midias" class="btn btn-sm btn-outline-light py-0 px-2" title="Enviar Vídeos/Imagens">
                                                <i class="fa fa-photo-film"></i> Mídias
                                            </a>
                                            <a href="/player/<?= htmlspecialchars($tela['player_code']) ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" title="Abrir Player no Carro">
                                                <i class="fa fa-play"></i> Player
                                            </a>
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

    <div class="col-12 col-lg-4">
        <!-- Status do Servidor e Plataforma -->
        <div class="glass-card mb-4">
            <h6 class="fw-bold mb-3 text-white"><i class="fa fa-server text-success me-2"></i> Servidor & Infraestrutura</h6>
            <ul class="list-unstyled mb-0" style="font-size: 0.92rem;">
                <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                    <span class="text-white-50 fw-medium">Arquitetura</span>
                    <span class="badge bg-info bg-opacity-20 text-info fw-bold">MVC Nativo</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                    <span class="text-white-50 fw-medium">Versão PHP</span>
                    <span class="fw-bold text-white"><?= PHP_VERSION ?></span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                    <span class="text-white-50 fw-medium">Servidor Web</span>
                    <span class="fw-bold text-white">Nginx 1.30</span>
                </li>
                <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                    <span class="text-white-50 fw-medium">Banco de Dados</span>
                    <span class="fw-bold text-white">SQLite3 (WAL Mode)</span>
                </li>
                <li class="d-flex justify-content-between py-2">
                    <span class="text-white-50 fw-medium">Status Operacional</span>
                    <span class="text-success fw-bold"><i class="fa fa-check-circle me-1"></i> 100% Saudável</span>
                </li>
            </ul>
        </div>

        <!-- Acesso Rápido -->
        <div class="glass-card">
            <h6 class="fw-bold mb-3"><i class="fa fa-bolt text-warning me-2"></i> Atalhos do Sistema</h6>
            <div class="d-grid gap-2">
                <a href="/admin/telas" class="btn btn-outline-light text-start btn-sm py-2 px-3">
                    <i class="fa fa-tablet-alt me-2 text-info"></i> Gerenciar Telas & Carros
                </a>
                <a href="/admin/usuarios" class="btn btn-outline-light text-start btn-sm py-2 px-3">
                    <i class="fa fa-users me-2 text-primary"></i> Usuários & Revendedores
                </a>
                <?php if (!empty($currentUser['is_root'])): ?>
                    <a href="/admin/configuracoes" class="btn btn-outline-danger text-start btn-sm py-2 px-3">
                        <i class="fa fa-shield-alt me-2"></i> Configurações Root (APIs)
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
