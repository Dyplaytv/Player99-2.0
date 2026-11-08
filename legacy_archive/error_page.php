<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oops! Algo deu errado</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            font-family: 'Inter', sans-serif;
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out forwards;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: bounce 2s infinite ease-in-out;
            display: inline-block;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        h1 {
            font-size: 28px;
            font-weight: 800;
            margin: 0 0 15px 0;
            background: -webkit-linear-gradient(45deg, #f43f5e, #fb923c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        p {
            font-size: 16px;
            line-height: 1.6;
            color: #cbd5e1;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            color: #fff;
            text-decoration: none;
            padding: 12px 28px;
            border-radius: 99px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px 0 rgba(139, 92, 246, 0.39);
        }
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px 0 rgba(139, 92, 246, 0.5);
        }
        .footer-note {
            margin-top: 30px;
            font-size: 12px;
            color: #64748b;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="icon">🛸</div>
    <h1>Houston, temos um problema!</h1>
    <p>Ocorreu uma falha inesperada no sistema.<br>Mas não se preocupe: <b>nossa equipe técnica já foi notificada automaticamente</b> com os detalhes do erro e está trabalhando na solução.</p>
    
    <a href="javascript:history.back()" class="btn">Voltar para a página anterior</a>
    
    <div class="footer-note">
        Erro rastreado: <?php echo date('d/m/Y H:i:s'); ?>
    </div>
</div>

</body>
</html>
