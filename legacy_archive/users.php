<?php
session_start();
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/includes/auth_helper.php';
$id = $_SESSION['id'] ?? 0;
$db = new SQLite3("./api/db/studiolivecode_clientes.db");

// Se a solicitação for um POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se o parâmetro 'delete' foi enviado
    if (isset($_POST['delete'])) {
        $itemToDelete = (int)$_POST['delete'];

        // Checagem de Blindagem Root
        $stmtChk = $db->prepare("SELECT email, username FROM ibo WHERE id = :id");
        $stmtChk->bindValue(':id', $itemToDelete, SQLITE3_INTEGER);
        $resChk = $stmtChk->execute();
        if ($rowChk = $resChk->fetchArray(SQLITE3_ASSOC)) {
            if (isRootUser($rowChk)) {
                header('Content-Type: application/json');
                echo json_encode(array('status' => 'error', 'message' => 'Usuário Root Blindado não pode ser excluído.'));
                exit;
            }
        }

        // Evite injeção SQL usando declarações preparadas
        $stmt = $db->prepare("DELETE FROM ibo WHERE id = :id");
        $stmt->bindValue(':id', $itemToDelete, SQLITE3_INTEGER);
        
        // Deleção em cascata
        $db->exec("DELETE FROM videos WHERE id_cliente = " . (int)$itemToDelete);
        $db->exec("DELETE FROM playlists WHERE id_cliente = " . (int)$itemToDelete);
        $db->exec("DELETE FROM telas WHERE id_cliente = " . (int)$itemToDelete);
        $db->exec("DELETE FROM affiliate_transactions WHERE userId = " . (int)$itemToDelete);
        
        try {
            $dbFin = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_financeiro.db");
            $dbFin->exec("DELETE FROM faturas WHERE id_cliente = " . (int)$itemToDelete);
            $dbFin->close();
        } catch (Exception $e) {}
        
        try {
            $dbLoja = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_loja.db");
            $dbLoja->exec("DELETE FROM produtos WHERE parceiro_id = " . (int)$itemToDelete);
            $dbLoja->close();
        } catch (Exception $e) {}
        
        // Execute a exclusão
        header('Content-Type: application/json');
        if ($stmt->execute()) {
            // A exclusão foi bem-sucedida
            if (ob_get_length()) ob_clean();
            echo json_encode(array('status' => 'success'));
        } else {
            // A exclusão falhou
            if (ob_get_length()) ob_clean();
            echo json_encode(array('status' => 'error'));
        }
        
        exit;
    }
}

include './includes/header.php';
// Restaura a conexão original pois o functions.php sobrescreve a variável $db com SQLiteWrapper
$db = new SQLite3("./api/db/studiolivecode_clientes.db");
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
        <!-- Título da Página -->
    <div class="col-12">
                	<center>
						
	        			<button type="submit" id="delete-selected" name="delete-selected" class="btn btn-danger mr-2"><i class='bx bx-check mr-1'></i>Excluir Selecionados</button>
	        		</center>
