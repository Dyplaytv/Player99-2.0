<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Primeiro de tudo, inclua o header pra session_start() e outros headers rodarem ANTES de qualquer output.
include './includes/header.php';

// Conexão com o banco de dados - SÓ PARA LIDAR COM URLS EXTERNAS
$db = new SQLite3("./api/db/studiolivecode_imagens.db");

// Nome da tabela (ainda usada para guardar a URL externa, se for o caso)
$table_name = "logo";

// Define o diretório de destino dos uploads NO SERVIDOR (caminho local)
$target_dir_local_path = __DIR__ . '/banners/'; 

// Define o NOME FIXO do arquivo da logo principal.
$fixed_logo_filename = 'logo.png'; 
$default_logo_filename = 'logo_padrao.png'; 

// Garante que o diretório de uploads existe no servidor
if (!file_exists($target_dir_local_path)) {
    mkdir($target_dir_local_path, 0777, true); 
}

// Lógica de inicialização do DB, só insere a linha se não existir
// Isso é importante para garantir que a linha para a URL externa exista
$db->exec('CREATE TABLE IF NOT EXISTS logo(id INT PRIMARY KEY NOT NULL, path TEXT)');
$rows = $db->query('SELECT COUNT(*) as count FROM logo');
$numRows = $rows->fetchArray(SQLITE3_ASSOC);
if ($numRows['count'] == 0) {
    $db->exec("INSERT INTO logo(id, path) VALUES('1', '')");
}

// Pega o caminho (URL externa ou vazio) do DB
$res = $db->query("SELECT * FROM " . $table_name . " WHERE id='1'");
$row = $res->fetchArray();
$logo_path_from_db = $row["path"]; // Este vai ter a URL externa ou vazio

// --- Processamento do Formulário de Upload ---
if (isset($_POST['submit'])) {
    $redirect = "logo.php?r=erro_att";
    if ($_POST['type'] == '0') { // Se o tipo for "upload de arquivo" (para logo.png local)
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $target_file_local_path_full = $target_dir_local_path . $fixed_logo_filename;
        $allowed_extensions = ['gif', 'png', 'jpg', 'jpeg'];

        if (in_array($file_ext, $allowed_extensions)) {
            if (file_exists($target_file_local_path_full)) {
                unlink($target_file_local_path_full);
            }
            
            // Função para comprimir a logo preservando transparência
            $source = $_FILES['image']['tmp_name'];
            $destination = $target_file_local_path_full;
            
            $info = getimagesize($source);
            $uploaded = false;
            
            if ($info && $info['mime'] == 'image/jpeg') {
                $image = imagecreatefromjpeg($source);
                $uploaded = imagejpeg($image, $destination, 85);
                imagedestroy($image);
            } elseif ($info && $info['mime'] == 'image/png') {
                $image = imagecreatefrompng($source);
                imagealphablending($image, false);
                imagesavealpha($image, true);
                $uploaded = imagepng($image, $destination, 8);
                imagedestroy($image);
            } else {
                $uploaded = move_uploaded_file($source, $destination);
            }
            
            if ($uploaded) {
                $db->exec("UPDATE logo SET path='' WHERE id='1'");
                $redirect = "logo.php?r=atualizado";
            }
        }
    } else { // Se o tipo for "URL externa"
        $url = $_POST['path'];
        $file_ext = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $allowed_extensions = ['gif', 'png', 'jpg', 'jpeg', 'mp4'];

        if (in_array($file_ext, $allowed_extensions)) {
            if (file_exists($target_dir_local_path . $fixed_logo_filename)) {
                unlink($target_dir_local_path . $fixed_logo_filename);
            }
            $db->exec("UPDATE logo SET path='" . SQLite3::escapeString($url) . "' WHERE id='1'");
            $redirect = "logo.php?r=atualizado";
        }
    }
    $db->close();
    header("Location: $redirect");
    exit();
}

// --- LÓGICA PARA CONSTRUIR O CAMINHO DA LOGO PARA PRÉ-VISUALIZAÇÃO UNIVERSAL (com cache-buster) ---
$current_logo_display_path = ''; 

