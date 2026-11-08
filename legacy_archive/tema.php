<?php
// Caminho para o arquivo JSON
$jsonFile = './api/theme_change/Setting.json';
// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar se o tema foi selecionado
    if (isset($_POST['theme'])) {
        $selectedTheme = $_POST['theme'];

        // Carregar o conteúdo atual do arquivo JSON
        $jsonData = file_get_contents($jsonFile);
        $data = json_decode($jsonData, true);

        // Verificar se há dados no JSON
        if (!empty($data)) {
            // Atualizar o tema no JSON
            $data[0]['PanalData'] = $selectedTheme;

            // Converter os dados atualizados para o formato JSON
            $updatedJsonData = json_encode($data, JSON_PRETTY_PRINT);

            // Gravar os dados atualizados no arquivo JSON
            file_put_contents($jsonFile, $updatedJsonData);

            // Redirecionar para evitar o envio repetido do formulário
            header("Location: tema.php?r=atualizado");
            exit;
        }
    }
}

// Carregar o conteúdo do arquivo JSON
$jsonData = file_get_contents($jsonFile);

// Decodificar o JSON
$data = json_decode($jsonData, true);

// Verificar se há dados no JSON
if (!empty($data)) {
    $theme = $data[0]['PanalData']; // Obter o valor do tema do JSON

    // Array de opções de temas
    $themes = [
    "theme_d" => "Tema 1",
    "theme_2" => "Tema 2",
    "theme_3" => "Tema 3",
    "theme_4" => "Tema 4",
    "theme_5" => "Tema 5",
    "theme_6" => "Tema 6",
    "theme_7" => "Tema 7",
    "theme_8" => "Tema 8",
    "theme_9" => "Tema 9"
    ];
}
?>
<?php include './includes/header.php';?>
<div class="card radius-10">
    <div class="card-body">
    <form method="post">
        <div class="form-group mb-4">
            <label class="control-label requiredField" for="aa">
                <strong>Selecione o Tema</strong>
            </label>
            <select class="form-control type" id="select" name="theme">
                <?php foreach ($themes as $value => $label) { ?>
                    <option value="<?php echo $value; ?>" <?php echo ($theme == $value) ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Enviar</button>
    </form>
</div>
</div>
<div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 row-cols-xl-4">
<?php foreach ($themes as $value => $label) { ?>
    <div class="col">
        <div class="card radius-10" style="border: 8px solid transparent; <?php echo ($theme == $value) ? 'border-left-color: #000000;' : ''; ?>">
            <div class="card-body">
                <h6 class="card-title"><?php echo $label; ?></h6>
                <?php
                    $imagePath = ''; // Defina o caminho da imagem correspondente a cada tema
                    if ($value === 'theme_d') {
                        $imagePath = './img_custom/layout/d.jpg';
                    } elseif ($value === 'theme_1') {
                        $imagePath = './img_custom/layout/1.jpg';
                    } elseif ($value === 'theme_2') {
                        $imagePath = './img_custom/layout/2.jpg';
                    } elseif ($value === 'theme_3') {
                        $imagePath = './img_custom/layout/3.jpg';
                    } elseif ($value === 'theme_4') {
                        $imagePath = './img_custom/layout/4.jpg';
                    } elseif ($value === 'theme_5') {
                        $imagePath = './img_custom/layout/5.jpg';
                    } elseif ($value === 'theme_6') {
                        $imagePath = './img_custom/layout/6.jpg';
                     } elseif ($value === 'theme_7') {
                        $imagePath = './img_custom/layout/7.jpg';
                    } elseif ($value === 'theme_8') {
                        $imagePath = './img_custom/layout/8.jpg';
                    } elseif ($value === 'theme_9') {
                        $imagePath = './img_custom/layout/9.jpg';
                    }
                    
                    // Continue para os outros temas
                ?>
                <img src="<?php echo $imagePath; ?>" class="card-img-top" alt="...">
            </div>
        </div>
    </div>
<?php } ?>
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
        title: 'Tema Atualizado com Sucesso!'
        })</script>";
    }
}
?>
<?php include './includes/footer.php';?>