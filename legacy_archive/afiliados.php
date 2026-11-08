<?php
// afiliados.php - Página de Afiliados (Imagens Empilhadas)

// Força o ref para afiliado nesta página
$linkCadastro = "cadastro.php?ref=afiliado";

function getImgTime($file) {
    $path = __DIR__ . "/uploads/landing/" . $file;
    if (file_exists($path)) {
        return "?v=" . filemtime($path);
    }
    return "";
}

$hasImg1 = file_exists(__DIR__ . "/uploads/landing/afil1.jpg");
$hasImg2 = file_exists(__DIR__ . "/uploads/landing/afil2.jpg");
$hasImg3 = file_exists(__DIR__ . "/uploads/landing/afil3.jpg");

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player99 - Seja um Afiliado</title>
    <meta name="description" content="Seja um afiliado Player99 e lucre com o melhor player de Mídia Indoor do mercado.">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0f1015;
            text-align: center;
        }
        .landing-img {
            width: 100%;
            max-width: 900px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        a:first-of-type .landing-img {
            margin-top: 100px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        a:last-of-type .landing-img {
            margin-bottom: 50px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        }
        a {
            display: block;
            text-decoration: none;
        }
        .no-image {
            color: white;
            font-family: sans-serif;
            padding: 50px;
            font-size: 20px;
        }

        /* Nova Navbar Glassmorphism */
        .glass-nav {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1200px;
            background: rgba(15, 16, 21, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            font-family: sans-serif;
        }

        .glass-nav .logo-img {
            max-height: 45px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            transition: transform 0.3s ease;
        }
        .glass-nav .logo-img:hover {
            transform: scale(1.05);
        }

        .glass-nav .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .glass-nav .nav-btn {
            color: #ffffff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 20px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .glass-nav .nav-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #34e2e4;
        }

        .glass-nav .nav-btn-highlight {
            background: linear-gradient(135deg, #34e2e4, #4721fb);
            border: none;
            box-shadow: 0 4px 15px rgba(71, 33, 251, 0.4);
        }
        .glass-nav .nav-btn-highlight:hover {
            background: linear-gradient(135deg, #4721fb, #34e2e4);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 226, 228, 0.5);
        }

        @media (max-width: 850px) {
            .glass-nav {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
                border-radius: 20px;
                top: 10px;
            }
            .glass-nav .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar Global Dinâmica -->
    <nav class="glass-nav">
        <a href="index.php">
            <!-- Puxando a logo que é gerenciada no painel (logo.php) -->
            <img src="./banners/logo.png<?php echo '?v='.time(); ?>" alt="Logo" class="logo-img" onerror="this.src='assets/images/logo.png'">
        </a>
        <div class="nav-links">
            <a href="login.php" class="nav-btn">Entrar</a>
            <a href="parceiros.php" class="nav-btn">Parceiros</a>
            <a href="afiliados.php" class="nav-btn">Afiliado</a>
            <a href="loja.php" class="nav-btn nav-btn-highlight">Loja</a>
            <a href="cadastro.php" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #4721fb); box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4); border: 1px solid rgba(255,255,255,0.2);">Cadastre-se Aqui</a>
        </div>
    </nav>

    <?php if ($hasImg1): ?>
        <a href="<?php echo $linkCadastro; ?>">
            <img src="uploads/landing/afil1.jpg<?php echo getImgTime('afil1.jpg'); ?>" class="landing-img" alt="Afiliados 1">
        </a>
    <?php endif; ?>

    <?php if ($hasImg2): ?>
        <a href="<?php echo $linkCadastro; ?>">
            <img src="uploads/landing/afil2.jpg<?php echo getImgTime('afil2.jpg'); ?>" class="landing-img" alt="Afiliados 2">
        </a>
    <?php endif; ?>

    <?php if ($hasImg3): ?>
        <a href="<?php echo $linkCadastro; ?>">
            <img src="uploads/landing/afil3.jpg<?php echo getImgTime('afil3.jpg'); ?>" class="landing-img" alt="Afiliados 3">
        </a>
    <?php endif; ?>

    <?php if (!$hasImg1 && !$hasImg2 && !$hasImg3): ?>
        <div class="no-image">
            <p>Página de Afiliados em construção.</p>
            <p>Acesse o painel administrativo para fazer o upload das imagens de afiliados.</p>
            <a href="login.php" style="color: #34e2e4; text-decoration: underline;">Ir para o Login</a>
        </div>
    <?php endif; ?>

</body>
</html>
