<?php 
include ('includes/header.php');

//table name
$table_name = "ads2";
$base_file = basename($_SERVER["SCRIPT_NAME"]);

//create if not
$adb->exec("CREATE TABLE IF NOT EXISTS {$table_name}(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, title VARCHAR(100), url TEXT)");

// add image_path column if not exists
try {
    $adb->exec("ALTER TABLE {$table_name} ADD COLUMN image_path TEXT");
} catch(Exception $e) {}

// Garante que existam exatamente 2 linhas no banco de dados
$countRes = $adb->query("SELECT COUNT(*) as count FROM {$table_name}");
$countRow = $countRes->fetchArray();
if ($countRow['count'] < 2) {
    for ($i = $countRow['count']; $i < 2; $i++) {
        $title = "Anúncio " . ($i + 1);
        $adb->exec("INSERT INTO {$table_name}(title, url, image_path) VALUES('{$title}', '', '')");
    }
}

//table call
$res = $adb->query("SELECT * FROM {$table_name} ORDER BY id ASC LIMIT 2");

//update call
@$resU = $adb->query("SELECT * FROM {$table_name} WHERE id='{$_GET['update']}'");
@$rowU=$resU->fetchArray();

if(isset($_POST['submitU'])){
	$id = $_POST['id'];
	$title = $_POST['title'];
	$url = $_POST['url'];
	$image_path = $_POST['current_image_path'];
	
	if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
		$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
		$filename = uniqid() . '.' . $ext;
		if(!file_exists('img/')) mkdir('img/', 0777, true);
		$target = 'img/' . $filename;
		move_uploaded_file($_FILES['image']['tmp_name'], $target);
		$image_path = $target;
	} else if (!empty($_POST['image_url'])) {
        $image_path = $_POST['image_url'];
    }
	
	$adb->exec("UPDATE {$table_name} SET title='{$title}', url='{$url}', image_path='{$image_path}' WHERE id='{$id}'");
	echo "<script>window.location.href='{$base_file}';</script>";
    exit;
}

//submit new
if (isset($_POST['submit'])){
	$title = $_POST['title'];
	$url = $_POST['url'];
	$image_path = "";
	
	if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
		$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
		$filename = uniqid() . '.' . $ext;
		if(!file_exists('img/')) mkdir('img/', 0777, true);
		$target = 'img/' . $filename;
		move_uploaded_file($_FILES['image']['tmp_name'], $target);
		$image_path = $target;
	} else if (!empty($_POST['image_url'])) {
        $image_path = $_POST['image_url'];
    }
	
	$adb->exec("INSERT INTO {$table_name}(title, url, image_path) VALUES('{$title}', '{$url}', '{$image_path}')");
	echo "<script>window.location.href='{$base_file}';</script>";
    exit;
}

?>
<?php
if (isset($_GET['update'])){ 
?>
            <div class="col-md-8 mx-auto">
                <center>
		        <h1 class="colorboard"></i> Editar Anúncio</h1>
	            </center>
				<div class="card bg-primary text-white">
					<div class="card-header">
						<center>
							<h2><i class="fa fa-file-image-o"></i> Editar Ad</h2>
						</center>
					</div>
					<div class="card-body">
                            <form method="post" enctype="multipart/form-data">
                                  <input type="hidden" class="form-control" name="id" value="<?=$_GET['update'] ?>">
                                  <input type="hidden" name="current_image_path" value="<?=$rowU['image_path'] ?>">
                                <div class="form-group">
                                  <input type="text" name="title" class="form-control" value="<?=$rowU['title'] ?>" required>
                                  <label>Titulo</label>
                                </div>
                                <div class="form-group mt-3">
                                  <input type="text" class="form-control" name="url" value="<?=$rowU['url'] ?>">
                                  <label>Link de Redirecionamento (Opcional)</label>
                                </div>
                                <div class="form-group mt-3" style="border: 1px solid #00f3ff; padding: 15px; border-radius: 8px;">
                                  <label style="color:#00f3ff; font-weight:bold; margin-bottom:10px; display:block;">Nova Imagem (deixe ambos em branco para manter a atual)</label>
                                  <label>1. Fazer novo Upload (Substitui a atual)</label>
                                  <input class="form-control mb-3" type="file" name="image" accept="image/*">

                                  <label>OU 2. Novo Link Externo da Imagem</label>
                                  <input class="form-control" type="text" name="image_url" placeholder="https://...">

                                  <?php if(!empty($rowU['image_path'])) { ?>
                                      <br><img src="<?=$rowU['image_path']?>" width="150" style="margin-top:10px;">
                                  <?php } else if (!empty($rowU['url'])) { ?>
                                      <br><img src="<?=$rowU['url']?>" width="150" style="margin-top:10px;">
                                  <?php } ?>
                                </div>  
                                <div class="col-12 mt-4"> <button type="submit" name="submitU" class="btn btn-info ">Salvar Alterações</button> </div>
                              </form>
					</div>
					</div>
				</div>
<?php
 }else{
//main table/form
	 ?>

	<div class="col-md-12 mx-auto">
	<center>
		<h1 class="colorboard"></i> Banners Anúncios</h1>
	</center>
	<br>
	<div class="table-responsive">
		<table class="table table-striped table-sm">
			<thead style="color:white!important">
				<tr>
					<th>Index (Ordem)</th>
					<th>Título</th>
					<th>Link URL</th>
					<th>Preview Imagem</th>
					<th>Ações</th>
				</tr>
			</thead>
			<tbody>
			<?php 
            $i = 1;
            while ($row = $res->fetchArray()) {
                $displayImg = !empty($row['image_path']) ? $row['image_path'] : $row['url'];
            ?>
				<tr>
					<td><?=$row['id'] ?> <br><small class="text-warning">(Anúncio <?=$i?>)</small></td>
					<td><?=$row['title'] ?></td>
					<td><?=$row['url'] ?></td>
					<td>
						<?php if (!empty($displayImg) && (strpos($displayImg, '.mp4') !== false || strpos($displayImg, '.webm') !== false || strpos($displayImg, '.ogg') !== false)) {?>
							<video src="<?=$displayImg?>" controls width="100px" autoplay = true controls = false muted = true loop = false></video>
						<?php } else if (!empty($displayImg)) {?>
							<img src="<?=$displayImg?>" width="100px"/>
						<?php }?>
					</td>
					<td>
						<a class="btn btn-info btn-ok" href="<?php echo $base_file ?>?update=<?=$row['id'] ?>"><i class="fa fa-pencil-square-o"></i> Editar</a>
					</td>
				</tr>
			<?php $i++; }?>
            </tbody>
		</table>
	</div>
</div>

<?php }?>

<?php include ('includes/footer.php');?>
<script type="text/javascript">
</script>
</body>
</html>