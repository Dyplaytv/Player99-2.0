<?php
session_start();
require_once __DIR__ . '/includes/auth_helper.php';

if(isset($_GET['update']) && is_numeric($_GET['update'])) {
    $userId = (int)$_GET['update'];
    
    $db = new SQLite3("./api/db/studiolivecode_users.db");
    
    // Use prepared statements to prevent SQL injection
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    
    $row = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$row) {
        die("User not found.");
    }

    $isTargetRoot = isRootUser($row);
    
    if (isset($_POST["submit"])) {
        $name = trim($_POST["name"]);
        $username = trim($_POST["username"]);
        $password = trim($_POST["password"]);
        $logo = trim($_POST["logo"]);
        $email = trim($_POST["email"] ?? '');
        $admin = $isTargetRoot ? 1 : intval($_POST["admin"]);

        // Se a senha mudou e não é hash, hash
        if (!empty($password) && $password !== $row['password']) {
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
        } else {
            $hashedPass = $row['password'];
        }

        $stmt = $db->prepare("UPDATE users SET name = :name, username = :username, password = :password, logo = :logo, admin = :admin, email = :email WHERE id = :id");
        $stmt->bindValue(':name', $name, SQLITE3_TEXT);
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $stmt->bindValue(':password', $hashedPass, SQLITE3_TEXT);
        $stmt->bindValue(':logo', $logo, SQLITE3_TEXT);
        $stmt->bindValue(':admin', $admin, SQLITE3_INTEGER);
        $stmt->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
        $stmt->execute();

        header("Location: usuarios.php?r=atualizado");
        exit;
    }

    $name = $row["name"];
    $user = $row["username"];
    $pass = $row["password"];
    $logo = $row["logo"];
    $admin = $row["admin"];
    $email = $row["email"] ?? '';
} else {
    die("Invalid user ID.");
}
?>

<?php include './includes/header.php'; ?>

<div class="card radius-10">
    <div class="card-body">
        <h4 class="card-title">Editar Perfil <?= $isTargetRoot ? '<span class="badge bg-danger ms-2"><i class="bx bx-shield-quarter"></i> ROOT BLINDADO</span>' : '' ?></h4>
        <form class="forms-sample" method="post" enctype="multipart/form-data">

            <div class="form-group mb-4">
                <label for="name"><strong>Nome</strong></label>
                <input type="text" class="form-control" placeholder="Digite o Nome" name="name" id="name" value="<?= htmlspecialchars($name); ?>" required>
            </div>
            <div class="form-group mb-4">
                <label for="username"><strong>Usuário</strong></label>
                <input type="text" class="form-control" placeholder="Digite o Usuário" name="username" id="username" value="<?= htmlspecialchars($user); ?>" required>
            </div>
            <div class="form-group mb-4">
                <label for="email"><strong>E-mail</strong></label>
                <input type="email" class="form-control" placeholder="ex: baitabric@gmail.com" name="email" id="email" value="<?= htmlspecialchars($email); ?>">
            </div>
            <div class="form-group mb-4">
                <label for="password"><strong>Senha</strong></label>
                <div class="input-group">
                    <input type="password" class="form-control" placeholder="Digite a Senha" name="password" id="password" value="<?= htmlspecialchars($pass); ?>" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="const p = document.getElementById('password'); const i = this.querySelector('i'); if(p.type==='password'){p.type='text'; i.className='bx bx-hide';}else{p.type='password'; i.className='bx bx-show';}"><i class='bx bx-show'></i></button>
                </div>
            </div>
            <div class="form-group mb-4">
                <label for="logo"><strong>Logo</strong></label>
                <input type="text" class="form-control" placeholder="Digite Link da Logo" name="logo" id="logo" value="<?= htmlspecialchars($logo); ?>">
            </div>
            <div class="form-group mb-4">
                <label for="admin"><strong>Nível Atual: <b style="color: green;"><?= $admin == 1 ? "Admin" : "Usuário"; ?></b></strong></label>
                <?php if ($isTargetRoot): ?>
                    <input type="hidden" name="admin" value="1">
                    <p class="text-danger mb-0"><i class="bx bx-shield"></i> Este usuário é ROOT do sistema e seus privilégios de Administrador não podem ser removidos.</p>
                <?php else: ?>
                    <select name="admin" class="form-control" id="admin">
                        <option value="1" <?= $admin == 1 ? 'selected' : '' ?>>Admin</option>
                        <option value="0" <?= $admin != 1 ? 'selected' : '' ?>>Usuário / Revenda</option>
                    </select>
                <?php endif; ?>
            </div>
            <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Salvar Alterações</button>
            <a href="usuarios.php" class="btn btn-secondary">Voltar</a>
        </form>
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

<?php include './includes/footer.php'; ?>