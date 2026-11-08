<?php
session_start();
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/includes/auth_helper.php';
$id = $_SESSION['id'] ?? 0;
$db1 = new SQLite3("./api/db/studiolivecode_users.db");

// Consulta para obter informações de admin e nível do usuário
$query = $db1->prepare("SELECT admin FROM users WHERE id = :id");
$query->bindValue(':id', $id, SQLITE3_INTEGER);
$userData = $query->execute()->fetchArray(SQLITE3_ASSOC);

// Verifica se o usuário é administrador (admin = 1) ou tem o nível necessário
if (!$userData || ($userData['admin'] != 1 && !isRootUser($id, $db1))) {
    header("Location: users.php");
    exit;
}

$db = new SQLite3("./api/db/studiolivecode_clientes.db");

// Se a solicitação for um POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se o parâmetro 'delete' foi enviado
if (isset($_POST['delete'])) {
    $itemToDelete = intval($_POST['delete']);

    // Pega dados do cliente antes de deletar e checa blindagem
    $macToDelete = '';
    $macQuery = $db->prepare("SELECT mac_address, email, username FROM ibo WHERE id = :id");
    $macQuery->bindValue(':id', $itemToDelete, SQLITE3_INTEGER);
    $resMac = $macQuery->execute();
    if ($macRow = $resMac->fetchArray(SQLITE3_ASSOC)) {
        if (isRootUser($macRow)) {
            echo json_encode(array('status' => 'error', 'message' => 'Usuário Root Blindado não pode ser excluído.'));
            exit;
        }
        $macToDelete = strtoupper(trim($macRow['mac_address']));
    }

    // Deleta do SQLite (tabela ibo)
    $stmt = $db->prepare("DELETE FROM ibo WHERE id = :id");
    $stmt->bindValue(':id', $itemToDelete, SQLITE3_INTEGER);
    $stmt->execute();

    // Deleção em cascata: Vídeos, Playlists, Telas
    try {
        $dbMedia = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_cliente_media.db");
        $dbMedia->exec("DELETE FROM videos WHERE id_cliente = " . (int)$itemToDelete);
        $dbMedia->exec("DELETE FROM playlists WHERE id_cliente = " . (int)$itemToDelete);
        $dbMedia->exec("DELETE FROM telas WHERE id_cliente = " . (int)$itemToDelete);
        $dbMedia->close();
    } catch (Exception $e) {}

    // Deleção em cascata: Financeiro (Faturas e Transações Afiliado)
    try {
        $dbFin = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_financeiro.db");
        $dbFin->exec("DELETE FROM faturas WHERE id_cliente = " . (int)$itemToDelete);
        $dbFin->exec("DELETE FROM affiliate_transactions WHERE userId = " . (int)$itemToDelete);
        $dbFin->close();
    } catch (Exception $e) {}

    // Deleção em cascata: Loja do Parceiro
    try {
        $dbLoja = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_loja.db");
        $dbLoja->exec("DELETE FROM produtos WHERE parceiro_id = " . (int)$itemToDelete);
        $dbLoja->close();
    } catch (Exception $e) {}

    // Remove do JSON
    $whatsFile = __DIR__ . '/whats.json';
    if (!file_exists($whatsFile)) file_put_contents($whatsFile, json_encode([]));
    $whatsData = json_decode(file_get_contents($whatsFile), true);

    if ($macToDelete && isset($whatsData[$macToDelete])) {
        unset($whatsData[$macToDelete]);
        file_put_contents($whatsFile, json_encode($whatsData, JSON_PRETTY_PRINT));
    }

    echo json_encode(array('status' => 'success'));
    exit;
}

