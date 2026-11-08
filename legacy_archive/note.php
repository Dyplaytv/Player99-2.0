<?php

$file = "./api/language.json";
// Ler o conteúdo do arquivo JSON existente
$jsondata = file_get_contents($file);
// Decodificar o JSON em um array associativo
$data = json_decode($jsondata, true);
$json = $data["words"];
$file1 = "./api/note.json";
// Ler o conteúdo do arquivo JSON existente
$jsondata1 = file_get_contents($file1);
// Decodificar o JSON em um array associativo
$data1 = json_decode($jsondata1, true);
$json = $data["words"];
$title = $json["ibo_pro_description"];
$content = $json["ibo_pro_general_player"];
if (isset($_POST["submit"])) {

// Dados a serem substituídos
$replacementData = [
    'words' => [
        "ibo_pro_description" => $_POST["title"],
        "ibo_pro_general_player" => $_POST["content"]
    ]
];
// Substituir os dados no array existente
$newData = array_replace_recursive($data, $replacementData);
// Converter o array de volta para JSON
$newJsonData = json_encode($newData, JSON_PRETTY_PRINT);
// Escrever o JSON atualizado de volta no arquivo
file_put_contents($file, $newJsonData);

// Dados a serem substituídos    
$replacementData1 = [
    "title" => $_POST["title"], 
    "content" => $_POST["content"]
];
// Substituir os dados no array existente
$newData1 = array_replace_recursive($data1, $replacementData1);
// Converter o array de volta para JSON
$newJsonData1 = json_encode($newData1, JSON_UNESCAPED_UNICODE);
// Escrever o JSON atualizado de volta no arquivo
file_put_contents($file1, $newJsonData1);
    
    header("Location: note.php?r=atualizado");
}

?>
<?php include './includes/header.php';?>
<div class="card radius-10">
          <div class="card-body">
                        <h4 class="card-title">Editar Mensagem</h4>
                        <form class="forms-sample" method="post">
                            <div class="form-group mb-4">
                                <label class="control-label" for="title">
                                    <strong>Título</strong>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Título da Mensagem" name="title" value="<?php echo $title; ?>">
                                </div>
                            </div>
                            <div class="form-group mb-4">
                                <label class="control-label" for="content">
                                    <strong>Mensagem</strong>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Mensagem" name="content" value="<?php echo $content; ?>">
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
  title: 'Mensagem Atualizada com Sucesso!'
})</script>";
              }
            }
            ?>
				
<?php include './includes/footer.php';?>