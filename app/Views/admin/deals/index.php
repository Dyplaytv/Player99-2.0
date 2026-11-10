<style>
    /* Estilos de Alto Contraste para Vitrine de Afiliados */
    .deals-webhook-box {
        background: #0d1424 !important;
        border: 1px solid rgba(0, 242, 254, 0.35) !important;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }
    .deals-webhook-title {
        color: #ffffff !important;
        font-weight: 700;
        font-size: 14px;
    }
    .deals-webhook-url {
        background: #050811 !important;
        color: #00f2fe !important;
        border: 1px solid rgba(0, 242, 254, 0.5) !important;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 13px;
    }
    .deals-webhook-desc {
        color: #cbd5e1 !important;
        font-size: 13px;
        line-height: 1.5;
    }
    .table-dark thead th {
        color: #f8fafc !important;
        font-weight: 700 !important;
        font-size: 12px !important;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #334155 !important;
        text-transform: uppercase;
    }
    .deals-input {
        background-color: #060913 !important;
        color: #ffffff !important;
        border: 1px solid #334155 !important;
        font-weight: 600;
        font-size: 13px;
    }
    .deals-input:focus {
        border-color: #00f2fe !important;
        background-color: #0f172a !important;
        color: #ffffff !important;
        box-shadow: 0 0 8px rgba(0, 242, 254, 0.35) !important;
    }
    .deals-input::placeholder {
        color: #94a3b8 !important;
        opacity: 0.9 !important;
        font-weight: 400;
    }
    .deals-addon {
        background-color: #1e293b !important;
        color: #f1f5f9 !important;
        border: 1px solid #334155 !important;
        font-weight: 700;
    }
    .deals-label {
        color: #e2e8f0 !important;
        font-weight: 700;
        font-size: 12px;
        letter-spacing: 0.5px;
    }
    .deals-subtext {
        color: #94a3b8 !important;
        font-size: 13px;
    }
</style>

