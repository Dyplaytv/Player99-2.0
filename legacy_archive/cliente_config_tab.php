<?php
$faturamento = [
    'nome_razao' => '',
    'cpf_cnpj' => '',
    'endereco' => '',
    'whats' => ''
];
if(isset($dbFinanceiro)) {
    $stmtFat = $dbFinanceiro->prepare("SELECT * FROM faturamento_cliente WHERE id_cliente = :id");
    $stmtFat->bindValue(':id', $idCliente, SQLITE3_INTEGER);
    $resFat = $stmtFat->execute();
    if ($rowFat = $resFat->fetchArray(SQLITE3_ASSOC)) {
        $faturamento = $rowFat;
    }

    $faturas = [];
    $totalAcumulado = 0;
    $totalPrevisto = 0;
    $stmtFaturas = $dbFinanceiro->prepare("SELECT * FROM faturas WHERE id_cliente = :id ORDER BY id DESC");
    $stmtFaturas->bindValue(':id', $idCliente, SQLITE3_INTEGER);
    $resFaturas = $stmtFaturas->execute();
    while($rowF = $resFaturas->fetchArray(SQLITE3_ASSOC)) {
        $faturas[] = $rowF;
        if($rowF['status'] == 'PAGA' && date('m', strtotime($rowF['data_gerada'])) == date('m')) {
            $totalAcumulado += $rowF['valor_total'];
        }
        if($rowF['status'] == 'VENCIDA' || $rowF['status'] == 'PENDENTE') {
            // we will override $totalPrevisto later
        }
    }
    
    $countTelas = $dbCliente->querySingle("SELECT COUNT(*) FROM telas WHERE id_cliente = $idCliente") ?: 0;
    $mpGoldConfig = json_decode(file_get_contents(__DIR__ . '/api/mercado_pago_gold/config.json'), true);
    $baseValorPorTela = isset($mpGoldConfig['plano_telas_ativas']['valor']) ? (float)$mpGoldConfig['plano_telas_ativas']['valor'] : 100.00;
    $multiplo = $dbFinanceiro->querySingle("SELECT multiplo FROM ibo WHERE id = $idCliente") ?: 1.0;
    $totalPrevisto = $countTelas * ($baseValorPorTela * (float)$multiplo);
}
?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-user text-primary mr-2"></i> Perfil</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row">
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Alterar Senha de Acesso</label>
                <input type="password" name="nova_senha" class="form-control" placeholder="Digite a nova senha..." required>
            </div>
            <div class="col-md-12">
                <button type="submit" name="atualizar_senha" class="btn btn-primary"><i class="fas fa-save"></i> SALVAR NOVA SENHA</button>
            </div>
        </form>
    </div>
</div>


<div class="card shadow-sm border-0 mt-4" style="border-top: 4px solid #1abc9c !important;">
    <div class="card-body text-center py-5">
        <div class="mb-3">
            <i class="fas fa-wallet fa-3x" style="color: #1abc9c;"></i>
        </div>
        <h2 class="font-weight-bold" style="color: #1abc9c;">Financeiro</h2>
        <p class="text-muted mb-4">Visualize faturas, realize pagamentos e informe dados de faturamento.</p>
        <div class="d-flex justify-content-center gap-3">
            <button class="btn btn-lg mx-2 text-white" style="background-color: #1abc9c;" data-toggle="modal" data-target="#faturasModal">
                <i class="fas fa-file-invoice-dollar mr-2"></i> VISUALIZAR FATURAS
            </button>
            <button class="btn btn-lg btn-primary mx-2" data-toggle="modal" data-target="#dadosFaturamentoModal">
                <i class="fas fa-pen mr-2"></i> DADOS DE FATURAMENTO
            </button>
        </div>
    </div>
</div>


