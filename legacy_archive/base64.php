<?php
ini_set('display_errors', 0);
include('includes/header.php');

// Obter o domínio atual
$dominio_completo = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];

// Extrair apenas o nome do domínio
$parsed_url = parse_url($dominio_completo);
$host = $parsed_url['host'];

// Remover o prefixo "www." se existir
$host = preg_replace('/^www\./', '', $host);

// Remover a última parte do domínio, como ".com" ou ".lol"
$dominio_final = preg_replace('/\.[a-z]{2,}$/', '', $host);

// Converter para Base64 automaticamente
$base64_encoded = base64_encode($dominio_final);
?>

<style>
    .custom-button {
        padding: 10px 20px;
    }

    .custom-input {
        color: blue;
    }

    .copy-button {
        margin-top: 10px;
    }
</style>

<div class="col-md-6 mx-auto">
    <div class="card-body">
        <div class="card ctcard">
            <div class="card-header">
                <center>
                    <h2><i class="fa fa-file-image-o"></i> Conexão API</h2>
                </center>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="input_text">Texto para converter:</label>
                        <textarea class="form-control" name="input_text" readonly><?php echo htmlspecialchars($dominio_final); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Resultado em Base64:</label>
                        <textarea class="form-control" id="base64_result" readonly><?php echo $base64_encoded; ?></textarea>
                    </div>

                    <button type="button" class="btn btn-success copy-button" onclick="copiarTexto()">Copiar Base64</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function copiarTexto() {
        var copyText = document.getElementById("base64_result");
        copyText.select();
        document.execCommand("copy");
        alert("Texto copiado: " + copyText.value);
    }
</script>

<?php include('includes/footer.php'); ?>

</body>
</html>
