<?php 
session_start();
require_once __DIR__ . '/includes/auth_helper.php';

$db = new SQLite3("./api/db/studiolivecode_users.db");
$res = $db->query("SELECT * FROM users");

if (isset($_GET["delete"])) {
    $user_id = (int)$_GET["delete"];
    
    // Blindagem de Usuário Root
    if (isRootUser($user_id, $db)) {
        header("Location: usuarios.php?r=root_protegido");
        exit;
    }
    
    // Deleta o revendedor com prepared statement
    $stmtDel = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmtDel->bindValue(':id', $user_id, SQLITE3_INTEGER);
    $stmtDel->execute();
    
    // Deleta todas as telas (MACs) pertencentes a este revendedor
    $dbClientes = new SQLite3("./api/db/studiolivecode_clientes.db");
    $stmtDelIbo = $dbClientes->prepare("DELETE FROM ibo WHERE id_user = :id");
    $stmtDelIbo->bindValue(':id', $user_id, SQLITE3_INTEGER);
    $stmtDelIbo->execute();
    
    header("Location: usuarios.php?r=deletado");
    exit;
}
?>
<?php include './includes/header.php';?>
<div class="card radius-10">
                  <div class="card-body">
                    <h4 class="card-title">Editar Usuários</h4>
                    <div class="table-responsive">
                      <table class="table">
                        <thead>
                          <tr>
                            
                            <th> Nome</th>
                            <th> Usuario </th>
                            <th> Nivel </th>
                            <th> Editar </th>
                            <th> Excluir </th>
                          </tr>
                        </thead>
                        <?php
                        while ($row = $res->fetchArray()) {
                          $id = $row["id"];
                          $name = $row["name"];
                          $user = $row["username"];
                          $pass = $row["password"];
                          $logo = $row["logo"];
                          $nivel = $row["nivel"];
                          ?>
                        <tbody>
                          <tr>
                          
                          <td><?=$name;?></td>
                          <td><?=$user;?></td>
                          <td><?php echo $nivel == 1 ? "Admin" : "Usuário";?></td>
                            <td>
                          <div class="d-flex order-actions">
                          <a href="edit_usuario.php?update=<?=$id?>" class="text-primary bg-light-primary border-0"><i class='bx bxs-edit' ></i></a></div></td>
                          <td>
                           <div class="d-flex order-actions">
                          <?php if (isRootUser($row)): ?>
                            <span class="badge bg-danger p-2" title="Usuário Root Blindado"><i class='bx bx-shield-quarter'></i> ROOT</span>
                          <?php else: ?>
                            <a href="usuarios.php?delete=<?=$id?>" onclick="return confirm('Deseja realmente excluir este usuário?');" class="text-danger bg-light-danger border-0"><i class='bx bxs-trash' ></i></a>
                          <?php endif; ?>
                          </div>
                        </td>
                          </tr>
                    </tbody>
                            <?php } ?>
                      </table>
                    </div>
                    <a href="cad_usuarios.php" class="btn btn-primary mr-2"><i class='bx bx-plus mr-1'></i>Cadastrar Usuários</button></a>
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
            case "add_sucesso":
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
            case "deletado":
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
            case "root_protegido":
            echo "<script>
            const Toast = Swal.mixin({
  toast: true,
  position: 'bottom',
  showConfirmButton: false,
  timer: 3000,
})
Toast.fire({
  icon: 'warning',
  title: 'Usuário Root Blindado! Não pode ser excluído.'
})</script>";
            break;
              }
            }
            ?>
				
<?php include './includes/footer.php';?>