<script>
$(document).ready(function() {
    $('#select-all').click(function() {
        $('input[name="selected_items[]"]').prop('checked', this.checked);
    });

    $(document).on("click", ".delete-item", function() {
        var itemId = $(this).data("id");

        if (confirm("Tem certeza de que deseja excluir este item?")) {
            $.post("users.php", { delete: itemId }, function(data) {
                if (data.status === 'success') {
                    // Exclusão bem-sucedida
                    window.location.href = "users.php?r=excluido";
                } else {
                    // Falha na exclusão
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
                // Crie uma variável para rastrear o número de exclusões bem-sucedidas
                var successfulDeletes = 0;

                $.each(selectedItems, function(index, value) {
                    $.post("users.php", { delete: value }, function(data) {
                        if (data.status === 'success') {
                            successfulDeletes++;
                        }
                        
                        if (successfulDeletes === selectedItems.length) {
                            window.location.href = "users.php?r=all_excluido";
                        }
                    }, 'json');
                });
            }
        } else {
            alert("Selecione pelo menos um item para excluir.");
        }
    });
});
</script>
    			</div>
				<br>
        
        <div class="table-responsive">
            <table id="myTable" class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th style="display: none;">id</th>
                        <th>
                    <input type="checkbox" id="select-all">
                </th>
                        <th>MAC do Dispositivo</th>
                        <th>Key</th>
                        <th>Dns</th>
                        <th>Título</th>
                        <th>Vencimento</th>
                        <th>Link Pagamento</th>
                        <th>Editar</th>
                        <th>Deletar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $db->query("SELECT * FROM ibo WHERE id_user = " . $_SESSION['id'] . " ORDER BY id DESC");

                    while ($row = $res->fetchArray()) {
                        $iid = $row["id"];
                        $imac = $row["mac_address"];
                        $ikey = $row["key"];
                        $idns = $row["dns"];
                        $iexpire_date = $row["expire_date"];
                        $ititle = $row["title"];
                        $ilink_payment = $row["link_payment"];
                        ?>
                        <tr>
                            <td style="display: none;"><?php echo $iid; ?></td>
                            <td><input type="checkbox" name="selected_items[]" value="<?php echo $iid; ?>"></td>
                            <td><?php echo $imac; ?></td>
                            <td><?php echo $ikey; ?></td>
                            <td><?php echo $idns; ?></td>
                            <td><?php echo $ititle; ?></td>
                            <td data-sort="<?php echo $iexpire_date; ?>"><?php echo date('d/m/Y', strtotime($iexpire_date)); ?></td>
                            <td><?= $ilink_payment; ?></td>
                            <td>
                                <div class="d-flex order-actions">
                                    <a href="./users_update.php?update=<?php echo $iid; ?>" class="text-light bg-primary border-0"><i class='bx bxs-edit'></i></a>
                                </div>
                            </td>
                            <td>
                           <div class="d-flex order-actions">
                          <a href="javascript:void(0);" class="text-light bg-danger border-0 delete-item" data-id="<?php echo $iid; ?>">
                        <i class="bx bxs-trash"></i>
                    </a>
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
    switch ($result) {
        case "atualizado":
            echo "<script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom',
                showConfirmButton: false,
                timer: 2000,
            })
            Toast.fire({
                icon: 'success',
                title: 'Usuário Atualizado com Sucesso!'
            })</script>";
            break;
        case "erro_att":
            echo "<script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom',
                showConfirmButton: false,
                timer: 2000,
            })
            Toast.fire({
                icon: 'error',
                title: 'Erro ao Atualizar Usuário!'
            })</script>";
            break;
        case "adicionado":
            echo "<script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom',
                showConfirmButton: false,
                timer: 2000,
            })
            Toast.fire({
                icon: 'success',
                title: 'Usuário Adicionado com Sucesso!'
            })</script>";
            break;
        case "excluido":
            echo "<script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom',
                showConfirmButton: false,
                timer: 2000,
            })
            Toast.fire({
                icon: 'success',
                title: 'Usuário Excluído com Sucesso!'
            })</script>";
            break;
        case "all_excluido":
            echo "<script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'bottom',
                showConfirmButton: false,
                timer: 2000,
            })
            Toast.fire({
                icon: 'success',
                title: 'Usuários Excluídos com Sucesso!'
            })</script>";
            break;
    }
}
if (isset($_GET["err"]) && $_GET["err"] == "duplicate") {
    echo "<script>
    const Toast = Swal.mixin({
        toast: true,
        position: 'bottom',
        showConfirmButton: false,
        timer: 3000,
    })
    Toast.fire({
        icon: 'error',
        title: 'O login (usuário) já está em uso! Escolha outro.'
    })</script>";
}
?>

<?php include './includes/footer.php'; ?>