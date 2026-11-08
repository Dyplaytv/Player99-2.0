<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cadastro de Parceiro - Player99 MVC</title>

    <link rel="manifest" href="/manifest_cliente.json">
    <meta name="theme-color" content="#0d0f17">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.15) rgba(10, 11, 16, 0.5);
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(10, 11, 16, 0.6); }
        ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(52, 226, 228, 0.6); }
        body {
            background: radial-gradient(circle at 50% 10%, #1a1e30 0%, #08090e 100%);
            color: #fff;
            min-height: 100vh;
            margin: 0;
            padding: 30px 15px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .top-nav {
            width: 100%;
            max-width: 480px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .top-nav a {
            color: #8b92b2;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.2s ease;
        }
        .top-nav a:hover {
            color: #34e2e4;
            background: rgba(52, 226, 228, 0.1);
            border-color: rgba(52, 226, 228, 0.3);
        }

        .register-card {
            background: rgba(15, 17, 26, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 22px;
            padding: 35px 30px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7), 0 0 0 1px rgba(255, 255, 255, 0.08);
            border-top: 3px solid #34e2e4;
            position: relative;
        }

        .register-header {
            text-align: center;
            margin-bottom: 20px;
        }
        .register-header .brand-logo {
            max-height: 48px;
            margin-bottom: 12px;
            filter: drop-shadow(0 4px 12px rgba(52, 226, 228, 0.3));
        }
        .register-header h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 4px;
            color: #ffffff;
        }
        .register-header p {
            color: #7e879f;
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        .auth-switch-tabs {
            display: flex;
            background: rgba(24, 27, 40, 0.85);
            border-radius: 14px;
            padding: 4px;
            margin-bottom: 22px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .auth-switch-tab {
            flex: 1;
            text-align: center;
            padding: 10px 14px;
            color: #8b92b2;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 700;
            border-radius: 10px;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .auth-switch-tab.active {
            background: linear-gradient(135deg, #0A38D1 0%, #34e2e4 100%);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(52, 226, 228, 0.3);
        }
        .auth-switch-tab:hover:not(.active) {
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }

        .register-alert {
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.85rem;
            line-height: 1.4;
            margin-bottom: 20px;
            text-align: center;
        }
        .register-alert.alert-danger {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
        }

        .form-group { margin-bottom: 16px; }
        .form-group label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #9aa1b9;
            margin-bottom: 6px;
            display: block;
        }
        .input-box {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-box .icon-left {
            position: absolute;
            left: 14px;
            color: #616a82;
            font-size: 15px;
            pointer-events: none;
        }
        .input-box input {
            width: 100%;
            background: rgba(24, 27, 40, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 11px 40px 11px 42px;
            border-radius: 12px;
            font-size: 0.92rem;
            font-family: inherit;
            transition: all 0.25s ease;
        }
        .input-box input:focus {
            background: rgba(28, 32, 48, 0.98);
            border-color: #34e2e4;
            outline: none;
            box-shadow: 0 0 16px rgba(52, 226, 228, 0.25);
            color: #fff;
        }
        .input-box .toggle-pass {
            position: absolute;
            right: 14px;
            color: #616a82;
            cursor: pointer;
            padding: 6px;
            font-size: 14px;
        }
        .input-box .toggle-pass:hover { color: #34e2e4; }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #0A38D1 0%, #34e2e4 100%);
            border: none;
            color: #ffffff;
            font-weight: 700;
            font-size: 0.95rem;
            padding: 13px;
            border-radius: 12px;
            cursor: pointer;
            box-shadow: 0 8px 24px rgba(10, 56, 209, 0.35);
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(52, 226, 228, 0.45);
            color: #fff;
        }

        .card-footer-links {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            font-size: 0.82rem;
            color: #7e879f;
        }
        .card-footer-links a { color: #34e2e4; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>

    <div class="top-nav">
        <a href="/"><i class="fa fa-arrow-left"></i> Início</a>
        <a href="/login"><i class="fa fa-sign-in"></i> Já tenho conta</a>
    </div>

    <div class="register-card">
        <div class="register-header">
            <img src="/banners/logo.png" alt="Player99" class="brand-logo" onerror="this.style.display='none'">
            <h3>Cadastro de Parceiro</h3>
            <p>Conecte seus veículos e comece a faturar com mídia</p>
        </div>

        <div class="auth-switch-tabs">
            <a href="/login" class="auth-switch-tab">
                <i class="fa fa-sign-in"></i> Entrar
            </a>
            <a href="/cadastro" class="auth-switch-tab active">
                <i class="fa fa-user-plus"></i> Cadastre-se
            </a>
        </div>

        <?php if (!empty($errorMessage)): ?>
            <div class="register-alert alert-danger">
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/cadastro">
            <input type="hidden" name="ref" value="<?= htmlspecialchars($ref ?? '') ?>">

            <div class="form-group">
                <label>Nome Completo / Identificação</label>
                <div class="input-box">
                    <i class="fa fa-user icon-left"></i>
                    <input type="text" name="title" required placeholder="Ex: João da Silva / Carro 01" value="<?= htmlspecialchars($inputTitle ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>WhatsApp com DDD</label>
                <div class="input-box">
                    <i class="fa fa-whatsapp icon-left"></i>
                    <input type="text" name="whatsapp" required placeholder="(99) 99999-9999" value="<?= htmlspecialchars($inputWhatsapp ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>E-mail de Contato</label>
                <div class="input-box">
                    <i class="fa fa-envelope icon-left"></i>
                    <input type="email" name="email" placeholder="seuemail@exemplo.com" value="<?= htmlspecialchars($inputEmail ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Nome de Usuário (Para Login)</label>
                <div class="input-box">
                    <i class="fa fa-id-badge icon-left"></i>
                    <input type="text" name="username" required placeholder="Escolha um usuário (ex: joaosilva)" value="<?= htmlspecialchars($inputUsername ?? '') ?>" autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label>Senha de Acesso</label>
                <div class="input-box">
                    <i class="fa fa-lock icon-left"></i>
                    <input type="password" name="password" id="inputPass" required placeholder="Crie sua senha segura" autocomplete="new-password">
                    <i class="fa fa-eye toggle-pass" id="eyeToggle" onclick="togglePassVis()"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa fa-check-circle me-1"></i> Criar Minha Conta
            </button>
        </form>

        <div class="card-footer-links">
            Já possui uma conta ativa? <a href="/login">Fazer Login</a>
        </div>
    </div>

    <script>
        function togglePassVis() {
            var passInput = document.getElementById("inputPass");
            var eyeIcon = document.getElementById("eyeToggle");
            if (passInput.type === "password") {
                passInput.type = "text";
                eyeIcon.classList.remove("fa-eye");
                eyeIcon.classList.add("fa-eye-slash");
                eyeIcon.style.color = "#34e2e4";
            } else {
                passInput.type = "password";
                eyeIcon.classList.remove("fa-eye-slash");
                eyeIcon.classList.add("fa-eye");
                eyeIcon.style.color = "#616a82";
            }
        }
    </script>
</body>
</html>
