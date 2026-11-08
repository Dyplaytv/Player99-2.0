<?php 
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include(dirname(__DIR__) . '/includes/header.php');

function uploadFile($file) {
    if ($file["size"] == 0) return null;

    $targetDir = dirname(__DIR__) . "/uploads/bloqueio/";  
    if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
    
    // Gerar nome único para evitar sobrescrita
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $fileName = uniqid() . "." . $imageFileType;  
    $targetFile = $targetDir . $fileName;  

    $uploadOk = 1;

    // Permitir mp4, png, jpg, etc.
    $allowed = ["jpg", "jpeg", "png", "gif", "mp4", "webm", "mkv"];
    if (!in_array($imageFileType, $allowed)) {
        echo "<script>alert('Apenas arquivos JPG, JPEG, PNG, GIF, MP4, WEBM e MKV são permitidos.');</script>";
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

$table_name = "bloqueio";
$base_file = basename($_SERVER["SCRIPT_NAME"]);
$db_bloqueio = new SQLite3(dirname(__DIR__) . '/api/.db_bloqueio.db');
$db_bloqueio->exec("CREATE TABLE IF NOT EXISTS {$table_name}(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(100), caminho TEXT)");

$res = $db_bloqueio->query("SELECT * FROM {$table_name}");
if (isset($_GET['update'])) {
    $resU = $db_bloqueio->query("SELECT * FROM {$table_name} WHERE id='{$_GET['update']}'");
    $rowU = $resU->fetchArray();
}

if (isset($_POST['submitU'])) {
    $file_path = null;
    $newFileUploaded = !empty($_FILES["fileToUpload"]["name"]);
    
    if ($newFileUploaded) {
        $updateQuery = $db_bloqueio->query("SELECT caminho FROM {$table_name} WHERE id={$_POST['id']}");
        $updateRow = $updateQuery->fetchArray();
        
        $file_path = uploadFile($_FILES["fileToUpload"]);
        if ($file_path !== null) {
            deleteFile($updateRow['caminho']); // deleta antigo
        }
    }
    
    if (isset($_POST['id']) && (isset($_POST['title']) || $file_path !== null)) {
        $title = isset($_POST['title']) ? $_POST['title'] : $rowU['title'];
        if ($file_path !== null) {
            $caminho = $file_path;
            $db_bloqueio->exec("UPDATE {$table_name} SET title='{$title}', caminho='{$caminho}' WHERE id='{$_POST['id']}'");
        } else {
            $db_bloqueio->exec("UPDATE {$table_name} SET title='{$title}' WHERE id='{$_POST['id']}'");
        }
        $db_bloqueio->close();
        echo "<script>window.location.href='$base_file';</script>";
        exit;
    }
}

if (isset($_POST['submit'])) {
    $file_path = null;
    if (!empty($_FILES["fileToUpload"]["name"])) $file_path = uploadFile($_FILES["fileToUpload"]);
    
    if ($file_path !== null) {
        $title = isset($_POST['title']) ? $_POST['title'] : "Vídeo/Imagem de Bloqueio";
        $caminho = $file_path;
        $db_bloqueio->exec("INSERT INTO {$table_name}(title, caminho) VALUES('{$title}', '{$caminho}')");
        echo "<script>window.location.href='$base_file';</script>";
        exit;
    } else {
        echo "<script>alert('Erro no upload.');</script>";
    }
}

if (isset($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    $deleteQuery = $db_bloqueio->query("SELECT caminho FROM {$table_name} WHERE id={$deleteId}");
    $deleteRow = $deleteQuery->fetchArray();
    if ($deleteRow) {
        deleteFile($deleteRow['caminho']);
        $db_bloqueio->exec("DELETE FROM {$table_name} WHERE id={$deleteId}");
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
        <h4 class="card-title">NOVA MÍDIA DE BLOQUEIO</h4>
        <form method="post" enctype="multipart/form-data">
            <div class="form-group mb-3">
                <label>TÍTULO DA MÍDIA</label>
                <input class="form-control" type="text" name="title" required>
            </div>
            <div class="form-group mb-3">
                <label>ARQUIVO (Vídeo ou Imagem)</label>
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
        <h4 class="card-title">EDITAR MÍDIA DE BLOQUEIO</h4>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?=$_GET['update'] ?>">
            <div class="form-group mb-3">
                <label>TÍTULO DA MÍDIA</label>
                <input type="text" name="title" class="form-control" value="<?=$rowU['title'] ?>" required>
            </div>
            <div class="form-group mb-3">
                <label>NOVO ARQUIVO (Deixe em branco para não alterar)</label>
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
            <div><h5 class="mb-0">PLAYLIST DE BLOQUEIO UNIVERSAL</h5></div>
            <div class="ms-auto"><a href="./<?=$base_file?>?create" class="btn btn-primary btn-sm">ADICIONAR MÍDIA</a></div>
        </div>
        <p>Essas mídias serão exibidas nas telas dos clientes que estiverem com a conta vencida ou inativa.</p>
        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr><th>ID</th><th>TÍTULO</th><th>ARQUIVO</th><th>AÇÕES</th></tr>
                </thead>
                <tbody>
                <?php while ($row = $res->fetchArray()) { ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td>
                            <?php 
                                $ext = strtolower(pathinfo($row['caminho'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['mp4', 'webm', 'mkv'])) {
                                    echo "<video src='{$row['caminho']}' style='height:40px;' muted></video>";
                                } else {
                                    echo "<img src='{$row['caminho']}?".time()."' alt='Mídia' style='height:40px; background-color:#ccc;'>";
                                }
                            ?>
                        </td>
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
