<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Telas & Carros de Aplicativo</h4>
            <p class="text-muted mb-0">Gerencie todos os tablets, motoristas e mídias ativas na sua rede de mídia indoor (DOOH).</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/admin/telas/criar" class="btn btn-cyan">
                <i class="fa fa-plus me-1"></i> Cadastrar Nova Tela
            </a>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="background: rgba(16, 185, 129, 0.15); color: #6ee7b7;">
            <i class="fa fa-check-circle fa-lg"></i>
            <div>Nova tela e acesso cadastrados com sucesso! Clique em <b>Mídias</b> abaixo para enviar vídeos ou imagens.</div>
        </div>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="background: rgba(52, 226, 228, 0.12); color: #34e2e4;">
            <i class="fa fa-info-circle fa-lg"></i>
            <div>Tela removida com sucesso.</div>
        </div>
    <?php endif; ?>

    <?php if (empty($screens)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fa fa-tablet-screen-button fa-4x mb-3 text-secondary" style="opacity: 0.3;"></i>
            <h5>Nenhuma tela cadastrada</h5>
            <p class="mb-4">Cadastre sua primeira tela/tablet para começar a transmitir vídeos e campanhas publicitárias.</p>
            <a href="/admin/telas/criar" class="btn btn-cyan">
                <i class="fa fa-plus me-1"></i> Cadastrar Primeira Tela
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Código Player</th>
                        <th>Identificação / Carro</th>
                        <th>Motorista / Parceiro</th>
                        <th>Status</th>
                        <th>Validade</th>
                        <th>Rotação</th>
                        <th>Modo de Áudio</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($screens as $tela): 
                        $isOnline = false;
                        if (!empty($tela['ultimo_ping'])) {
                            $diff = time() - strtotime($tela['ultimo_ping']);
                            if ($diff <= 180) $isOnline = true;
                        }
                        $playerLink = "/player/" . htmlspecialchars($tela['player_code']);
                    ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark border border-secondary font-monospace px-2 py-1 fs-6 text-info">
                                        <?= htmlspecialchars($tela['player_code']) ?>
                                    </span>
                                    <button class="btn btn-sm btn-outline-secondary p-1" title="Copiar Link do Player" onclick="navigator.clipboard.writeText(window.location.origin + '<?= $playerLink ?>'); alert('Link do Player copiado!');">
                                        <i class="fa fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="fw-bold text-white">
                                <?= htmlspecialchars(!empty($tela['title']) ? $tela['title'] : (!empty($tela['nome_tela']) ? $tela['nome_tela'] : 'Carro #' . $tela['id'])) ?>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($tela['client_name'] ?? $tela['client_username'] ?? 'Sem Vínculo') ?></div>
                                <?php if (!empty($tela['client_username'])): ?>
                                    <small class="text-muted font-monospace">@<?= htmlspecialchars($tela['client_username']) ?></small>
                                <?php endif; ?>
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
                                <?php
                                    $ownExp = trim((string)($tela['expire_date'] ?? ''));
                                    $effExp = $ownExp !== '' ? $ownExp : trim((string)($tela['client_expire_date'] ?? ''));
                                    $expired = ($effExp !== '' && substr($effExp, 0, 10) < date('Y-m-d'));
                                ?>
                                <div class="d-flex align-items-center gap-1" style="min-width: 190px;">
                                    <input type="date" id="exp_<?= (int)$tela['id'] ?>" class="form-control form-control-sm bg-dark text-white border-secondary" value="<?= htmlspecialchars(substr($effExp, 0, 10)) ?>" style="color-scheme: dark; max-width: 140px;">
                                    <button type="button" class="btn btn-sm btn-success py-1 px-2" title="Salvar validade (somente admin)" onclick="saveScreenExpire(<?= (int)$tela['id'] ?>, this)">
                                        <i class="fa fa-check"></i>
                                    </button>
                                </div>
                                <small class="<?= $expired ? 'text-danger' : 'text-success' ?>" id="expst_<?= (int)$tela['id'] ?>">
                                    <?= $effExp === '' ? 'Sem vencimento' : ($expired ? 'Vencida - em bloqueio' : 'Ativa') ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                    <i class="fa fa-rotate me-1"></i> <?= (int)($tela['rotation'] ?? 0) ?>°
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25">
                                    <i class="fa fa-volume-high me-1"></i> <?= htmlspecialchars($tela['audio_mode'] ?? 'video') ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="/admin/telas/<?= $tela['id'] ?>/midias" class="btn btn-sm btn-outline-info py-1 px-2" title="Enviar Vídeos e Imagens">
                                        <i class="fa fa-photo-film me-1"></i> Mídias
                                    </a>
                                    <a href="<?= $playerLink ?>" target="_blank" class="btn btn-sm btn-cyan py-1 px-2" title="Abrir Player no Navegador">
                                        <i class="fa fa-play me-1"></i> Player
                                    </a>
                                    <a href="/admin/telas/<?= $tela['id'] ?>/deletar" class="btn btn-sm btn-outline-danger py-1 px-2" onclick="return confirm('Excluir esta tela?');" title="Excluir Tela">
                                        <i class="fa fa-trash"></i>
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

<script>
const SERVER_TODAY = '<?= date('Y-m-d') ?>';
function saveScreenExpire(id, btn) {
    const input = document.getElementById('exp_' + id);
    if (!input) return;
    const fd = new FormData();
    fd.append('expire_date', input.value);
    const old = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
    fetch('/admin/telas/' + id + '/validade', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = old;
            if (res.status === 'success') {
                const st = document.getElementById('expst_' + id);
                if (st) {
                    if (!input.value) { st.className = 'text-success'; st.textContent = 'Sem vencimento (salvo)'; }
                    else if (res.locked || input.value < SERVER_TODAY) { st.className = 'text-danger'; st.textContent = 'Vencida - em bloqueio'; }
                    else { st.className = 'text-success'; st.textContent = 'Ativa (salvo)'; }
                }
            } else {
                alert(res.message || 'Erro ao salvar validade.');
            }
        })
        .catch(() => { btn.disabled = false; btn.innerHTML = old; alert('Erro de conexão.'); });
}
</script>
