<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

include './includes/header.php';

$db = new SQLite3("./api/db/studiolivecode_imagens.db");

$table_name = "bg";

$target_dir_local_path = __DIR__ . '/banners/'; 
$fixed_bg_filename = 'bg.png'; 
$default_bg_filename = 'bg_padrao.png'; 

if (!file_exists($target_dir_local_path)) {
    mkdir($target_dir_local_path, 0777, true);
}

$db->exec('CREATE TABLE IF NOT EXISTS bg(id INT PRIMARY KEY NOT NULL, path TEXT)');
$rows = $db->query('SELECT COUNT(*) as count FROM bg');
$numRows = $rows->fetchArray(SQLITE3_ASSOC);
if ($numRows['count'] == 0) {
    $db->exec("INSERT INTO bg(id, path) VALUES('1', '')");
}

$res = $db->query("SELECT * FROM " . $table_name . " WHERE id='1'");
$row = $res->fetchArray();
$bg_path_from_db = $row["path"];

if (isset($_POST['submit'])) {
    $redirect = "bg.php?r=erro_att";
    if ($_POST['type'] == '0') {
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $target_file_local_path_full = $target_dir_local_path . $fixed_bg_filename;
        $allowed_extensions = ['gif', 'png', 'jpg', 'jpeg'];

        if (in_array($file_ext, $allowed_extensions)) {
            if (file_exists($target_file_local_path_full)) {
                unlink($target_file_local_path_full);
            }
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file_local_path_full)) {
                $db->exec("UPDATE bg SET path='' WHERE id='1'");
                $redirect = "bg.php?r=atualizado";
            }
        }
    } else {
        $url = $_POST['path'];
        $file_ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $allowed_extensions = ['gif', 'png', 'jpg', 'jpeg', 'mp4'];

        if (in_array($file_ext, $allowed_extensions)) {
            if (file_exists($target_dir_local_path . $fixed_bg_filename)) {
                unlink($target_dir_local_path . $fixed_bg_filename);
            }
            $db->exec("UPDATE bg SET path='" . SQLite3::escapeString($url) . "' WHERE id='1'");
            $redirect = "bg.php?r=atualizado";
        }
    }
    $db->close();
    header("Location: $redirect");
    exit();
}

// --- LÓGICA PARA CONSTRUIR O CAMINHO DO BACKGROUND PARA PRÉ-VISUALIZAÇÃO UNIVERSAL (com cache-buster) ---
$current_bg_display_path = '';

// Pega o esquema (http ou https) e o host (domínio.com ou sub.domínio.com)
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Pega o caminho web do script atual (ex: /iboteste/bg.php ou apenas /bg.php)
$script_name = $_SERVER['SCRIPT_NAME']; // Ex: /iboteste/bg.php
$script_dir_web_path = dirname($script_name); // Ex: /iboteste

// Normaliza o caminho (remove trailing slash e garante que começa com /)
if ($script_dir_web_path === '/' || $script_dir_web_path === '\\' || $script_dir_web_path === '.') {
    $script_dir_web_path = '';
} else {
    $script_dir_web_path = '/' . trim($script_dir_web_path, '/');
}

// O parâmetro de cache-buster será o timestamp do arquivo ou timestamp atual
$cache_buster = time();

// 1. Prioridade: Se o DB tem uma URL externa salva E é uma URL válida
if (!empty($bg_path_from_db) && (strpos($bg_path_from_db, 'http://') === 0 || strpos($bg_path_from_db, 'https://') === 0)) {
    $current_bg_display_path = $bg_path_from_db;
} 
// 2. Segunda Prioridade: Tenta usar a bg.png local
else if (file_exists($target_dir_local_path . $fixed_bg_filename)) {
    // Usa o timestamp do arquivo como cache-buster para forçar atualização quando o arquivo mudar
    $file_modified_time = filemtime($target_dir_local_path . $fixed_bg_filename);
    
    // Constrói a URL completa para a bg.png local
    $bg_local_web_url = $scheme . '://' . $host . $script_dir_web_path . '/banners/' . $fixed_bg_filename . '?v=' . $file_modified_time;
    $current_bg_display_path = $bg_local_web_url;
} 
// 3. Última Opção: Usa o background padrão
else {
    // Constrói a URL do background padrão
    $default_bg_web_url = $scheme . '://' . $host . $script_dir_web_path . '/assets/images/' . $default_bg_filename . '?v=' . $cache_buster;
    $current_bg_display_path = $default_bg_web_url;
}

// DEBUG: Descomente as linhas abaixo para verificar os caminhos gerados
// echo "<!-- DEBUG INFO:\n";
// echo "Scheme: $scheme\n";
// echo "Host: $host\n";
// echo "Script Dir: $script_dir_web_path\n";
// echo "Target Dir Local: $target_dir_local_path\n";
// echo "File exists: " . (file_exists($target_dir_local_path . $fixed_bg_filename) ? 'YES' : 'NO') . "\n";
// echo "Final URL: $current_bg_display_path\n";
// echo "-->\n";

// --- FIM DA LÓGICA DE PRÉ-VISUALIZAÇÃO ---
?>

<div class="card radius-10">
    <div class="card-body">
        <center><h4 class="card-title">Background Atual:</h4>
            <img class="preview-image" src="<?php echo $current_bg_display_path; ?>" alt="Uploaded Image" width="30%" height="auto"></center>
        <form class="forms-sample" method="post" enctype="multipart/form-data">
            <div class="form-group mb-4">
                <label for="url"><strong>Selecione como quer adicionar a imagem de Fundo</strong></label>
                <div class="form-group mb-4">
                    <select class="form-control type" id="type" name="type">
                        <option data-value="op0" value="0" selected>Enviar o arquivo de imagem</option>
                        <option data-value="op1" value="1">Digitar URL externa da imagem</option>
                    </select>
                </div>
            </div>

            <div class="activeu">
                <div class="form-group mb-4">
                    <label class="control-label"><strong>Digite o endereço da imagem</strong></label>
                    <div class="form-group mb-4">
                        <input class="form-control" type="hidden" name="del" value="<?php echo $bg_path_from_db ?>" hidden>
                        <input class="form-control" type="text" name="path" value="<?php echo (strpos($current_bg_display_path, 'http') === 0 && $bg_path_from_db !== '') ? $bg_path_from_db : ''; ?>" placeholder="Digite o endereço do arquivo de imagem no formato (.gif, .png ou .jpg)">
                    </div>
                </div>
            </div>

            <div class="actived">
                <div class="form-group mb-4">
                    <label class="form-label"><strong>Selecione o arquivo para envio</strong></label>
                    <input class="form-control" type="file" name="image" id="intro" placeholder="Escolher Imagem" onchange="uploadintro(this)" aria-describedby="intro">
                </div>
            </div>
            <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Editar</button>
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
                title: 'Background Atualizado com Sucesso!'
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
                title: 'Erro ao atualizar o Background!'
            })</script>";
            break;
    }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
$('.actived').show();
$('.activeu').hide();

$(document).ready(function(){
  $('.type').change(function(){
    if($('.type').val() < 1) {
      $('.actived').show();
      $('.activeu').hide();
    } else {
      $('.activeu').show();
      $('.actived').hide();
    }
  });
});
</script>
<?php 
include './includes/footer.php';
ob_end_flush();
?>