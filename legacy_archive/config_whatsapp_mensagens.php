<?php
include "includes/header.php";

if ($nivel_admin != 1) {
    echo "<script>window.location.href = 'index.php';</script>";
    exit;
}
?>

<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Sistema</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="javascript:;"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Mensagens Automáticas (WhatsApp)</li>
                    </ol>
                </nav>
            </div>
            <div class="ms-auto">
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#msgModal" onclick="resetForm()">
                    <i class="bx bx-plus"></i> Adicionar Mensagem
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-bordered text-center align-middle" id="msgTable">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Dias</th>
                                <th>Hora</th>
                                <th>Envio Automático</th>
                                <th>Mensagem</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="msgTbody">
                            <!-- Populated via Ajax -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="msgModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configurar Mensagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="msgForm">
                    <input type="hidden" name="id" id="msgId" value="0">
                    <input type="hidden" name="action" value="save">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Mensagem</label>
                            <select class="form-select" name="tipo" id="msgTipo" required>
                                <option value="">Selecione...</option>
                                <option value="A vencer">A vencer (Dias antes)</option>
                                <option value="Cobrança no dia">Cobrança no dia (Vencimento)</option>
                                <option value="Cobrança mensalidade vencida">Cobrança mensalidade vencida (Dias atrasado)</option>
                                <option value="Cobrança Manual">Cobrança Manual (Botão Avulso)</option>
                                <option value="Comprovante">Comprovante (Agradecimento Pix)</option>
                                <option value="Boas Vindas">Boas Vindas (Novo Cadastro)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status Automático</label>
                            <select class="form-select" name="ativo" id="msgAtivo">
                                <option value="1">Ativo</option>
                                <option value="0">Desativado</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Dias <small class="text-muted">(Ex: 5 dias antes, 2 dias depois. Vazio p/ na hora)</small></label>
                            <input type="number" class="form-control" name="dias" id="msgDias" placeholder="Ex: 5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Hora <small class="text-muted">(Ex: 16:00 p/ cron diário)</small></label>
                            <input type="time" class="form-control" name="hora" id="msgHora">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mensagem <small class="text-warning">Variáveis: {nome}, {vencimento}, {link}</small></label>
                        <textarea class="form-control" name="mensagem" id="msgTexto" rows="5" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" onclick="saveMsg()">Salvar Mensagem</button>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>

<script>
function loadMessages() {
    $('#msgTbody').html('<tr><td colspan="6"><i class="bx bx-loader bx-spin"></i> Carregando...</td></tr>');
    $.post('api/whatsapp_mensagens_backend.php', {action: 'list'}, function(res) {
        if (res.success) {
            let html = '';
            res.data.forEach(function(item) {
                let badge = item.ativo == 1 ? '<span class="badge bg-success" style="cursor:pointer;" onclick="toggleStatus('+item.id+',0)">Ativo</span>' : '<span class="badge bg-danger" style="cursor:pointer;" onclick="toggleStatus('+item.id+',1)">Desativado</span>';
                let shortMsg = item.mensagem.length > 50 ? item.mensagem.substring(0, 50) + '...' : item.mensagem;
                
                html += `<tr>
                    <td>${item.tipo}</td>
                    <td>${item.dias ? item.dias : '-'}</td>
                    <td>${item.hora ? item.hora : '-'}</td>
                    <td>${badge}</td>
                    <td class="text-start">${shortMsg}</td>
                    <td>
                        <button class="btn btn-warning btn-sm" onclick='editMsg(${JSON.stringify(item).replace(/'/g, "\\'")})'><i class="bx bx-edit"></i></button>
                        <button class="btn btn-danger btn-sm" onclick="deleteMsg(${item.id})"><i class="bx bx-trash"></i></button>
                    </td>
                </tr>`;
            });
            if(html === '') html = '<tr><td colspan="6">Nenhuma mensagem cadastrada.</td></tr>';
            $('#msgTbody').html(html);
        } else {
            alert(res.error);
        }
    }, 'json');
}

function resetForm() {
    $('#msgId').val('0');
    $('#msgForm')[0].reset();
}

function editMsg(item) {
    $('#msgId').val(item.id);
    $('#msgTipo').val(item.tipo);
    $('#msgAtivo').val(item.ativo);
    $('#msgDias').val(item.dias);
    $('#msgHora').val(item.hora);
    $('#msgTexto').val(item.mensagem);
    $('#msgModal').modal('show');
}

function saveMsg() {
    let data = $('#msgForm').serialize();
    $.post('api/whatsapp_mensagens_backend.php', data, function(res) {
        if (res.success) {
            $('#msgModal').modal('hide');
            loadMessages();
            alert('Mensagem salva com sucesso!');
        } else {
            alert(res.error);
        }
    }, 'json');
}

function deleteMsg(id) {
    if(confirm('Tem certeza que deseja excluir esta mensagem?')) {
        $.post('api/whatsapp_mensagens_backend.php', {action: 'delete', id: id}, function(res) {
            if (res.success) {
                loadMessages();
            } else {
                alert(res.error);
            }
        }, 'json');
    }
}

function toggleStatus(id, newStatus) {
    $.post('api/whatsapp_mensagens_backend.php', {action: 'toggle_status', id: id, status: newStatus}, function(res) {
        if (res.success) {
            loadMessages();
        } else {
            alert(res.error);
        }
    }, 'json');
}

$(document).ready(function() {
    loadMessages();
});
</script>