<!-- Modal Faturas -->
<div class="modal fade" id="faturasModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-wallet mr-2"></i> FINANCEIRO / FATURAS</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body bg-light">
                <h6 class="font-weight-bold mb-3 text-secondary">ANÁLISE DE CUSTOS</h6>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm" style="border-bottom: 4px solid #1abc9c !important;">
                            <div class="card-body">
                                <small class="text-muted font-weight-bold text-uppercase">Valor Acumulado Este Mês</small>
                                <h4 class="mt-2 font-weight-bold">R$ <?php echo number_format($totalAcumulado, 2, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm" style="border-bottom: 4px solid #001f3f !important; background-color: #001f3f; color: white;">
                            <div class="card-body">
                                <small class="text-white-50 font-weight-bold text-uppercase">Telas Ativas</small>
                                <h4 class="mt-2 font-weight-bold text-white"><?php echo $countTelas; ?> Telas</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm" style="border-bottom: 4px solid #ffaa00 !important;">
                            <div class="card-body">
                                <small class="text-muted font-weight-bold text-uppercase">Previsto P/ Próxima Fatura</small>
                                <h4 class="mt-2 font-weight-bold">R$ <?php echo number_format($totalPrevisto, 2, ',', '.'); ?></h4>
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="font-weight-bold mb-3 text-secondary mt-4">TODAS AS FATURAS</h6>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 text-center">
                                <thead class="bg-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Data gerada</th>
                                        <th>Data de vencimento</th>
                                        <th>Valor total</th>
                                        <th>Status</th>
                                        <th>Ação</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($faturas)): ?>
                                        <tr><td colspan="6" class="py-4 text-muted">Nenhuma fatura encontrada.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($faturas as $fat): ?>
                                            <tr>
                                                <td><?php echo $fat['id']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($fat['data_gerada'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($fat['data_vencimento'])); ?></td>
                                                <td>R$ <?php echo number_format($fat['valor_total'], 2, ',', '.'); ?></td>
                                                <td>
                                                    <?php if($fat['status'] == 'VENCIDA'): ?>
                                                        <span class="badge badge-danger px-3 py-2">VENCIDA</span>
                                                    <?php elseif($fat['status'] == 'PAGA'): ?>
                                                        <span class="badge badge-success px-3 py-2" style="background-color: #1abc9c;">PAGA</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning px-3 py-2">PENDENTE</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($fat['status'] != 'PAGA'): ?>
                                                        <form class="pagar-fatura-form mb-0" action="api/mercado_pago_gold/gerar_pix.php" method="POST" style="display:inline;">
                                                            <input type="hidden" name="is_fatura" value="1">
                                                            <input type="hidden" name="id_fatura" value="<?php echo $fat['id']; ?>">
                                                            <input type="hidden" name="valor_fatura" value="<?php echo $fat['valor_total']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-primary">Pagar Agora</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-secondary" disabled>Recibo</button>
                                                    <?php endif; ?>
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
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Dados Faturamento -->
<div class="modal fade" id="dadosFaturamentoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="fas fa-pen mr-2"></i> DADOS DE FATURAMENTO</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form method="post">
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">Esses dados serão utilizados para a emissão de recibos e faturas.</p>
                    <div class="row">
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold">Nome Completo / Razão Social</label>
                            <input type="text" name="nome_razao" class="form-control" value="<?php echo htmlspecialchars($faturamento['nome_razao']); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">CPF / CNPJ</label>
                            <input type="text" name="cpf_cnpj" class="form-control" value="<?php echo htmlspecialchars($faturamento['cpf_cnpj']); ?>" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">WhatsApp</label>
                            <input type="text" name="whats" class="form-control" value="<?php echo htmlspecialchars($faturamento['whats']); ?>">
                        </div>
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold">Endereço Completo</label>
                            <input type="text" name="endereco" class="form-control" value="<?php echo htmlspecialchars($faturamento['endereco']); ?>" placeholder="Rua, Número, Bairro, Cidade - UF">
                        </div>
                    </div>
                </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                    <button type="submit" name="atualizar_faturamento" class="btn btn-primary px-4">SALVAR DADOS</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal PIX -->
<div class="modal fade" id="pixModal" data-backdrop="static" tabindex="-1" style="z-index: 1060;">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background: #161b22; border: 1px solid #00c3ff; color: white;">
      <div class="modal-header" style="border-bottom: 1px solid rgba(0,195,255,0.3);">
        <h5 class="modal-title">Pagamento via PIX</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body text-center">
        <p>Escaneie o QR Code abaixo para pagar:</p>
        <div id="pix_loading" class="spinner-border text-info mb-3" role="status" style="display: none;"></div>
        <img id="pix_img" src="" alt="QR Code PIX" style="max-width: 100%; border-radius: 10px; display: none;">
        
        <div class="mt-4" id="pix_copy_area" style="display: none;">
            <p>Ou copie o código (PIX Copia e Cola):</p>
            <div class="input-group mb-3">
              <input type="text" id="pix_code" class="form-control" style="background: #0d1117; color: #fff; border-color: rgba(0,195,255,0.3);" readonly>
              <div class="input-group-append">
                  <button class="btn btn-outline-info" type="button" onclick="copiarPix()">Copiar</button>
              </div>
            </div>
        </div>
        
        <div id="pix_status" class="alert alert-warning mt-3" style="display: none;">
            <div class="spinner-grow spinner-grow-sm text-warning" role="status"></div> Aguardando pagamento...
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let pollInterval = null;

function copiarPix() {
    var copyText = document.getElementById("pix_code");
    copyText.select();
    copyText.setSelectionRange(0, 99999); 
    document.execCommand("copy");
    alert("Código PIX copiado!");
}

document.addEventListener('DOMContentLoaded', function() {
    $('.pagar-fatura-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var btn = form.find('button[type="submit"]');
        var originalText = btn.html();
        
        btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>').prop('disabled', true);
        
        var formData = form.serialize();

        $.ajax({
            url: 'api/mercado_pago_gold/gerar_pix.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                btn.html(originalText).prop('disabled', false);
                
                if(res.error) {
                    alert(res.error);
                    return;
                }
                
                if(res.success) {
                    $('#pix_img').attr('src', 'data:image/jpeg;base64,' + res.qr_code_base64).show();
                    $('#pix_code').val(res.qr_code);
                    $('#pix_copy_area').show();
                    $('#pix_status').show().removeClass('alert-success alert-danger').addClass('alert-warning').html('<div class="spinner-grow spinner-grow-sm text-warning" role="status"></div> Aguardando pagamento...');
                    
                    $('#faturasModal').modal('hide');
                    setTimeout(() => { $('#pixModal').modal('show'); }, 500);
                    
                    if (pollInterval) clearInterval(pollInterval);
                    pollInterval = setInterval(function() {
                        $.post('api/mercado_pago_gold/check_status.php', {payment_id: res.payment_id}, function(statusRes) {
                            if(statusRes.status === 'approved') {
                                clearInterval(pollInterval);
                                $('#pix_status').removeClass('alert-warning').addClass('alert-success').html('✅ Pagamento Aprovado!');
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000);
                            }
                        }, 'json');
                    }, 5000);
                }
            },
            error: function() {
                btn.html(originalText).prop('disabled', false);
                alert("Erro ao conectar com o servidor.");
            }
        });
    });
    
    $('#pixModal').on('hidden.bs.modal', function () {
        if (pollInterval) clearInterval(pollInterval);
    });
});
</script>
