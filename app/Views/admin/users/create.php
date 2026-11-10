<div class="row justify-content-center">
    <div class="col-12 col-lg-7">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fa fa-user-plus text-info me-2"></i> Novo Administrador / Revendedor</h4>
                    <p class="text-muted mb-0">Cadastre um novo operador ou parceiro com acesso ao painel de gestão.</p>
                </div>
                <a href="/admin/usuarios" class="btn btn-outline-light btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Voltar
                </a>
            </div>

            <form method="POST" action="/admin/usuarios/criar">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted small fw-bold text-uppercase">Nome Completo / Empresa</label>
                        <input type="text" name="name" class="form-control bg-dark text-white border-secondary" placeholder="Ex: João Silva - Parceiro Regional" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted small fw-bold text-uppercase">Usuário de Login</label>
                        <input type="text" name="username" class="form-control bg-dark text-white border-secondary" placeholder="Ex: joaosilva" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted small fw-bold text-uppercase">E-mail</label>
                        <input type="email" name="email" class="form-control bg-dark text-white border-secondary" placeholder="contato@empresa.com">
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Senha de Acesso</label>
                        <input type="text" name="password" class="form-control bg-dark text-white border-secondary" value="mudar123" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Nível de Acesso</label>
                        <select name="nivel" class="form-select bg-dark text-white border-secondary">
                            <option value="2">Franqueado / Revendedor (sem acesso admin)</option>
                            <option value="1">Administrador Geral</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted small fw-bold text-uppercase">Cota de Telas</label>
                        <input type="number" name="mac_amount" class="form-control bg-dark text-white border-secondary" value="20" min="1">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <a href="/admin/usuarios" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-cyan px-4">
                        <i class="fa fa-check me-1"></i> Criar Usuário
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
