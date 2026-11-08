<?php
session_start();
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/includes/auth_helper.php';
$id = $_SESSION['id'] ?? 0;
$db = new SQLite3("./api/db/studiolivecode_clientes.db");

// Se a solicitação for um POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        $stmt = $db->prepare("DELETE FROM ibo WHERE id = :id");
        $stmt->bindValue(':id', $itemToDelete, SQLITE3_INTEGER);
        
        // Deleção em cascata
        $db->exec("DELETE FROM videos WHERE id_cliente = " . (int)$itemToDelete);
        $db->exec("DELETE FROM playlists WHERE id_cliente = " . (int)$itemToDelete);
        $db->exec("DELETE FROM telas WHERE id_cliente = " . (int)$itemToDelete);
        $db->exec("DELETE FROM affiliate_transactions WHERE userId = " . (int)$itemToDelete);
        
        try {
            $dbFin = new SQLite3(__DIR__ . "/api/db/studiolivecode_financeiro.db");
            $dbFin->exec("DELETE FROM faturas WHERE id_cliente = " . (int)$itemToDelete);
            $dbFin->close();
        } catch (Exception $e) {}
        
        try {
            $dbLoja = new SQLite3(__DIR__ . "/api/db/studiolivecode_loja.db");
            $dbLoja->exec("DELETE FROM produtos WHERE parceiro_id = " . (int)$itemToDelete);
            $dbLoja->close();
        } catch (Exception $e) {}
        
        if ($stmt->execute()) {
            echo json_encode(array('status' => 'success'));
        } else {
            echo json_encode(array('status' => 'error'));
        }
        exit;
    }
    // Endpoint para busca dinâmica
    if (isset($_POST['search'])) {
        $search = strtoupper(trim($_POST['search']));
        $stmt = $db->prepare("SELECT * FROM ibo WHERE UPPER(`key`) LIKE :key ORDER BY expire_date DESC");
        $stmt->bindValue(':key', "%$search%", SQLITE3_TEXT);
        $res = $stmt->execute();
        $results = [];
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $results[] = $row;
        }
        echo json_encode($results);
        exit;
    }
}

include './includes/header.php';
// Restaura a conexão original pois o functions.php sobrescreve a variável $db com SQLiteWrapper
$db = new SQLite3("./api/db/studiolivecode_clientes.db");

// Conexão com o banco de dados
$db1 = new SQLite3("./api/db/studiolivecode_users.db");

// Consulta para obter informações de admin
$query = $db1->prepare("SELECT admin FROM users WHERE id = :id");
$query->bindValue(':id', $id, SQLITE3_INTEGER);
$userData = $query->execute()->fetchArray(SQLITE3_ASSOC);

// Verificando se o usuário é administrador
$tabelaVisivel = ($userData['admin'] == 1);

?>

<div class="card radius-10">
    <div class="card-header border-bottom-0 bg-transparent">
        <div class="d-flex align-items-center">
            <div>
                <h5 class="font-weight-bold mb-0">Pesquise pela Chave do Cliente:</h5>
            </div>
        </div>
    </div>
    <div class="card-body">
