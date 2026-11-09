<?php
/** @var string $pageKey */
/** @var string $pageTitle */
/** @var string $pageDescription */
/** @var array $banners */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= \App\Core\View::logoUrl() ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
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
        .glass-nav .logo-img { max-height: 38px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5)); }
        .glass-nav .nav-links { display: flex; align-items: center; gap: 15px; }
        .glass-nav .nav-btn {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 18px;
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        .glass-nav .nav-btn:hover { background: rgba(255, 255, 255, 0.15); color: #34e2e4; }
        .glass-nav .nav-btn.current { color: #34e2e4; background: rgba(52, 226, 228, 0.12); }
        .glass-nav .nav-btn-highlight { background: #00f3ff; color: #000 !important; font-weight: bold; box-shadow: 0 0 10px rgba(0, 243, 255, 0.4); }

        .stack { width: 100%; max-width: 900px; margin: 0 auto; padding-top: 100px; padding-bottom: 50px; }
        .stack a { display: block; text-decoration: none; }
        .landing-img { width: 100%; height: auto; display: block; margin: 0 auto; }
        .stack > :first-child .landing-img { border-top-left-radius: 20px; border-top-right-radius: 20px; }
        .stack > :last-child .landing-img { border-bottom-left-radius: 20px; border-bottom-right-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }

        .no-image { padding: 140px 20px 50px; font-size: 20px; }
        .no-image a { color: #34e2e4; font-weight: bold; }

        footer { background-color: #0a0c10; padding: 25px 20px; border-top: 1px solid rgba(255,255,255,0.1); color: #8e9bb0; font-size: 14px; }

        @media (max-width: 850px) {
            .glass-nav { flex-direction: column; gap: 12px; padding: 12px; border-radius: 20px; top: 10px; }
            .glass-nav .nav-links { flex-wrap: wrap; justify-content: center; gap: 8px; }
            .stack { padding-top: 150px; }
            .no-image { padding-top: 190px; }
        }
    </style>
</head>
<body>

    <nav class="glass-nav">
        <a href="/">
            <img src="<?= \App\Core\View::logoUrl() ?>" alt="Logo" class="logo-img" onerror="this.src='/assets/images/logo.png'">
        </a>
        <div class="nav-links">
            <a href="/" class="nav-btn"><i class="fas fa-car" style="margin-right: 6px;"></i>Motoristas</a>
            <a href="/parceiros" class="nav-btn <?= $pageKey === 'parceiros' ? 'current' : '' ?>">Parceiros</a>
            <a href="/afiliados" class="nav-btn <?= $pageKey === 'afiliados' ? 'current' : '' ?>">Afiliado</a>
            <a href="/loja.php" class="nav-btn nav-btn-highlight">Loja</a>
            <a href="/login" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #34e2e4); box-shadow: 0 4px 15px rgba(52, 226, 228, 0.4); font-weight: 700; border-radius: 25px; padding: 9px 20px;"><i class="fas fa-user-circle me-1"></i> Entrar / Cadastrar</a>
        </div>
    </nav>

    <?php if (!empty($banners)): ?>
        <div class="stack">
            <?php foreach ($banners as $i => $b): ?>
                <?php $href = $b['effective_link']; ?>
                <?php if ($href !== ''): ?>
                    <a href="<?= htmlspecialchars($href) ?>"><img src="<?= htmlspecialchars($b['url']) ?>" class="landing-img" alt="<?= htmlspecialchars($pageTitle) ?> <?= $i + 1 ?>"></a>
                <?php else: ?>
                    <div><img src="<?= htmlspecialchars($b['url']) ?>" class="landing-img" alt="<?= htmlspecialchars($pageTitle) ?> <?= $i + 1 ?>"></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-image">
            <p>Página de <?= $pageKey === 'parceiros' ? 'Parceiros' : 'Afiliados' ?> em construção.</p>
            <p>Acesse o painel administrativo (Editar Home) para adicionar os containers desta página.</p>
            <a href="/login">Ir para o Login</a>
        </div>
    <?php endif; ?>

    <footer>
        <p style="margin: 0; font-weight: 500;">Player 99 2.3</p>
    </footer>

</body>
</html>
