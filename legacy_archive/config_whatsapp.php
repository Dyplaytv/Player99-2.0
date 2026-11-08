<?php
include "includes/header.php";

if ($nivel_admin != 1) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}

$configFile = __DIR__ . '/evolution_config.json';
$config = ['evolution_url' => '', 'api_key' => '', 'instancia' => 'player99_' . substr(md5(uniqid()), 0, 6), 'hub_token' => 'HUB_6A6039D8DE252ccbcac01'];
if (file_exists($configFile)) {
    $saved = json_decode(file_get_contents($configFile), true);
    $config['evolution_url'] = $saved['evolution_url'] ?? '';
    $config['api_key'] = $saved['api_key'] ?? '';
    if (!empty($saved['instancia']) && $saved['instancia'] !== 'instancia') {
        $config['instancia'] = $saved['instancia'];
    }
    $config['hub_token'] = !empty($saved['hub_token']) ? $saved['hub_token'] : 'HUB_6A6039D8DE252ccbcac01';
}
?>
<style>
.neon-red {
    box-shadow: 0 0 20px rgba(255, 0, 0, 0.8);
    border: 2px solid #ff0000;
}
.neon-green {
    box-shadow: 0 0 20px rgba(0, 255, 0, 0.8);
    border: 2px solid #00ff00;
}
</style>
<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Sistema</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Configurar WhatsApp (Evolution API)</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6" id="colCredenciais">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-warning"><i class='bx bxl-whatsapp'></i> 1. Credenciais da API</h5>
                        <hr>
                        <form id="formConfigWa">
                            <div class="mb-3">
                                <label class="form-label">URL da Evolution API</label>
                                <input type="text" class="form-control" name="evolution_url" value="<?php echo htmlspecialchars($config['evolution_url']); ?>" placeholder="Ex: http://75.119.156.103:8080">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Global API Key</label>
                                <input type="text" class="form-control" name="api_key" value="<?php echo htmlspecialchars($config['api_key']); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nome da Instância (Automático)</label>
                                <input type="text" class="form-control" name="instancia" value="<?php echo htmlspecialchars($config['instancia']); ?>" readonly>
                                <small class="text-muted">Gerado automaticamente pelo sistema.</small>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class='bx bx-save'></i> Salvar Configurações</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-6" id="colWhatsApp">
                <div class="card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-success"><i class='bx bx-qr-scan'></i> 2. Conectar WhatsApp</h5>
                        <hr>
                        <p>Clique no botão abaixo para gerar o QR Code. Abra o WhatsApp no seu celular, vá em Aparelhos Conectados e escaneie o código.</p>
                        
                        <button id="btnGerarQr" class="btn btn-success mb-3"><i class='bx bx-refresh'></i> Gerar QR Code</button>
                        
                        <div id="qrContainer" style="display: none; padding: 20px; background: white; border-radius: 10px; max-width: 300px; margin: 0 auto;" class="neon-red">
                            <img id="qrImage" src="" alt="QR Code" style="width: 100%;">
                            <div id="successIcon" style="display: none;">
                                <i class='bx bxl-whatsapp' style='font-size: 150px; color: #25D366;'></i>
                                <h4 style="color: #000; margin-top:10px;">Conectado!</h4>
                            </div>
                        </div>
                        
                        <div id="waStatus" class="alert mt-3" style="display:none;"></div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title text-info"><i class='bx bx-cloud'></i> 3. Conexão via Hub Híbrido (Fallback)</h5>
                        <hr>
                        <form id="formHub">
                            <div class="mb-3">
                                <label class="form-label">Token da API (Hub)</label>
                                <input type="text" class="form-control" name="hub_token" id="hub_token_input" value="<?php echo htmlspecialchars($config['hub_token']); ?>" placeholder="Insira o Token do Hub Híbrido">
                            </div>
                            <button type="submit" class="btn btn-info text-white" id="btnTestHub"><i class='bx bx-cloud-upload'></i> Salvar e Testar Conexão</button>
                        </form>
                        <div class="mt-4 text-center">
                            <h6>Status do Hub:</h6>
                            <div id="hubStatusBadge" class="badge rounded-pill bg-secondary" style="font-size: 16px; padding: 10px 20px;">Desconhecido</div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script>