<?php if ($userData['admin'] == 1): ?>
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
            $.post("users_teste.php", { delete: itemId }, function(data) {
                if (data.status === 'success') {
                    window.location.href = "users_teste.php?r=excluido";
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
                    $.post("users_teste.php", { delete: value }, function(data) {
                        if (data.status === 'success') {
                            successfulDeletes++;
                        }
                        if (successfulDeletes === selectedItems.length) {
                            window.location.href = "users_teste.php?r=all_excluido";
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
<?php endif; ?>

<div class="input-group">
    <input class="form-control" type="text" id="searchInput" placeholder="Digite a Chave para buscar"/>
</div>
<br>
<div class="table-responsive" id="tableContainer" <?php echo $tabelaVisivel ? "" : "style='display: none;'"; ?>>
    <table id="myTable" class="table mb-0 align-middle">
        <thead>
            <tr>
                <th style="display: none;">id</th>
                <th><input type="checkbox" id="select-all"></th>
                <th>MAC do Dispositivo</th>
                <th>Título</th>
                <th>Vencimento</th>
                <th>Editar</th>
                <th>Deletar</th>
            </tr>
        </thead>
        <tbody id="tableBody">
            <?php
            $res = $db->query("SELECT * FROM ibo ORDER BY expire_date DESC");
            while ($row = $res->fetchArray()) {
                $iid = $row["id"];
                $imac = $row["mac_address"];
                $iexpire_date = $row["expire_date"];
                $ititle = $row["title"];
                ?>
                <tr>
                    <td style="display: none;"><?php echo $iid; ?></td>
                    <td><input type="checkbox" name="selected_items[]" value="<?php echo $iid; ?>"></td>
                    <td><?php echo $imac; ?></td>
                    <td><?php echo $ititle; ?></td>
                    <td data-sort="<?php echo $iexpire_date; ?>"><?php echo date('d/m/Y', strtotime($iexpire_date)); ?></td>
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

<script>
$(document).ready(function() {
    $('#searchInput').on('input', function() {
        var search = $(this).val().toUpperCase();
        
        if (search.length >= 3) { // Mínimo de 3 caracteres para buscar
            $.ajax({
                url: 'users_teste.php',
                type: 'POST',
                data: { search: search },
                dataType: 'json',
                success: function(data) {
                    $('#tableBody').empty();
                    if (data.length > 0) {
                        $.each(data, function(index, row) {
                            var rowHtml = '<tr>' +
                                '<td style="display: none;">' + row.id + '</td>' +
                                '<td><input type="checkbox" name="selected_items[]" value="' + row.id + '"></td>' +
                                '<td>' + row.mac_address + '</td>' +
                                '<td>' + row.title + '</td>' +
                                '<td data-sort="' + row.expire_date + '">' + new Date(row.expire_date).toLocaleDateString('pt-BR') + '</td>' +
                                '<td><div class="d-flex order-actions"><a href="./users_update.php?update=' + row.id + '" class="text-light bg-primary border-0"><i class="bx bxs-edit"></i></a></div></td>' +
                                '<td><div class="d-flex order-actions"><a href="javascript:void(0);" class="text-light bg-danger border-0 delete-item" data-id="' + row.id + '"><i class="bx bxs-trash"></i></a></div></td>' +
                                '</tr>';
                            $('#tableBody').append(rowHtml);
                        });
                        $('#tableContainer').show();
                    } else {
                        $('#tableBody').append('<tr><td colspan="7" class="text-center">Nenhum resultado encontrado.</td></tr>');
                    }
                },
                error: function() {
                    $('#tableBody').empty().append('<tr><td colspan="7" class="text-center">Erro na busca.</td></tr>');
                }
            });
        } else {
            // Recarrega todos os registros se o campo estiver vazio ou com menos de 3 caracteres
            $.ajax({
                url: 'users_teste.php',
                type: 'POST',
                data: { search: '' },
                dataType: 'json',
                success: function(data) {
                    $('#tableBody').empty();
                    $.each(data, function(index, row) {
                        var rowHtml = '<tr>' +
                            '<td style="display: none;">' + row.id + '</td>' +
                            '<td><input type="checkbox" name="selected_items[]" value="' + row.id + '"></td>' +
                            '<td>' + row.mac_address + '</td>' +
                            '<td>' + row.title + '</td>' +
                            '<td data-sort="' + row.expire_date + '">' + new Date(row.expire_date).toLocaleDateString('pt-BR') + '</td>' +
                            '<td><div class="d-flex order-actions"><a href="./users_update.php?update=' + row.id + '" class="text-light bg-primary border-0"><i class="bx bxs-edit"></i></a></div></td>' +
                            '<td><div class="d-flex order-actions"><a href="javascript:void(0);" class="text-light bg-danger border-0 delete-item" data-id="' + row.id + '"><i class="bx bxs-trash"></i></a></div></td>' +
                            '</tr>';
                        $('#tableBody').append(rowHtml);
                    });
                    $('#tableContainer').css('display', '<?php echo $tabelaVisivel ? "" : "none"; ?>');
                }
            });
        }
    });
});
</script>

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
?>

<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="myModalLabel">ATENÇÃO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Essa página contém todos os usuários disponíveis no banco.<br><br>
                Localize o cliente pela Chave e edite as informações.<br><br>
                Não faça alterações em clientes que não são seus!<br><br>
                Qualquer dúvida, fale com o administrador do painel.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
function checkCookie() {
    var modalShown = getCookie("modalShown");
    if (!modalShown) {
        $('#myModal').modal('show');
        setCookie("modalShown", true, 1);
    }
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

$(document).ready(function() {
    checkCookie();
});
</script>

<?php include './includes/footer.php'; ?>