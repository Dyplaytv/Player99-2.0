<?php
/** @var array $pages */
/** @var array $itemsByPage */
/** @var string $activePage */
$activeMeta = $pages[$activePage];
$activeItems = $itemsByPage[$activePage];
$formUrl = '/admin/landing-editor?page=' . urlencode($activePage);
?>
<?= $msg ?? '' ?>

<!-- Logo Oficial -->
<div class="glass-card mb-4 border border-info border-opacity-25" style="background: rgba(18, 22, 36, 0.9);">
    <form method="POST" action="<?= $formUrl ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="logo">
        <input type="hidden" name="page" value="<?= htmlspecialchars($activePage) ?>">
        <div class="row align-items-center g-4">
            <div class="col-12 col-md-4 text-center">
                <div class="fw-bold mb-2 text-white"><i class="fa fa-image text-info me-1"></i> Logo Atual do Sistema</div>
                <div class="p-3 rounded-4 d-inline-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.6); border: 2px dashed rgba(52, 226, 228, 0.3); min-width: 220px; min-height: 120px;">
                    <img src="<?= \App\Core\View::logoUrl() ?>" alt="Logo Oficial Player99" style="max-height: 90px; max-width: 100%; object-fit: contain; filter: drop-shadow(0 4px 10px rgba(52, 226, 228, 0.3));" onerror="this.onerror=null; this.src='/assets/images/logo.png';">
                </div>
            </div>
            <div class="col-12 col-md-8">
                <h6 class="fw-bold text-white mb-2">Alterar Logomarca Oficial</h6>
                <p class="text-muted small mb-3">
                    Esta logo é aplicada automaticamente em todos os locais: cabeçalho da <b>Home</b>, <b>Parceiros</b> e <b>Afiliados</b>, barra lateral do <b>Painel Administrativo</b>, tela de <b>Login</b> e <b>Portal do Motorista/Cliente</b>.
                </p>
                <div class="row g-2 align-items-start">
                    <div class="col-12 col-sm-8">
                        <input type="file" name="logo" class="form-control bg-dark text-white border-secondary" style="padding: 10px 14px;" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        <small class="text-muted d-block mt-2" style="font-size: 11px;">Recomendado: PNG com fundo transparente ou WEBP (formato horizontal ou circular).</small>
                    </div>
                    <div class="col-12 col-sm-4">
                        <button type="submit" class="btn btn-cyan w-100 fw-bold d-flex align-items-center justify-content-center" style="height: 46px; font-size: 0.95rem;">
                            <i class="fa fa-upload me-2"></i> Atualizar Logo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Abas das páginas -->
<div class="glass-card mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-1 text-white"><i class="fa fa-palette text-cyan me-2"></i> Containers das Páginas Públicas</h5>
            <p class="text-muted small mb-0">Cada container é uma imagem empilhada na página. Adicione, troque, reordene ou remova.</p>
        </div>
        <a href="<?= htmlspecialchars($activeMeta['url']) ?>" target="_blank" class="btn btn-outline-info btn-sm fw-bold">
            <i class="fa fa-external-link-alt me-1"></i> Visualizar <?= htmlspecialchars($activeMeta['label']) ?> ao vivo
        </a>
    </div>

    <ul class="nav nav-pills gap-2 mt-4">
        <?php foreach ($pages as $key => $meta): ?>
            <li class="nav-item">
                <a href="/admin/landing-editor?page=<?= urlencode($key) ?>"
                   class="nav-link fw-bold px-4 <?= $key === $activePage ? 'active' : 'text-white' ?>"
                   style="<?= $key === $activePage ? 'background: linear-gradient(135deg, #0A38D1, #34e2e4); color:#000;' : 'background: rgba(255,255,255,0.06);' ?>">
                    <i class="fa <?= htmlspecialchars($meta['icon']) ?> me-1"></i> <?= htmlspecialchars($meta['label']) ?>
                    <span class="badge bg-dark ms-1"><?= count($itemsByPage[$key]) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<!-- Adicionar novo(s) container(s) -->
