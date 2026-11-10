<div class="glass-card mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa fa-users text-cyan me-2"></i> Gestão de Usuários & Clientes</h4>
            <p class="text-muted mb-0">Gerencie todos os clientes, motoristas, prazos de vencimento, bloqueios e acessos administrativos.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="/portal-franqueado" class="btn btn-outline-light btn-sm" style="border-color: rgba(192,132,252,0.5); color:#c084fc;">
                <i class="fa fa-store me-1"></i> Portal do Franqueado
            </a>
            <a href="/admin/usuarios/criar" class="btn btn-cyan btn-sm">
                <i class="fa fa-user-plus me-1"></i> Novo Administrador/Franqueado
            </a>
            <a href="/admin/telas/criar" class="btn btn-outline-info btn-sm">
                <i class="fa fa-plus me-1"></i> Cadastrar Cliente & Tela
            </a>
        </div>
    </div>
</div>

<?php if (isset($_GET['err']) && $_GET['err'] === 'root_protected'): ?>
    <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="fa fa-shield-alt fa-lg"></i>
        <div><b>Ação Bloqueada:</b> Este usuário possui imunidade Root e não pode ser deletado ou desativado.</div>
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="background: rgba(16, 185, 129, 0.15); color: #6ee7b7;">
        <i class="fa fa-check-circle fa-lg"></i>
        <div>Registro cadastrado com sucesso!</div>
    </div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="fa fa-info-circle fa-lg"></i>
        <div>Removido com sucesso.</div>
    </div>
<?php endif; ?>

