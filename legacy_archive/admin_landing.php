<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["U"])) {
    header("location: /login");
    exit;
}

$id = $_SESSION['id'];
$db = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_users.db");

// Verificar se é admin
$query = $db->query("SELECT admin FROM users WHERE id = $id");
$userData = $query ? $query->fetchArray(SQLITE3_ASSOC) : null;

$isAllowed = !empty($_SESSION['is_admin']) || ($userData && $userData['admin'] == 1);
if (!$isAllowed) {
    header("location: /admin/dashboard");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['img1'])) {
    $targetDir = __DIR__ . "/uploads/landing/";
    
    // Cria o diretório se não existir (evita erro no git pull)
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Função para comprimir a imagem
    function compressAndSaveImage($source, $destination, $quality = 75) {
        $info = getimagesize($source);
        if (!$info) return move_uploaded_file($source, $destination);

        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            // Qualidade PNG vai de 0 a 9 (9 é máxima compressão, mais leve)
            imagepng($image, $destination, 8);
        } else {
            // Outros formatos, move normal
            move_uploaded_file($source, $destination);
            return true;
        }
        
        if (isset($image)) imagedestroy($image);
        return true;
    }
    
    // Processa Imagem 1
    if (isset($_FILES['img1']) && $_FILES['img1']['error'] == 0) {
        compressAndSaveImage($_FILES['img1']['tmp_name'], $targetDir . "img1.jpg");
    }
    // Processa Imagem 2
    if (isset($_FILES['img2']) && $_FILES['img2']['error'] == 0) {
        compressAndSaveImage($_FILES['img2']['tmp_name'], $targetDir . "img2.jpg");
    }
    // Processa Imagem 3
    if (isset($_FILES['img3']) && $_FILES['img3']['error'] == 0) {
        compressAndSaveImage($_FILES['img3']['tmp_name'], $targetDir . "img3.jpg");
    }
    
    // Processa Imagens Parceiros
    if (isset($_FILES['parc1']) && $_FILES['parc1']['error'] == 0) {
        compressAndSaveImage($_FILES['parc1']['tmp_name'], $targetDir . "parc1.jpg");
    }
    if (isset($_FILES['parc2']) && $_FILES['parc2']['error'] == 0) {
        compressAndSaveImage($_FILES['parc2']['tmp_name'], $targetDir . "parc2.jpg");
    }
    if (isset($_FILES['parc3']) && $_FILES['parc3']['error'] == 0) {
        compressAndSaveImage($_FILES['parc3']['tmp_name'], $targetDir . "parc3.jpg");
    }
    
    // Processa Imagens Afiliados
    if (isset($_FILES['afil1']) && $_FILES['afil1']['error'] == 0) {
        compressAndSaveImage($_FILES['afil1']['tmp_name'], $targetDir . "afil1.jpg");
    }
    if (isset($_FILES['afil2']) && $_FILES['afil2']['error'] == 0) {
        compressAndSaveImage($_FILES['afil2']['tmp_name'], $targetDir . "afil2.jpg");
    }
    if (isset($_FILES['afil3']) && $_FILES['afil3']['error'] == 0) {
        compressAndSaveImage($_FILES['afil3']['tmp_name'], $targetDir . "afil3.jpg");
    }
    
    // Processa Fundo do Cadastro
    if (isset($_FILES['cad_bg']) && $_FILES['cad_bg']['error'] == 0) {
        compressAndSaveImage($_FILES['cad_bg']['tmp_name'], $targetDir . "cadastro_bg.jpg");
    }
    
    // Processa Fundo do Login Cliente
    if (isset($_FILES['login_bg']) && $_FILES['login_bg']['error'] == 0) {
        compressAndSaveImage($_FILES['login_bg']['tmp_name'], $targetDir . "login_cliente_bg.jpg");
    }
    
    $msg = '<div class="alert alert-success">Páginas atualizadas com sucesso! <a href="index.php" target="_blank">Ver Home</a> | <a href="cadastro.php" target="_blank">Ver Cadastro</a> | <a href="login_cliente.php" target="_blank">Ver Login Cliente</a></div>';
}

function getImgTime($file) {
    $path = __DIR__ . "/uploads/landing/" . $file;
    if (file_exists($path)) {
        return "?v=" . filemtime($path);
    }
    return "";
}
?>
<?php include 'includes/header.php'; ?>

