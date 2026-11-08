<?= $msg ?? '' ?>

<div class="glass-card mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-1 text-white"><i class="fa fa-palette text-cyan me-2"></i> Identidade Visual & Banners da Home</h5>
            <p class="text-muted small mb-0">Gerencie a Logo oficial da Player99 e os 3 banners sequenciais da página inicial.</p>
        </div>
        <a href="/" target="_blank" class="btn btn-outline-info btn-sm fw-bold">
            <i class="fa fa-external-link-alt me-1"></i> Visualizar Home Ao Vivo
        </a>
    </div>
</div>

<form method="POST" enctype="multipart/form-data">
    <!-- Card Especial de Upload da LOGO Oficial -->
    <div class="glass-card mb-4 border border-info border-opacity-25" style="background: rgba(18, 22, 36, 0.9);">
        <div class="row align-items-center g-4">
            <div class="col-12 col-md-4 text-center">
                <div class="fw-bold mb-2 text-white"><i class="fa fa-image text-info me-1"></i> Logo Atual do Sistema</div>
                <div class="p-3 rounded-4 d-inline-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.6); border: 2px dashed rgba(52, 226, 228, 0.3); min-width: 220px; min-height: 120px;">
                    <img src="/uploads/landing/logo.png?v=<?= time() ?>" alt="Logo Oficial Player99" style="max-height: 90px; max-width: 100%; object-fit: contain; filter: drop-shadow(0 4px 10px rgba(52, 226, 228, 0.3));" onerror="this.onerror=null; this.src='/banners/logo.png';">
                </div>
            </div>

            <div class="col-12 col-md-8">
                <h6 class="fw-bold text-white mb-2">Alterar Logomarca Oficial</h6>
                <p class="text-muted small mb-3">
                    Esta logo é aplicada automaticamente em todos os locais: cabeçalho da <b>Home (Landing Page)</b>, barra lateral do <b>Painel Administrativo</b>, tela de <b>Login</b> e <b>Portal do Motorista/Cliente</b>.
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
    </div>

    <!-- Banners da Landing Page -->
    <h6 class="fw-bold text-white mb-3"><i class="fa fa-images text-warning me-2"></i> Banners Sequenciais da Página Inicial</h6>
    <div class="row g-4">
        <!-- Banner 1 -->
        <div class="col-12 col-md-4">
            <div class="glass-card h-100 d-flex flex-column">
                <div class="fw-bold mb-2 text-white d-flex align-items-center justify-content-between">
                    <span>Banner 1 (Topo / Destaque)</span>
                    <span class="badge bg-primary bg-opacity-25 text-info">img1.jpg</span>
                </div>
                <div class="mb-3 text-center" style="background: rgba(0,0,0,0.5); border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); height: 200px; display: flex; align-items: center; justify-content: center;">
                    <img src="/uploads/landing/img1.jpg?v=<?= time() ?>" alt="Banner 1" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.src='/banners/logo.png'">
                </div>
                <div class="mt-auto">
                    <label class="form-label text-muted small fw-bold">Trocar Imagem 1</label>
                    <input type="file" name="img1" class="form-control bg-dark text-white border-secondary" accept="image/*">
                </div>
            </div>
        </div>

        <!-- Banner 2 -->
        <div class="col-12 col-md-4">
            <div class="glass-card h-100 d-flex flex-column">
                <div class="fw-bold mb-2 text-white d-flex align-items-center justify-content-between">
                    <span>Banner 2 (Meio / Proposta)</span>
                    <span class="badge bg-primary bg-opacity-25 text-info">img2.jpg</span>
                </div>
                <div class="mb-3 text-center" style="background: rgba(0,0,0,0.5); border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); height: 200px; display: flex; align-items: center; justify-content: center;">
                    <img src="/uploads/landing/img2.jpg?v=<?= time() ?>" alt="Banner 2" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.src='/banners/logo.png'">
                </div>
                <div class="mt-auto">
                    <label class="form-label text-muted small fw-bold">Trocar Imagem 2</label>
                    <input type="file" name="img2" class="form-control bg-dark text-white border-secondary" accept="image/*">
                </div>
            </div>
        </div>

        <!-- Banner 3 -->
        <div class="col-12 col-md-4">
            <div class="glass-card h-100 d-flex flex-column">
                <div class="fw-bold mb-2 text-white d-flex align-items-center justify-content-between">
                    <span>Banner 3 (Rodapé / Chamada)</span>
                    <span class="badge bg-primary bg-opacity-25 text-info">img3.jpg</span>
                </div>
                <div class="mb-3 text-center" style="background: rgba(0,0,0,0.5); border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.08); height: 200px; display: flex; align-items: center; justify-content: center;">
                    <img src="/uploads/landing/img3.jpg?v=<?= time() ?>" alt="Banner 3" style="max-width: 100%; max-height: 100%; object-fit: contain;" onerror="this.src='/banners/logo.png'">
                </div>
                <div class="mt-auto">
                    <label class="form-label text-muted small fw-bold">Trocar Imagem 3</label>
                    <input type="file" name="img3" class="form-control bg-dark text-white border-secondary" accept="image/*">
                </div>
            </div>
        </div>
    </div>

    <div class="glass-card mt-4 text-end">
        <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow">
            <i class="fa fa-save me-1"></i> Salvar e Publicar Alterações
        </button>
    </div>
</form>
