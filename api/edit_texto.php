<?php include 'includes/header.php'; 

if (file_exists('mensagem.txt')) {
    $data = json_decode(file_get_contents('mensagem.txt'), true);
    $mensagem = htmlspecialchars($data['mensagem']);
    $cor = htmlspecialchars($data['cor']);
    $bgcolor = htmlspecialchars($data['bgcolor']);
    $opacity = htmlspecialchars($data['opacity']);
    $fontSize = isset($data['fontSize']) ? htmlspecialchars($data['fontSize']) : "16";
    $efeito = isset($data['efeito']) ? htmlspecialchars($data['efeito']) : "scroll"; // Efeito padrão
} else {
    $mensagem = "Nenhuma mensagem gravada.";
    $cor = "#000000";
    $bgcolor = "#ffffff";
    $opacity = "1";
    $fontSize = "16";
    $efeito = "scroll"; // Efeito padrão
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        .color-picker {
            margin-top: 10px;
        }

        iframe {
            width: 100%;
            height: 30px;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: transparent;
        }

        .pickr {
            margin-top: 10px;
        }
    </style>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@simonwep/pickr/dist/themes/classic.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/@simonwep/pickr"></script>
    <script>
        function updatePreview() {
            const mensagem = document.getElementById('mensagem').value;
            const cor = document.getElementById('cor').value;
            const bgColor = document.getElementById('bgcolor').value;
            const opacity = document.getElementById('opacity').value;
            const fontSize = document.getElementById('fontSize').value;
            const efeito = document.getElementById('efeito').value; // Captura o valor do efeito
            const iframe = document.getElementById('preview');

            const url = `api/msg.php?mensagem=${encodeURIComponent(mensagem)}&cor=${encodeURIComponent(cor)}&bgColor=${encodeURIComponent(bgColor)}&opacity=${encodeURIComponent(opacity)}&fontSize=${encodeURIComponent(fontSize)}&efeito=${encodeURIComponent(efeito)}`;
            iframe.src = url;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const pickrTextColor = Pickr.create({
                el: '#cor-picker',
                theme: 'classic',
                default: '<?php echo $cor; ?>',
                components: {
                    preview: true,
                    opacity: true,
                    hue: true,
                    interaction: {
                        hex: true,
                        rgba: true,
                        input: true,
                        save: true
                    }
                }
            });

            pickrTextColor.on('save', (color) => {
                document.getElementById('cor').value = color.toHEXA().toString();
                updatePreview();
            });

            const pickrBgColor = Pickr.create({
                el: '#bgcolor-picker',
                theme: 'classic',
                default: '<?php echo $bgcolor; ?>',
                components: {
                    preview: true,
                    opacity: true,
                    hue: true,
                    interaction: {
                        hex: true,
                        rgba: true,
                        input: true,
                        save: true
                    }
                }
            });

            pickrBgColor.on('save', (color) => {
                document.getElementById('bgcolor').value = color.toHEXA().toString();
                updatePreview();
            });

            document.getElementById('opacity').addEventListener('input', function() {
                document.getElementById('opacityValue').textContent = this.value;
                updatePreview();
            });

            document.getElementById('fontSize').addEventListener('input', function() {
                document.getElementById('fontSizeValue').textContent = this.value + 'px';
                updatePreview();
            });
        });
    </script>
</head>
<body>
    <div class="col-md-6 mx-auto">
        <div class="card-body">
            <div class="card bg-primary text-white">
                <div class="card-header card-header-warning">
                    <center>
                        <h2>Enviar Mensagem de Texto</h2>
                    </center>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <h5><i class="icon icon-user"></i> Definir Mensagem</h5>
                    </div>
                    <br>
                    <form action="salvar_mensagem.php" method="post">
                        <div>
                            <label for="mensagem" style="font-weight: bold;">Mensagem:</label>
                            <input class="form-control" type="text" id="mensagem" name="mensagem" value="<?php echo $mensagem; ?>" required>
                        </div>
                        <br>
                        <div>
                            <label for="cor" style="font-weight: bold;">Cor do Texto:</label>
                            <input class="form-control" type="text" id="cor" name="cor" value="<?php echo $cor; ?>" required>
                            <div id="cor-picker" class="pickr"></div><br>

                            <label style="font-weight: bold;" for="bgcolor">Cor de Fundo:</label>
                            <input class="form-control" type="text" id="bgcolor" name="bgcolor" value="<?php echo $bgcolor; ?>" required>
                            <div id="bgcolor-picker" class="pickr"></div><br>

                            <label style="font-weight: bold;" for="opacity">Opacidade:</label>
                            <input type="range" id="opacity" name="opacity" min="0" max="1" step="0.1" value="<?php echo $opacity; ?>" required><span id="opacityValue"><?php echo $opacity; ?></span><br>
                            
                            <label style="font-weight: bold;" for="fontSize">Tamanho do Texto:</label>
                            <input type="number" id="fontSize" name="fontSize" min="8" max="72" value="<?php echo $fontSize; ?>" required><span id="fontSizeValue"><?php echo $fontSize; ?>px</span><br><br>

                            <label for="efeito" style="font-weight: bold;">Efeito:</label>
                            <select id="efeito" name="efeito" class="form-control" required>
                                <option value="scroll" <?php echo ($efeito === 'scroll' ? 'selected' : ''); ?>>Rolante</option>
                                <option value="static" <?php echo ($efeito === 'static' ? 'selected' : ''); ?>>Parado</option>
                                <option value="blink" <?php echo ($efeito === 'blink' ? 'selected' : ''); ?>>Piscando</option>
                                <option value="flash" <?php echo ($efeito === 'flash' ? 'selected' : ''); ?>>Flash</option>
                            </select><br>

                            <button class="btn btn-info" type="submit">Enviar</button>
                        </div>
                    </form>
                </div>
                <br>
                <center><h5 class="card-title">Mensagem a ser Exibida:</h5><i class="fa-regular fa-hand-point-down"></i></center>
                <iframe id="preview" src="api/msg.php"></iframe>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