<!-- Abas de Alternância: Clientes/Parceiros x Admins/Revendas -->
<ul class="nav nav-pills mb-4 gap-2" id="usersTabs">
    <li class="nav-item">
        <a class="btn <?= ($currentTab ?? 'clientes') === 'clientes' ? 'btn-cyan' : 'btn-outline-light' ?> px-4 fw-bold" href="/admin/usuarios?tab=clientes">
            <i class="fa fa-car-side me-2"></i> Clientes & Motoristas (<?= count($clients) ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="btn <?= ($currentTab ?? '') === 'admins' ? 'btn-cyan' : 'btn-outline-light' ?> px-4 fw-bold" href="/admin/usuarios?tab=admins">
            <i class="fa fa-user-shield me-2"></i> Administradores & Revendas (<?= count($users) ?>)
        </a>
    </li>
</ul>

<?php if (($currentTab ?? 'clientes') === 'clientes'): ?>
    <!-- TABELA COMPLETA DE CLIENTES COM MODO ESPIÃO (ÍCONE DO OLHO) -->
    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold text-white mb-0">Clientes & Motoristas Cadastrados</h5>
            <div class="d-flex align-items-center gap-2">
                <input type="text" id="filterClientsTable" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Pesquisar cliente ou whatsapp..." style="width: 250px;">
            </div>
        </div>

        <div class="table-responsive">
            <table class="table-custom text-nowrap" id="tblClients">
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="checkAll"></th>
                        <th>Cliente / Nome</th>
                        <th>Status</th>
                        <th>Vencimento</th>
                        <th class="text-center">Alt. Data</th>
                        <th class="text-center">Bloquear</th>
                        <th class="text-center">Deletar</th>
                        <th class="text-center">Múltiplo</th>
                        <th class="text-center">Cobrar</th>
                        <th class="text-center">Franqueado / Rev.</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $today = date('Y-m-d');
                    foreach ($clients as $c): 
                        $expDate = $c['expire_date'] ?? '';
                        $isActive = isset($c['active']) ? (int)$c['active'] : 1;
                        $multiplo = isset($c['multiplo']) ? (float)$c['multiplo'] : 1.0;
                        $whats = trim($c['whatsapp'] ?? '');
                        $cleanWhats = preg_replace('/\D/', '', $whats);

                        $isExpired = false;
                        if (!empty($expDate) && $expDate < $today) {
                            $isExpired = true;
                        }
                    ?>
                        <tr class="client-row" data-search="<?= strtolower(htmlspecialchars(($c['title'] ?? '') . ' ' . ($c['username'] ?? '') . ' ' . $whats)) ?>">
                            <td><input type="checkbox" class="client-check" value="<?= $c['id'] ?>"></td>
                            <td>
                                <div class="fw-bold text-white"><?= htmlspecialchars(!empty($c['title']) ? $c['title'] : $c['username']) ?></div>
                                <small class="text-muted font-monospace">@<?= htmlspecialchars($c['username']) ?></small>
                            </td>

                            <!-- STATUS COM BOTÃO DO OLHO (MODO ESPIÃO AUTORIZADO) -->
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($isActive === 0): ?>
                                        <span class="badge bg-secondary text-dark border border-secondary px-2 py-1">Bloqueado</span>
                                    <?php elseif ($isExpired): ?>
                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1">Vencido</span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-2 py-1">Ativo</span>
                                    <?php endif; ?>

                                    <!-- ÍCONE DO OLHO: ENTRAR COMO O CLIENTE COM ACESSO TOTAL -->
                                    <a href="/admin/usuarios/<?= $c['id'] ?>/espiao" target="_blank" class="btn btn-sm btn-warning text-dark py-0 px-2 fw-bold d-inline-flex align-items-center" title="Acessar como este cliente (Modo Espião com acesso total)">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </div>
                            </td>

                            <td>
                                <?php if (!empty($expDate)): ?>
                                    <span class="font-monospace text-white"><?= date('d/m/Y', strtotime($expDate)) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Sem Data</span>
                                <?php endif; ?>
                            </td>

                            <!-- ALTERAR DATA -->
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-info text-dark py-1 px-2" onclick="promptChangeDate(<?= $c['id'] ?>, '<?= $expDate ?>')" title="Alterar Vencimento">
                                    <i class="fa fa-calendar-alt"></i>
                                </button>
                            </td>

                            <!-- BLOQUEAR / DESBLOQUEAR -->
                            <td class="text-center">
                                <?php if ($isActive === 1): ?>
                                    <button type="button" class="btn btn-sm btn-warning text-dark py-1 px-2" onclick="toggleBlockClient(<?= $c['id'] ?>, 0)" title="Bloquear Acesso">
                                        <i class="fa fa-ban"></i>
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-success text-white py-1 px-2" onclick="toggleBlockClient(<?= $c['id'] ?>, 1)" title="Desbloquear Acesso">
                                        <i class="fa fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </td>

                            <!-- DELETAR -->
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-danger py-1 px-2" onclick="deleteClient(<?= $c['id'] ?>)" title="Deletar Cliente">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </td>

                            <!-- MÚLTIPLO -->
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-info py-1 px-2 font-monospace" onclick="promptChangeMultiplo(<?= $c['id'] ?>, '<?= $multiplo ?>')" title="Alterar Múltiplo">
                                    x<?= number_format($multiplo, 1) ?>
                                </button>
                            </td>

                            <!-- COBRAR VIA WHATSAPP -->
                            <td class="text-center">
                                <?php if (!empty($cleanWhats)): ?>
                                    <a href="https://api.whatsapp.com/send?phone=<?= $cleanWhats ?>&text=<?= urlencode('Olá ' . ($c['title'] ?? $c['username']) . ', tudo bem? Entramos em contato sobre o seu plano do Player99.') ?>" target="_blank" class="btn btn-sm btn-success py-1 px-3 d-inline-flex align-items-center gap-1">
                                        <i class="fab fa-whatsapp"></i> Cobrar
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted small">Sem WhatsApp</span>
                                <?php endif; ?>
                            </td>

                            <!-- FRANQUEADO / REVENDEDOR (PERFIL INTERMEDIÁRIO) -->
                            <td class="text-center">
                                <?php $isReseller = !empty($c['is_reseller']); ?>
                                <?php if ($isReseller): ?>
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-primary py-1 px-2" onclick="toggleReseller(<?= (int)$c['id'] ?>, 0)" title="Remover perfil de franqueado/revendedor">
                                            <i class="fa fa-store me-1"></i> Franqueado
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2 font-monospace" onclick="promptCommission(<?= (int)$c['id'] ?>, '<?= (float)($c['commissionPercent'] ?? 20) ?>')" title="Comissão do franqueado">
                                            <?= number_format((float)($c['commissionPercent'] ?? 20), 0) ?>%
                                        </button>
                                        <a href="/portal-franqueado?client_id=<?= (int)$c['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info py-1 px-2" title="Abrir Portal do Franqueado">
                                            <i class="fa fa-external-link-alt"></i>
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" onclick="toggleReseller(<?= (int)$c['id'] ?>, 1)" title="Tornar franqueado/revendedor">
                                        <i class="fa fa-store me-1"></i> Tornar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- TABELA DE USUÁRIOS ADMINISTRATIVOS & FRANQUEADOS/REVENDAS -->
    <div class="glass-card">
        <h5 class="fw-bold text-white mb-3">Administradores &amp; Franqueados / Revendedores</h5>
        <div class="table-responsive">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome / Usuário</th>
                        <th>E-mail</th>
                        <th>Nível de Acesso</th>
                        <th>Limite de Telas</th>
                        <th class="text-center">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): 
                        $isRoot = \App\Core\Auth::isRoot($u);
                    ?>
                        <tr>
                            <td><span class="text-muted font-monospace">#<?= $u['id'] ?></span></td>
                            <td>
                                <div class="fw-bold text-white"><?= htmlspecialchars($u['name'] ?? $u['username']) ?></div>
                                <small class="text-muted font-monospace">@<?= htmlspecialchars($u['username']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($u['email'] ?? '---') ?></td>
                            <td>
                                <?php if ($isRoot): ?>
                                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1">
                                        <i class="fa fa-crown me-1"></i> Root Superadmin
                                    </span>
                                <?php elseif ($u['admin'] == '1' || $u['nivel'] == '1'): ?>
                                    <span class="badge bg-primary bg-opacity-25 text-info border border-info border-opacity-50 px-2 py-1">
                                        Administrador
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-50 px-2 py-1">
                                        <i class="fa fa-store me-1"></i> Franqueado / Revendedor
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-dark border border-secondary px-2 py-1">
                                    <?= (int)($u['mac_amount'] ?? 0) ?> Telas
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if ($isRoot): ?>
                                    <span class="text-muted small"><i class="fa fa-lock me-1"></i> Protegido</span>
                                <?php else: ?>
                                    <a href="/admin/usuarios/<?= $u['id'] ?>/deletar" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Modal Calendário para Alterar Vencimento -->
<div class="modal fade" id="datePickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content bg-dark text-white border border-secondary">
            <div class="modal-header border-secondary">
                <h6 class="modal-title fw-bold"><i class="fa fa-calendar-alt text-info me-2"></i> Alterar Validade</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="dpClientId" value="">
                <label class="form-label small text-muted">Escolha a nova data no calendário:</label>
                <input type="date" id="dpDateInput" class="form-control bg-black text-white border-secondary" style="color-scheme: dark;">
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-cyan fw-bold" onclick="saveClientExpireDate()">
                    <i class="fa fa-check me-1"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Filtro instantâneo na tabela de clientes
const searchInput = document.getElementById('filterClientsTable');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        document.querySelectorAll('#tblClients tbody tr.client-row').forEach(row => {
            const data = row.getAttribute('data-search') || '';
            row.style.display = data.includes(q) ? '' : 'none';
        });
    });
}

