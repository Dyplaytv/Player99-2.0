<div class="row justify-content-center">
    <div class="col-12 col-xl-8">
        <?= $msg ?? '' ?>

        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                <div>
                    <h5 class="fw-bold mb-1"><i class="fa fa-wallet text-cyan me-2"></i> Dados Fiscais & Pagamento PIX</h5>
                    <p class="text-muted small mb-0">Estes dados aparecem nas faturas, relatórios de cobrança e recebimentos de clientes.</p>
                </div>
                <div class="stat-icon green" style="width: 48px; height: 48px; font-size: 20px;">
                    <i class="fa fa-receipt"></i>
                </div>
            </div>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label text-muted small fw-bold">Razão Social / Nome da Empresa</label>
                        <input type="text" name="nome_razao" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($dados['nome_razao'] ?? '') ?>" placeholder="Ex: BaitaBric Publicidade e Mídia Ltda" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label text-muted small fw-bold">CNPJ ou CPF</label>
                        <input type="text" name="cpf_cnpj" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($dados['cpf_cnpj'] ?? '') ?>" placeholder="00.000.000/0001-00" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label text-muted small fw-bold">Endereço Completo</label>
                        <input type="text" name="endereco" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($dados['endereco'] ?? '') ?>" placeholder="Av. Principal, 1000 - Sala 202 - Centro">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">WhatsApp Financeiro / Suporte</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-success"><i class="fab fa-whatsapp"></i></span>
                            <input type="text" name="whats" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($dados['whats'] ?? '') ?>" placeholder="5551999999999">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label text-muted small fw-bold">Chave PIX Oficial de Recebimento</label>
                        <div class="input-group">
                            <span class="input-group-text bg-dark border-secondary text-cyan"><i class="fa fa-qrcode"></i></span>
                            <input type="text" name="chave_pix" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($dados['chave_pix'] ?? '') ?>" placeholder="CNPJ, E-mail, Celular ou Chave Aleatória">
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-end">
                    <button type="submit" class="btn btn-primary fw-bold px-4 py-2 shadow">
                        <i class="fa fa-save me-1"></i> Salvar Dados Fiscais
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
