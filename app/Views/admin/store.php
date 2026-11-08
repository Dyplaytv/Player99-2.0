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

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Descrição Completa</label>
                    <textarea name="descricao" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Detalhes técnicos, compatibilidade veicular, etc."></textarea>
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
                            <th class="text-center" style="width: 100px;">Ação</th>
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
                                        <form method="POST" onsubmit="return confirm('Deseja realmente excluir este produto?');" class="d-inline m-0">
                                            <input type="hidden" name="delete" value="<?= (int)$p['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger text-danger border-danger border-opacity-50" title="Excluir">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
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
