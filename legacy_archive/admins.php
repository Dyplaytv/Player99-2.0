<?php
session_start();
require_once __DIR__ . '/includes/auth_helper.php';

if (!isset($_SESSION["U"])) {
    header("location: login.php");
    exit;
}

$id = intval($_SESSION['id']);
$db = new SQLite3("./api/db/studiolivecode_users.db");
$dbv = new SQLite3("./api/db/studiolivecode_vencimento.db");

// Verificar se é admin
$query = $db->prepare("SELECT admin FROM users WHERE id = :id");
$query->bindValue(':id', $id, SQLITE3_INTEGER);
$userData = $query->execute()->fetchArray(SQLITE3_ASSOC);

if (!$userData || $userData['admin'] != 1) {
    header("location: login.php");
    exit;
}

// Processar operações CRUD
if (isset($_GET["create"])) {
    // Criação de novo admin
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $vencimento = $_POST['vencimento'];

        if (empty($name) || empty($username) || empty($password) || empty($vencimento)) {
            $mensagem = "Todos os campos são obrigatórios.";
        } else {
            $db->exec("BEGIN TRANSACTION");
            
            try {
                $stmt = $db->prepare("INSERT INTO users (name, username, password, nivel) 
                                    VALUES (:name, :username, :password, 0)");
                $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':password', $password, SQLITE3_TEXT);

                if ($stmt->execute()) {
                    $newUserId = $db->lastInsertRowID();
                    
                    $stmtVencimento = $dbv->prepare("INSERT INTO vencimento (id, vencimento) 
                                                    VALUES (:id, :vencimento)");
                    $stmtVencimento->bindValue(':id', $newUserId, SQLITE3_INTEGER);
                    $stmtVencimento->bindValue(':vencimento', $vencimento, SQLITE3_TEXT);

                    if ($stmtVencimento->execute()) {
                        $db->exec("COMMIT");
                        header("Location: admins.php?r=adicionado");
                        exit;
                    } else {
                        $db->exec("ROLLBACK");
                        $mensagem = "Erro ao definir vencimento.";
                    }
                } else {
                    $db->exec("ROLLBACK");
                    $mensagem = "Erro ao criar administrador.";
                }
            } catch (Exception $e) {
                $db->exec("ROLLBACK");
                $mensagem = "Erro: " . $e->getMessage();
            }
        }
    }
    
    include './includes/header.php';
?>
<div class="card radius-10">
    <div class="card-body">
        <?php if (!empty($mensagem)): ?>
            <div class='alert alert-danger'><b><?= htmlspecialchars($mensagem) ?></b></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group mb-4">
                <label class="control-label" for="name"><strong>Nome</strong></label>
                <input type="text" class="form-control" name="name" required>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="username"><strong>Usuário</strong></label>
                <input type="text" class="form-control" name="username" required>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="password"><strong>Senha</strong></label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="passAdminCreate" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="const p = document.getElementById('passAdminCreate'); const i = this.querySelector('i'); if(p.type==='password'){p.type='text'; i.className='bx bx-hide';}else{p.type='password'; i.className='bx bx-show';}"><i class='bx bx-show'></i></button>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="vencimento"><strong>Vencimento</strong></label>
                <input type="date" class="form-control" name="vencimento" required>
            </div>
            <button type="submit" class="btn btn-primary">Criar Admin</button>
        </form>
    </div>
</div>
<?php
    include './includes/footer.php';

} elseif (isset($_GET['update'])) {
    // Atualização de admin
    $user_id = intval($_GET['update']);
    
    $query = $db->query("SELECT * FROM users WHERE id = $user_id AND nivel = 0");
    $userData = $query->fetchArray(SQLITE3_ASSOC);
    
    $stmtVencimento = $dbv->prepare("SELECT vencimento FROM vencimento WHERE id = :id");
    $stmtVencimento->bindValue(':id', $user_id, SQLITE3_INTEGER);
    $vencimentoData = $stmtVencimento->execute()->fetchArray(SQLITE3_ASSOC);

    if (!$userData) {
        header("Location: admins.php?r=nao_encontrado");
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $vencimento = $_POST['vencimento'];

        if (empty($name) || empty($username) || empty($password) || empty($vencimento)) {
            $mensagem = "Todos os campos são obrigatórios.";
        } else {
            $db->exec("BEGIN TRANSACTION");
            
            try {
                $stmt = $db->prepare("UPDATE users SET 
                    name = :name,
                    username = :username,
                    password = :password
                    WHERE id = :id");
                
                $stmt->bindValue(':name', $name, SQLITE3_TEXT);
                $stmt->bindValue(':username', $username, SQLITE3_TEXT);
                $stmt->bindValue(':password', $password, SQLITE3_TEXT);
                $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);

                if ($stmt->execute()) {
                    // Atualizar vencimento
                    $stmtVencimento = $dbv->prepare("UPDATE vencimento SET 
                        vencimento = :vencimento 
                        WHERE id = :id");
                    
                    $stmtVencimento->bindValue(':vencimento', $vencimento, SQLITE3_TEXT);
                    $stmtVencimento->bindValue(':id', $user_id, SQLITE3_INTEGER);
                    
                    if ($stmtVencimento->execute()) {
                        $db->exec("COMMIT");
                        header("Location: admins.php?r=atualizado");
                        exit;
                    } else {
                        $db->exec("ROLLBACK");
                        $mensagem = "Erro ao atualizar vencimento.";
                    }
                } else {
                    $db->exec("ROLLBACK");
                    $mensagem = "Erro ao atualizar administrador.";
                }
            } catch (Exception $e) {
                $db->exec("ROLLBACK");
                $mensagem = "Erro: " . $e->getMessage();
            }
        }
    }
    
    include './includes/header.php';
?>
<div class="card radius-10">
    <div class="card-body">
        <?php if (!empty($mensagem)): ?>
            <div class='alert alert-danger'><b><?= htmlspecialchars($mensagem) ?></b></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group mb-4">
                <label class="control-label" for="name"><strong>Nome</strong></label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($userData['name']) ?>" required>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="username"><strong>Usuário</strong></label>
                <input type="text" class="form-control" name="username" value="<?= htmlspecialchars($userData['username']) ?>" required>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="password"><strong>Senha</strong></label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="passAdminUpdate" value="<?= htmlspecialchars($userData['password']) ?>" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="const p = document.getElementById('passAdminUpdate'); const i = this.querySelector('i'); if(p.type==='password'){p.type='text'; i.className='bx bx-hide';}else{p.type='password'; i.className='bx bx-show';}"><i class='bx bx-show'></i></button>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="vencimento"><strong>Vencimento</strong></label>
                <input type="date" class="form-control" name="vencimento" value="<?= htmlspecialchars(date('Y-m-d', strtotime($vencimentoData['vencimento']))) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Atualizar Admin</button>
        </form>
    </div>
</div>
<?php
    include './includes/footer.php';

} elseif (isset($_GET['delete'])) {
    // Exclusão de admin com Blindagem Root
    $user_id = intval($_GET['delete']);

    if (isRootUser($user_id, $db)) {
        header("Location: admins.php?r=root_protegido");
        exit;
    }
    
    $db->exec("BEGIN TRANSACTION");
    
    try {
        $dbv->exec("DELETE FROM vencimento WHERE id = $user_id");
        $db->exec("DELETE FROM users WHERE id = $user_id AND nivel = 0");
        
        // Deleta todas as telas (MACs) pertencentes a este admin/revendedor
        $dbClientes = new SQLite3("./api/db/studiolivecode_clientes.db");
        $dbClientes->exec("DELETE FROM ibo WHERE id_user = " . $user_id);
        
        $db->exec("COMMIT");
        header("Location: admins.php?r=excluido");
    } catch (Exception $e) {
        $db->exec("ROLLBACK");
        header("Location: admins.php?r=erro_exclusao");
    }
    exit;

} else {
    // Listagem principal
    include './includes/header.php';
?>
<div class="card radius-10">
    <div class="card-header border-bottom-0 bg-transparent">
        <div class="d-flex align-items-center">
            <div>
                <h5 class="font-weight-bold mb-0">Administradores</h5>
            </div>
            <div class="ms-auto">
                <a href="admins.php?create">
                    <button type="button" class="btn btn-white radius-10">Adicionar Admin</button>
                </a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Usuário</th>
                        <th>Vencimento</th>
                        <th>Editar</th>
                        <th>Deletar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $db->query("SELECT * FROM users WHERE nivel = 0");
                    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                        $stmtVencimento = $dbv->prepare("SELECT vencimento FROM vencimento WHERE id = :id");
                        $stmtVencimento->bindValue(':id', $row['id'], SQLITE3_INTEGER);
                        $vencimentoData = $stmtVencimento->execute()->fetchArray(SQLITE3_ASSOC);
                        $expireDate = $vencimentoData ? $vencimentoData['vencimento'] : '2050-01-01';
                        $vencimentoText = $vencimentoData ? date('d/m/Y', strtotime($vencimentoData['vencimento'])) : 'Não Definido';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td data-sort='<?= $expireDate ?>'><?= $vencimentoText ?></td>
                        <td>
                            <a href="admins.php?update=<?= $row['id'] ?>" class="text-primary">
                                <i class='bx bxs-edit'></i>
                            </a>
                        </td>
                        <td>
                            <?php if (isRootUser($row)): ?>
                                <span class="badge bg-danger" title="Usuário Root Blindado"><i class='bx bx-shield-quarter'></i> ROOT</span>
                            <?php else: ?>
                            <a href="#" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $row['id'] ?>" class="text-danger">
                                <i class='bx bxs-trash'></i>
                            </a>
                            <div class="modal fade" id="deleteModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Confirmar Exclusão</h5>
                                        </div>
                                        <div class="modal-body">
                                            Tem certeza que deseja excluir este administrador?
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <a href="admins.php?delete=<?= $row['id'] ?>" class="btn btn-danger">Excluir</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
    include './includes/footer.php';
}

// Notificações
if (isset($_GET["r"])) {
    $messages = [
        'adicionado' => ['Admin adicionado com sucesso!', 'success'],
        'excluido' => ['Admin excluído com sucesso!', 'success'],
        'atualizado' => ['Admin atualizado com sucesso!', 'success'],
        'nao_encontrado' => ['Admin não encontrado!', 'error'],
        'erro_exclusao' => ['Erro ao excluir admin!', 'error'],
        'root_protegido' => ['Usuário Root Blindado! Este usuário não pode ser excluído.', 'warning']
    ];

    if (isset($messages[$_GET['r']])) {
        list($msg, $type) = $messages[$_GET['r']];
        echo "<script>
            Swal.fire({
                icon: '$type',
                title: '$msg',
                toast: true,
                position: 'bottom',
                showConfirmButton: false,
                timer: 3000
            });
        </script>";
    }
}
?>