<div class="glass-card mb-4 border border-warning border-opacity-25">
    <form method="POST" action="<?= $formUrl ?>" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="page" value="<?= htmlspecialchars($activePage) ?>">
        <h6 class="fw-bold text-white mb-3"><i class="fa fa-plus-circle text-warning me-2"></i> Adicionar container na página <?= htmlspecialchars($activeMeta['label']) ?></h6>
        <div class="row g-3 align-items-end">
            <div class="col-12 col-lg-5">
                <label class="form-label text-muted small fw-bold">Imagem(ns) do container</label>
                <input type="file" name="images[]" multiple required class="form-control bg-dark text-white border-secondary" accept="image/png,image/jpeg,image/webp,image/gif">
                <small class="text-muted" style="font-size: 11px;">Pode selecionar várias imagens de uma vez (JPG, PNG, WEBP, GIF até 15MB).</small>
            </div>
            <div class="col-12 col-lg-5">
                <label class="form-label text-muted small fw-bold">Link ao clicar (opcional)</label>
                <input type="text" name="link" class="form-control bg-dark text-white border-secondary" placeholder="<?= $activeMeta['default_link'] !== '' ? 'Padrão: ' . htmlspecialchars($activeMeta['default_link']) : 'Ex: /cadastro ou https://...' ?>">
            </div>
            <div class="col-12 col-lg-2">
                <button type="submit" class="btn btn-warning w-100 fw-bold" style="height: 46px;">
                    <i class="fa fa-plus me-1"></i> Adicionar
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Containers existentes -->
<?php if (empty($activeItems)): ?>
    <div class="glass-card text-center py-5">
        <i class="fa fa-images fa-2x text-muted mb-3"></i>
        <h6 class="text-white fw-bold">Nenhum container nesta página ainda</h6>
        <p class="text-muted small mb-0">Use o formulário acima para adicionar o primeiro container da página <?= htmlspecialchars($activeMeta['label']) ?>.</p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($activeItems as $index => $item): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <div class="glass-card h-100 d-flex flex-column mb-0">
                    <div class="fw-bold mb-2 text-white d-flex align-items-center justify-content-between">
                        <span>Container <?= $index + 1 ?></span>
                        <span class="d-flex align-items-center gap-1">
                            <?php if (!$item['exists']): ?>
                                <span class="badge bg-danger">arquivo ausente</span>
                            <?php endif; ?>
                            <span class="badge bg-primary bg-opacity-25 text-info"><?= htmlspecialchars($item['file']) ?></span>
                        </span>
                    </div>

                    <div class="mb-3 text-center" style="background: rgba(0,0,0,0.5); border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); height: 200px; display: flex; align-items: center; justify-content: center;">
                        <img src="<?= htmlspecialchars($item['url']) ?>" alt="Container <?= $index + 1 ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='/assets/images/logo.png'">
                    </div>

                    <form method="POST" action="<?= $formUrl ?>" enctype="multipart/form-data" class="mt-auto">
                        <input type="hidden" name="page" value="<?= htmlspecialchars($activePage) ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($item['id']) ?>">

                        <label class="form-label text-muted small fw-bold mb-1">Trocar imagem</label>
                        <input type="file" name="image" class="form-control form-control-sm bg-dark text-white border-secondary mb-2" accept="image/png,image/jpeg,image/webp,image/gif">

                        <label class="form-label text-muted small fw-bold mb-1">Link ao clicar</label>
                        <input type="text" name="link" value="<?= htmlspecialchars($item['link']) ?>" class="form-control form-control-sm bg-dark text-white border-secondary mb-3" placeholder="<?= $activeMeta['default_link'] !== '' ? 'Padrão: ' . htmlspecialchars($activeMeta['default_link']) : 'Sem link' ?>">

                        <div class="d-flex gap-2">
                            <button type="submit" name="action" value="update" class="btn btn-primary btn-sm fw-bold flex-grow-1">
                                <i class="fa fa-save me-1"></i> Salvar
                            </button>
                            <button type="submit" name="action" value="move_up" class="btn btn-outline-light btn-sm" title="Mover para cima" <?= $index === 0 ? 'disabled' : '' ?> formnovalidate>
                                <i class="fa fa-arrow-up"></i>
                            </button>
                            <button type="submit" name="action" value="move_down" class="btn btn-outline-light btn-sm" title="Mover para baixo" <?= $index === count($activeItems) - 1 ? 'disabled' : '' ?> formnovalidate>
                                <i class="fa fa-arrow-down"></i>
                            </button>
                            <button type="submit" name="action" value="delete" class="btn btn-outline-danger btn-sm" title="Remover container" onclick="return confirm('Remover este container da página?');" formnovalidate>
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