<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-white"><i class="fa fa-tags text-warning me-2"></i> Vitrine de Afiliados E-commerce (DOOH)</h4>
            <p class="deals-subtext mb-0">Gerencie suas tags de afiliados, captação automática via WhatsApp e ofertas exibidas nos tablets dos veículos.</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-info" onclick="checkLinksHealth()">
                <i class="fa fa-stethoscope me-1"></i> Verificar Links Quebrados
            </button>
            <button type="button" class="btn btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#modalAddDeal">
                <i class="fa fa-plus-circle me-1"></i> Adicionar Oferta Manual
            </button>
        </div>
    </div>

    <?= $msg ?? '' ?>

    <!-- Métricas Rápidas -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="p-3 rounded-3 bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-25 text-warning p-3 rounded-circle">
                    <i class="fa fa-shopping-bag fa-2x"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-white"><?= number_format($totalDeals) ?></h3>
                    <span class="text-muted small">Ofertas Ativas na Grade</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 rounded-3 bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-25 text-info p-3 rounded-circle">
                    <i class="fa fa-qrcode fa-2x"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-white"><?= number_format($totalClicks) ?></h3>
                    <span class="text-muted small">Leads / Escaneamentos de Passageiros</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="p-3 rounded-3 bg-dark border border-secondary border-opacity-25 d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-25 text-success p-3 rounded-circle">
                    <i class="fa fa-store fa-2x"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-white"><?= count($platforms) ?></h3>
                    <span class="text-muted small">Plataformas E-commerce Suportadas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 0. Conexão Oficial WhatsApp de Ofertas (Evolution API) -->
    <div class="card bg-dark border-secondary border-opacity-25 mb-4">
        <div class="card-header bg-dark border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-bold text-success"><i class="fab fa-whatsapp me-2"></i> WhatsApp de Ofertas (Instância Dedicada Evolution)</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-info fw-bold" onclick="checkDealsEvoStatus()">
                    <i class="fa fa-sync-alt me-1"></i> Checar Conexão
                </button>
                <button type="button" class="btn btn-sm btn-success fw-bold" onclick="connectDealsEvo()">
                    <i class="fa fa-qrcode me-1"></i> Gerar QR Code
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger fw-bold" onclick="disconnectDealsEvo()">
                    <i class="fa fa-power-off me-1"></i> Desconectar
                </button>
            </div>
        </div>
        <div class="card-body">
            <div id="dealsEvoBadgeContainer" class="mb-3">
                <span id="dealsEvoBadge" class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-50 px-3 py-2">
                    <i class="fa fa-circle me-1" style="font-size: 8px;"></i> Status: Clique em 'Checar Conexão'
                </span>
            </div>

            <!-- Box Modal QR Code -->
            <div id="dealsQrCodeWrapper" class="p-4 mb-3 bg-black rounded-3 text-center d-none border border-success border-opacity-50">
                <h6 class="text-white fw-bold mb-2">Escaneie com o WhatsApp que está no Grupo de Promoções</h6>
                <p class="text-muted small mb-3">Abra o WhatsApp > Aparelhos Conectados > Conectar um aparelho e aponte para o código abaixo:</p>
                <div id="dealsQrCodeHolder" class="d-flex justify-content-center mb-2"></div>
                <small class="text-info font-monospace" id="dealsQrTimer">Aguardando leitura...</small>
            </div>

            <div class="row g-3 align-items-center">
                <div class="col-12 col-md-5">
                    <label class="form-label deals-label">NOME DA INSTÂNCIA DEDICADA</label>
                    <input type="text" id="dealsEvoInstance" class="form-control form-control-sm deals-input" value="player99_ofertas">
                    <small class="deals-subtext" style="font-size: 11px;">Instância exclusiva para monitorar ofertas sem interferir no suporte.</small>
                </div>
                <div class="col-12 col-md-7">
                    <label class="form-label deals-label">WEBHOOK AUTOCONFIGURADO</label>
                    <div class="input-group input-group-sm">
                        <input type="text" readonly class="form-control deals-input text-info" value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '75.119.156.103') ?>/api/evolution/deals-webhook" id="inputWebhookUrl">
                        <button type="button" class="btn btn-outline-info deals-addon" onclick="navigator.clipboard.writeText(document.getElementById('inputWebhookUrl').value); alert('URL copiada!');">
                            <i class="fa fa-copy"></i>
                        </button>
                    </div>
                    <small class="deals-subtext" style="font-size: 11px;">Ao clicar em 'Gerar QR Code', a Evolution já amarra esse webhook automaticamente!</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 1. Configurações de Afiliados por Plataforma -->
    <div class="card bg-dark border-secondary border-opacity-25 mb-4">
        <div class="card-header bg-dark border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-white"><i class="fa fa-cogs text-info me-2"></i> Plataformas E-commerce & Tags de Afiliado</h6>
            <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-50">Substituição Automática</span>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">

                <div class="deals-webhook-box p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <span class="deals-webhook-title me-1">
                                <i class="fab fa-whatsapp text-success me-1"></i> Webhook de Captação da Evolution API:
                            </span>
                            <code class="deals-webhook-url ms-1 user-select-all" id="dealsWebhookUrl"><?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '75.119.156.103') ?>/api/evolution/deals-webhook</code>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-info fw-bold" onclick="navigator.clipboard.writeText(document.getElementById('dealsWebhookUrl').innerText); alert('URL copiada!');">
                            <i class="fa fa-copy me-1"></i> Copiar URL
                        </button>
                    </div>
                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-30 deals-webhook-desc">
                        Cadastre essa URL na sua instância dedicada da Evolution API no evento <code style="color: #ffaa00; background: #1e293b; padding: 2px 6px; border-radius: 4px;">MESSAGES_UPSERT</code> para capturar ofertas de grupos de promoções automaticamente.
                    </div>
                </div>

                <div class="table-responsive mb-3">
                    <table class="table table-dark table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 220px;">PLATAFORMA</th>
                                <th>SUA TAG / CÓDIGO DE AFILIADO</th>
                                <th style="width: 200px;">REPASSE AO MOTORISTA (%)</th>
                                <th style="width: 100px;" class="text-center">STATUS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($platforms as $p): ?>
                                <tr>
                                    <td class="fw-bold text-white">
                                        <?php if ($p['platform_key'] === 'mercadolivre'): ?>
                                            <i class="fa fa-handshake text-warning me-2"></i> Mercado Livre
                                        <?php elseif ($p['platform_key'] === 'shopee'): ?>
                                            <i class="fa fa-shopping-bag text-danger me-2"></i> Shopee
                                        <?php elseif ($p['platform_key'] === 'amazon'): ?>
                                            <i class="fab fa-amazon text-warning me-2"></i> Amazon
                                        <?php elseif ($p['platform_key'] === 'magalu'): ?>
                                            <i class="fa fa-store text-primary me-2"></i> Magazine Luiza
                                        <?php elseif ($p['platform_key'] === 'temu'): ?>
                                            <i class="fa fa-bolt text-danger me-2"></i> Temu
                                        <?php else: ?>
                                            <i class="fa fa-globe text-secondary me-2"></i> <?= htmlspecialchars($p['platform_name']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="text" name="tags[<?= $p['platform_key'] ?>]" class="form-control form-control-sm deals-input" value="<?= htmlspecialchars($p['affiliate_tag']) ?>" placeholder="Ex: <?= $p['platform_key'] === 'amazon' ? 'player99-20' : ($p['platform_key'] === 'mercadolivre' ? 'matt=SEU_ID' : 'SEU_CODIGO') ?>">
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.1" min="0" max="100" name="splits[<?= $p['platform_key'] ?>]" class="form-control deals-input text-end" value="<?= htmlspecialchars($p['driver_split_percent']) ?>">
                                            <span class="input-group-text deals-addon">%</span>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input" type="checkbox" name="actives[<?= $p['platform_key'] ?>]" value="1" <?= $p['active'] == 1 ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row g-3 align-items-center">
                    <div class="col-12 col-md-6">
                        <label class="form-label deals-label">FILTRO POR JID DO GRUPO (OPCIONAL)</label>
                        <input type="text" name="group_jid" class="form-control form-control-sm deals-input" value="<?= htmlspecialchars($platforms[0]['group_jid'] ?? '') ?>" placeholder="Ex: 1203630283921@g.us (Deixe em branco para aceitar qualquer grupo)">
                    </div>
                    <div class="col-12 col-md-6 text-md-end mt-md-4">
                        <button type="submit" class="btn btn-cyan px-4 fw-bold">
                            <i class="fa fa-save me-1"></i> Salvar Configurações de Afiliados
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Testador de Conversão de Link ao Vivo -->
    <div class="card bg-dark border-secondary border-opacity-25 mb-4">
        <div class="card-header bg-dark border-secondary border-opacity-25">
            <h6 class="mb-0 fw-bold text-info"><i class="fa fa-vial me-2"></i> Testador de Substituição de Tag ao Vivo</h6>
        </div>
        <div class="card-body">
            <p class="deals-subtext mb-2">Cole qualquer link de promoção (Mercado Livre, Shopee, Amazon, etc.) para ver como o Player99 troca a tag do vendedor pela sua:</p>
            <div class="input-group mb-3">
                <input type="url" id="testUrlInput" class="form-control deals-input" placeholder="Cole aqui: https://shope.ee/... ou https://amzn.to/... ou https://mercadolivre.com/sec/...">
                <button type="button" class="btn btn-outline-info fw-bold" onclick="runLiveTest()">
                    <i class="fa fa-magic me-1"></i> Converter Link
                </button>
            </div>
            <div id="testResultBox" class="p-3 rounded bg-black border border-secondary border-opacity-25 d-none font-monospace small"></div>
        </div>
    </div>

    <!-- 3. Lista de Ofertas Ativas -->
    <div class="card bg-dark border-secondary border-opacity-25">
        <div class="card-header bg-dark border-secondary border-opacity-25 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-white"><i class="fa fa-list text-warning me-2"></i> Ofertas em Exibição nos Tablets (<?= count($deals) ?>)</h6>
            <small class="text-muted">Ordenadas pelas mais recentes</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0">
                    <thead>
                        <tr class="text-muted small">
                            <th style="width: 80px;" class="text-center">MÍDIA</th>
                            <th>PRODUTO / TÍTULO</th>
                            <th>PLATAFORMA</th>
                            <th>PREÇO</th>
                            <th class="text-center">CLIQUES NO CARRO</th>
                            <th class="text-center">STATUS</th>
                            <th style="width: 140px;" class="text-end">AÇÕES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deals)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fa fa-tags fa-2x mb-2 d-block"></i>
                                    Nenhuma oferta cadastrada ainda. Conecte o grupo do WhatsApp da Evolution ou adicione ofertas manuais acima!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($deals as $d): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if (!empty($d['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($d['image_url']) ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid #333;" alt="Thumb">
                                        <?php else: ?>
                                            <div class="bg-secondary bg-opacity-25 text-muted rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                                <i class="fa fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($d['title']) ?>">
                                            <?= htmlspecialchars($d['title']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <span class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-50 me-1"><?= htmlspecialchars($d['category'] ?: 'Geral') ?></span>
                                            Cadastrado em: <?= date('d/m/Y H:i', strtotime($d['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-black border border-secondary text-capitalize">
                                            <?= htmlspecialchars($d['platform']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($d['price_new'] > 0): ?>
                                            <span class="text-success fw-bold">R$ <?= number_format($d['price_new'], 2, ',', '.') ?></span>
                                            <?php if ($d['discount_pct'] > 0): ?>
                                                <span class="badge bg-danger ms-1">-<?= $d['discount_pct'] ?>%</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted small">Consulte</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info bg-opacity-25 text-info px-3 py-2 fs-6">
                                            <i class="fa fa-qrcode me-1"></i> <?= (int)$d['clicks_count'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="deal_id" value="<?= $d['id'] ?>">
                                            <button type="submit" class="btn btn-sm <?= $d['active'] == 1 ? 'btn-success' : 'btn-outline-secondary' ?> py-0 px-2" style="font-size: 11px;">
                                                <?= $d['active'] == 1 ? 'Ativo na TV' : 'Pausado' ?>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="/o/<?= $d['id'] ?>?carro=1" target="_blank" class="btn btn-sm btn-outline-info" title="Testar link de redirecionamento">
                                                <i class="fa fa-external-link-alt"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Remover esta oferta permanentemente?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="deal_id" value="<?= $d['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Adicionar Oferta Manual -->
<div class="modal fade" id="modalAddDeal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark border-secondary">
            <form method="POST">
                <input type="hidden" name="action" value="add_manual">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-white"><i class="fa fa-plus-circle text-success me-2"></i> Cadastrar Oferta Manual</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label deals-label">LINK DO PRODUTO (URL ORIGINAL) *</label>
                        <input type="url" name="product_url" required class="form-control deals-input" placeholder="https://shope.ee/... ou https://mercadolivre.com/...">
                        <small class="deals-subtext" style="font-size: 11px;">O sistema trocará a tag do vendedor pela sua automaticamente.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label deals-label">TÍTULO DA OFERTA</label>
                        <input type="text" name="title" class="form-control deals-input" placeholder="Ex: Fone Bluetooth Sem Fio TWS">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label deals-label">PREÇO DE (R$)</label>
                            <input type="text" name="price_old" class="form-control deals-input" placeholder="Ex: 199,90">
                        </div>
                        <div class="col-6">
                            <label class="form-label deals-label">PREÇO POR (R$)</label>
                            <input type="text" name="price_new" class="form-control deals-input" placeholder="Ex: 89,90">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label deals-label">URL DA IMAGEM DO PRODUTO</label>
                        <input type="url" name="image_url" class="form-control deals-input" placeholder="https://... imagem.jpg">
                    </div>
                    <div class="mb-3">
                        <label class="form-label deals-label">CATEGORIA</label>
                        <select name="category" class="form-select deals-input">
                            <option value="Eletrônicos">Eletrônicos</option>
                            <option value="Automotivo">Automotivo</option>
                            <option value="Beleza & Cuidados">Beleza & Cuidados</option>
                            <option value="Casa & Utilidades">Casa & Utilidades</option>
                            <option value="Moda & Acessórios">Moda & Acessórios</option>
                            <option value="Geral">Geral</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-save me-1"></i> Cadastrar Oferta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ===============================================
// GESTÃO DA INSTÂNCIA WHATSAPP DE OFERTAS
// ===============================================
function checkDealsEvoStatus() {
    const badge = document.getElementById('dealsEvoBadge');
    badge.className = 'badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-2';
    badge.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Verificando conexão da instância de ofertas...';

    const inst = document.getElementById('dealsEvoInstance').value.trim() || 'player99_ofertas';
    fetch('/admin/achadinhos/evolution-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ instance: inst })
    })
        .then(r => r.json())
        .then(res => {
            const state = res.data?.instance?.state || res.data?.state || 'close';
            if (state === 'open') {
                badge.className = 'badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-2 fw-bold';
                badge.innerHTML = '<i class="fa fa-check-circle me-1"></i> Conectado e Operacional (Monitorando Grupos)';
                document.getElementById('dealsQrCodeWrapper').classList.add('d-none');
            } else if (state === 'connecting') {
                badge.className = 'badge bg-info bg-opacity-25 text-info border border-info border-opacity-50 px-3 py-2';
                badge.innerHTML = '<i class="fa fa-sync fa-spin me-1"></i> Conectando...';
            } else {
                badge.className = 'badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2';
                badge.innerHTML = '<i class="fa fa-times-circle me-1"></i> Desconectado (' + state + ') - Clique em Gerar QR Code';
            }
        })
        .catch(() => {
            badge.className = 'badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2';
            badge.innerHTML = '<i class="fa fa-exclamation-triangle me-1"></i> Erro ao consultar servidor Evolution';
        });
}

function connectDealsEvo() {
    const wrapper = document.getElementById('dealsQrCodeWrapper');
    const holder = document.getElementById('dealsQrCodeHolder');
    const timer = document.getElementById('dealsQrTimer');

    wrapper.classList.remove('d-none');
    holder.innerHTML = '<div class="text-info py-4"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Criando instância e gerando QR Code...</p></div>';

    const inst = document.getElementById('dealsEvoInstance').value.trim() || 'player99_ofertas';
    fetch('/admin/achadinhos/evolution-connect', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ instance: inst })
    })
        .then(r => r.json())
        .then(res => {
            if (res.data?.base64 || res.data?.qrcode?.base64) {
                const b64 = res.data.base64 || res.data.qrcode.base64;
                holder.innerHTML = '<img src="' + b64 + '" style="max-width: 260px; border-radius: 12px; border: 4px solid #fff; box-shadow: 0 4px 20px rgba(0,0,0,0.5);" alt="QR Code">';
                timer.innerText = 'QR Code gerado! Aponte o WhatsApp do celular para conectar.';

                const interval = setInterval(() => {
                    fetch('/admin/achadinhos/evolution-status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ instance: inst })
                    })
                        .then(r2 => r2.json())
                        .then(st => {
                            const state = st.data?.instance?.state || st.data?.state;
                            if (state === 'open') {
                                clearInterval(interval);
                                checkDealsEvoStatus();
                                alert('WhatsApp de Ofertas Conectado com Sucesso!');
                            }
                        });
                }, 4000);
            } else if (res.data?.instance?.state === 'open') {
                holder.innerHTML = '<div class="alert alert-success m-0 fw-bold"><i class="fa fa-check-circle me-1"></i> Este WhatsApp já está conectado e operacional!</div>';
                checkDealsEvoStatus();
            } else {
                holder.innerHTML = '<div class="alert alert-danger m-0">' + (res.error || 'Não foi possível gerar o QR Code. Verifique se o servidor Evolution está ativo.') + '</div>';
            }
        })
        .catch(() => {
            holder.innerHTML = '<div class="alert alert-danger m-0">Falha na conexão com a Evolution API.</div>';
        });
}

function disconnectDealsEvo() {
    if (!confirm('Deseja realmente desconectar a instância de ofertas do WhatsApp?')) return;
    const inst = document.getElementById('dealsEvoInstance').value.trim() || 'player99_ofertas';
    fetch('/admin/achadinhos/evolution-logout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ instance: inst })
    })
        .then(r => r.json())
        .then(() => {
            alert('Instância desconectada.');
            checkDealsEvoStatus();
        });
}

document.addEventListener('DOMContentLoaded', () => {
    checkDealsEvoStatus();
});

function runLiveTest() {
    const url = document.getElementById('testUrlInput').value.trim();
    const box = document.getElementById('testResultBox');
    if (!url) {
        alert('Por favor, cole um link para testar!');
        return;
    }

    box.classList.remove('d-none');
    box.innerHTML = '<span class="text-warning"><i class="fa fa-spinner fa-spin me-1"></i> Convertendo link e aplicando tag...</span>';

    fetch('/admin/achadinhos/test-convert?url=' + encodeURIComponent(url))
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const d = res.data;
                box.innerHTML = `
                    <div class="text-success fw-bold mb-1"><i class="fa fa-check-circle me-1"></i> Link Convertido com Sucesso!</div>
                    <div class="text-muted"><b>Plataforma:</b> <span class="text-info text-capitalize">${d.platform}</span></div>
                    <div class="text-muted text-truncate"><b>Link Canônico:</b> <span class="text-secondary">${d.expanded_url}</span></div>
                    <div class="text-white mt-1 pt-1 border-top border-secondary border-opacity-50 text-break">
                        <b>Seu Link de Afiliado:</b><br>
                        <a href="${d.affiliate_url}" target="_blank" class="text-warning text-decoration-none">${d.affiliate_url}</a>
                    </div>
                `;
            } else {
                box.innerHTML = '<span class="text-danger"><i class="fa fa-times me-1"></i> Erro: ' + (res.error || 'Falha ao converter') + '</span>';
            }
        })
        .catch(err => {
            box.innerHTML = '<span class="text-danger"><i class="fa fa-times me-1"></i> Erro na requisição</span>';
        });
}

function checkLinksHealth() {
    if (!confirm('Deseja executar a verificação de saúde de todos os links ativos agora? Isso desativará automaticamente ofertas que retornarem 404.')) return;

    alert('Iniciando checagem... Aguarde alguns segundos.');
    fetch('/admin/achadinhos/check-health')
        .then(r => r.json())
        .then(res => {
            alert(res.msg || 'Checagem finalizada!');
            location.reload();
        })
        .catch(err => alert('Erro ao executar checagem.'));
}
</script>
