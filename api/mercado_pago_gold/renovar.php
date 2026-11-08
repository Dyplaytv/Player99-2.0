<?php
session_start();
if (!isset($_SESSION['mpg_username'])) {
    header("Location: consulta.php");
    exit;
}

require_once __DIR__ . '/../../includes/db_financeiro.php';

$config_file = 'config.json';
$config = json_decode(file_get_contents($config_file), true);
$pacotes = $config['pacotes'] ?? [];
$cupons = $config['cupons'] ?? [];

$msg_cupom = '';

$username = $_SESSION['mpg_username'] ?? '';
$fatura_pendente = null;

if (!empty($username)) {
    $idCliente = $dbFinanceiro->querySingle("SELECT id FROM ibo WHERE username = '$username'");
    if ($idCliente) {
        $resFatura = $dbFinanceiro->query("SELECT * FROM faturas WHERE id_cliente = $idCliente AND status = 'PENDENTE' ORDER BY id DESC LIMIT 1");
        $fatura_pendente = $resFatura->fetchArray(SQLITE3_ASSOC);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aplicar_cupom'])) {
        $codigo = strtoupper(trim($_POST['codigo_cupom']));
        $valido = false;
        foreach ($cupons as $c) {
            if ($c['codigo'] === $codigo) {
                $_SESSION['mpg_cupom_codigo'] = $c['codigo'];
                $_SESSION['mpg_cupom_desconto'] = $c['desconto'];
                $valido = true;
                $msg_cupom = "<div class='alert alert-success mt-3'>Cupom aplicado com sucesso!</div>";
                break;
            }
        }
        if (!$valido) {
            $msg_cupom = "<div class='alert alert-danger mt-3'>Cupom inválido!</div>";
        }
    }
    
    if (isset($_POST['remover_cupom'])) {
        unset($_SESSION['mpg_cupom_codigo']);
        unset($_SESSION['mpg_cupom_desconto']);
        $msg_cupom = "<div class='alert alert-info mt-3'>Cupom removido.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Escolha seu Pacote</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #c9d1d9; padding-top: 50px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .header-title { text-align: center; margin-bottom: 40px; color: #00c3ff; text-transform: uppercase; font-weight: bold; text-shadow: 0 0 10px rgba(0, 195, 255, 0.5); }
        .card-pacote { background: #161b22; border: 1px solid rgba(0, 195, 255, 0.3); border-radius: 20px; text-align: center; padding: 30px 20px; transition: transform 0.3s; box-shadow: 0 0 20px rgba(0, 195, 255, 0.1); position: relative; overflow: hidden; }
        .card-pacote::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: linear-gradient(90deg, #00c3ff, #0051ff); }
        .card-pacote:hover { transform: translateY(-10px); border-color: #00c3ff; box-shadow: 0 0 30px rgba(0, 195, 255, 0.4); }
        .pacote-nome { font-size: 24px; font-weight: bold; margin-bottom: 15px; color: #fff; }
        .pacote-valor { font-size: 32px; color: #00c3ff; font-weight: bold; margin-bottom: 15px; text-shadow: 0 0 10px rgba(0, 195, 255, 0.4); }
        .btn-gold { background: linear-gradient(90deg, #00c3ff, #0051ff); border: none; color: white; font-weight: bold; width: 100%; border-radius: 30px; padding: 12px; text-transform: uppercase; box-shadow: 0 0 15px rgba(0, 195, 255, 0.4); transition: all 0.3s ease; }
        .btn-gold:hover { background: linear-gradient(90deg, #0051ff, #00c3ff); color: white; box-shadow: 0 0 25px rgba(0, 195, 255, 0.8); transform: scale(1.05); }
        .btn-outline-light { border-radius: 30px; padding: 10px 30px; border-color: rgba(0, 195, 255, 0.5); color: #00c3ff; }
        .btn-outline-light:hover { background: rgba(0, 195, 255, 0.1); border-color: #00c3ff; color: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="header-title">
            Login: <?php echo htmlspecialchars($_SESSION['mpg_username']); ?>
        </h2>
        <p class="text-center mb-4">Escolha o pacote de renovação que deseja adquirir. O seu aplicativo será renovado na hora!</p>
        
        <div class="row justify-content-center mb-5">
            <div class="col-md-6 col-sm-10">
                <div class="card p-3" style="background: #161b22; border: 1px solid rgba(0, 195, 255, 0.3); border-radius: 20px; box-shadow: 0 0 20px rgba(0, 195, 255, 0.1);">
                    <?php if (isset($_SESSION['mpg_cupom_codigo'])): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-success">Cupom Ativo</span>
                                <strong class="ms-2 text-warning"><?php echo htmlspecialchars($_SESSION['mpg_cupom_codigo']); ?> (-<?php echo $_SESSION['mpg_cupom_desconto']; ?>%)</strong>
                            </div>
                            <form method="POST" style="margin: 0;">
                                <button type="submit" name="remover_cupom" class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <form method="POST" class="d-flex">
                            <input type="text" name="codigo_cupom" class="form-control me-2" placeholder="Tem um cupom de desconto?" style="background: rgba(51, 51, 51, 0.8); border: 1px solid rgba(52, 152, 219, 0.3); color: white;">
                            <button type="submit" name="aplicar_cupom" class="btn btn-gold" style="width: auto; padding: 10px 20px;">Aplicar</button>
                        </form>
                    <?php endif; ?>
                    <?php echo $msg_cupom; ?>
                </div>
            </div>
        </div>

        
        <?php if ($fatura_pendente): ?>
            <div class="row justify-content-center mb-5">
                <div class="col-md-6 col-sm-10">
                    <div class="card-pacote" style="border-color: #ffaa00; box-shadow: 0 0 20px rgba(255, 170, 0, 0.4);">
                        <h3 style="color: #ffaa00;">Fatura Pendente</h3>
                        <p>Você possui uma fatura automática gerada em <?php echo date('d/m/Y', strtotime($fatura_pendente['data_gerada'])); ?></p>
                        <div class="pacote-valor" style="color: #ffaa00;">R$ <?php echo number_format($fatura_pendente['valor_total'], 2, ',', '.'); ?></div>
                        <p class="mb-4">Vencimento: <?php echo date('d/m/Y', strtotime($fatura_pendente['data_vencimento'])); ?></p>
                        
                        <form method="POST" action="gerar_pix.php">
                            <input type="hidden" name="is_fatura" value="1">
                            <input type="hidden" name="id_fatura" value="<?php echo htmlspecialchars($fatura_pendente['id']); ?>">
                            <input type="hidden" name="valor_fatura" value="<?php echo htmlspecialchars($fatura_pendente['valor_total']); ?>">
                            <button type="submit" class="btn btn-gold" style="background: linear-gradient(90deg, #ffaa00, #ff5500); max-width: 300px; margin: 0 auto; display: block;">PAGAR FATURA AGORA</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <h4 class="text-center mb-4" style="color: #888;">Ou escolha um pacote avulso:</h4>
        <?php endif; ?>

        <div class="row g-4 justify-content-center">
            <?php if (empty($pacotes)): ?>
                <div class="col-12 text-center">
                    <p>Nenhum pacote disponível no momento.</p>
                </div>
            <?php else: ?>
                <?php foreach ($pacotes as $p): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card-pacote">
                        <div class="pacote-nome"><?php echo htmlspecialchars($p['nome']); ?></div>
                        
                        <?php if (isset($_SESSION['mpg_cupom_desconto'])): ?>
                            <?php 
                                $valor_original = $p['valor'];
                                $desconto_reais = ($valor_original * $_SESSION['mpg_cupom_desconto']) / 100;
                                $valor_com_desconto = $valor_original - $desconto_reais;
                            ?>
                            <div style="text-decoration: line-through; color: #888; font-size: 18px;">De R$ <?php echo number_format($valor_original, 2, ',', '.'); ?></div>
                            <div class="pacote-valor" style="color: #00ff00;">Por R$ <?php echo number_format($valor_com_desconto, 2, ',', '.'); ?></div>
                        <?php else: ?>
                            <div class="pacote-valor">R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></div>
                        <?php endif; ?>
                        <p class="mb-4">+<?php echo htmlspecialchars($p['dias'] ?? $p['creditos']); ?> Dias de Acesso</p>
                        <form method="POST" action="gerar_pix.php">
                            <input type="hidden" name="id_pacote" value="<?php echo htmlspecialchars($p['id']); ?>">
                            <button type="submit" class="btn btn-gold">Comprar Agora</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="consulta.php" class="btn btn-outline-light">Voltar</a>
        </div>
    </div>

    <!-- Modal PIX -->
    <div class="modal fade" id="pixModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: #161b22; border: 1px solid #00c3ff; color: white;">
          <div class="modal-header" style="border-bottom: 1px solid rgba(0,195,255,0.3);">
            <h5 class="modal-title">Pagamento via PIX</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <p>Escaneie o QR Code abaixo para pagar:</p>
            <div id="pix_loading" class="spinner-border text-info mb-3" role="status" style="display: none;"></div>
            <img id="pix_img" src="" alt="QR Code PIX" style="max-width: 100%; border-radius: 10px; display: none;">
            
            <div class="mt-4" id="pix_copy_area" style="display: none;">
                <p>Ou copie o código (PIX Copia e Cola):</p>
                <div class="input-group mb-3">
                  <input type="text" id="pix_code" class="form-control" style="background: #0d1117; color: #fff; border-color: rgba(0,195,255,0.3);" readonly>
                  <button class="btn btn-outline-info" type="button" onclick="copiarPix()">Copiar</button>
                </div>
            </div>
            
            <div id="pix_status" class="alert alert-warning mt-3" style="display: none;">
                <div class="spinner-grow spinner-grow-sm text-warning" role="status"></div> Aguardando pagamento...
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Scripts necessários -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    let pollInterval = null;

    function copiarPix() {
        var copyText = document.getElementById("pix_code");
        copyText.select();
        copyText.setSelectionRange(0, 99999); 
        document.execCommand("copy");
        alert("Código PIX copiado!");
    }

    $(document).ready(function() {
        var pixModal = new bootstrap.Modal(document.getElementById('pixModal'));

        $('form[action="gerar_pix.php"]').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = form.find('button[type="submit"]');
            var originalText = btn.html();
            
            btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Gerando...').prop('disabled', true);
            
            // Verifica se é form Multi ou Comum (baseado nos inputs hidden)
            var formData = form.serialize();

            $.ajax({
                url: 'gerar_pix.php',
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
                        
                        pixModal.show();
                        
                        // Inicia o Polling
                        if (pollInterval) clearInterval(pollInterval);
                        pollInterval = setInterval(function() {
                            $.post('check_status.php', {payment_id: res.payment_id}, function(statusRes) {
                                if(statusRes.status === 'approved') {
                                    clearInterval(pollInterval);
                                    $('#pix_status').removeClass('alert-warning').addClass('alert-success').html('✅ Pagamento Aprovado!');
                                    setTimeout(function() {
                                        window.location.href = 'sucesso.php';
                                    }, 2000);
                                }
                            }, 'json');
                        }, 5000); // 5 segundos
                    }
                },
                error: function() {
                    btn.html(originalText).prop('disabled', false);
                    alert("Erro ao conectar com o servidor.");
                }
            });
        });
        
        // Limpa o intervalo ao fechar o modal
        $('#pixModal').on('hidden.bs.modal', function () {
            if (pollInterval) clearInterval(pollInterval);
        });
    });
    </script>
</body>
</html>