$(document).ready(function() {
    $('#formConfigWa').on('submit', function(e) {
        e.preventDefault();
        var hubTokenVal = $('#hub_token_input').val();
        var data = $(this).serialize() + '&hub_token=' + encodeURIComponent(hubTokenVal) + '&action=save_config';
        
        $.post('api/whatsapp_backend.php', data, function(res) {
            if(res.success) {
                alert('Configurações salvas com sucesso!');
                checkConnection(true);
            } else {
                alert('Erro ao salvar: ' + res.error);
            }
        }, 'json');
    });

    $('#formHub').on('submit', function(e) {
        e.preventDefault();
        var btn = $('#btnTestHub');
        var originalText = btn.html();
        var token = $('#hub_token_input').val();
        
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Testando...');
        
        // Primeiro salva
        var configData = $('#formConfigWa').serialize() + '&hub_token=' + encodeURIComponent(token) + '&action=save_config';
        $.post('api/whatsapp_backend.php', configData, function(saveRes) {
            if (saveRes.success) {
                // Testa
                $.post('api/whatsapp_backend.php', { action: 'test_hub', hub_token: token }, function(res) {
                    btn.prop('disabled', false).html(originalText);
                    if (res.connected) {
                        $('#hubStatusBadge').removeClass('bg-secondary bg-danger').addClass('bg-success').text('Conectado');
                    } else {
                        $('#hubStatusBadge').removeClass('bg-secondary bg-success').addClass('bg-danger').text('Desconectado');
                        alert('Falha no teste: ' + (res.error || 'Erro desconhecido.'));
                    }
                }, 'json').fail(function() {
                    btn.prop('disabled', false).html(originalText);
                    $('#hubStatusBadge').removeClass('bg-secondary bg-success').addClass('bg-danger').text('Desconectado');
                    alert('Erro de comunicação ao testar Hub.');
                });
            } else {
                btn.prop('disabled', false).html(originalText);
                alert('Erro ao salvar token do Hub antes do teste.');
            }
        }, 'json');
    });

    let pollQr = null;

    function setConnectedState() {
        if (pollQr) clearInterval(pollQr);
        
        $('#colCredenciais').hide(); // Esconde as informações
        $('#colWhatsApp').removeClass('col-md-6').addClass('col-md-12'); // Centraliza
        
        $('#btnGerarQr').hide();
        $('#qrImage').hide();
        $('#successIcon').show();
        $('#qrContainer').removeClass('neon-red').addClass('neon-green').show();
        
        // Substitui a mensagem de conectado pelo botão de desconectar
        $('#waStatus').removeClass('alert-warning alert-danger alert-success').addClass('alert-success').html('<strong><i class="bx bx-check-circle"></i> O WhatsApp está conectado!</strong><br><br><button id="btnDesconectar" class="btn btn-danger btn-sm"><i class="bx bx-log-out"></i> Desconectar WhatsApp</button>').show();
    }
    
    function setDisconnectedState() {
        if (pollQr) clearInterval(pollQr);
        
        $('#colCredenciais').show(); 
        $('#colWhatsApp').removeClass('col-md-12').addClass('col-md-6'); 
        
        $('#btnGerarQr').show();
        $('#qrContainer').hide();
        $('#waStatus').hide();
    }

    function checkConnection(isInit = false) {
        if(isInit) $('#btnGerarQr').html('<i class="bx bx-loader bx-spin"></i> Verificando...');
        
        $.post('api/whatsapp_backend.php', {action: 'check_status'}, function(res) {
            if(isInit) $('#btnGerarQr').html('<i class="bx bx-refresh"></i> Gerar QR Code');
            
            if(res.connected) {
                setConnectedState();
            } else if(isInit) {
                setDisconnectedState();
            }
        }, 'json');
    }
    
    // Auto check no load
    checkConnection(true);

    function checkHubConnection() {
        var token = $('#hub_token_input').val();
        if (token) {
            $('#hubStatusBadge').removeClass('bg-secondary bg-danger bg-success').addClass('bg-warning text-dark').text('Verificando...');
            $.post('api/whatsapp_backend.php', { action: 'test_hub', hub_token: token }, function(res) {
                if (res.connected) {
                    $('#hubStatusBadge').removeClass('bg-warning bg-secondary bg-danger text-dark').addClass('bg-success text-white').text('Conectado');
                } else {
                    $('#hubStatusBadge').removeClass('bg-warning bg-secondary bg-success text-dark').addClass('bg-danger text-white').text('Desconectado');
                }
            }, 'json').fail(function() {
                $('#hubStatusBadge').removeClass('bg-warning bg-secondary bg-success text-dark').addClass('bg-danger text-white').text('Desconectado');
            });
        }
    }
    checkHubConnection();

    $(document).on('click', '#btnDesconectar', function() {
        if(confirm("Tem certeza que deseja desconectar o WhatsApp?")) {
            var btn = $(this);
            btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Desconectando...');
            $.post('api/whatsapp_backend.php', {action: 'disconnect'}, function(res) {
                alert('WhatsApp desconectado com sucesso!');
                setDisconnectedState();
            }, 'json');
        }
    });

    $('#btnGerarQr').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i> Conectando...');
        $('#qrContainer').hide();
        $('#waStatus').hide();
        if (pollQr) clearInterval(pollQr);

        $.post('api/whatsapp_backend.php', {action: 'get_qrcode'}, function(res) {
            btn.prop('disabled', false).html('<i class="bx bx-refresh"></i> Gerar QR Code');
            
            if(res.error) {
                $('#waStatus').removeClass('alert-success').addClass('alert-danger').text(res.error).show();
            } else if(res.connected) {
                setConnectedState();
            } else if(res.qr) {
                $('#qrImage').attr('src', res.qr).show();
                $('#successIcon').hide();
                $('#qrContainer').removeClass('neon-green').addClass('neon-red').show();
                $('#waStatus').removeClass('alert-danger alert-success').addClass('alert-warning').text('Aguardando leitura do QR Code... Escaneie rapidamente.').show();
                pollQr = setInterval(function(){ checkConnection(false); }, 3000);
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html('<i class="bx bx-refresh"></i> Gerar QR Code');
            alert('Falha na comunicação com o servidor local.');
        });
    });
});
</script>

<?php include "includes/footer.php"; ?>
