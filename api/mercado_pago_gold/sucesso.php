<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Aprovado!</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #0d1117; color: #c9d1d9; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; text-align: center; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .success-box { background: #161b22; padding: 50px; border-radius: 20px; max-width: 500px; box-shadow: 0 0 30px rgba(0, 195, 255, 0.2); border: 1px solid rgba(0, 195, 255, 0.3); position: relative; overflow: hidden; }
        .success-box::before { content: ''; position: absolute; top: -2px; left: -2px; right: -2px; bottom: -2px; background: linear-gradient(45deg, #00c3ff, #0051ff); z-index: -1; border-radius: 22px; filter: blur(10px); opacity: 0.6; }
        .success-icon { font-size: 80px; color: #00c3ff; margin-bottom: 20px; text-shadow: 0 0 20px rgba(0, 195, 255, 0.8); }
        h2 { color: #fff; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .btn-gold { background: linear-gradient(90deg, #00c3ff, #0051ff); border: none; color: white; font-weight: bold; padding: 12px 30px; border-radius: 30px; margin-top: 20px; text-decoration: none; display: inline-block; text-transform: uppercase; box-shadow: 0 0 15px rgba(0, 195, 255, 0.4); transition: all 0.3s ease; }
        .btn-gold:hover { background: linear-gradient(90deg, #0051ff, #00c3ff); color: white; box-shadow: 0 0 25px rgba(0, 195, 255, 0.8); transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="success-icon">✔️</div>
        <h2>Pagamento Aprovado!</h2>
        <p class="mt-3">Obrigado pela sua compra. Os seus créditos foram adicionados à sua conta com sucesso e já estão prontos para uso no seu painel.</p>
        <a href="../../painel.php" class="btn-gold">Voltar ao Painel Inicial</a>
    </div>
</body>
</html>
