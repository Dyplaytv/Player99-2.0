<?php
session_start();

$id = $_SESSION['id'];
$db = new SQLite3("./api/db/studiolivecode_users.db");

// Faça uma consulta para obter as informações de admin e nivel do usuário
$query = $db->query("SELECT nivel FROM users WHERE id = $id");
$userData = $query->fetchArray(SQLITE3_ASSOC);

// Verifique se o usuário é administrador (admin = 1) ou tem o nível necessário (por exemplo, nivel = 1)
if (!$userData || $userData['nivel'] != 1) {
    header("location: painel.php"); // Redirecionar usuários que não têm acesso
    exit;
}

$dbv = new SQLite3("./api/db/studiolivecode_vencimento.db");
$resv = $dbv->query("SELECT vencimento FROM vencimento WHERE id='1'");
$rowv = $resv->fetchArray();

if (isset($_POST["submit"])) {
    // Certifique-se de escapar e validar os dados do formulário para evitar SQL Injection
    $newVencimento = $_POST["vencimento"];
    $stmt = $dbv->prepare("UPDATE vencimento SET vencimento = :newVencimento WHERE id = 1");
    $stmt->bindValue(':newVencimento', $newVencimento, SQLITE3_TEXT);
    $stmt->execute();
    
    header("Location: vencimento.php?r=vencimento");
    exit;
}
?>

<?php include './includes/header.php';?>
<div class="card radius-10">
    <div class="card-body">
        <h4 class="card-title">Vencimento</h4>
        <form class="forms-sample" method="post" enctype="multipart/form-data">
            <div class="form-group mb-4">
                <label for="title"><strong>Vencimento Atual</strong></label>
                <input type="date" class="form-control timepicker" name="vencimento" id="vencimento" value="<?php echo date('Y-m-d', strtotime($rowv['vencimento'])); ?>">
            </div>
            <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Editar</button>
        </form>
    </div>
</div>

<?php
if (isset($_GET["r"])) {
    $result = $_GET["r"];
    switch ($result) {
        case "vencimento":
            $formattedDate = date('d/m/Y', strtotime($rowv['vencimento']));
            ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: '✔️Painel Renovado com Sucesso!',
                    html: '<br>O novo vencimento é <?php echo $formattedDate; ?><br><br>Sugerimos que faça sempre a renovação antes dessa data para que o Aplicativo não pare de funcionar.<br><br>Qualquer dúvida, sugestão ou reclamação estamos à disposição.',
                    focusConfirm: false,
                    confirmButtonText:
                        '<i class="fa fa-thumbs-up"></i> Copiar',
                    confirmButtonAriaLabel: 'Copiar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        const el = document.createElement('textarea');
                        el.value = '*👍Painel Renovado com Sucesso!*\n\nO novo vencimento é *<?php echo $formattedDate; ?>*\n\nSugerimos que faça sempre a renovação antes dessa data para que o Aplicativo não pare de funcionar.\n\nQualquer dúvida, sugestão ou reclamação estamos à disposição.';
                        document.body.appendChild(el);
                        el.select();
                        document.execCommand('copy');
                        document.body.removeChild(el);
                        Swal.fire({
                            icon: 'success',
                            timer: 3000,
                            title: 'Conteúdo copiado para a área de transferência'
                        })
                    }
                })
            </script>
            <?php
            break;
    }
}
?>
				
<?php include './includes/footer.php';?>
