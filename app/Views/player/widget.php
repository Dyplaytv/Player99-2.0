<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> - Player99 DOOH</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html {
            width: 100%;
            height: 100%;
            overflow: hidden;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: <?= $bgGradient ?>;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            user-select: none;
        }
        <?php if (!empty($bgImage)): ?>
        .bg-photo {
            position: absolute;
            inset: 0;
            background: url(<?= json_encode($bgImage) ?>) center center / cover no-repeat;
            filter: brightness(0.35) saturate(1.15);
            z-index: 0;
            animation: zoomBg 18s ease-in-out infinite alternate;
        }
        @keyframes zoomBg {
            from { transform: scale(1); }
            to { transform: scale(1.08); }
        }
        <?php endif; ?>
        .overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(8,10,18,0.65) 0%, rgba(8,10,18,0.3) 40%, rgba(8,10,18,0.92) 100%);
            z-index: 1;
        }
        .top-bar {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 32px 48px;
        }
        .category-pill {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: <?= $accentColor ?>;
            color: #fff;
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 1.25rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
        }
        .live-clock {
            font-size: 1.6rem;
            font-weight: 800;
            background: rgba(0,0,0,0.55);
            padding: 10px 22px;
            border-radius: 14px;
            border: 1px solid rgba(255,255,255,0.15);
            letter-spacing: 1px;
        }
        .center-content {
            position: relative;
            z-index: 2;
            padding: 20px 60px;
            max-width: 1500px;
            margin: auto 0;
        }
        .headline {
            font-size: clamp(2rem, 4.2vw, 3.8rem);
            font-weight: 800;
            line-height: 1.22;
            text-shadow: 0 4px 24px rgba(0,0,0,0.75);
            margin-bottom: 18px;
        }
        .subtext {
            font-size: clamp(1.1rem, 2vw, 1.6rem);
            color: rgba(255,255,255,0.82);
            line-height: 1.45;
            max-width: 1200px;
        }
        .clock-hero {
            text-align: center;
            width: 100%;
        }
        .clock-hero .big-time {
            font-size: clamp(4.5rem, 12vw, 9.5rem);
            font-weight: 800;
            color: #00f3ff;
            text-shadow: 0 0 35px rgba(0, 243, 255, 0.5);
            line-height: 1;
        }
        .clock-hero .big-date {
            font-size: clamp(1.4rem, 3vw, 2.4rem);
            margin-top: 16px;
            color: #d1e0fc;
            font-weight: 600;
            text-transform: capitalize;
        }
        .bottom-bar {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 48px;
            border-top: 1px solid rgba(255,255,255,0.12);
            background: rgba(6, 8, 14, 0.75);
        }
        .brand-footer {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            color: #00f3ff;
        }
        .brand-footer img {
            height: 34px;
            object-fit: contain;
        }
        .dots {
            display: flex;
            gap: 8px;
        }
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
        }
        .dot.active {
            width: 28px;
            border-radius: 10px;
            background: #00f3ff;
        }
    </style>
</head>
<body>
    <?php if (!empty($bgImage)): ?>
        <div class="bg-photo" id="bgPhoto"></div>
    <?php endif; ?>
    <div class="overlay"></div>

    <div class="top-bar">
        <div class="category-pill">
            <i class="<?= htmlspecialchars($icon) ?>"></i>
            <span><?= htmlspecialchars($title) ?></span>
        </div>
        <div class="live-clock" id="topClock"><?= date('H:i') ?></div>
    </div>

    <div class="center-content">
        <?php if ($mode === 'clock'): ?>
            <div class="clock-hero">
                <div class="big-time" id="heroTime"><?= date('H:i:s') ?></div>
                <div class="big-date" id="heroDate"><?= date('d/m/Y') ?></div>
            </div>
        <?php else: ?>
            <div class="headline" id="slideHeadline"><?= htmlspecialchars($items[0]['title'] ?? '') ?></div>
            <?php if (!empty($items[0]['desc'])): ?>
                <div class="subtext" id="slideDesc"><?= htmlspecialchars($items[0]['desc']) ?></div>
            <?php else: ?>
                <div class="subtext" id="slideDesc"></div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="bottom-bar">
        <div class="brand-footer">
            <img src="<?= htmlspecialchars(\App\Core\View::logoUrl()) ?>" alt="Player99" onerror="this.style.display='none'">
            <span>Player99 DOOH • Informação em Tempo Real</span>
        </div>
        <div class="dots" id="dotsContainer">
            <?php foreach ($items as $idx => $it): ?>
                <div class="dot <?= $idx === 0 ? 'active' : '' ?>"></div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function updateClocks() {
            const now = new Date();
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            const topClock = document.getElementById('topClock');
            if (topClock) topClock.innerText = hh + ':' + mm;
            const heroTime = document.getElementById('heroTime');
            if (heroTime) heroTime.innerText = hh + ':' + mm + ':' + ss;
            const heroDate = document.getElementById('heroDate');
            if (heroDate) {
                heroDate.innerText = now.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
            }
        }
        setInterval(updateClocks, 1000);
        updateClocks();

        const slides = <?= json_encode($items) ?>;
        let sIdx = 0;
        if (slides.length > 1) {
            setInterval(() => {
                sIdx = (sIdx + 1) % slides.length;
                const item = slides[sIdx];
                const h = document.getElementById('slideHeadline');
                const d = document.getElementById('slideDesc');
                const bg = document.getElementById('bgPhoto');
                if (h) h.innerText = item.title || '';
                if (d) d.innerText = item.desc || '';
                if (bg && item.image) {
                    bg.style.backgroundImage = 'url(' + JSON.stringify(item.image) + ')';
                }
                const dots = document.querySelectorAll('#dotsContainer .dot');
                dots.forEach((dot, i) => dot.classList.toggle('active', i === sIdx));
            }, 5000);
        }
    </script>
</body>
</html>
