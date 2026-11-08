<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#0b132b">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Player99">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/images/icon-192.png">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">
    <title>Player99 - Tela / App</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * { box-sizing: border-box; }
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            min-height: 100vh;
            background: radial-gradient(circle at 50% 20%, #1c2541 0%, #0b132b 70%, #050814 100%);
            color: #fff;
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 16px;
        }
        .tv-card {
            width: 100%;
            max-width: 560px;
            background: rgba(255, 255, 255, 0.05);
            padding: clamp(24px, 5vw, 44px) clamp(18px, 5vw, 48px);
            border-radius: 24px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(0, 243, 255, 0.2);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
            position: relative;
        }
        .logo-wrap img {
            max-height: 68px;
            width: auto;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 12px rgba(0, 243, 255, 0.3));
        }
        h2 {
            font-weight: 700;
            font-size: clamp(20px, 4.5vw, 26px);
            margin: 0 0 8px 0;
            color: #fff;
        }
        p.instrucao {
            font-size: clamp(14px, 3.5vw, 16px);
            color: #cbd5e1;
            max-width: 460px;
            margin: 0 auto;
            line-height: 1.45;
        }
        .code-box {
            font-size: clamp(42px, 11vw, 72px);
            font-weight: 800;
            letter-spacing: clamp(5px, 2vw, 10px);
            background: linear-gradient(90deg, #00f2fe 0%, #4facfe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 22px 0;
            padding: 12px 16px;
            border-radius: 16px;
            border: 1px dashed rgba(0, 242, 254, 0.35);
            background-color: rgba(0, 0, 0, 0.25);
            text-transform: uppercase;
            user-select: all;
        }
        .loading {
            margin-top: 14px;
            font-size: 14px;
            color: #94a3b8;
        }
        @keyframes pulse {
            0% { opacity: 0.5; }
            50% { opacity: 1; }
            100% { opacity: 0.5; }
        }
        .loading span {
            display: inline-block;
            animation: pulse 2s infinite ease-in-out;
        }
        .actions-bar {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 10px;
            margin-top: 22px;
        }
        .btn-pwa {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 30px;
            font-family: inherit;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.25s ease;
            text-decoration: none;
        }
        .btn-install {
            background: linear-gradient(135deg, #00f2fe 0%, #0072ff 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(0, 114, 255, 0.45);
            width: 100%;
            max-width: 360px;
        }
        .btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 242, 254, 0.6);
        }
        .btn-secondary-outline {
            background: rgba(255, 255, 255, 0.07);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 9px 16px;
            font-size: 13px;
        }
        .btn-secondary-outline:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }
        .manual-code-box {
            display: none;
            margin-top: 16px;
            padding: 14px;
            border-radius: 14px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .manual-code-input {
            background: #0b132b;
            border: 1px solid #334155;
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 3px;
            width: 160px;
            text-align: center;
        }
        /* Modal de Instrução de Instalação Android */
        .install-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .install-modal {
            background: #111827;
            border: 1px solid rgba(0, 242, 254, 0.35);
            border-radius: 20px;
            max-width: 420px;
            width: 100%;
            padding: 24px;
            text-align: left;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
        }
        .install-step {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            background: rgba(255, 255, 255, 0.04);
            padding: 12px;
            border-radius: 12px;
        }
        .install-step-num {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #00f2fe;
            color: #0b132b;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="tv-card">
    <div class="logo-wrap">
        <img src="<?= \App\Core\View::logoUrl() ?>" alt="Player99" onerror="this.src='/assets/images/logo.png'">
    </div>
    <h2>Aguardando Vinculação da Tela</h2>
    <p class="instrucao">
        No Painel do Motorista, acesse a aba <b>Telas (Player)</b>, clique em <b>+ Vincular Tela</b> e digite o código abaixo:
    </p>

    <div class="code-box" id="displayCode">------</div>

    <div class="loading">
        <span><i class="fas fa-satellite-dish mr-1"></i> Aguardando conexão com o painel...</span>
    </div>

    <div class="actions-bar" id="pwaActionsBar">
        <button type="button" class="btn-pwa btn-install" id="btnInstallApp" onclick="triggerInstallApp()">
            <i class="fas fa-mobile-alt"></i> INSTALAR APP NO CELULAR / TABLET
        </button>

        <button type="button" class="btn-pwa btn-secondary-outline" onclick="toggleManualCode()">
            <i class="fas fa-keyboard"></i> Digitar Código Existente
        </button>

        <button type="button" class="btn-pwa btn-secondary-outline" onclick="enterFullScreen()">
            <i class="fas fa-expand"></i> Tela Cheia
        </button>
    </div>

    <div class="manual-code-box" id="manualCodeBox">
        <div style="font-size:13px;color:#cbd5e1;margin-bottom:8px;">Já criou a tela no painel? Digite o código dela:</div>
        <div style="display:flex;justify-content:center;gap:8px;">
            <input type="text" id="inputCustomCode" class="manual-code-input" placeholder="EX: 79T7SW" maxlength="10">
            <button type="button" class="btn-pwa" style="background:#10b981;color:#fff;padding:10px 18px;border-radius:8px;" onclick="applyCustomCode()">
                Conectar
            </button>
        </div>
    </div>
</div>

<!-- Modal de Instruções caso o navegador exija confirmação pelo menu -->
<div class="install-modal-backdrop" id="installHelpModal">
    <div class="install-modal">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <h3 style="margin:0;font-size:18px;color:#00f2fe;"><i class="fas fa-download mr-1"></i> Como Instalar no Android</h3>
            <button type="button" onclick="document.getElementById('installHelpModal').style.display='none'" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer;">&times;</button>
        </div>
        <div class="install-step">
            <div class="install-step-num">1</div>
            <div style="font-size:14px;color:#e2e8f0;">
                Toque nos <b>3 pontinhos (⋮)</b> no canto superior direito do seu navegador <b>Google Chrome</b>.
            </div>
        </div>
        <div class="install-step">
            <div class="install-step-num">2</div>
            <div style="font-size:14px;color:#e2e8f0;">
                Selecione a opção <b>"Instalar aplicativo"</b> ou <b>"Adicionar à tela inicial"</b>.
            </div>
        </div>
        <div class="install-step">
            <div class="install-step-num">3</div>
            <div style="font-size:14px;color:#e2e8f0;">
                Confirme tocando em <b>Instalar / Adicionar</b>. O ícone <b>Player99</b> aparecerá na tela inicial do seu celular como um App!
            </div>
        </div>
        <button type="button" class="btn-pwa btn-install" style="max-width:100%;margin-top:8px;" onclick="document.getElementById('installHelpModal').style.display='none'">
            Entendi
        </button>
    </div>
</div>

<script>
    let deferredPwaPrompt = null;

    // Se já estiver rodando como App instalado (standalone), oculta o botão de instalar
    if (window.matchMedia('(display-mode: standalone)').matches || window.matchMedia('(display-mode: fullscreen)').matches || window.navigator.standalone === true) {
        const btn = document.getElementById('btnInstallApp');
        if (btn) btn.style.display = 'none';
    }

    window.addEventListener('beforeinstallprompt', function(e) {
        e.preventDefault();
        deferredPwaPrompt = e;
        const btn = document.getElementById('btnInstallApp');
        if (btn) {
            btn.style.display = 'inline-flex';
        }
    });

    window.addEventListener('appinstalled', function() {
        deferredPwaPrompt = null;
        const btn = document.getElementById('btnInstallApp');
        if (btn) btn.style.display = 'none';
    });

    function triggerInstallApp() {
        if (deferredPwaPrompt) {
            deferredPwaPrompt.prompt();
            deferredPwaPrompt.userChoice.then(function(choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    const btn = document.getElementById('btnInstallApp');
                    if (btn) btn.style.display = 'none';
                }
                deferredPwaPrompt = null;
            });
        } else {
            document.getElementById('installHelpModal').style.display = 'flex';
        }
    }

    function enterFullScreen() {
        const el = document.documentElement;
        if (el.requestFullscreen) el.requestFullscreen();
        else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
    }

    function toggleManualCode() {
        const box = document.getElementById('manualCodeBox');
        if (box) {
            box.style.display = (box.style.display === 'block') ? 'none' : 'block';
            if (box.style.display === 'block') {
                document.getElementById('inputCustomCode').focus();
            }
        }
    }

    function generateCode() {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        let result = '';
        for (let i = 0; i < 6; i++) {
            result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return result;
    }

    let tvCode = localStorage.getItem('tv_code');
    if (!tvCode) {
        tvCode = generateCode();
        localStorage.setItem('tv_code', tvCode);
    }
    document.getElementById('displayCode').innerText = tvCode;

    function applyCustomCode() {
        const input = document.getElementById('inputCustomCode');
        if (!input || !input.value.trim()) return;
        tvCode = input.value.trim().toUpperCase();
        localStorage.setItem('tv_code', tvCode);
        document.getElementById('displayCode').innerText = tvCode;
        document.getElementById('manualCodeBox').style.display = 'none';
        checkPairing();
    }

    function checkPairing() {
        try {
            if (!navigator.onLine && localStorage.getItem('is_paired') === 'true') {
                window.location.href = '/player/' + encodeURIComponent(tvCode);
                return;
            }
        } catch (e) {}

        fetch('/api_pairing.php?code=' + encodeURIComponent(tvCode))
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res && res.paired) {
                    try { localStorage.setItem('is_paired', 'true'); } catch (e) {}
                    window.location.href = '/player/' + encodeURIComponent(tvCode);
                }
            })
            .catch(function() {
                try {
                    if (localStorage.getItem('is_paired') === 'true') {
                        window.location.href = '/player/' + encodeURIComponent(tvCode);
                    }
                } catch (e) {}
            });
    }

    setInterval(checkPairing, 4000);
    checkPairing();

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function(reg) {
            console.log('Service Worker Player99 registrado:', reg.scope);
        }).catch(function(err) {
            console.warn('Falha ao registrar Service Worker:', err);
        });
    }
</script>

</body>
</html>
