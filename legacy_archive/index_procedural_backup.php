<?php
require_once __DIR__ . '/error_handler.php';
// index.php - Landing Page Principal (Imagens Empilhadas)

$refParam = '';
if (!empty($_GET['ref'])) {
    $ref = strtoupper(trim($_GET['ref']));
    $refParam = '?ref=' . urlencode($ref);
    
    // Salva o cookie de indicação por 30 dias para garantir a comissão
    setcookie('affiliate_ref', $ref, time() + (86400 * 30), "/");
    
    // Registra o clique apenas se não clicou nas últimas 24h (evita F5/Spam de cliques)
    if (!isset($_COOKIE['clicked_ref_' . $ref])) {
        try {
            $dbClick = new SQLite3(__DIR__ . "/api/db/studiolivecode_clientes.db");
            $dbClick->busyTimeout(5000);
            
            // Verifica se as colunas existem (caso seja a primeira execução)
            $resCols = $dbClick->query("PRAGMA table_info(ibo)");
            $columns = [];
            while ($c = $resCols->fetchArray(SQLITE3_ASSOC)) { $columns[] = $c['name']; }
            if (in_array('affiliateClicks', $columns)) {
                $stmt = $dbClick->prepare("UPDATE ibo SET affiliateClicks = affiliateClicks + 1 WHERE affiliateCode = :ref");
                $stmt->bindValue(':ref', $ref, SQLITE3_TEXT);
                $stmt->execute();
                setcookie('clicked_ref_' . $ref, '1', time() + 86400, "/");
            }
            $dbClick->close();
        } catch (Exception $e) {
            // Ignora falha de DB para não quebrar a home
        }
    }
}

$linkCadastro = "cadastro.php" . $refParam;

function getImgTime($file) {
    $path = __DIR__ . "/uploads/landing/" . $file;
    if (file_exists($path)) {
        return "?v=" . filemtime($path);
    }
    return "";
}

$hasImg1 = file_exists(__DIR__ . "/uploads/landing/img1.jpg");
$hasImg2 = file_exists(__DIR__ . "/uploads/landing/img2.jpg");
$hasImg3 = file_exists(__DIR__ . "/uploads/landing/img3.jpg");

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player99 - O Melhor Player de Mídia Indoor</title>
    <meta name="description" content="Transforme qualquer TV em uma vitrine digital inteligente com o Player99. Notícias, Loterias, Clima e muito mais!">
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

        /* Seção de Estatísticas */
        .stats-section {
            background: linear-gradient(135deg, #2b2e59 0%, #463428 100%);
            color: #ffffff;
            font-family: sans-serif;
            padding: 40px 20px;
            text-align: center;
        }
        .stats-header {
            background-color: #0d1627;
            color: #55b8ff;
            padding: 15px;
            font-size: 24px;
            font-weight: bold;
            margin: -40px -20px 40px -20px; /* Faz encostar nas bordas */
        }
        .stats-container {
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 800px;
            margin: 0 auto;
            gap: 40px;
        }
        .stat-box {
            flex: 1;
        }
        .stat-number {
            font-size: 48px;
            font-weight: bold;
            color: #55b8ff;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 16px;
            color: #8da4d8;
        }
        .stat-divider {
            width: 1px;
            height: 80px;
            background-color: rgba(255, 255, 255, 0.2);
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

        @media (max-width: 600px) {
            .stats-container {
                flex-direction: column;
                gap: 20px;
            }
            .stat-divider {
                width: 80%;
                height: 1px;
            }
            .stats-header {
                font-size: 18px;
            }
            .stat-number {
                font-size: 36px;
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
            <a href="login.php" class="nav-btn" style="color: #34e2e4; font-weight: bold;"><i class="fas fa-user-circle"></i> Entrar / Login</a>
            <a href="parceiros.php" class="nav-btn">Parceiros</a>
            <a href="afiliados.php" class="nav-btn">Afiliado</a>
            <a href="loja.php" class="nav-btn nav-btn-highlight">Loja</a>
            <a href="cadastro.php" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #4721fb); box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4); border: 1px solid rgba(255,255,255,0.2);">Cadastre-se Aqui</a>
        </div>
    </nav>

    <?php if ($hasImg1): ?>
        <a href="<?php echo $linkCadastro; ?>">
            <img src="uploads/landing/img1.jpg<?php echo getImgTime('img1.jpg'); ?>" class="landing-img" alt="Página Inicial 1">
        </a>
    <?php endif; ?>

    <?php if ($hasImg2): ?>
        <a href="<?php echo $linkCadastro; ?>">
            <img src="uploads/landing/img2.jpg<?php echo getImgTime('img2.jpg'); ?>" class="landing-img" alt="Página Inicial 2">
        </a>
    <?php endif; ?>

    <?php if ($hasImg3): ?>
        <a href="<?php echo $linkCadastro; ?>">
            <img src="uploads/landing/img3.jpg<?php echo getImgTime('img3.jpg'); ?>" class="landing-img" alt="Página Inicial 3">
        </a>
    <?php endif; ?>

    <?php if (!$hasImg1 && !$hasImg2 && !$hasImg3): ?>
        <div class="no-image">
            <p>Página inicial em construção.</p>
            <p>Acesse o painel administrativo para fazer o upload das imagens.</p>
            <a href="painel.php" style="color: #34e2e4; text-decoration: underline;">Ir para o Login do Painel</a>
        </div>
    <?php endif; ?>

    <!-- Seção de Estatísticas Animadas -->
    <div class="stats-section">
        <div class="stats-header">
            Faturamento médio estimado para parceiros com 6 meses de atuação
        </div>
        <div class="stats-container">
            <div class="stat-box">
                <div class="stat-number"><span class="counter" data-target="1000">0</span>+</div>
                <div class="stat-label">corridas geradas</div>
            </div>
            <div class="stat-divider"></div>
            <div class="stat-box">
                <div class="stat-number"><span class="counter" data-target="2500">0</span>R$</div>
                <div class="stat-label">renda extra no mês</div>
            </div>
        </div>
    </div>

    <script>
        // Animação dos Contadores
        const counters = document.querySelectorAll('.counter');
        const speed = 200; // Quanto menor, mais rápido

        const animateCounters = () => {
            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.getAttribute('data-target');
                    // No Brasil o separador de milhar é ponto, não vírgula
                    const count = +counter.innerText.replace(/\./g, '');
                    
                    // Calculo de incremento
                    const inc = target / speed;

                    if (count < target) {
                        counter.innerText = Math.ceil(count + inc).toLocaleString('pt-BR');
                        setTimeout(updateCount, 15);
                    } else {
                        counter.innerText = target.toLocaleString('pt-BR');
                        // Aguarda 4 segundos e recomeça a animação
                        setTimeout(() => {
                            counter.innerText = '0';
                            updateCount();
                        }, 4000);
                    }
                };
                updateCount();
            });
        };

        // Usa IntersectionObserver para só rodar a animação quando chegar no scroll da página
        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</body>
</html>