// Pega o esquema (http ou https) e o host (domínio.com ou sub.domínio.com)
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];

// Pega o caminho web do script atual (ex: /iboteste/logo.php ou apenas /logo.php)
$script_name = $_SERVER['SCRIPT_NAME']; // Ex: /iboteste/logo.php
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
if (!empty($logo_path_from_db) && (strpos($logo_path_from_db, 'http://') === 0 || strpos($logo_path_from_db, 'https://') === 0)) {
    $current_logo_display_path = $logo_path_from_db; 
} 
// 2. Segunda Prioridade: Tenta usar a logo.png local
else if (file_exists($target_dir_local_path . $fixed_logo_filename)) {
    // Usa o timestamp do arquivo como cache-buster para forçar atualização quando o arquivo mudar
    $file_modified_time = filemtime($target_dir_local_path . $fixed_logo_filename);
    
    // Constrói a URL completa para a logo.png local
    $logo_local_web_url = $scheme . '://' . $host . $script_dir_web_path . '/banners/' . $fixed_logo_filename . '?v=' . $file_modified_time;
    $current_logo_display_path = $logo_local_web_url;
} 
// 3. Última Opção: Usa a logo padrão
else {
    // Constrói a URL da logo padrão
    $default_logo_web_url = $scheme . '://' . $host . $script_dir_web_path . '/assets/images/' . $default_logo_filename . '?v=' . $cache_buster;
    $current_logo_display_path = $default_logo_web_url;
}

// DEBUG: Descomente as linhas abaixo para verificar os caminhos gerados
// echo "<!-- DEBUG INFO:\n";
// echo "Scheme: $scheme\n";
// echo "Host: $host\n";
// echo "Script Dir: $script_dir_web_path\n";
// echo "Target Dir Local: $target_dir_local_path\n";
// echo "File exists: " . (file_exists($target_dir_local_path . $fixed_logo_filename) ? 'YES' : 'NO') . "\n";
// echo "Final URL: $current_logo_display_path\n";
// echo "-->\n";

// --- FIM DA LÓGICA DE PRÉ-VISUALIZAÇÃO ---

?>

<div class="card radius-10">
        <div class="card-body">
           <center><h4 class="card-title">Logo Atual:</h4>
                                 <img class="preview-image" src="<?php echo $current_logo_display_path; ?>" alt="Uploaded Image" width="20%" height="auto"></center>
           <form class="forms-sample" method="post" enctype="multipart/form-data">
                 <div class="form-group mb-4">
                     <label for="url"><strong>Selecione como quer adicionar a imagem Logo</strong></label>
                     <div class="form-group mb-4">
                         <select class="form-control type" id="type" name="type">
                             <option data-value="op0" value="0" selected>Enviar o arquivo de imagem</option>
                             <option data-value="1">Digitar URL externa da imagem</option>
                         </select>
                     </div>
                 </div>

                 <div class="activeu">
                     <div class="form-group mb-4">
                         <label class="control-label"><strong>Digite o endereço da imagem</strong></label>
                         <div class="form-group mb-4">
                             <input class="form-control" type="hidden" name="del" value="<?php echo $logo_path_from_db ?>" hidden>
                             <input class="form-control" type="text" name="path" value="<?php echo (strpos($current_logo_display_path, 'http') === 0 && $logo_path_from_db !== '') ? $logo_path_from_db : ''; ?>" placeholder="Digite o endereço do arquivo de imagem no formato (.gif, .png ou .jpg)">
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
                title: 'Logo Atualizada com Sucesso!'
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
                title: 'Erro ao atualizar a Logo!'
            })</script>";
            break;
    }
}
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
//hide activecode form
      $('.actived').show();
      $('.activeu').hide();

//Show/hide activecode select
$(document).ready(function(){
  $('.type').change(function(){
    if($('.type').val() < 1) {
      $('.actived').show();
      $('.activeu').hide();
    }else {
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