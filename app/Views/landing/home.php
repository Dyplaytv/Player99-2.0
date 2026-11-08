<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= \App\Core\View::logoUrl() ?>">
    <title>Player99 - O Melhor Player de Mídia Indoor</title>
    <meta name="description" content="Transforme qualquer TV em uma vitrine digital inteligente com o Player99. Notícias, Loterias, Clima e muito mais!">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background-color: #0f1015;
            text-align: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: #fff;
        }

        .glass-nav {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 950px;
            background: rgba(15, 16, 21, 0.75);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 8px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .glass-nav .logo-img {
            max-height: 38px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5));
            transition: transform 0.3s ease;
        }

        .glass-nav .nav-links {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .glass-nav .nav-btn {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .glass-nav .nav-btn:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #34e2e4;
        }

        .glass-nav .nav-btn-highlight {
            background: #00f3ff;
            color: #000 !important;
            font-weight: bold;
            box-shadow: 0 0 10px rgba(0, 243, 255, 0.4);
        }

        .glass-nav .nav-btn-register {
            background: linear-gradient(135deg, #0A38D1, #4721fb);
            box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff !important;
        }

        .landing-img {
            width: 100%;
            max-width: 900px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .landing-container {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
            pointer-events: none; /* Impede qualquer clique acidental nas laterais */
        }
        .landing-img {
            width: 100%;
            max-width: 900px;
            height: auto;
            display: block;
            margin: 0 auto;
            pointer-events: auto;
        }
        .landing-container img:first-child {
            margin-top: 100px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        .landing-container img:last-child {
            margin-bottom: 50px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        }

        .no-image {
            color: white;
            padding: 120px 20px 50px;
            font-size: 1.1rem;
        }

        /* Seção de Estatísticas */
        .stats-section {
            background: linear-gradient(135deg, #2b2e59 0%, #463428 100%);
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }
        .stats-header {
            background-color: #0d1627;
            color: #55b8ff;
            padding: 15px;
            font-size: 22px;
            font-weight: bold;
            margin: -40px -20px 40px -20px;
        }
        .stats-container {
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 800px;
            margin: 0 auto;
            gap: 40px;
        }
        .stat-box { flex: 1; }
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            color: #34e2e4;
        }
        .stat-label {
            font-size: 15px;
            color: #c4c9e2;
            margin-top: 5px;
        }
        .stat-divider {
            width: 1px;
            height: 60px;
            background: rgba(255, 255, 255, 0.2);
        }

        @media (max-width: 850px) {
            .glass-nav {
                flex-direction: column;
                gap: 12px;
                padding: 12px;
                border-radius: 20px;
                top: 10px;
            }
            .glass-nav .nav-links {
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }
        }
    </style>
</head>
<body>

    <nav class="glass-nav">
        <a href="/">
            <img src="<?= \App\Core\View::logoUrl() ?>" alt="Logo" class="logo-img" onerror="this.src='/assets/images/logo.png'">
        </a>
        <div class="nav-links">
            <a href="/parceiros.php" class="nav-btn">Parceiros</a>
            <a href="/afiliados.php" class="nav-btn">Afiliado</a>
            <a href="/loja.php" class="nav-btn nav-btn-highlight">Loja</a>
            <a href="/login" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #34e2e4); box-shadow: 0 4px 15px rgba(52, 226, 228, 0.4); font-weight: 700; border-radius: 25px; padding: 9px 20px;"><i class="fas fa-user-circle me-1"></i> Entrar / Cadastrar</a>
        </div>
    </nav>

        <div class="landing-container">
        <?php if ($hasImg1): ?>
            <img src="/uploads/landing/img1.jpg" class="landing-img" alt="Banner 1">
        <?php endif; ?>

        <?php if ($hasImg2): ?>
            <img src="/uploads/landing/img2.jpg" class="landing-img" alt="Banner 2">
        <?php endif; ?>

        <?php if ($hasImg3): ?>
            <img src="/uploads/landing/img3.jpg" class="landing-img" alt="Banner 3">
        <?php endif; ?>
    </div>

    <?php if (!$hasImg1 && !$hasImg2 && !$hasImg3): ?>
        <div class="no-image">
            <p>Página inicial pronta no modelo MVC.</p>
            <p>As imagens de banner podem ser adicionadas em <code>uploads/landing/</code>.</p>
            <a href="/login" style="color: #34e2e4; font-weight: bold;">Acessar o Painel de Login</a>
        </div>
    <?php endif; ?>

    <div class="stats-section">
        <div class="stats-header">
            Faturamento médio estimado para parceiros com 6 meses de atuação
        </div>
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-number">1.000+</div>
                <div class="stat-label">corridas geradas</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-box">
                <div class="stat-number">2.500 R$</div>
                <div class="stat-label">renda extra no mês</div>
            </div>
        </div>
    </div>

    <footer style="background-color: #0a0c10; padding: 25px 20px; border-top: 1px solid rgba(255, 255, 255, 0.1); text-align: center; color: #8e9bb0; font-size: 14px;">
        <p style="margin: 0; font-weight: 500;">Player 99 2.3</p>
    </footer>

</body>
</html>
