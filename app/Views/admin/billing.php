<style>
    .plan-card {
        background: #11131a;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 22px;
        padding: 28px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
        height: 100%;
    }
    .plan-card-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: #ffffff;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 22px;
    }
    .plan-label {
        display: block;
        color: #9ca3af;
        font-size: 0.92rem;
        font-weight: 500;
        margin-bottom: 8px;
    }
    .plan-input {
        width: 100%;
        background: #0b0d13 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        border-radius: 14px !important;
        padding: 14px 18px !important;
        color: #ffffff !important;
        font-size: 1rem !important;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .plan-input::placeholder {
        color: #6b7280;
    }
    .plan-input:focus {
        outline: none !important;
        border-color: #22c55e !important;
        box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15) !important;
    }
    .btn-create-plan {
        width: 100%;
        background: #22c55e;
        color: #050505;
        font-weight: 800;
        font-size: 0.98rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: none;
        border-radius: 14px;
        padding: 15px 20px;
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .btn-create-plan:hover {
        background: #16a34a;
        color: #ffffff;
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(34, 197, 94, 0.35);
    }
    .plan-list-header {
        display: grid;
        grid-template-columns: 2fr 1.2fr 0.9fr 0.6fr;
        padding: 0 20px 12px 20px;
        color: #9ca3af;
        font-size: 0.8rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }
    .plan-list-row {
        display: grid;
        grid-template-columns: 2fr 1.2fr 0.9fr 0.6fr;
        align-items: center;
        background: #171923;
        border: 1px solid rgba(255, 255, 255, 0.04);
        border-radius: 14px;
        padding: 16px 20px;
        margin-bottom: 12px;
        transition: transform 0.15s ease, border-color 0.15s ease;
    }
    .plan-list-row:hover {
        border-color: rgba(52, 226, 228, 0.25);
        transform: translateY(-1px);
    }
    .plan-row-name {
        font-weight: 700;
        color: #ffffff;
        font-size: 1rem;
    }
    .plan-row-val {
        color: #06b6d4;
        font-weight: 600;
        font-size: 1rem;
    }
    .plan-row-days {
        color: #ffffff;
        font-weight: 500;
        font-size: 0.98rem;
    }
    .btn-trash-icon {
        background: transparent;
        border: none;
        color: #ef4444;
        font-size: 1rem;
        padding: 6px 10px;
        border-radius: 8px;
        transition: background 0.2s;
    }
    .btn-trash-icon:hover {
        background: rgba(239, 68, 68, 0.15);
        color: #f87171;
    }
    .billing-nav-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 24px;
    }
    .billing-nav-btn {
        background: #11131a;
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #cbd5e1;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 10px 18px;
        border-radius: 12px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .billing-nav-btn:hover, .billing-nav-btn.active {
        background: rgba(52, 226, 228, 0.12);
        border-color: #34e2e4;
        color: #ffffff;
    }
</style>

<?= $msg ?? '' ?>

<!-- Barra Rápida de Navegação -->
<div class="billing-nav-pills">
    <button type="button" class="billing-nav-btn active" onclick="filterBillingSec('all', this)">
        <i class="fa fa-th-large text-info"></i> Visão Completa
    </button>
    <button type="button" class="billing-nav-btn" onclick="filterBillingSec('sec-add-plano', this)">
        <i class="fa fa-plus-circle text-success"></i> Adicionar Plano
    </button>
    <button type="button" class="billing-nav-btn" onclick="filterBillingSec('sec-planos-ativos', this)">
        <i class="fa fa-list-ul text-info"></i> Planos Ativos (<?= count($planos ?? []) ?>)
    </button>
    <button type="button" class="billing-nav-btn" onclick="filterBillingSec('sec-cupons', this)">
        <i class="fa fa-ticket-alt text-warning"></i> Cupons de Desconto (<?= count($cupons ?? []) ?>)
    </button>
    <button type="button" class="billing-nav-btn" onclick="filterBillingSec('sec-creditos', this)">
        <i class="fa fa-bolt text-success"></i> Liberar Crédito / Pedidos
    </button>
    <button type="button" class="billing-nav-btn" onclick="filterBillingSec('sec-faturamento', this)">
        <i class="fa fa-wallet text-cyan"></i> Dados Fiscais & PIX
    </button>
</div>

<!-- ========================================================= -->
<!-- 1. ADICIONAR PLANO & PLANOS ATIVOS                        -->
<!-- ========================================================= -->
<div class="row g-4 mb-4">
    <!-- Card: Adicionar Plano -->
    <div class="col-12 col-lg-5 billing-section" id="sec-add-plano">
        <div class="plan-card">
            <div class="plan-card-title">
                <i class="fa fa-plus-circle" style="color: #22c55e;"></i>
                <span>Adicionar Plano</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create_plan">

                <div class="mb-3">
                    <label class="plan-label">Nome (Ex: Básico / 30 Dias Premium)</label>
                    <input type="text" name="nome_plano" class="plan-input" placeholder="Ex: Básico" required>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="plan-label">Valor (R$)</label>
                        <input type="number" step="0.01" min="0" name="valor_plano" class="plan-input" placeholder="99.00" required>
                    </div>
                    <div class="col-6">
                        <label class="plan-label">Dias</label>
                        <input type="number" min="1" name="dias_plano" class="plan-input" placeholder="30" value="30" required>
                    </div>
                </div>

                <button type="submit" class="btn-create-plan">
                    <i class="fa fa-plus"></i> CRIAR PLANO
                </button>
            </form>

            <div class="mt-3 pt-3 border-top border-secondary border-opacity-25 small text-muted">
                <i class="fa fa-info-circle text-info me-1"></i>
                Cada plano adquirido gera <b>1 crédito</b> com a duração em dias configurada acima para o motorista recarregar uma tela no painel.
            </div>
        </div>
    </div>

    <!-- Card: Planos Ativos -->
    <div class="col-12 col-lg-7 billing-section" id="sec-planos-ativos">
        <div class="plan-card">
            <div class="plan-card-title">
                <i class="fa fa-list-ul" style="color: #06b6d4;"></i>
                <span>Planos de Telas Ativos</span>
            </div>

            <div class="plan-list-header">
                <div>PLANO</div>
                <div>VALOR</div>
                <div>DIAS</div>
                <div class="text-center">AÇÃO</div>
            </div>

            <?php if (empty($planos)): ?>
                <div class="text-center py-5 text-muted" style="background: #171923; border-radius: 14px;">
                    <i class="fa fa-layer-group fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Nenhum plano cadastrado ainda. Crie seu primeiro plano ao lado!</p>
                </div>
            <?php else: ?>
                <?php foreach ($planos as $pl): ?>
                    <div class="plan-list-row">
                        <div class="plan-row-name"><?= htmlspecialchars($pl['nome']) ?></div>
                        <div class="plan-row-val">R$ <?= number_format((float)$pl['valor'], 2, ',', '.') ?></div>
                        <div class="plan-row-days"><?= (int)$pl['dias'] ?> d</div>
                        <div class="text-center">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Deseja remover o plano <?= htmlspecialchars(addslashes($pl['nome'])) ?>?');">
                                <input type="hidden" name="action" value="delete_plan">
                                <input type="hidden" name="plan_id" value="<?= (int)$pl['id'] ?>">
                                <button type="submit" class="btn-trash-icon" title="Excluir Plano">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- 2. CADASTRAR CUPOM DE DESCONTO & CUPONS ATIVOS            -->
<!-- ========================================================= -->
<div class="row g-4 mb-4 billing-section" id="sec-cupons">
    <!-- Card: Cadastrar Cupom de Desconto -->
    <div class="col-12 col-lg-5">
        <div class="plan-card">
            <div class="plan-card-title">
                <i class="fa fa-ticket-alt" style="color: #f59e0b;"></i>
                <span>Cadastrar Cupom de Desconto</span>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="create_coupon">

                <div class="mb-3">
                    <label class="plan-label">Código do Cupom (Ex: BEMVINDO10, PROMO50)</label>
                    <input type="text" name="codigo_cupom" class="plan-input text-uppercase" placeholder="PROMO10" style="letter-spacing: 1.5px; font-weight: 700;" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="plan-label">Tipo de Desconto</label>
                        <select name="tipo_cupom" class="plan-input">
                            <option value="percent">Porcentagem (%)</option>
                            <option value="fixed">Valor Fixo (R$)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="plan-label">Valor do Desconto</label>
                        <input type="number" step="0.01" min="0.01" name="desconto_cupom" class="plan-input" placeholder="10" required>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <label class="plan-label">Limite de Usos (0 = Ilimitado)</label>
                        <input type="number" min="0" name="max_usos" class="plan-input" value="0" placeholder="0">
                    </div>
                    <div class="col-6">
                        <label class="plan-label">Validade (Opcional)</label>
                        <input type="date" name="validade_cupom" class="plan-input">
                    </div>
                </div>

                <button type="submit" class="btn-create-plan" style="background: #f59e0b; color: #050505;">
                    <i class="fa fa-plus"></i> CRIAR CUPOM DE DESCONTO
                </button>
            </form>
        </div>
    </div>

    <!-- Card: Cupons Ativos -->
    <div class="col-12 col-lg-7">
        <div class="plan-card">
            <div class="plan-card-title">
                <i class="fa fa-tags" style="color: #f59e0b;"></i>
                <span>Cupons de Desconto Ativos</span>
            </div>

            <div class="plan-list-header" style="grid-template-columns: 1.5fr 1.2fr 1.2fr 0.6fr;">
                <div>CUPOM</div>
                <div>DESCONTO</div>
                <div>USOS / VALIDADE</div>
                <div class="text-center">AÇÃO</div>
            </div>

            <?php if (empty($cupons)): ?>
                <div class="text-center py-5 text-muted" style="background: #171923; border-radius: 14px;">
                    <i class="fa fa-ticket-alt fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Nenhum cupom de desconto cadastrado.</p>
                </div>
            <?php else: ?>
                <?php foreach ($cupons as $cp): 
                    $descText = ($cp['tipo'] ?? 'percent') === 'percent'
                        ? number_format((float)$cp['desconto'], 0) . '% OFF'
                        : 'R$ ' . number_format((float)$cp['desconto'], 2, ',', '.') . ' OFF';
                    $usosText = (int)$cp['usos'] . ((int)$cp['max_usos'] > 0 ? '/' . (int)$cp['max_usos'] : ' usos');
                    if (!empty($cp['validade'])) {
                        $usosText .= ' • até ' . date('d/m/Y', strtotime($cp['validade']));
                    }
                ?>
                    <div class="plan-list-row" style="grid-template-columns: 1.5fr 1.2fr 1.2fr 0.6fr;">
                        <div class="plan-row-name">
                            <span class="badge bg-warning text-dark font-monospace px-2 py-1"><?= htmlspecialchars($cp['codigo']) ?></span>
                        </div>
                        <div class="plan-row-val" style="color: #fbbf24;"><?= $descText ?></div>
                        <div class="plan-row-days small text-muted"><?= htmlspecialchars($usosText) ?></div>
                        <div class="text-center">
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Excluir o cupom <?= htmlspecialchars(addslashes($cp['codigo'])) ?>?');">
                                <input type="hidden" name="action" value="delete_coupon">
                                <input type="hidden" name="coupon_id" value="<?= (int)$cp['id'] ?>">
                                <button type="submit" class="btn-trash-icon" title="Excluir Cupom">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- 3. LIBERAR CRÉDITO MANUAL & PEDIDOS DE PLANOS             -->
<!-- ========================================================= -->
<div class="row g-4 mb-4 billing-section" id="sec-creditos">
    <!-- Liberar Crédito Manualmente -->
    <div class="col-12 col-lg-5">
        <div class="plan-card">
            <div class="plan-card-title">
                <i class="fa fa-bolt" style="color: #34e2e4;"></i>
                <span>Liberar Crédito para Motorista</span>
            </div>
            <p class="text-muted small mb-3">Adicione manualmente 1 crédito de plano à conta de um motorista para que ele possa recarregar uma tela.</p>

            <form method="POST">
                <input type="hidden" name="action" value="grant_credit">

                <div class="mb-3">
                    <label class="plan-label">Selecione o Motorista / Parceiro</label>
                    <select name="id_cliente" class="plan-input" required>
                        <option value="">-- Escolha o motorista --</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?= (int)$cli['id'] ?>">
                                #<?= (int)$cli['id'] ?> - <?= htmlspecialchars($cli['title'] ?: $cli['username']) ?> (@<?= htmlspecialchars($cli['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="plan-label">Plano / Crédito a Liberar</label>
                    <select name="id_plano" class="plan-input" required>
                        <option value="">-- Escolha o plano --</option>
                        <?php foreach ($planos as $pl): ?>
                            <option value="<?= (int)$pl['id'] ?>">
                                <?= htmlspecialchars($pl['nome']) ?> — R$ <?= number_format((float)$pl['valor'], 2, ',', '.') ?> (<?= (int)$pl['dias'] ?> dias)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-create-plan" style="background: #34e2e4; color: #050505;">
                    <i class="fa fa-bolt"></i> ADICIONAR CRÉDITO AO MOTORISTA
                </button>
            </form>
        </div>
    </div>

    <!-- Últimos Pedidos de Planos -->
    <div class="col-12 col-lg-7">
        <div class="plan-card">
            <div class="plan-card-title">
                <i class="fa fa-receipt" style="color: #22c55e;"></i>
                <span>Pedidos de Recarga / Planos Recentes</span>
            </div>

            <?php if (empty($pedidos)): ?>
                <div class="text-center py-5 text-muted" style="background: #171923; border-radius: 14px;">
                    <i class="fa fa-shopping-cart fa-2x mb-2 opacity-50"></i>
                    <p class="mb-0">Nenhum pedido de plano gerado ainda.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 310px; overflow-y: auto; padding-right: 4px;">
                    <?php foreach ($pedidos as $ped): ?>
                        <div class="plan-list-row" style="grid-template-columns: 1.5fr 1.2fr 1fr 1fr;">
                            <div>
                                <div class="fw-bold text-white"><?= htmlspecialchars($ped['nome_plano']) ?> (<?= (int)$ped['dias'] ?>d)</div>
                                <small class="text-muted">Motorista: <?= htmlspecialchars($ped['nome_cliente'] ?: ('#' . $ped['id_cliente'])) ?></small>
                                <?php if (!empty($ped['cupom'])): ?>
                                    <span class="badge bg-warning text-dark ms-1"><?= htmlspecialchars($ped['cupom']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="plan-row-val">R$ <?= number_format((float)$ped['valor_final'], 2, ',', '.') ?></div>
                            <div>
                                <?php if (($ped['status'] ?? '') === 'PAGO'): ?>
                                    <span class="badge bg-success px-2 py-1"><i class="fa fa-check me-1"></i> PAGO</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark px-2 py-1">PENDENTE</span>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <?php if (($ped['status'] ?? '') !== 'PAGO'): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Confirmar recebimento e liberar crédito de <?= (int)$ped['dias'] ?> dias para este motorista?');">
                                        <input type="hidden" name="action" value="approve_order">
                                        <input type="hidden" name="order_id" value="<?= (int)$ped['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success fw-bold">
                                            <i class="fa fa-check me-1"></i> Liberar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <small class="text-muted"><?= !empty($ped['paid_at']) ? date('d/m H:i', strtotime($ped['paid_at'])) : 'Liberado' ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================================= -->
<!-- 4. DADOS FISCAIS & PAGAMENTO PIX                          -->
<!-- ========================================================= -->
<div class="row justify-content-center billing-section" id="sec-faturamento">
    <div class="col-12">
        <div class="plan-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                <div>
                    <h5 class="fw-bold mb-1 text-white"><i class="fa fa-wallet text-cyan me-2"></i> Dados Fiscais & Recebimento PIX da Empresa</h5>
                    <p class="text-muted small mb-0">Estes dados aparecem nas cobranças de planos, faturas e recebimentos via PIX.</p>
                </div>
                <div class="stat-icon green" style="width: 48px; height: 48px; font-size: 20px;">
                    <i class="fa fa-receipt"></i>
                </div>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="save_faturamento">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="plan-label">Razão Social / Nome da Empresa</label>
                        <input type="text" name="nome_razao" class="plan-input" value="<?= htmlspecialchars($dados['nome_razao'] ?? '') ?>" placeholder="Ex: Player99 Publicidade e Mídia Ltda" required>
                    </div>

                    <div class="col-md-5">
                        <label class="plan-label">CNPJ ou CPF</label>
                        <input type="text" name="cpf_cnpj" class="plan-input" value="<?= htmlspecialchars($dados['cpf_cnpj'] ?? '') ?>" placeholder="00.000.000/0001-00" required>
                    </div>

                    <div class="col-12">
                        <label class="plan-label">Endereço Completo</label>
                        <input type="text" name="endereco" class="plan-input" value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>" placeholder="Av. Principal, 1000 - Sala 202 - Centro">
                    </div>

                    <div class="col-md-6">
                        <label class="plan-label">WhatsApp Financeiro / Suporte</label>
                        <input type="text" name="whats" class="plan-input" value="<?= htmlspecialchars($dados['whats'] ?? '') ?>" placeholder="5551999999999">
                    </div>

                    <div class="col-md-6">
                        <label class="plan-label">Chave PIX Oficial de Recebimento</label>
                        <input type="text" name="chave_pix" class="plan-input" value="<?= htmlspecialchars($dados['chave_pix'] ?? '') ?>" placeholder="CNPJ, E-mail, Celular ou Chave Aleatória">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-end">
                    <button type="submit" class="btn btn-cyan fw-bold px-4 py-2 shadow">
                        <i class="fa fa-save me-1"></i> Salvar Dados Fiscais & PIX
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterBillingSec(secId, btnEl) {
    document.querySelectorAll('.billing-nav-btn').forEach(function(b) {
        b.classList.remove('active');
    });
    if (btnEl) btnEl.classList.add('active');

    const sections = document.querySelectorAll('.billing-section');
    sections.forEach(function(s) {
        if (secId === 'all' || s.id === secId) {
            s.style.display = '';
        } else {
            s.style.display = 'none';
        }
    });
}
</script>
