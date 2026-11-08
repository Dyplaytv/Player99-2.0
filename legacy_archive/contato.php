<?php

$file = "./api/language.json";
// Ler o conteúdo do arquivo JSON existente
$jsondata = file_get_contents($file);
// Decodificar o JSON em um array associativo
$data = json_decode($jsondata, true);
$json = $data["words"];
$title = $json["to_continue"];
$title = $json["to_add_manage"];
$title = $json["no_playlist_description"];
if (isset($_POST["submit"])) {

// Dados a serem substituídos
$replacementData = [
    'words' => [
        "to_continue" => $_POST["title"],
        "to_add_manage" => $_POST["title"],
        "no_playlist_description" => $_POST["title"]
    ]
];
// Substituir os dados no array existente
$newData = array_replace_recursive($data, $replacementData);
// Converter o array de volta para JSON
$newJsonData = json_encode($newData, JSON_PRETTY_PRINT);
// Escrever o JSON atualizado de volta no arquivo
file_put_contents($file, $newJsonData);
    
    header("Location: contato.php?r=atualizado");
}

?>
<?php include './includes/header.php';?>
<div class="card radius-10">
          <div class="card-body">
                        <h4 class="card-title">Editar Contato</h4>
                        <form class="forms-sample" method="post">
                            <div class="form-group mb-4">
                                <label class="control-label" for="title">
                                    <strong>Telefone ou Site</strong>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="Contato do Aplicativo" name="title" value="<?php echo $title; ?>">
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
  title: 'Contato Atualizado com Sucesso!'
})</script>";
              }
            }
            ?>
				
<?php include './includes/footer.php';?>