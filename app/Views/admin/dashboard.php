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

<!-- Container Financeiro em Destaque (Topo) + Servidor & Atalhos -->
<?php
$finData = $fin ?? [];
$f = static fn(string $k, $def = 0) => $finData[$k] ?? $def;
?>
<div class="row g-4 mb-4">
    <div class="col-12 col-lg-8">
        <div class="glass-card h-100">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                <div>
                    <h5 class="fw-bold mb-0"><i class="fa fa-wallet text-success me-2"></i> Resumo Financeiro, Afiliados &amp; Franqueados</h5>
                    <small class="text-muted">Receitas de planos/recargas, comissões da rede e status financeiro das telas</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="/admin/faturamento" class="btn btn-sm btn-outline-success">
                        <i class="fa fa-tags me-1"></i> Planos &amp; Faturamentos
                    </a>
                    <a href="/admin/afiliados" class="btn btn-sm btn-outline-warning">
                        <i class="fa fa-handshake me-1"></i> Afiliados &amp; Saques
                    </a>
                </div>
            </div>

            <!-- 4 KPIs Financeiros Principais -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.25);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-white-50">Ganhos no Dia</span>
                            <i class="fa fa-sun text-success"></i>
                        </div>
                        <div class="fs-4 fw-bold text-success">R$ <?= number_format((float)$f('ganhos_dia', 0), 2, ',', '.') ?></div>
                        <small class="text-muted"><?= (int)$f('qtd_dia', 0) ?> recarga(s) hoje</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(52, 226, 228, 0.08); border: 1px solid rgba(52, 226, 228, 0.25);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-white-50">Ganhos no Mês</span>
                            <i class="fa fa-calendar-check text-info"></i>
                        </div>
                        <div class="fs-4 fw-bold" style="color: #34e2e4;">R$ <?= number_format((float)$f('ganhos_mes', 0), 2, ',', '.') ?></div>
                        <small class="text-muted"><?= (int)$f('qtd_mes', 0) ?> pagamento(s) em <?= date('m/Y') ?></small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-white-50">Valor Afiliados</span>
                            <i class="fa fa-handshake text-warning"></i>
                        </div>
                        <div class="fs-4 fw-bold text-warning">R$ <?= number_format((float)$f('afiliados_saldo', 0), 2, ',', '.') ?></div>
                        <small class="text-muted">Gerado: R$ <?= number_format((float)$f('afiliados_total_gerado', 0), 2, ',', '.') ?> · <?= (int)$f('afiliados_qtd', 0) ?> ativo(s)</small>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="p-3 rounded-3 h-100" style="background: rgba(192, 132, 252, 0.09); border: 1px solid rgba(192, 132, 252, 0.28);">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="small fw-semibold text-white-50">Valor Franqueados</span>
                            <i class="fa fa-store" style="color: #c084fc;"></i>
                        </div>
                        <div class="fs-4 fw-bold" style="color: #c084fc;">R$ <?= number_format((float)$f('franqueados_saldo', 0), 2, ',', '.') ?></div>
                        <small class="text-muted">Gerado: R$ <?= number_format((float)$f('franqueados_total_gerado', 0), 2, ',', '.') ?> · <?= (int)$f('franqueados_qtd', 0) ?> franq.</small>
                    </div>
                </div>
            </div>

            <!-- Indicadores Financeiros Complementares -->
            <div class="row g-2 mb-3">
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border border-secondary border-opacity-25 bg-dark bg-opacity-50">
                        <div class="small text-muted">Receita Líquida (Mês)</div>
                        <div class="fw-bold <?= (float)$f('receita_liquida_mes', 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                            R$ <?= number_format((float)$f('receita_liquida_mes', 0), 2, ',', '.') ?>
                        </div>
                        <small class="text-muted" style="font-size:11px;">Comissões mês: R$ <?= number_format((float)$f('comissoes_mes', 0), 2, ',', '.') ?></small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border border-secondary border-opacity-25 bg-dark bg-opacity-50">
                        <div class="small text-muted">PIX Pendentes</div>
                        <div class="fw-bold text-warning">R$ <?= number_format((float)$f('pendentes_valor', 0), 2, ',', '.') ?></div>
                        <small class="text-muted" style="font-size:11px;"><?= (int)$f('pendentes_qtd', 0) ?> pedido(s) aguardando</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border border-secondary border-opacity-25 bg-dark bg-opacity-50">
                        <div class="small text-muted">Saques Solicitados</div>
                        <div class="fw-bold text-danger">R$ <?= number_format((float)$f('saques_pendentes_valor', 0), 2, ',', '.') ?></div>
                        <small class="text-muted" style="font-size:11px;"><?= (int)$f('saques_pendentes_qtd', 0) ?> pendente(s) de PIX</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="p-2 px-3 rounded-3 border border-secondary border-opacity-25 bg-dark bg-opacity-50">
                        <div class="small text-muted">Total Geral / Ticket</div>
                        <div class="fw-bold text-white">R$ <?= number_format((float)$f('ganhos_total', 0), 2, ',', '.') ?></div>
                        <small class="text-muted" style="font-size:11px;">Ticket médio: R$ <?= number_format((float)$f('ticket_medio', 0), 2, ',', '.') ?></small>
                    </div>
                </div>
            </div>

            <!-- Resumo Operacional / Rede Financeira -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-2 px-3 rounded-3 mb-3 border border-secondary border-opacity-25" style="background: rgba(255,255,255,0.02); font-size: 0.85rem;">
                <span><i class="fa fa-check-circle text-success me-1"></i> Telas Ativas: <b class="text-white"><?= (int)$f('telas_ativas', 0) ?></b></span>
                <span><i class="fa fa-ban text-danger me-1"></i> Vencidas/Bloqueio: <b class="text-danger"><?= (int)$f('telas_vencidas', 0) ?></b></span>
                <span><i class="fa fa-ticket text-info me-1"></i> Créditos em Carteira: <b class="text-info"><?= (int)$f('creditos_disponiveis', 0) ?></b> <span class="text-muted">(usados: <?= (int)$f('creditos_usados', 0) ?>)</span></span>
                <span><i class="fa fa-users me-1" style="color:#c084fc;"></i> Motoristas de Franqueados: <b class="text-white"><?= (int)$f('motoristas_franqueados', 0) ?></b></span>
            </div>

            <!-- Últimas Recargas Confirmadas -->
            <?php $ultimasRecargas = $f('ultimas_recargas', []); ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-uppercase text-white-50"><i class="fa fa-receipt text-success me-1"></i> Últimas Recargas Confirmadas</span>
                <a href="/admin/faturamento" class="small text-info text-decoration-none">Ver todos os pedidos →</a>
            </div>
            <?php if (empty($ultimasRecargas)): ?>
                <div class="text-center py-3 text-muted small border border-secondary border-opacity-25 rounded-3">
                    Nenhuma recarga paga registrada ainda. Assim que motoristas pagarem planos, os valores aparecerão aqui.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Motorista</th>
                                <th>Plano</th>
                                <th>Data Pagto</th>
                                <th class="text-end">Valor Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimasRecargas as $rec): ?>
                                <tr>
                                    <td class="fw-semibold text-white"><?= htmlspecialchars($rec['nome_cliente'] ?: ('Cliente #' . $rec['id_cliente'])) ?></td>
                                    <td><span class="badge bg-info bg-opacity-20 text-info"><?= htmlspecialchars($rec['nome_plano']) ?> (<?= (int)$rec['dias'] ?>d)</span></td>
                                    <td class="small text-muted"><?= !empty($rec['paid_at'] ?: $rec['created_at']) ? date('d/m/Y H:i', strtotime($rec['paid_at'] ?: $rec['created_at'])) : '-' ?></td>
                                    <td class="text-end fw-bold text-success">R$ <?= number_format((float)$rec['valor_final'], 2, ',', '.') ?></td>
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
            <h6 class="fw-bold mb-3 text-white"><i class="fa fa-server text-success me-2"></i> Servidor &amp; Infraestrutura</h6>
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
                <a href="/admin/faturamento" class="btn btn-outline-light text-start btn-sm py-2 px-3">
                    <i class="fa fa-wallet me-2 text-success"></i> Planos &amp; Faturamentos
                </a>
                <a href="/admin/afiliados" class="btn btn-outline-light text-start btn-sm py-2 px-3">
                    <i class="fa fa-handshake me-2 text-warning"></i> Afiliados &amp; Comissões
                </a>
                <a href="/portal-franqueado" class="btn btn-outline-light text-start btn-sm py-2 px-3">
                    <i class="fa fa-store me-2" style="color:#c084fc;"></i> Portal do Franqueado
                </a>
                <a href="/admin/telas" class="btn btn-outline-light text-start btn-sm py-2 px-3">
                    <i class="fa fa-tablet-alt me-2 text-info"></i> Gerenciar Telas &amp; Carros
                </a>
                <a href="/admin/usuarios" class="btn btn-outline-light text-start btn-sm py-2 px-3">
                    <i class="fa fa-users me-2 text-primary"></i> Usuários &amp; Revendedores
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

<!-- Container das Telas Baixado (Largura Completa) -->
<div class="row g-4">
    <div class="col-12">
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
</div>
