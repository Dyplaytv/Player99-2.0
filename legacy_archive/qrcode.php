<?php
// Inicia o buffer de saída para controlar cabeçalhos
ob_start();

// Conexão com o banco de dados
try {
    $db = new SQLite3("./api/db/studiolivecode_qrcode.db");
} catch (Exception $e) {
    die("Erro ao conectar ao banco de dados: " . $e->getMessage());
}

// Nome da tabela
$table_name = "qrcode";

// Caminho relativo dinâmico para payment_go.php
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['SCRIPT_NAME']) . '/api/payment/payment_go.php';

// Cria a tabela se não existir
$db->exec("CREATE TABLE IF NOT EXISTS $table_name (id INTEGER PRIMARY KEY, qrcode TEXT)") or die("Erro ao criar tabela: " . $db->lastErrorMsg());

// Verifica se a tabela está vazia e insere o padrão
$res = $db->query("SELECT COUNT(*) as count FROM $table_name");
if ($res === false) {
    die("Erro na consulta de contagem: " . $db->lastErrorMsg());
}
$row = $res->fetchArray();
$numRows = $row["count"];
if ($numRows == 0) {
    $db->exec("INSERT INTO $table_name (id, qrcode) VALUES(1, '$base_url')") or die("Erro ao inserir padrão: " . $db->lastErrorMsg());
}

// Busca o QR code atual
$res = $db->query("SELECT qrcode FROM $table_name WHERE id=1");
if ($res === false) {
    die("Erro na consulta de QR code: " . $db->lastErrorMsg());
}
$row = $res->fetchArray();
$qrcode = $row ? $row["qrcode"] : $base_url;

if (isset($_POST["submit"])) {
    // Atualiza o QR code com o link fornecido pelo usuário
    $newQrcode = filter_var($_POST["qrcode"], FILTER_SANITIZE_URL);
    if (!empty($newQrcode)) {
        $db->exec("UPDATE $table_name SET qrcode='$newQrcode' WHERE id=1") or die("Erro ao atualizar: " . $db->lastErrorMsg());
    }
    $db->close();
    header("Location: qrcode.php?r=atualizado");
    exit;
}
?>

<?php include './includes/header.php';?>
<div class="card radius-10">
    <div class="card-body">
        <center><h4 class="card-title">QR Code Atual:</h4>
        <?php
        $qrcodeUrl = "https://image-charts.com/chart?chs=300x300&cht=qr&chl=" . urlencode($qrcode);
        if (!empty($qrcode)) {
            ?>
            <img class="preview-image" src="<?= htmlspecialchars($qrcodeUrl) ?>" alt="Uploaded Image" width="30%" height="auto">
            <?php
        } else {
            ?>
            <img class="preview-image" src="assets/images/noqr.png" alt="No QR Code Available" width="30%" height="auto">
            <?php
        }
        ?>
        </center>
        <div style="text-align: center; margin-bottom: 14px;">
            <span style="color: red; animation: blink 1s step-end infinite; font-weight: bold;">
                Copie o Link Renovação e Cole abaixo!
            </span>
            <style>
                @keyframes blink {
                    50% { opacity: 0; }
                }
            </style>
        </div>
        <form class="forms-sample" method="post" enctype="multipart/form-data">
            <div class="form-group mb-4">
                <br>
                <input type="text" class="form-control" name="qrcode" id="qrcode" value="<?= htmlspecialchars($qrcode) ?>" placeholder="Insira um link (opcional)">
            </div>
            <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Enviar</button>
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
                title: 'QRCODE Atualizado com Sucesso!'
            })</script>";
            break;
    }
}
?>

<?php include './includes/footer.php';?>
<?php ob_end_flush(); // Encerra o buffer de saída ?>