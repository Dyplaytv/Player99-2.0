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
        .bg-photo {
            position: absolute;
            inset: 0;
            background: <?= !empty($bgImage) ? ('url(' . json_encode($bgImage) . ')') : 'none' ?> center center / cover no-repeat;
            filter: brightness(0.35) saturate(1.15);
            z-index: 0;
            animation: zoomBg 18s ease-in-out infinite alternate;
            transition: background-image 0.5s ease-in-out, opacity 0.4s ease;
        }
        @keyframes zoomBg {
            from { transform: scale(1); }
            to { transform: scale(1.08); }
        }
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
            transition: opacity 0.35s ease, transform 0.35s ease;
        }
        .center-content.fade-out {
            opacity: 0;
            transform: translateY(10px);
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
            align-items: center;
            gap: 8px;
        }
        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255,255,255,0.25);
            transition: all 0.3s ease;
        }
        .dot.active {
            width: 28px;
            border-radius: 10px;
            background: #00f3ff;
        }
    </style>
</head>
<body>
    <div class="bg-photo" id="bgPhoto" style="<?= empty($bgImage) ? 'opacity:0;' : 'opacity:1;' ?>"></div>
    <div class="overlay"></div>

    <div class="top-bar">
        <div class="category-pill">
            <i class="<?= htmlspecialchars($icon) ?>"></i>
            <span><?= htmlspecialchars($title) ?></span>
        </div>
        <div class="live-clock" id="topClock"><?= date('H:i') ?></div>
    </div>

    <div class="center-content" id="centerContent">
        <?php if ($mode === 'clock'): ?>
            <div class="clock-hero">
                <div class="big-time" id="heroTime"><?= date('H:i:s') ?></div>
                <div class="big-date" id="heroDate"><?= date('d/m/Y') ?></div>
            </div>
        <?php else: ?>
            <div class="headline" id="slideHeadline"><?= htmlspecialchars($items[0]['title'] ?? '') ?></div>
            <div class="subtext" id="slideDesc"><?= htmlspecialchars($items[0]['desc'] ?? '') ?></div>
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

        const widgetSlug = <?= json_encode($slug ?? 'noticias') ?>;
        const widgetMode = <?= json_encode($mode ?? 'rss') ?>;
        const slides = <?= json_encode(array_values($items)) ?>;
        const storageKey = 'p99_rss_offset_' + widgetSlug;

        // Pré-carrega as imagens de fundo para transição instantânea
        slides.forEach(function(s) {
            if (s && s.image) {
                const img = new Image();
                img.src = s.image;
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        const defaultDurMs = Math.max(4000, parseInt(urlParams.get('dur') || '15000', 10) || 15000);
        const autoStart = urlParams.get('autostart') !== '0';

        let pairTimer = null;
        let standaloneLoopTimer = null;
        let lastCycleStartTime = 0;
        let memoryOffset = 0;

        function getSavedOffset() {
            if (slides.length === 0) return 0;
            try {
                const raw = localStorage.getItem(storageKey);
                if (raw !== null) {
                    const parsed = parseInt(raw, 10);
                    if (!isNaN(parsed) && parsed >= 0) {
                        return parsed % slides.length;
                    }
                }
            } catch (e) {}
            return memoryOffset % slides.length;
        }

        function saveNextOffset(nextIdx) {
            if (slides.length === 0) return;
            memoryOffset = nextIdx % slides.length;
            try {
                localStorage.setItem(storageKey, String(memoryOffset));
            } catch (e) {}
        }

        function renderSlideByIndex(idx, animate) {
            if (!slides.length || widgetMode === 'clock') return;
            const safeIdx = ((idx % slides.length) + slides.length) % slides.length;
            const item = slides[safeIdx] || {};
            const center = document.getElementById('centerContent');
            const h = document.getElementById('slideHeadline');
            const d = document.getElementById('slideDesc');
            const bg = document.getElementById('bgPhoto');
            const dots = document.querySelectorAll('#dotsContainer .dot');

            const applyContent = function() {
                if (h) h.innerText = item.title || '';
                if (d) d.innerText = item.desc || '';
                if (bg) {
                    if (item.image) {
                        bg.style.backgroundImage = 'url(' + JSON.stringify(item.image) + ')';
                        bg.style.opacity = '1';
                    }
                }
                dots.forEach(function(dot, i) {
                    dot.classList.toggle('active', i === safeIdx);
                });
            };

            if (animate && center) {
                center.classList.add('fade-out');
                setTimeout(function() {
                    applyContent();
                    center.classList.remove('fade-out');
                }, 260);
            } else {
                applyContent();
            }
        }

        /**
         * Exibe no máximo 2 notícias por entrada da categoria:
         * - 1ª notícia do par entra imediatamente (0s até metade do tempo)
         * - 2ª notícia do par entra na metade do tempo (ex: aos 7.5s de 15s)
         * - Salva o próximo índice (+2) para que na próxima vez que a categoria entrar
         *   na playlist ela exiba as próximas 2 notícias até finalizar todas e recomeçar.
         */
        function startPairCycle(durationMs) {
            if (widgetMode === 'clock' || slides.length <= 1) return;

            const now = Date.now();
            if (now - lastCycleStartTime < 1200) {
                return;
            }
            lastCycleStartTime = now;

            if (pairTimer) {
                clearTimeout(pairTimer);
                pairTimer = null;
            }

            const totalMs = Math.max(4000, parseInt(durationMs || defaultDurMs, 10) || 15000);
            const halfMs = Math.floor(totalMs / 2);

            const firstIdx = getSavedOffset();
            const hasSecondInBatch = (firstIdx + 1) < slides.length;
            const nextOffset = (firstIdx + 2 >= slides.length) ? 0 : (firstIdx + 2);

            // Salva imediatamente o ponteiro para a próxima entrada desta categoria
            saveNextOffset(nextOffset);

            // Mostra a 1ª notícia deste ciclo imediatamente
            renderSlideByIndex(firstIdx, false);

            // Na metade do tempo da categoria (ex: 7.5s de 15s), mostra no máximo a 2ª notícia e para
            if (hasSecondInBatch) {
                pairTimer = setTimeout(function() {
                    renderSlideByIndex(firstIdx + 1, true);
                }, halfMs);
            }
        }

        // Prepara visualmente a notícia atual sem avançar o contador caso esteja pré-carregado oculto
        renderSlideByIndex(getSavedOffset(), false);

        // Escuta o comando do player principal quando este widget fica ativo na tela
        window.addEventListener('message', function(event) {
            if (event && event.data && event.data.type === 'START_WIDGET_CYCLE') {
                startPairCycle(event.data.durationMs || defaultDurMs);
            }
        });

        // Se for o primeiro item da playlist ou aberto diretamente no navegador
        if (autoStart || window.self === window.top) {
            startPairCycle(defaultDurMs);
            if (window.self === window.top && slides.length > 1) {
                standaloneLoopTimer = setInterval(function() {
                    startPairCycle(defaultDurMs);
                }, defaultDurMs);
            }
        }
    </script>
</body>
</html>
