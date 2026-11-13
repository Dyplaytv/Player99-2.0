<div class="row g-4">
    <!-- Coluna Esquerda: Cadastro de Produto -->
    <div class="col-12 col-lg-5">
        <?= $msg ?? '' ?>

        <div class="glass-card">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="fa fa-plus-circle text-info"></i> Novo Produto / Acessório
            </h5>
            <p class="text-muted small mb-3">Cadastre suportes, tablets, cabeamento ou planos para venda direta aos motoristas e parceiros.</p>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Título do Produto</label>
                    <input type="text" name="titulo" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Suporte Veicular Articulado para Tablet 10 polegadas" required>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Preço de Venda (R$)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-dark border-secondary text-success">R$</span>
                        <input type="number" step="0.01" min="0" name="preco" class="form-control bg-dark text-white border-secondary" placeholder="149.90" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Foto do Produto</label>
                    <input type="file" name="imagem" class="form-control bg-dark text-white border-secondary" accept="image/*">
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Descrição Completa</label>
                    <textarea name="descricao" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Detalhes técnicos, compatibilidade veicular, etc."></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">WhatsApp do Vendedor (aviso de venda)</label>
                    <input type="tel" name="whatsapp_vendedor" class="form-control bg-dark text-white border-secondary" placeholder="Ex: 11999999999 (com DDD)">
                    <small class="text-muted">Você receberá uma mensagem no WhatsApp sempre que este produto for vendido.</small>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Observações ao cliente após a compra</label>
                    <textarea name="observacoes_pos_compra" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Instruções de ativação, link de acesso ou prazo de entrega enviados ao cliente após o pagamento."></textarea>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                    <i class="fa fa-save me-1"></i> Cadastrar Produto
                </button>
            </form>
        </div>
    </div>

    <!-- Coluna Direita: Catálogo Cadastrado -->
    <div class="col-12 col-lg-7">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="fa fa-store text-warning"></i> Produtos Cadastrados
                </h5>
                <span class="badge bg-secondary bg-opacity-25 border border-secondary border-opacity-50 px-3 py-2">
                    <?= count($produtos) ?> item(ns)
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th class="text-center" style="width: 120px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produtos)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fa fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                    Nenhum produto cadastrado na loja ainda.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td style="width: 80px;">
                                        <div style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                                            <?php if (!empty($p['imagem'])): ?>
                                                <img src="/uploads/loja/<?= htmlspecialchars($p['imagem']) ?>" alt="Produto" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <i class="fa fa-image text-muted opacity-50"></i>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($p['titulo']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 250px;">
                                            <?= htmlspecialchars($p['descricao'] ?: 'Sem descrição') ?>
                                        </div>
                                    </td>
                                    <td class="fw-bold text-success fs-5">
                                        R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center gap-2">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-info text-info border-info border-opacity-50"
                                                title="Editar Produto"
                                                onclick="openEditProductModal(this)"
                                                data-id="<?= (int)$p['id'] ?>"
                                                data-titulo="<?= htmlspecialchars($p['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-preco="<?= number_format((float)$p['preco'], 2, '.', '') ?>"
                                                data-descricao="<?= htmlspecialchars($p['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-observacoes="<?= htmlspecialchars($p['observacoes_pos_compra'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-whatsapp-vendedor="<?= htmlspecialchars($p['whatsapp_vendedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                data-imagem="<?= htmlspecialchars($p['imagem'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Deseja realmente excluir este produto?');" class="d-inline m-0">
                                                <input type="hidden" name="delete" value="<?= (int)$p['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-danger border-danger border-opacity-50" title="Excluir">
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

<!-- Modal Editar Produto -->
<div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white border border-secondary border-opacity-50 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-secondary border-opacity-25">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="fa fa-edit text-info"></i> Editar Produto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="edit_prod_id">

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Título do Produto</label>
                        <input type="text" name="titulo" id="edit_prod_titulo" class="form-control bg-dark text-white border-secondary" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Preço de Venda (R$)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-success">R$</span>
                            <input type="number" step="0.01" min="0" name="preco" id="edit_prod_preco" class="form-control bg-dark text-white border-secondary" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Foto do Produto</label>
                        <div id="edit_prod_img_wrap" class="mb-2 d-none align-items-center gap-3 p-2 rounded border border-secondary border-opacity-25" style="background: rgba(255,255,255,0.03);">
                            <img id="edit_prod_img_preview" src="" alt="Preview" style="width: 56px; height: 56px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(255,255,255,0.15);">
                            <small class="text-muted">Foto atual (envie outra abaixo apenas se quiser substituir)</small>
                        </div>
                        <input type="file" name="imagem" class="form-control bg-dark text-white border-secondary" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Descrição Completa</label>
                        <textarea name="descricao" id="edit_prod_descricao" rows="3" class="form-control bg-dark text-white border-secondary"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">WhatsApp do Vendedor (aviso de venda)</label>
                        <input type="tel" name="whatsapp_vendedor" id="edit_prod_whatsapp_vendedor" class="form-control bg-dark text-white border-secondary" placeholder="Ex: 11999999999 (com DDD)">
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-muted small fw-bold">Observações ao cliente após a compra</label>
                        <textarea name="observacoes_pos_compra" id="edit_prod_observacoes" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Instruções ou observações enviadas ao cliente após a compra."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-secondary border-opacity-25">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info text-dark fw-bold px-4">
                        <i class="fa fa-save me-1"></i> Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditProductModal(btn) {
    document.getElementById('edit_prod_id').value = btn.getAttribute('data-id') || '';
    document.getElementById('edit_prod_titulo').value = btn.getAttribute('data-titulo') || '';
    document.getElementById('edit_prod_preco').value = btn.getAttribute('data-preco') || '0.00';
    document.getElementById('edit_prod_descricao').value = btn.getAttribute('data-descricao') || '';
    document.getElementById('edit_prod_observacoes').value = btn.getAttribute('data-observacoes') || '';
    document.getElementById('edit_prod_whatsapp_vendedor').value = btn.getAttribute('data-whatsapp-vendedor') || '';

    const imgName = btn.getAttribute('data-imagem') || '';
    const imgWrap = document.getElementById('edit_prod_img_wrap');
    const imgPreview = document.getElementById('edit_prod_img_preview');
    if (imgName) {
        imgPreview.src = '/uploads/loja/' + imgName;
        imgWrap.classList.remove('d-none');
        imgWrap.classList.add('d-flex');
    } else {
        imgPreview.src = '';
        imgWrap.classList.remove('d-flex');
        imgWrap.classList.add('d-none');
    }

    const modalEl = document.getElementById('editProductModal');
    if (window.bootstrap && modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}
</script>
