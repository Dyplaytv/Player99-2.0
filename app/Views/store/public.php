<?php
/** @var array $produtos */
/** @var string $pageTitle */
/** @var string $pageDescription */
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="<?= \App\Core\View::logoUrl() ?>">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <title><?= htmlspecialchars($pageTitle ?? 'Loja Digital - Player99') ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription ?? 'Adquira serviços e receba seu Voucher instantaneamente.') ?>">
    <style>
        * { box-sizing: border-box; }
        body {
            background-color: #0f1015;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding-bottom: 50px;
        }

        /* Navbar Glassmorphism */
        .glass-nav {
            position: relative;
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(15, 16, 21, 0.75);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
            z-index: 1000;
        }
        .glass-nav .logo-img { max-height: 42px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5)); transition: transform 0.3s; }
        .glass-nav .logo-img:hover { transform: scale(1.05); }
        .glass-nav .nav-links { display: flex; gap: 15px; align-items: center; }
        .glass-nav .nav-btn { color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; padding: 8px 18px; border-radius: 20px; transition: all 0.3s; }
        .glass-nav .nav-btn:hover { background: rgba(255,255,255,0.12); color: #34e2e4; text-decoration: none; }
        .glass-nav .nav-btn-highlight { background: #00f3ff; color: #000 !important; font-weight: bold; box-shadow: 0 0 10px rgba(0, 243, 255, 0.4); }
        .glass-nav .nav-btn-highlight:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(52, 226, 228, 0.5); }

        .hero-title {
            text-align: center;
            margin: 40px 0 30px;
        }
        .hero-title h1 {
            font-size: 2.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #34e2e4, #4721fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-title p {
            color: #94a3b8;
            font-size: 1.1rem;
        }

        /* Store Grid & Cards */
        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .product-card {
            background: rgba(18, 20, 30, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            backdrop-filter: blur(10px);
        }
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(71, 33, 251, 0.3);
            border-color: rgba(52, 226, 228, 0.5);
        }
        .product-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            background: rgba(0,0,0,0.3);
        }
        .product-body {
            padding: 22px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .product-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: #fff;
        }
        .product-desc {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 20px;
            flex-grow: 1;
            line-height: 1.5;
        }
        .product-price {
            font-size: 1.6rem;
            font-weight: 800;
            color: #34e2e4;
            margin-bottom: 16px;
        }
        .btn-buy {
            background: linear-gradient(135deg, #0A38D1, #4721fb);
            color: #fff !important;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4);
        }
        .btn-buy:hover {
            background: linear-gradient(135deg, #4721fb, #34e2e4);
            color: #fff !important;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 226, 228, 0.4);
        }

        footer {
            background-color: #0a0c10;
            padding: 25px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: #8e9bb0;
            font-size: 14px;
            margin-top: 60px;
        }

        @media (max-width: 850px) {
            .glass-nav { flex-direction: column; gap: 12px; padding: 12px; border-radius: 20px; }
            .glass-nav .nav-links { flex-wrap: wrap; justify-content: center; gap: 8px; }
            .hero-title h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <!-- Navbar Global -->
    <nav class="glass-nav">
        <a href="/">
            <img src="<?= \App\Core\View::logoUrl() ?>" alt="Logo" class="logo-img" onerror="this.src='/assets/images/logo.png'">
        </a>
        <div class="nav-links">
            <a href="/" class="nav-btn"><i class="fas fa-car" style="margin-right: 6px;"></i>Motoristas</a>
            <a href="/parceiros" class="nav-btn">Parceiros</a>
            <a href="/afiliados" class="nav-btn">Afiliado</a>
            <a href="/loja" class="nav-btn nav-btn-highlight">Loja</a>
            <a href="/login" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #34e2e4); box-shadow: 0 4px 15px rgba(52, 226, 228, 0.4); font-weight: 700; border-radius: 25px; padding: 9px 20px;"><i class="fas fa-user-circle me-1"></i> Entrar / Cadastrar</a>
        </div>
    </nav>

    <div class="hero-title">
        <h1>Loja de Serviços Digitais</h1>
        <p>Adquira serviços e receba seu Voucher instantaneamente via PIX.</p>
    </div>

    <div class="store-grid">
        <?php if (!empty($produtos)): ?>
            <?php foreach ($produtos as $p): ?>
                <div class="product-card">
                    <img src="/uploads/loja/<?= !empty($p['imagem']) ? htmlspecialchars($p['imagem']) : 'default.jpg' ?>" class="product-img" onerror="this.src='https://via.placeholder.com/400x200/181828/ffffff?text=Produto'">
                    <div class="product-body">
                        <div class="product-title"><?= htmlspecialchars($p['titulo']) ?></div>
                        <div class="product-desc"><?= nl2br(htmlspecialchars($p['descricao'] ?? '')) ?></div>
                        <div class="product-price">R$ <?= number_format((float)$p['preco'], 2, ',', '.') ?></div>
                        <a href="/api/loja/checkout.php?prod_id=<?= (int)$p['id'] ?>" class="btn-buy"><i class="fas fa-shopping-cart me-1"></i> Comprar Agora</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5 w-100" style="grid-column: 1 / -1;">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Nenhum produto disponível no momento.</h4>
                <p class="text-muted small">Os produtos cadastrados no painel administrativo aparecerão aqui.</p>
            </div>
        <?php endif; ?>
    </div>

    <footer>
        <p style="margin: 0; font-weight: 500;">player99 2.4</p>
    </footer>

</body>
</html>
