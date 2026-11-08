<?php include 'includes/header.php'; 

if (file_exists('mensagem.txt')) {
    $data = json_decode(file_get_contents('mensagem.txt'), true);
    $mensagem = htmlspecialchars($data['mensagem']);
    $cor = htmlspecialchars($data['cor']);
    $bgcolor = htmlspecialchars($data['bgcolor']);
    $opacity = htmlspecialchars($data['opacity']);
    $fontSize = isset($data['fontSize']) ? htmlspecialchars($data['fontSize']) : "16";
    $efeito = isset($data['efeito']) ? htmlspecialchars($data['efeito']) : "scroll";
} else {
    $mensagem = "Nenhuma mensagem gravada.";
    $cor = "#000000";
    $bgcolor = "#ffffff";
    $opacity = "1";
    $fontSize = "16";
    $efeito = "scroll";
}
?>
<style>
    iframe { width: 100%; height: 30px; border: 1px solid #ccc; border-radius: 10px; background-color: transparent; }
    .pickr { margin-top: 10px; }
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
        const efeito = document.getElementById('efeito').value;
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
                preview: true, opacity: true, hue: true,
                interaction: { hex: true, rgba: true, input: true, save: true }
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
                preview: true, opacity: true, hue: true,
                interaction: { hex: true, rgba: true, input: true, save: true }
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

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card radius-10">
            <div class="card-body">
                <h4 class="card-title">TEXTO ADM PLAY</h4>
                <form action="admplay_salvar_texto.php" method="post">
                    <div class="form-group mb-3">
                        <label for="mensagem" style="font-weight: bold;">Mensagem:</label>
                        <input class="form-control" type="text" id="mensagem" name="mensagem" value="<?php echo $mensagem; ?>" required>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label for="cor" style="font-weight: bold;">Cor do Texto:</label>
                        <input class="form-control" type="text" id="cor" name="cor" value="<?php echo $cor; ?>" required>
                        <div id="cor-picker" class="pickr"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-weight: bold;" for="bgcolor">Cor de Fundo:</label>
                        <input class="form-control" type="text" id="bgcolor" name="bgcolor" value="<?php echo $bgcolor; ?>" required>
                        <div id="bgcolor-picker" class="pickr"></div>
                    </div>

                    <div class="form-group mb-3">
                        <label style="font-weight: bold;" for="opacity">Opacidade:</label><br>
                        <input type="range" id="opacity" name="opacity" min="0" max="1" step="0.1" value="<?php echo $opacity; ?>" required>
                        <span id="opacityValue"><?php echo $opacity; ?></span>
                    </div>
                    
                    <div class="form-group mb-3">
                        <label style="font-weight: bold;" for="fontSize">Tamanho do Texto:</label><br>
                        <input type="number" id="fontSize" name="fontSize" min="8" max="72" value="<?php echo $fontSize; ?>" required>
                        <span id="fontSizeValue"><?php echo $fontSize; ?>px</span>
                    </div>

                    <div class="form-group mb-3">
                        <label for="efeito" style="font-weight: bold;">Efeito:</label>
                        <select id="efeito" name="efeito" class="form-control" required>
                            <option value="scroll" <?php echo ($efeito === 'scroll' ? 'selected' : ''); ?>>Rolante</option>
                            <option value="static" <?php echo ($efeito === 'static' ? 'selected' : ''); ?>>Parado</option>
                            <option value="blink" <?php echo ($efeito === 'blink' ? 'selected' : ''); ?>>Piscando</option>
                            <option value="typing" <?php echo ($efeito === 'typing' ? 'selected' : ''); ?>>Digitando</option>
                            <option value="flash" <?php echo ($efeito === 'flash' ? 'selected' : ''); ?>>Flash</option>
                        </select>
                    </div>

                    <button class="btn btn-info" type="submit">Enviar</button>
                </form>

                <hr>
                <div class="text-center mt-3">
                    <h5 class="card-title">Mensagem a ser Exibida:</h5>
                    <i class="bx bx-hand-down fs-4"></i>
                </div>
                <iframe id="preview" src="api/msg.php"></iframe>
            </div>
        </div>
    </div>
</div>

<?php 
if (isset($_GET["r"]) && $_GET["r"] == "atualizado") {
    echo "<script>
    const Toast = Swal.mixin({
      toast: true, position: 'bottom', showConfirmButton: false, timer: 2000
    });
    Toast.fire({ icon: 'success', title: 'Mensagem Atualizada com Sucesso!' });
    </script>";
}
?>

<?php include 'includes/footer.php'; ?>