<style>
    .img-preview { width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 10px; border: 1px solid #4721fb; }
</style>

<div class="container mt-4 mb-5">
    <h2><i class="fas fa-edit text-primary"></i> Editar Página Inicial e Parceiros</h2>
    <p>Faça o upload de até 3 imagens para formar a Página Inicial e 3 para a Página de Parceiros.</p>

    <?php echo $msg; ?>

    <form action="" method="post" enctype="multipart/form-data">
        
        <!-- PÁGINA INICIAL -->
        <div class="card bg-gray-800 text-white shadow-lg mt-4">
            <div class="card-header bg-dark border-bottom border-secondary">
                <h4 class="mb-0 text-info">Imagens da Página Inicial (Home)</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- IMG 1 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 1 (Topo)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/img1.jpg")): ?>
                            <img src="uploads/landing/img1.jpg<?php echo getImgTime('img1.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="img1" class="form-control" accept="image/jpeg, image/png">
                    </div>

                    <!-- IMG 2 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 2 (Meio)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/img2.jpg")): ?>
                            <img src="uploads/landing/img2.jpg<?php echo getImgTime('img2.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="img2" class="form-control" accept="image/jpeg, image/png">
                    </div>

                    <!-- IMG 3 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 3 (Fim)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/img3.jpg")): ?>
                            <img src="uploads/landing/img3.jpg<?php echo getImgTime('img3.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="img3" class="form-control" accept="image/jpeg, image/png">
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA DE PARCEIROS -->
        <div class="card bg-gray-800 text-white shadow-lg mt-4">
            <div class="card-header bg-dark border-bottom border-secondary">
                <h4 class="mb-0 text-warning">Imagens da Página de Parceiros</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- PARC 1 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 1 (Topo)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/parc1.jpg")): ?>
                            <img src="uploads/landing/parc1.jpg<?php echo getImgTime('parc1.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="parc1" class="form-control" accept="image/jpeg, image/png">
                    </div>

                    <!-- PARC 2 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 2 (Meio)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/parc2.jpg")): ?>
                            <img src="uploads/landing/parc2.jpg<?php echo getImgTime('parc2.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="parc2" class="form-control" accept="image/jpeg, image/png">
                    </div>

                    <!-- PARC 3 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 3 (Fim)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/parc3.jpg")): ?>
                            <img src="uploads/landing/parc3.jpg<?php echo getImgTime('parc3.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="parc3" class="form-control" accept="image/jpeg, image/png">
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA DE AFILIADOS -->
        <div class="card bg-gray-800 text-white shadow-lg mt-4">
            <div class="card-header bg-dark border-bottom border-secondary">
                <h4 class="mb-0 text-success">Imagens da Página de Afiliados</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- AFIL 1 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 1 (Topo)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/afil1.jpg")): ?>
                            <img src="uploads/landing/afil1.jpg<?php echo getImgTime('afil1.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="afil1" class="form-control" accept="image/jpeg, image/png">
                    </div>

                    <!-- AFIL 2 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 2 (Meio)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/afil2.jpg")): ?>
                            <img src="uploads/landing/afil2.jpg<?php echo getImgTime('afil2.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="afil2" class="form-control" accept="image/jpeg, image/png">
                    </div>

                    <!-- AFIL 3 -->
                    <div class="col-md-4 mb-4 text-center">
                        <h5>Bloco 3 (Fim)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/afil3.jpg")): ?>
                            <img src="uploads/landing/afil3.jpg<?php echo getImgTime('afil3.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="afil3" class="form-control" accept="image/jpeg, image/png">
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA DE CADASTRO -->
        <div class="card bg-gray-800 text-white shadow-lg mt-4">
            <div class="card-header bg-dark border-bottom border-secondary">
                <h4 class="mb-0 text-info">Imagem de Fundo - Cadastro</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4 text-center mx-auto">
                        <h5>Imagem de Fundo (1920x1080 recomendado)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/cadastro_bg.jpg")): ?>
                            <img src="uploads/landing/cadastro_bg.jpg<?php echo getImgTime('cadastro_bg.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="cad_bg" class="form-control" accept="image/jpeg, image/png">
                    </div>
                </div>
            </div>
        </div>

        <!-- PÁGINA DE LOGIN CLIENTE -->
        <div class="card bg-gray-800 text-white shadow-lg mt-4">
            <div class="card-header bg-dark border-bottom border-secondary">
                <h4 class="mb-0 text-info">Imagem de Fundo - Login Área do Cliente</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4 text-center mx-auto">
                        <h5>Imagem de Fundo (1920x1080 recomendado)</h5>
                        <?php if (file_exists(__DIR__ . "/uploads/landing/login_cliente_bg.jpg")): ?>
                            <img src="uploads/landing/login_cliente_bg.jpg<?php echo getImgTime('login_cliente_bg.jpg'); ?>" class="img-preview">
                        <?php else: ?>
                            <div class="img-preview d-flex align-items-center justify-content-center bg-secondary">Sem imagem</div>
                        <?php endif; ?>
                        <input type="file" name="login_bg" class="form-control" accept="image/jpeg, image/png">
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save"></i> Salvar Imagens</button>
        </div>
    </form>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
