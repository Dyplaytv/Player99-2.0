<?= $msg ?? '' ?>

<div class="row g-4 mb-4">
    <!-- Solicitações de Saque Pendentes -->
    <div class="col-12">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="fa fa-money-bill-wave text-warning"></i> Pedidos de Saque Pendentes
                </h5>
                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-1">
                    <?= count($saques) ?> pendente(s)
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Afiliado / Parceiro</th>
                            <th>Chave PIX Cadastrada</th>
                            <th>Valor Solicitado</th>
                            <th class="text-center" style="width: 140px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($saques)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fa fa-check-circle fa-2x mb-2 d-block opacity-50 text-success"></i>
                                    Nenhum pedido de saque pendente no momento.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($saques as $s): ?>
                                <tr>
                                    <td class="fw-bold text-white"><?= htmlspecialchars($s['title'] ?: 'Parceiro #' . $s['userId']) ?></td>
                                    <td><code class="text-info"><?= htmlspecialchars($s['pixKey'] ?: 'Não informada') ?></code></td>
                                    <td class="fw-bold text-success fs-5">R$ <?= number_format($s['total'], 2, ',', '.') ?></td>
                                    <td class="text-center">
                                        <form method="POST" onsubmit="return confirm('Confirmar que efetuou a transferência PIX deste saque?');" class="m-0">
                                            <input type="hidden" name="user_id" value="<?= (int)$s['userId'] ?>">
                                            <input type="hidden" name="pay_withdraw" value="1">
                                            <button type="submit" class="btn btn-sm btn-success fw-bold px-3">
                                                <i class="fa fa-check me-1"></i> Marcar Pago
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

    <!-- Lista de Afiliados e Comissões -->
    <div class="col-12">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="fa fa-handshake text-info"></i> Parceiros Afiliados & Links de Indicação
                </h5>
                <span class="badge bg-secondary bg-opacity-25 border border-secondary border-opacity-50 px-3 py-1">
                    <?= count($afiliados) ?> cadastrado(s)
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Parceiro</th>
                            <th>Código de Afiliado</th>
                            <th class="text-center">Cliques no Link</th>
                            <th class="text-center" style="width: 220px;">Comissão Atual (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($afiliados)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">Nenhum parceiro encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($afiliados as $a): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($a['title'] ?: $a['username']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($a['username']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($a['affiliateCode'])): ?>
                                            <code class="text-info">/cadastro?ref=<?= htmlspecialchars($a['affiliateCode']) ?></code>
                                        <?php else: ?>
                                            <span class="text-muted small">Sem código gerado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-white"><?= (int)$a['affiliateClicks'] ?></td>
                                    <td class="text-center">
                                        <form method="POST" class="d-flex align-items-center justify-content-center gap-2 m-0">
                                            <input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>">
                                            <input type="hidden" name="update_percent" value="1">
                                            <div class="input-group input-group-sm" style="width: 130px;">
                                                <input type="number" step="0.5" min="0" max="100" name="percent" class="form-control bg-dark text-white border-secondary text-center" value="<?= htmlspecialchars($a['commissionPercent'] ?? '20') ?>">
                                                <span class="input-group-text bg-dark border-secondary text-muted">%</span>
                                            </div>
                                            <button type="submit" class="btn btn-sm btn-outline-info" title="Salvar Nova Porcentagem">
                                                <i class="fa fa-save"></i>
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
