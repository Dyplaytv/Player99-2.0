<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
?>
<?= $msg ?? '' ?>

<div class="row g-4 mb-4">
    <!-- Solicitações de Saque Pendentes -->
    <div class="col-lg-7">
        <div class="glass-card h-100">
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
                                    <td class="fw-bold text-white"><?= htmlspecialchars($s['title'] ?: ($s['username'] ?? 'Parceiro #' . $s['userId'])) ?></td>
                                    <td><code class="text-info"><?= htmlspecialchars($s['pixKey'] ?: 'Não informada') ?></code></td>
                                    <td class="fw-bold text-success fs-5">R$ <?= number_format((float)$s['total'], 2, ',', '.') ?></td>
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

    <!-- Lançar Comissão sobre Pagamento / Bônus -->
    <div class="col-lg-5">
        <div class="glass-card h-100">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="fa fa-plus-circle text-info"></i> Lançar Comissão / Pagamento Manual
            </h5>
            <form method="POST" class="row g-2">
                <input type="hidden" name="add_manual_commission" value="1">
                <div class="col-12">
                    <label class="form-label small text-muted mb-1">Afiliado Beneficiário</label>
                    <select name="affiliate_id" class="form-select form-select-sm bg-dark text-white border-secondary" required>
                        <option value="">-- Selecione o afiliado --</option>
                        <?php foreach ($afiliados as $af): ?>
                            <option value="<?= (int)$af['id'] ?>">
                                <?= htmlspecialchars($af['title'] ?: $af['username']) ?> (@<?= htmlspecialchars($af['username']) ?> — <?= number_format((float)$af['commissionPercent'], 0) ?>%)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small text-muted mb-1">Modo de Cálculo</label>
                    <select name="calc_mode" class="form-select form-select-sm bg-dark text-white border-secondary">
                        <option value="percent">Calcular % sobre Mensalidade</option>
                        <option value="direct">Valor Direto de Comissão (R$)</option>
                    </select>
                </div>
                <div class="col-sm-6">
                    <label class="form-label small text-muted mb-1">Valor Base / Mensalidade (R$)</label>
                    <input type="number" step="0.01" min="0.01" name="base_value" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Ex: 100.00" required>
                </div>
                <div class="col-12">
                    <label class="form-label small text-muted mb-1">Descrição (opcional)</label>
                    <input type="text" name="description" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Ex: Comissão mensalidade cliente indicado">
                </div>
                <div class="col-12 mt-2">
                    <button type="submit" class="btn btn-sm btn-info fw-bold w-100 text-dark">
                        <i class="fa fa-coins me-1"></i> Creditar Comissão no Saldo do Afiliado
                    </button>
                </div>
            </form>
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
                            <th>Link de Indicação</th>
                            <th class="text-center">Cliques</th>
                            <th class="text-center">Indicados</th>
                            <th class="text-center">Saldo Disponível</th>
                            <th class="text-center" style="width: 210px;">Comissão Atual (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($afiliados)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Nenhum parceiro encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($afiliados as $a):
                                $fullRefUrl = $protocol . $host . '/?ref=' . urlencode($a['affiliateCode'] ?? '');
                            ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($a['title'] ?: $a['username']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($a['username']) ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($a['affiliateCode'])): ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <code class="text-info">/?ref=<?= htmlspecialchars($a['affiliateCode']) ?></code>
                                                <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($fullRefUrl, ENT_QUOTES) ?>'); alert('Link copiado: <?= htmlspecialchars($fullRefUrl, ENT_QUOTES) ?>');" title="Copiar Link Completo">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">Sem código gerado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold text-white"><?= (int)($a['affiliateClicks'] ?? 0) ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-50 px-2 py-1">
                                            <?= (int)($a['referralsCount'] ?? 0) ?> cadastro(s)
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold text-success">
                                        R$ <?= number_format(max(0.0, (float)($a['balance'] ?? 0)), 2, ',', '.') ?>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" class="d-flex align-items-center justify-content-center gap-2 m-0">
                                            <input type="hidden" name="user_id" value="<?= (int)$a['id'] ?>">
                                            <input type="hidden" name="update_percent" value="1">
                                            <div class="input-group input-group-sm" style="width: 130px;">
                                                <input type="number" step="0.5" min="0" max="100" name="percent" class="form-control bg-dark text-white border-secondary text-center" value="<?= htmlspecialchars((string)($a['commissionPercent'] ?? '20')) ?>">
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

    <!-- Histórico Recente de Comissões e Saques -->
    <div class="col-12">
        <div class="glass-card">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="fa fa-history text-secondary"></i> Extrato Recente de Comissões & Saques
            </h5>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Afiliado</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th class="text-end">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transacoes)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-3 text-muted">Nenhuma movimentação de comissão ou saque registrada ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transacoes as $tx): ?>
                                <tr>
                                    <td class="text-muted small"><?= !empty($tx['createdAt']) ? date('d/m/Y H:i', strtotime($tx['createdAt'])) : '-' ?></td>
                                    <td class="fw-bold text-white"><?= htmlspecialchars($tx['affiliateName'] ?: ($tx['affiliateUser'] ?: '#' . $tx['userId'])) ?></td>
                                    <td>
                                        <?php if (($tx['type'] ?? '') === 'COMMISSION'): ?>
                                            <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">Comissão</span>
                                        <?php elseif (($tx['type'] ?? '') === 'WITHDRAW_PENDING'): ?>
                                            <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50">Saque Pendente</span>
                                        <?php else: ?>
                                            <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-50">Saque Pago</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($tx['description'] ?? '') ?></td>
                                    <td class="text-end fw-bold <?= ($tx['type'] ?? '') === 'COMMISSION' ? 'text-success' : 'text-warning' ?>">
                                        <?= ($tx['type'] ?? '') === 'COMMISSION' ? '+' : '-' ?> R$ <?= number_format((float)($tx['amount'] ?? 0), 2, ',', '.') ?>
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
