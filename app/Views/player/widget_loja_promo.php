<?php
/** @var array $item */
$item = $item ?? [];
$isStore = (($item['item_type'] ?? 'store') === 'store');
$title = trim((string)($item['title'] ?? 'Oferta Player99'));
$desc = trim((string)($item['description'] ?? ''));
$image = trim((string)($item['image_url'] ?? ''));
$priceOld = (float)($item['price_old'] ?? 0);
$priceNew = (float)($item['price_new'] ?? 0);
$discountPct = (int)($item['discount_pct'] ?? 0);
$platform = trim((string)($item['platform'] ?? 'Player99'));
$targetPath = (string)($item['target_path'] ?? '/loja');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$targetUrl = $scheme . '://' . $host . $targetPath;
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=280x280&margin=4&data=' . urlencode($targetUrl);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Playlist da Player99</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #070a14;
            color: #fff;
        }
        .promo-bg {
            position: fixed;
            inset: -30px;
            background: radial-gradient(circle at 20% 20%, rgba(52, 226, 228, 0.18), transparent 45%),
                        radial-gradient(circle at 80% 80%, rgba(255, 170, 0, 0.2), transparent 50%),
                        #070a14;
            z-index: 0;
        }
        <?php if ($image !== ''): ?>
        .promo-bg-img {
            position: fixed;
            inset: -40px;
            background-image: url('<?= htmlspecialchars($image, ENT_QUOTES) ?>');
            background-size: cover;
            background-position: center;
            filter: blur(42px) brightness(0.28) saturate(1.3);
            z-index: 0;
        }
        <?php endif; ?>
        .promo-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100%;
            padding: 3.5vh 4vw;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 2vh;
        }
        .promo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .brand-pill {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.85);
            border: 2px solid <?= $isStore ? '#34e2e4' : '#ffaa00' ?>;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: clamp(12px, 1.8vw, 18px);
            font-weight: 800;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: <?= $isStore ? '#34e2e4' : '#ffaa00' ?>;
            box-shadow: 0 0 24px <?= $isStore ? 'rgba(52,226,228,0.28)' : 'rgba(255,170,0,0.28)' ?>;
        }
        .discount-pill {
            background: linear-gradient(135deg, #ef4444, #f97316);
            color: #fff;
            padding: 8px 18px;
            border-radius: 999px;
            font-size: clamp(13px, 1.9vw, 20px);
            font-weight: 900;
            text-transform: uppercase;
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.45);
        }
        .promo-main {
            flex: 1;
            min-height: 0;
            display: grid;
            grid-template-columns: 1.05fr 1.15fr;
            gap: 3.5vw;
            align-items: center;
        }
        .media-card {
            width: 100%;
            height: 100%;
            max-height: 62vh;
            background: rgba(15, 23, 42, 0.72);
            border: 2px solid rgba(255, 255, 255, 0.14);
            border-radius: 28px;
            padding: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55);
            overflow: hidden;
        }
        .media-card img {
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
            border-radius: 18px;
        }
        .media-placeholder {
            font-size: clamp(32px, 5vw, 64px);
            font-weight: 900;
            color: #ffaa00;
            text-align: center;
        }
        .info-col {
            display: flex;
            flex-direction: column;
            gap: 1.8vh;
            min-width: 0;
        }
        .promo-title {
            font-size: clamp(22px, 3.3vw, 44px);
            font-weight: 900;
            line-height: 1.15;
            color: #ffffff;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .promo-desc {
            font-size: clamp(14px, 1.7vw, 20px);
            color: #cbd5e1;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .price-qr-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-top: 1vh;
            background: rgba(12, 16, 30, 0.85);
            border: 2px solid rgba(255, 170, 0, 0.45);
            border-radius: 24px;
            padding: 16px 22px;
        }
        .price-old {
            font-size: clamp(14px, 1.6vw, 20px);
            color: #94a3b8;
            text-decoration: line-through;
            font-weight: 600;
        }
        .price-new {
            font-size: clamp(28px, 4.2vw, 56px);
            font-weight: 900;
            color: #00ffaa;
            line-height: 1.05;
        }
        .qr-cta-box {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-shrink: 0;
        }
        .qr-frame {
            background: #fff;
            padding: 8px;
            border-radius: 14px;
            line-height: 0;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }
        .qr-frame img {
            width: clamp(86px, 11vw, 136px);
            height: clamp(86px, 11vw, 136px);
            display: block;
        }
        .qr-label {
            max-width: 140px;
            font-size: clamp(11px, 1.3vw, 15px);
            font-weight: 800;
            color: #ffaa00;
            text-transform: uppercase;
            line-height: 1.25;
        }
        .promo-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: clamp(11px, 1.3vw, 15px);
            color: #94a3b8;
            font-weight: 600;
        }

        /* Adaptação automática para telas na vertical (Portrait) */
        @media (orientation: portrait) {
            .promo-main {
                grid-template-columns: 1fr;
                grid-template-rows: 42% 1fr;
                gap: 2.2vh;
            }
            .media-card {
                max-height: 100%;
            }
            .promo-title {
                font-size: clamp(20px, 4.8vw, 34px);
            }
            .price-qr-row {
                flex-direction: row;
                padding: 14px 16px;
            }
            .price-new {
                font-size: clamp(26px, 6.2vw, 44px);
            }
            .qr-label {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="promo-bg"></div>
    <?php if ($image !== ''): ?>
        <div class="promo-bg-img"></div>
    <?php endif; ?>

    <div class="promo-wrap">
        <div class="promo-header">
            <div class="brand-pill">
                <?= $isStore ? '🛒 Playlist da Player99 • Loja Oficial' : '🔥 Playlist da Player99 • ' . htmlspecialchars(ucfirst($platform)) ?>
            </div>
            <?php if ($discountPct > 0): ?>
                <div class="discount-pill">-<?= $discountPct ?>% OFF</div>
            <?php endif; ?>
        </div>

        <div class="promo-main">
            <div class="media-card">
                <?php if ($image !== ''): ?>
                    <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($title) ?>">
                <?php else: ?>
                    <div class="media-placeholder">Player99<br>Oferta Especial</div>
                <?php endif; ?>
            </div>

            <div class="info-col">
                <div class="promo-title"><?= htmlspecialchars($title) ?></div>
                <?php if ($desc !== ''): ?>
                    <div class="promo-desc"><?= htmlspecialchars($desc) ?></div>
                <?php endif; ?>

                <div class="price-qr-row">
                    <div>
                        <?php if ($priceOld > $priceNew && $priceOld > 0): ?>
                            <div class="price-old">De R$ <?= number_format($priceOld, 2, ',', '.') ?></div>
                        <?php endif; ?>
                        <div class="price-new">
                            <?= $priceNew > 0 ? ('R$ ' . number_format($priceNew, 2, ',', '.')) : 'Oferta Especial' ?>
                        </div>
                    </div>

                    <div class="qr-cta-box">
                        <div class="qr-frame">
                            <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Code da Oferta">
                        </div>
                        <div class="qr-label">Aponte a câmera e garanta já!</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="promo-footer">
            <span>Playlist da Player99 • Propaganda Automática da Loja</span>
            <span>Escaneie o QR Code para comprar pelo celular</span>
        </div>
    </div>
</body>
</html>
