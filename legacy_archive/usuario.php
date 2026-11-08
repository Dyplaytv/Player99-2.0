<?php

session_start();
$ifofuser = $_SESSION["id"];
$db = new SQLite3("./api/db/studiolivecode_users.db");
$res = $db->query("SELECT * \n\t\t\t\t  FROM users \n\t\t\t\t  WHERE id='" . $ifofuser . "'");
$row = $res->fetchArray();
$id = $row["id"];
$name = $row["name"];
$user = $row["username"];
$pass = $row["password"];
$logo = $row["logo"];
$admin= $row["admin"];
if (isset($_POST["submit"])) {
    $dircheck0 = substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/"));
    $dircheck22 = substr($dircheck0, 1);
    $postu2 = $_POST["username"] . $dircheck22;
    $db->exec("UPDATE users SET username='" . $_POST["username"] . "', password='" . $_POST["password"] . "' WHERE id='" . $ifofuser . "'");
    session_start();
    session_regenerate_id();
    $_SESSION["slcibo"] = true;
    $_SESSION["id"] = $ifofuser;
    $_SESSION[$postu2] = $postu2;
    $_SESSION[$dircheck22] = $dircheck22;
    $_SESSION["U"] = $_POST["username"];
    header("Location: usuario.php?r=atualizado");
}

?>
<?php include './includes/header.php';?>
<div class="card radius-10">
                  <div class="card-body">
                    <h4 class="card-title">Editar Perfil</h4>
                    <form class="forms-sample" method="post" enctype="multipart/form-data">
                    <div class="form-group mb-4">
                        <label for="title">Nome</label>
                        <input type="text" class="form-control" name="name" id="name" value="<?=$name;?>">
                      </div>
                      <div class="form-group mb-4">
                        <label for="title">Usuário</label>
                        <input type="text" class="form-control" name="username" id="username" value="<?=$user;?>">
                      </div>
                      <div class="form-group mb-4">
                        <label for="url">Senha</label>
                        <input type="text" class="form-control" name="password" id="password" value="<?=$pass;?>">
                      </div>
                      <input type="text" class="form-control" name="admin" id="admin" value="<?=$admin;?>" hidden>
                      <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Editar</button>
                    </form>
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
              }
            }
            ?>
				
<?php include './includes/footer.php';?>