<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="manifest" href="manifest.json">
    <title>Vinculação da Tela</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .container {
            background: rgba(255, 255, 255, 0.05);
            padding: 50px 80px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
        }
        .logo-placeholder {
            font-size: 32px;
            font-weight: 700;
            color: #4facfe;
            margin-bottom: 20px;
            letter-spacing: 2px;
        }
        h2 {
            font-weight: 400;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .code-box {
            font-size: 80px;
            font-weight: 700;
            letter-spacing: 10px;
            background: -webkit-linear-gradient(#4facfe, #00f2fe);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 30px 0;
            text-transform: uppercase;
        }
        p.instrucao {
            font-size: 18px;
            color: #ddd;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.5;
        }
        .loading {
            margin-top: 30px;
            font-size: 14px;
            color: #888;
        }
        /* Efeito de piscar sutil */
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
        .loading span {
            display: inline-block;
            animation: pulse 2s infinite ease-in-out;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="logo-placeholder"><img src="banners/logo.png" style="max-height: 80px;" alt="Logo da Empresa"></div>
    <h2>Aguardando Vinculação...</h2>
    <p class="instrucao">Acesse o painel do cliente pelo seu computador ou celular, vá na aba Telas, clique em Vincular Tela e digite o código abaixo:</p>
    
    <div class="code-box" id="displayCode">------</div>
    
    <div class="loading">
        <span>Procurando cadastro no sistema...</span>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    // Gerador de código alfanumérico (6 caracteres)
    function generateCode() {
        var chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Removido I, O, 0, 1 para evitar confusão visual
        var result = '';
        for (var i = 0; i < 6; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    // Pega o código da TV ou cria um novo
    var tvCode = localStorage.getItem('tv_code');
    if (!tvCode) {
        tvCode = generateCode();
        localStorage.setItem('tv_code', tvCode);
    }
    
    document.getElementById('displayCode').innerText = tvCode;

    function checkPairing() {
        // Se estiver sem internet e já foi pareado antes, vai direto pro Player (em cache)
        try {
            if (!navigator.onLine && localStorage.getItem('is_paired') === 'true') {
                console.log("Modo Offline: Iniciando player da memória...");
                window.location.href = 'player.php?codigo=' + tvCode;
                return;
            }
        } catch(e) {}

        $.get('api_pairing.php', { code: tvCode }, function(res) {
            if (res && res.paired) {
                try { localStorage.setItem('is_paired', 'true'); } catch(e) {}
                window.location.href = 'player.php?codigo=' + tvCode;
            }
        }, 'json').fail(function() {
            // Se falhar (caiu internet bem na hora de abrir), tenta ir pro cache
            try {
                if (localStorage.getItem('is_paired') === 'true') {
                    window.location.href = 'player.php?codigo=' + tvCode;
                }
            } catch(e) {}
        });
    }

    // Checa a cada 5 segundos
    setInterval(checkPairing, 5000);
    checkPairing(); // Checa na hora

    // Registrar Service Worker para permitir instalação como PWA (App)
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('sw.js').then(function(reg) {
            console.log('SW Registrado no tv.php:', reg.scope);
        });
    }
</script>

</body>
</html>
