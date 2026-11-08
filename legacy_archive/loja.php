<?php
require_once __DIR__ . '/init_loja_db.php';
$dbPath = dirname(__DIR__) . '/api/db/studiolivecode_loja.db';
$dbLoja = new SQLite3($dbPath);
$produtos = $dbLoja->query("SELECT * FROM produtos WHERE ativo = 1 ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <title>Loja Digital - Player99</title>
    <style>
        body {
            background-color: #0f1015;
            color: #fff;
            font-family: sans-serif;
            margin: 0;
            padding-bottom: 50px;
        }
        
        /* Navbar Glassmorphism */
        .glass-nav {
            position: relative; /* Diferente da home, pra não ficar sobreposta ao título */
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background: rgba(15, 16, 21, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            z-index: 1000;
        }
        .glass-nav .logo-img { max-height: 45px; width: auto; transition: transform 0.3s; }
        .glass-nav .logo-img:hover { transform: scale(1.05); }
        .glass-nav .nav-links { display: flex; gap: 15px; align-items: center; }
        .glass-nav .nav-btn { color: #fff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 8px 20px; border-radius: 20px; transition: all 0.3s; }
        .glass-nav .nav-btn:hover { background: rgba(255,255,255,0.1); color: #34e2e4; text-decoration: none;}
        .glass-nav .nav-btn-highlight { background: linear-gradient(135deg, #34e2e4, #4721fb); border: none; box-shadow: 0 4px 15px rgba(71,33,251,0.4); }
        .glass-nav .nav-btn-highlight:hover { background: linear-gradient(135deg, #4721fb, #34e2e4); transform: translateY(-2px); color:#fff; text-decoration: none;}

        .hero-title {
            text-align: center;
            margin: 40px 0;
        }
        .hero-title h1 {
            font-size: 3rem;
            font-weight: 800;
            background: linear-gradient(135deg, #34e2e4, #4721fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Store Cards */
        .store-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .product-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(71, 33, 251, 0.3);
            border-color: rgba(52, 226, 228, 0.5);
        }
        .product-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .product-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .product-title {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #fff;
        }
        .product-desc {
            color: #a0a0b0;
            font-size: 0.9rem;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #34e2e4;
            margin-bottom: 15px;
        }
        .btn-buy {
            background: linear-gradient(135deg, #0A38D1, #4721fb);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .btn-buy:hover {
            background: linear-gradient(135deg, #4721fb, #34e2e4);
            color: #fff;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(52, 226, 228, 0.4);
        }

        @media (max-width: 850px) {
            .glass-nav { flex-direction: column; gap: 15px; padding: 15px; border-radius: 20px; }
            .glass-nav .nav-links { flex-wrap: wrap; justify-content: center; gap: 10px; }
            .hero-title h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>

    <!-- Navbar Global Dinâmica -->
    <nav class="glass-nav">
        <a href="index.php">
            <img src="<?= class_exists('\\App\\Core\\View') ? \App\Core\View::logoUrl() : '/assets/images/logo.png' ?>" alt="Logo" class="logo-img" onerror="this.src='assets/images/logo.png'">
        </a>
        <div class="nav-links">
            <a href="login.php" class="nav-btn">Entrar</a>
            <a href="parceiros.php" class="nav-btn">Parceiros</a>
            <a href="afiliados.php" class="nav-btn">Afiliado</a>
            <a href="loja.php" class="nav-btn nav-btn-highlight">Loja</a>
            <a href="cadastro.php" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #4721fb); box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4); border: 1px solid rgba(255,255,255,0.2);">Cadastre-se Aqui</a>
        </div>
    </nav>

    <div class="hero-title">
        <h1>Loja de Serviços Digitais</h1>
        <p class="text-muted">Adquira serviços e receba seu Voucher instantaneamente.</p>
    </div>

    <div class="store-grid">
        <?php 
        $count = 0;
        while($p = $produtos->fetchArray(SQLITE3_ASSOC)): 
            $count++;
        ?>
        <div class="product-card">
            <img src="uploads/loja/<?php echo $p['imagem'] ? $p['imagem'] : 'default.jpg'; ?>" class="product-img" onerror="this.src='https://via.placeholder.com/400x200/181828/ffffff?text=Produto'">
            <div class="product-body">
                <div class="product-title"><?php echo htmlspecialchars($p['titulo']); ?></div>
                <div class="product-desc"><?php echo nl2br(htmlspecialchars($p['descricao'])); ?></div>
                <div class="product-price">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></div>
                <a href="api/loja/checkout.php?prod_id=<?php echo $p['id']; ?>" class="btn-buy"><i class="fas fa-shopping-cart"></i> Comprar Agora</a>
            </div>
        </div>
        <?php endwhile; ?>
        
        <?php if ($count == 0): ?>
            <div class="col-12 text-center mt-5 w-100" style="grid-column: 1 / -1;">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">Nenhum serviço disponível no momento.</h4>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