if (isset($_POST['update_date'])) {
    $itemId = $_POST['id'];
    $newDate = $_POST['new_date'];
    
    $stmt = $db->prepare("UPDATE ibo SET expire_date = :expire_date WHERE id = :id");
    $stmt->bindValue(':expire_date', $newDate, SQLITE3_TEXT);
    $stmt->bindValue(':id', $itemId, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode(array('status' => 'success'));
    exit;
}

if (isset($_POST['toggle_block'])) {
    $itemId = $_POST['id'];
    $newState = $_POST['new_state']; // 0 para bloquear, 1 para desbloquear
    
    $stmt = $db->prepare("UPDATE ibo SET active = :active WHERE id = :id");
    $stmt->bindValue(':active', $newState, SQLITE3_INTEGER);
    $stmt->bindValue(':id', $itemId, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode(array('status' => 'success'));
    exit;
}

if (isset($_POST['update_multiplo'])) {
    $itemId = $_POST['id'];
    $newMultiplo = (float)$_POST['new_multiplo'];
    
    // Safety check to add column if not exists
    try {
        $db->exec("ALTER TABLE ibo ADD COLUMN multiplo REAL DEFAULT 1.0;");
    } catch(Exception $e) {}
    
    $stmt = $db->prepare("UPDATE ibo SET multiplo = :multiplo WHERE id = :id");
    $stmt->bindValue(':multiplo', $newMultiplo, SQLITE3_FLOAT);
    $stmt->bindValue(':id', $itemId, SQLITE3_INTEGER);
    $stmt->execute();
    
    echo json_encode(array('status' => 'success'));
    exit;
}

}

// Caminho do arquivo WhatsApp na raiz
$whatsFile = __DIR__ . '/whats.json';
if (!file_exists($whatsFile)) {
    file_put_contents($whatsFile, json_encode([]));
}
$whatsData = json_decode(file_get_contents($whatsFile), true);

include './includes/header.php';
// Restaura a conexão original pois o functions.php sobrescreve a variável $db com SQLiteWrapper
$db = new SQLite3("./api/db/studiolivecode_clientes.db");
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>

<div class="card radius-10">
    <div class="card-header border-bottom-0 bg-transparent">
        <div class="d-flex align-items-center">
            <div>
                <h5 class="font-weight-bold mb-0">Usuários Cadastrados</h5>
            </div>
            <div class="ms-auto">
                <a href="./users_create.php">
                    <button type="button" class="btn btn-primary mr-2 radius-10">Adicionar Usuário</button>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="col-12">
            <center>
                <button type="submit" id="delete-selected" name="delete-selected" class="btn btn-danger mr-2">
                    <i class='bx bx-check mr-1'></i>Excluir Selecionados
                </button>
            </center>
            <script>
            $(document).ready(function() {
                $('#select-all').click(function() {
                    $('input[name="selected_items[]"]').prop('checked', this.checked);
                });

                $(document).on("click", ".delete-item", function() {
                    var itemId = $(this).data("id");

                    if (confirm("Tem certeza de que deseja excluir este item?")) {
                        $.post("all_users.php", { delete: itemId }, function(data) {
                            if (data.status === 'success') {
                                window.location.href = "all_users.php?r=excluido";
                            } else {
                                alert("Falha na exclusão do item com ID " + itemId);
                            }
                        }, 'json');
                    }
                });

                $(document).on("click", "#delete-selected", function() {
                    var selectedItems = [];
                    $.each($("input[name='selected_items[]']:checked"), function(){
                        selectedItems.push($(this).val());
                    });

                    if (selectedItems.length > 0) {
                        if (confirm("Tem certeza de que deseja excluir os itens selecionados?")) {
                            var successfulDeletes = 0;
                            $.each(selectedItems, function(index, value) {
                                $.post("all_users.php", { delete: value }, function(data) {
                                    if (data.status === 'success') successfulDeletes++;
                                    if (successfulDeletes === selectedItems.length) {
                                        window.location.href = "all_users.php?r=all_excluido";
                                    }
                                }, 'json');
                            });
                        }
                    } else {
                        alert("Selecione pelo menos um item para excluir.");
                    }
                });
            });

            function openDatePrompt(id, currentDate) {
                Swal.fire({
                    title: 'Alterar Vencimento',
                    html: '<input type="date" id="swal-input-date" class="swal2-input" value="'+currentDate+'">',
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Salvar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        return document.getElementById('swal-input-date').value
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("all_users.php", { update_date: true, id: id, new_date: result.value }, function(data) {
                            if (data.status === 'success') {
                                window.location.href = "all_users.php?r=data_atualizada";
                            }
                        }, 'json');
                    }
                })
            }

            function toggleBlock(id, currentState) {
                var newState = currentState === 1 ? 0 : 1;
                var actionName = currentState === 1 ? "Bloquear" : "Desbloquear";
                
                if (confirm("Tem certeza de que deseja " + actionName + " este cliente?")) {
                    $.post("all_users.php", { toggle_block: true, id: id, new_state: newState }, function(data) {
                        if (data.status === 'success') {
                            window.location.href = "all_users.php?r=" + (newState === 0 ? "bloqueado" : "desbloqueado");
                        }
                    }, 'json');
                }
            }

            function openMultiploPrompt(id, currentMultiplo) {
                Swal.fire({
                    title: 'Alterar Múltiplo (x)',
                    html: '<input type="number" step="0.1" id="swal-input-multiplo" class="swal2-input" value="'+currentMultiplo+'">',
                    focusConfirm: false,
                    showCancelButton: true,
                    confirmButtonText: 'Salvar',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        return document.getElementById('swal-input-multiplo').value
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post("all_users.php", { update_multiplo: true, id: id, new_multiplo: result.value }, function(data) {
                            if (data.status === 'success') {
                                window.location.reload();
                            }
                        }, 'json');
                    }
                })
            }

            function copiarLinkSmartOne(link) {
                var tempInput = document.createElement("input");
                tempInput.value = link;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand("copy");
                document.body.removeChild(tempInput);
                const Toast = Swal.mixin({toast: true, position: 'bottom', showConfirmButton: false, timer: 2000});
                Toast.fire({icon: 'success', title: 'Link SmartOne copiado!'});
            }
            </script>
        </div>
        <br>
        
        <div class="table-responsive">
            <table id="myTable" class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="display: none;">id</th>
                        <th><input type="checkbox" id="select-all"></th>

                        <th>Cliente</th>
                        <th>Status</th>
                        <th>Vencimento</th>
                        <th>Alt. Data</th>
                        <th>Bloquear</th>
                        <th>Editar</th>
                        <th>Deletar</th>
                        <th>Múltiplo</th>
                        <th>Cobrar</th>
                        <th>Faturas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $db->query("SELECT * FROM ibo ORDER BY id DESC");

                    while ($row = $res->fetchArray()) {
                        $iid = $row["id"];
                        $imac = strtoupper(trim($row["mac_address"]));
                      
                        $idns = $row["dns"];
                        $iexpire_date = $row["expire_date"];
                        $ititle = $row["title"];
                        $ilink_payment = $row["link_payment"];
                        $iactive = isset($row["active"]) ? $row["active"] : 1;
                        $imultiplo = isset($row["multiplo"]) ? (float)$row["multiplo"] : 1.0;
                        $iwhats = !empty($row["whatsapp"]) ? $row["whatsapp"] : (isset($whatsData[$imac]['whatsapp']) ? $whatsData[$imac]['whatsapp'] : '');

                        $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']);
                        $smartone_link = $base_url . "/api/m3u_proxy.php?mac=" . urlencode($imac);
                        ?>
                        <?php
                            $hoje = date('Y-m-d');
                            if ($iactive == 0) {
                                $status_html = '<span class="badge bg-dark">Bloqueado</span>';
                            } elseif ($iexpire_date >= $hoje) {
                                $status_html = '<span class="badge bg-success">Ativo</span>';
                            } else {
                                $status_html = '<span class="badge bg-danger">Vencido</span>';
                            }
                        ?>
                        <tr>
                            <td style="display: none;"><?php echo $iid; ?></td>
                            <td><input type="checkbox" name="selected_items[]" value="<?php echo $iid; ?>"></td>
                            
                            <td><?php echo $ititle; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php echo $status_html; ?>
                                    <a href="admin_spy.php?cliente_id=<?php echo $iid; ?>" target="_blank" class="text-white bg-warning border-0 ms-2" style="padding: 2px 6px; border-radius: 4px; font-size: 14px; margin-left: 8px;" title="Modo Espião (Painel do Cliente)">
                                        <i class="bx bx-show"></i>
                                    </a>
                                </div>
                            </td>
                            <td data-sort="<?php echo $iexpire_date; ?>"><?php echo date('d/m/Y', strtotime($iexpire_date)); ?></td>
                            
                            <td>
                                <button type="button" class="btn btn-sm btn-info text-white" onclick="openDatePrompt(<?php echo $iid; ?>, '<?php echo $iexpire_date; ?>')" title="Alterar Vencimento Rápido">
                                    <i class='bx bx-calendar'></i>
                                </button>
                            </td>

                            <td>
                                <div class="d-flex order-actions">
                                    <?php if ($iactive == 1): ?>
                                        <a href="javascript:void(0);" onclick="toggleBlock(<?php echo $iid; ?>, 1)" class="text-white bg-warning border-0" title="Bloquear Cliente"><i class='bx bx-block'></i></a>
                                    <?php else: ?>
                                        <a href="javascript:void(0);" onclick="toggleBlock(<?php echo $iid; ?>, 0)" class="text-white bg-danger border-0" title="Desbloquear Cliente"><i class='bx bx-check-shield'></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex order-actions">
                                    <a href="./users_update.php?update=<?php echo $iid; ?>" class="text-light bg-primary border-0"><i class='bx bxs-edit'></i></a>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="javascript:void(0);" class="text-light bg-danger border-0 delete-item" data-id="<?php echo $iid; ?>" title="Deletar">
                                        <i class="bx bxs-trash"></i>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-info text-white" onclick="openMultiploPrompt(<?php echo $iid; ?>, '<?php echo $imultiplo; ?>')" title="Alterar Múltiplo">
                                    x<?php echo number_format($imultiplo, 1, ',', '.'); ?>
                                </button>
                            </td>
                            <td>
                                <?php if(!empty($iwhats)): ?>
                                <button type="button" class="btn btn-success btn-sm btn-cobrar-avulso" data-id="<?php echo $iid; ?>">
                                    <i class="bx bxl-whatsapp"></i> Cobrar
                                </button>
                                <?php else: ?>
                                <span class="text-muted">Sem WhatsApp</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="admin_faturas_cliente.php?id=<?php echo $iid; ?>" class="text-white bg-secondary border-0" title="Modo Espião - Faturas"><i class='bx bx-receipt'></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
if (isset($_GET["r"])) {
    $result = $_GET["r"];
    $msg = "";
    switch ($result) {
        case "atualizado": $msg = "Usuário Atualizado com Sucesso!"; $icon = "success"; break;
        case "erro_att": $msg = "Erro ao Atualizar Usuário!"; $icon = "error"; break;
        case "adicionado": $msg = "Usuário Adicionado com Sucesso!"; $icon = "success"; break;
        case "excluido": $msg = "Usuário Excluído com Sucesso!"; $icon = "success"; break;
        case "all_excluido": $msg = "Usuários Excluídos com Sucesso!"; $icon = "success"; break;
        case "data_atualizada": $msg = "Vencimento Alterado com Sucesso!"; $icon = "success"; break;
        case "bloqueado": $msg = "Usuário Bloqueado com Sucesso!"; $icon = "success"; break;
        case "desbloqueado": $msg = "Usuário Desbloqueado com Sucesso!"; $icon = "success"; break;
    }
    if ($msg) {
        echo "<script>
        const Toast = Swal.mixin({toast: true, position: 'bottom', showConfirmButton: false, timer: 2000});
        Toast.fire({icon: '$icon', title: '$msg'});
        </script>";
    }
}
if (isset($_GET["err"]) && $_GET["err"] == "duplicate") {
    echo "<script>
    const Toast = Swal.mixin({toast: true, position: 'bottom', showConfirmButton: false, timer: 3000});
    Toast.fire({icon: 'error', title: 'O login (usuário) já está em uso! Escolha outro.'});
    </script>";
}
?>

<script>
$(document).ready(function() {
    $('.btn-cobrar-avulso').on('click', function() {
        var btn = $(this);
        var id = btn.data('id');
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        
        $.post('api/whatsapp_avulso.php', {id: id}, function(res) {
            btn.prop('disabled', false).html(originalText);
            const Toast = Swal.mixin({toast: true, position: 'bottom', showConfirmButton: false, timer: 3000});
            if(res.success) {
                Toast.fire({icon: 'success', title: 'Cobrança enviada com sucesso!'});
            } else {
                Toast.fire({icon: 'error', title: 'Erro: ' + res.error});
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html(originalText);
            alert('Erro de comunicação com o servidor.');
        });
    });
});
</script>

<?php include './includes/footer.php'; ?>