function promptChangeDate(id, current) {
    document.getElementById('dpClientId').value = String(id);
    document.getElementById('dpDateInput').value = (current || '').slice(0, 10);
    const modalEl = document.getElementById('datePickerModal');
    if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } else {
        const newDate = prompt("Digite a nova data de vencimento (AAAA-MM-DD):", current);
        if (newDate && newDate.trim() !== '') {
            submitClientExpireDate(id, newDate.trim());
        }
    }
}

function saveClientExpireDate() {
    const id = document.getElementById('dpClientId').value;
    const val = document.getElementById('dpDateInput').value;
    if (!val) { alert('Selecione uma data válida.'); return; }
    submitClientExpireDate(id, val);
}

function submitClientExpireDate(id, newDate) {
    const formData = new FormData();
    formData.append('action', 'update_date');
    formData.append('id', id);
    formData.append('new_date', newDate);

    fetch('/admin/usuarios', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(() => window.location.reload());
}

function toggleBlockClient(id, newState) {
    const actionName = newState === 0 ? "bloquear" : "desbloquear";
    if (confirm("Deseja realmente " + actionName + " o acesso deste cliente?")) {
        const formData = new FormData();
        formData.append('action', 'toggle_block');
        formData.append('id', id);
        formData.append('new_state', newState);

        fetch('/admin/usuarios', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    window.location.reload();
                } else {
                    alert('Erro ao atualizar status do cliente.');
                }
            })
            .catch(err => {
                alert('Erro na requisição. Tente novamente.');
            });
    }
}

function toggleReseller(id, state) {
    if (!confirm(state === 1 ? "Tornar este usuário um FRANQUEADO / REVENDEDOR?" : "Remover o perfil de franqueado/revendedor deste usuário?")) return;
    const formData = new FormData();
    formData.append('action', 'toggle_reseller');
    formData.append('id', id);
    formData.append('new_state', state);
    fetch('/admin/usuarios', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(() => window.location.reload());
}

function promptCommission(id, current) {
    const val = prompt("Comissão do franqueado/revendedor sobre as recargas dos motoristas (%):", current);
    if (val !== null && val.trim() !== '') {
        const formData = new FormData();
        formData.append('action', 'update_commission');
        formData.append('id', id);
        formData.append('percent', parseFloat(val.trim().replace(',', '.')));
        fetch('/admin/usuarios', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(() => window.location.reload());
    }
}

function promptChangeMultiplo(id, current) {
    const val = prompt("Digite o novo multiplicador de cobrança (ex: 1.0 ou 1.5):", current);
    if (val !== null && val.trim() !== '') {
        const formData = new FormData();
        formData.append('action', 'update_multiplo');
        formData.append('id', id);
        formData.append('new_multiplo', parseFloat(val.trim()));

        fetch('/admin/usuarios', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(() => window.location.reload());
    }
}

function deleteClient(id) {
    if (confirm("Tem certeza que deseja excluir permanentemente este cliente e suas mídias?")) {
        const formData = new FormData();
        formData.append('action', 'delete_client');
        formData.append('id', id);

        fetch('/admin/usuarios', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(() => window.location.reload());
    }
}
</script>
