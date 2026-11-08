<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fa fa-tablet-screen-button text-cyan me-2"></i> Cadastrar Nova Tela / Carro</h4>
                    <p class="text-muted small mb-0">Crie o acesso do motorista/parceiro e gere o código de transmissão para o tablet.</p>
                </div>
                <a href="/admin/telas" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Voltar
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4">
                    <i class="fa fa-times-circle fa-lg"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/admin/telas/criar">
                <div class="row g-3">
                    <!-- Identificação -->
                    <div class="col-md-8">
                        <label class="form-label text-muted small fw-bold">Identificação do Carro / Nome do Motorista *</label>
                        <input type="text" name="title" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Corolla Prata - Carlos Silva" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Código do Player (Tablet)</label>
                        <input type="text" name="player_code" class="form-control bg-dark text-info border-secondary font-monospace fw-bold text-uppercase" value="<?= htmlspecialchars($suggestedCode ?? '') ?>" required>
                    </div>

                    <!-- Dados de Acesso do Motorista -->
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Usuário do Motorista (Login)</label>
                        <input type="text" name="username" class="form-control bg-dark text-white border-secondary" placeholder="Ex: carlos.silva" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Senha de Acesso</label>
                        <input type="text" name="password" class="form-control bg-dark text-white border-secondary" value="mudar123" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">WhatsApp de Contato</label>
                        <input type="text" name="whatsapp" class="form-control bg-dark text-white border-secondary" placeholder="5551999999999">
                    </div>

                    <!-- Parâmetros Técnicos do Player -->
                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Orientação / Rotação da Tela</label>
                        <select name="rotation" class="form-select bg-dark text-white border-secondary">
                            <option value="0">0° - Horizontal (Padrão Paisagem)</option>
                            <option value="90">90° - Vertical (Retrato Direita)</option>
                            <option value="180">180° - Horizontal Invertido</option>
                            <option value="270">270° - Vertical (Retrato Esquerda)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Modo de Áudio no Veículo</label>
                        <select name="audio_mode" class="form-select bg-dark text-white border-secondary">
                            <option value="video">Som Original dos Vídeos</option>
                            <option value="mute">Mudo (Apenas Imagem)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label text-muted small fw-bold">Validade / Vencimento</label>
                        <input type="date" name="expire_date" class="form-control bg-dark text-white border-secondary" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-end gap-2">
                    <a href="/admin/telas" class="btn btn-outline-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-cyan px-4 fw-bold">
                        <i class="fa fa-check me-1"></i> Criar Tela e Gerar Player
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
