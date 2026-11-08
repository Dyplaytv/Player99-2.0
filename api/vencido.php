<?php
$mac = isset($_GET['mac']) ? $_GET['mac'] : '';

// Link de pagamento (Mercado Pago Gold)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domain = $_SERVER['HTTP_HOST'];
$base_path = dirname($_SERVER['PHP_SELF']);
$link_payment = $protocol . $domain . $base_path . '/mercado_pago_gold/consulta.php?mac=' . urlencode($mac);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Vencido</title>
    <style>
        body {
            background-color: #0d1117;
            color: #c9d1d9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .container {
            background: #161b22;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 0 20px rgba(0, 195, 255, 0.2), 0 0 40px rgba(0, 195, 255, 0.1);
            border: 1px solid rgba(0, 195, 255, 0.3);
            position: relative;
        }
        .container::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, #00c3ff, #0051ff);
            z-index: -1;
            border-radius: 22px;
            filter: blur(10px);
            opacity: 0.6;
        }
        h1 {
            color: #00c3ff;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 28px;
            margin-bottom: 10px;
            text-shadow: 0 0 10px rgba(0, 195, 255, 0.5);
        }
        p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .mac {
            font-weight: bold;
            color: #fff;
            background: rgba(0, 195, 255, 0.1);
            padding: 5px 15px;
            border-radius: 5px;
            border: 1px solid #00c3ff;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 30px;
        }
        .qr-placeholder {
            width: 200px;
            height: 200px;
            background: white;
            margin: 0 auto 20px auto;
            border-radius: 10px;
            padding: 10px;
            box-shadow: 0 0 15px rgba(0, 195, 255, 0.4);
        }
        .qr-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(90deg, #00c3ff, #0051ff);
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            box-shadow: 0 0 15px rgba(0, 195, 255, 0.4);
            transition: all 0.3s ease;
        }
        .btn:hover {
            box-shadow: 0 0 25px rgba(0, 195, 255, 0.8);
            transform: scale(1.05);
        }
        .instruction {
            color: #8b949e;
            font-size: 14px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Acesso Bloqueado</h1>
        <p>Seu acesso venceu ou foi suspenso. Para continuar assistindo aos melhores canais, filmes e séries, renove agora mesmo de forma automática!</p>
        
        <?php if (!empty($mac)): ?>
            <div class="mac">MAC: <?php echo htmlspecialchars($mac); ?></div>
        <?php endif; ?>
        
        <div class="qr-placeholder">
            <!-- API gratuita para gerar o QR Code apontando para a sua pagina PIX -->
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($link_payment); ?>" alt="QR Code PIX">
        </div>
        
        <a href="<?php echo $link_payment; ?>" class="btn">Renovar com PIX</a>
        
        <div class="instruction">Escaneie o QR Code com a câmera do seu celular para liberar na hora.</div>
    </div>
</body>
</html>
