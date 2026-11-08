<?php 
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include ('includes/header.php');

function uploadFile($file) {
    if ($file["size"] == 0) return null;

    $targetDir = "img/";  
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    $fileName = "logo.png";  
    $targetFile = $targetDir . $fileName;  

    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        echo "<script>alert('Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.');</script>";
        $uploadOk = 0;
    }

    if ($uploadOk == 0) {
        return null;
    } else {
        if (move_uploaded_file($file["tmp_name"], $targetFile)) {
            return $targetFile; 
        } else {
            return null;
        }
    }
}

function deleteFile($url) {
    if(file_exists($url)) unlink($url);
}

$table_name = "ads";
$base_file = basename($_SERVER["SCRIPT_NAME"]);
$adb3 = new SQLite3('./api/.db_logo.db');
$adb3->exec("CREATE TABLE IF NOT EXISTS {$table_name}(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(100), caminho TEXT)");

$res = $adb3->query("SELECT * FROM {$table_name}");
@$resU = $adb3->query("SELECT * FROM {$table_name} WHERE id='{$_GET['update']}'");
@$rowU = $resU->fetchArray();

if (isset($_POST['submitU'])) {
    $file_path = null;
    $newFileUploaded = !empty($_FILES["fileToUpload"]["name"]);
    
    if ($newFileUploaded) {
        $updateQuery = $adb3->query("SELECT caminho FROM {$table_name} WHERE id={$_POST['id']}");
        $updateRow = $updateQuery->fetchArray();
        $currentFileUrl = $updateRow['caminho'];
        
        $file_path = uploadFile($_FILES["fileToUpload"]);
    }
    
    if (isset($_POST['id']) && (isset($_POST['title']) || $file_path !== null)) {
        $title = isset($_POST['title']) ? $_POST['title'] : $rowU['title'];
        $caminho = $file_path !== null ? $file_path : $updateRow['caminho'];
        
        $adb3->exec("UPDATE {$table_name} SET title='{$title}', caminho='{$caminho}' WHERE id='{$_POST['id']}'");
        $adb3->close();
        echo "<script>window.location.href='$base_file';</script>";
        exit;
    }
}

if (isset($_POST['submit'])) {
    $file_path = null;
    if (!empty($_FILES["fileToUpload"]["name"])) $file_path = uploadFile($_FILES["fileToUpload"]);
    
    if ($file_path !== null || isset($_POST['title'])) {
        $title = isset($_POST['title']) ? $_POST['title'] : "";
        $caminho = $file_path !== null ? $file_path : "";
        $adb3->exec("INSERT INTO {$table_name}(title, caminho) VALUES('{$title}', '{$caminho}')");
        echo "<script>window.location.href='$base_file';</script>";
        exit;
    }
}

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $deleteQuery = $adb3->query("SELECT caminho FROM {$table_name} WHERE id={$deleteId}");
    $deleteRow = $deleteQuery->fetchArray();
    if ($deleteRow) {
        deleteFile($deleteRow['caminho']);
        $adb3->exec("DELETE FROM {$table_name} WHERE id={$deleteId}");
    }
    echo "<script>window.location.href='$base_file';</script>";
    exit;
}
?>

<div class="modal fade" id="confirm-delete" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">CONFIRMAR</h5></div>
            <div class="modal-body">QUER MESMO DELETAR?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCELAR</button>
                <a class="btn btn-danger btn-ok">DELETAR</a>
            </div>
        </div>
    </div>
</div>

<?php if (isset($_GET['create'])) { ?>
<div class="card radius-10">
    <div class="card-body">
        <h4 class="card-title">NOVO LOGO ADM PLAY</h4>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label>NOME DO LOGO</label>
                <input class="form-control" type="text" name="title">
            </div>
            <div class="form-group mb-3">
                <input type="file" class="form-control" name="fileToUpload" id="fileToUpload" required>
            </div>
            <button type="submit" name="submit" class="btn btn-info">SALVAR</button>
            <a href="<?=$base_file?>" class="btn btn-secondary">CANCELAR</a>
        </form>
    </div>
</div>
<?php } else if (isset($_GET['update'])) { ?>
<div class="card radius-10">
    <div class="card-body">
        <h4 class="card-title">EDITAR LOGO ADM PLAY</h4>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?=$_GET['update'] ?>">
            <div class="form-group mb-3">
                <label>NOME DO LOGO</label>
                <input type="text" name="title" class="form-control" value="<?=$rowU['title'] ?>">
            </div>
            <div class="form-group mb-3">
                <input type="file" class="form-control" name="fileToUpload" id="fileToUpload">
            </div>
            <button type="submit" name="submitU" class="btn btn-info">SALVAR</button>
            <a href="<?=$base_file?>" class="btn btn-secondary">CANCELAR</a>
        </form>
    </div>
</div>
<?php } else { ?>
<div class="card radius-10">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <div><h5 class="mb-0">LOGO ADM PLAY</h5></div>
            <div class="ms-auto"><a href="./<?=$base_file?>?create" class="btn btn-primary btn-sm">NOVO</a></div>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr><th>ID</th><th>NOME</th><th>PRÉVIA</th><th>AÇÕES</th></tr>
                </thead>
                <tbody>
                <?php while ($row = $res->fetchArray()) { ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['title'] ?></td>
                        <td><img src="<?= $row['caminho'] ?>?<?= time() ?>" alt="Logo" style="height:40px; background-color:#ccc;"></td>
                        <td>
                            <a href="./<?=$base_file?>?update=<?= $row['id'] ?>" class="btn btn-warning btn-sm">EDITAR</a>
                            <a href="#" class="btn btn-danger btn-sm" data-href="./<?=$base_file?>?delete=<?= $row['id'] ?>" data-bs-toggle="modal" data-bs-target="#confirm-delete">DELETAR</a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php } ?>

<?php include ('includes/footer.php'); ?>
<script>
$('#confirm-delete').on('show.bs.modal', function(e) {
    $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
});
</script>
