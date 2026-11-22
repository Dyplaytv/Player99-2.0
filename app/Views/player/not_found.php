<?php
/** @var string $codigo */
/** @var string $headline */
/** @var string $subtext */
$codigo = trim((string)($codigo ?? ''));
$headline = $headline ?? 'Código de tela não encontrado';
$subtext = $subtext ?? 'Verifique se o código foi digitado corretamente ou vincule esta tela no Portal do Motorista.';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player99 • Tela não encontrada</title>
    <link rel="icon" type="image/png" href="<?= \App\Core\View::logoUrl() ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: radial-gradient(circle at 20% 20%, rgba(52, 226, 228, 0.16), transparent 45%),
                        radial-gradient(circle at 80% 80%, rgba(10, 56, 209, 0.22), transparent 50%),
                        #070911;
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .nf-card {
            width: 100%;
            max-width: 460px;
            background: rgba(15, 19, 32, 0.88);
            border: 1px solid rgba(52, 226, 228, 0.25);
            border-radius: 28px;
            padding: 36px 28px;
            text-align: center;
            backdrop-filter: blur(18px);
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.55), 0 0 40px rgba(52, 226, 228, 0.08);
        }
        .nf-logo {
            height: 54px;
            width: auto;
            object-fit: contain;
            margin-bottom: 14px;
            filter: drop-shadow(0 4px 12px rgba(52, 226, 228, 0.35));
        }
        .nf-brand {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #ffffff 0%, #34e2e4 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 18px;
        }
        .nf-icon-wrap {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 20px;
            background: rgba(245, 158, 11, 0.12);
            border: 1px solid rgba(245, 158, 11, 0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.65rem;
            color: #fbbf24;
        }
        .nf-code {
            display: inline-block;
            background: rgba(52, 226, 228, 0.1);
            border: 1px solid rgba(52, 226, 228, 0.35);
            color: #34e2e4;
            font-family: monospace;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 2px;
            padding: 6px 16px;
            border-radius: 999px;
            margin-bottom: 14px;
        }
        .nf-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 10px;
        }
        .nf-desc {
            font-size: 0.94rem;
            color: #94a3b8;
            line-height: 1.55;
            margin-bottom: 26px;
        }
        .nf-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        .nf-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 14px;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
        }
        .nf-btn-primary {
            background: linear-gradient(135deg, #0A38D1 0%, #34e2e4 100%);
            color: #fff;
            box-shadow: 0 6px 20px rgba(52, 226, 228, 0.3);
        }
        .nf-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(52, 226, 228, 0.45);
        }
        .nf-btn-outline {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.16);
            color: #e2e8f0;
        }
        .nf-btn-outline:hover {
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="nf-card">
        <img src="<?= \App\Core\View::logoUrl() ?>" alt="Player99" class="nf-logo" onerror="this.style.display='none'">
        <div class="nf-brand">Player99</div>

        <div class="nf-icon-wrap">
            <i class="fas fa-tv"></i>
        </div>

        <?php if ($codigo !== ''): ?>
            <div class="nf-code"><?= htmlspecialchars(strtoupper($codigo)) ?></div>
        <?php endif; ?>

        <h1 class="nf-title"><?= htmlspecialchars($headline) ?></h1>
        <p class="nf-desc"><?= htmlspecialchars($subtext) ?></p>

        <div class="nf-actions">
            <button type="button" class="nf-btn nf-btn-primary" onclick="window.location.reload()">
                <i class="fas fa-sync-alt"></i> Tentar Novamente
            </button>
            <a href="/login" class="nf-btn nf-btn-outline">
                <i class="fas fa-sign-in-alt"></i> Portal do Motorista
            </a>
        </div>
    </div>
</body>
</html>
