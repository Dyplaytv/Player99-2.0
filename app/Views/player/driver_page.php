<?php
/** @var array $screen */
/** @var array $videos */
/** @var string $codigo */
/** @var string $driverName */
/** @var int $driverId */
/** @var bool $locked */

$screenId = (int)($screen['id'] ?? 0);
$lojaUrl = '/loja?carro=' . $screenId . ($driverId > 0 ? '&motorista=' . (int)$driverId : '');
$clientId = (int)($screen['id_cliente'] ?? 0);
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($driverName) ?> • Player99</title>
    <link rel="icon" type="image/png" href="<?= \App\Core\View::logoUrl() ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: radial-gradient(circle at top right, rgba(52, 226, 228, 0.14), transparent 40%),
                        radial-gradient(circle at bottom left, rgba(255, 170, 0, 0.12), transparent 45%),
                        #080a12;
            color: #f8fafc;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            padding: 18px 14px 40px;
        }
        .container-driver {
            max-width: 820px;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        /* Header acolhedor */
        .driver-header {
            background: rgba(16, 20, 34, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 22px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            backdrop-filter: blur(12px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }
        .driver-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }
        .driver-brand img {
            height: 42px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(52, 226, 228, 0.4));
        }
        .driver-welcome {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #34e2e4;
            font-weight: 800;
        }
        .driver-name {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Card do Player */
        .player-card {
            background: rgba(14, 18, 30, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        .player-stage {
            position: relative;
            width: 100%;
            aspect-ratio: 16 / 9;
            max-height: 70vh;
            background: #000;
            overflow: hidden;
            margin: 0 auto;
            transition: all 0.25s ease;
        }
        .player-stage.is-portrait {
            aspect-ratio: 9 / 16;
            max-width: 400px;
            max-height: 68vh;
        }
        @media (max-width: 640px) {
            .player-stage:not(.is-portrait) {
                aspect-ratio: 16 / 10;
                min-height: 220px;
            }
            .player-stage.is-portrait {
                max-width: 330px;
                max-height: 64vh;
            }
        }
        .stage-item {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            border: none;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.35s ease;
        }
        .stage-item.active {
            opacity: 1;
            pointer-events: auto;
            z-index: 2;
        }
        .player-empty {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
            color: #94a3b8;
        }
        .player-controls {
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(10, 13, 22, 0.9);
        }
        .now-playing {
            min-width: 0;
            flex: 1;
        }
        .now-playing-label {
            font-size: 11px;
            color: #34e2e4;
            font-weight: 700;
            text-transform: uppercase;
        }
        .now-playing-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ctrl-btns {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ctrl-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #fff;
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .ctrl-btn:hover {
            background: rgba(52, 226, 228, 0.2);
            border-color: #34e2e4;
            color: #34e2e4;
        }

        /* Lista para rever anúncios */
        .playlist-strip {
            padding: 12px 16px 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
        }
        .playlist-strip-title {
            font-size: 12px;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .playlist-items {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        .pl-chip {
            flex-shrink: 0;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #cbd5e1;
            border-radius: 12px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            max-width: 210px;
            transition: all 0.2s;
        }
        .pl-chip span {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .pl-chip.active, .pl-chip:hover {
            background: rgba(52, 226, 228, 0.16);
            border-color: #34e2e4;
            color: #fff;
        }

        /* Banner Destaque Achadinhos da Player99 */
        .achadinhos-hero {
            position: relative;
            background: linear-gradient(135deg, rgba(255, 170, 0, 0.18) 0%, rgba(234, 88, 12, 0.22) 50%, rgba(16, 20, 34, 0.95) 100%);
            border: 2px solid #ffaa00;
            border-radius: 26px;
            padding: 28px 24px;
            text-align: center;
            box-shadow: 0 15px 45px rgba(255, 170, 0, 0.22);
            overflow: hidden;
        }
        .achadinhos-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 170, 0, 0.18);
            border: 1px solid rgba(255, 170, 0, 0.5);
            color: #fbbf24;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 14px;
        }
        .achadinhos-title {
            font-size: clamp(1.35rem, 4vw, 2rem);
            font-weight: 900;
            line-height: 1.22;
            color: #fff;
            margin-bottom: 10px;
        }
        .achadinhos-title span {
            background: linear-gradient(135deg, #fbbf24, #ff6b00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .achadinhos-sub {
            color: #cbd5e1;
            font-size: 0.98rem;
            margin-bottom: 22px;
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.5;
        }
        .btn-achadinhos {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, #ffaa00, #f97316);
            color: #0b0e17 !important;
            text-decoration: none;
            font-weight: 900;
            font-size: 1.05rem;
            padding: 15px 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(249, 115, 22, 0.45);
            transition: all 0.25s ease;
        }
        .btn-achadinhos:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 14px 35px rgba(255, 170, 0, 0.6);
        }
        .driver-footer {
            text-align: center;
            color: #64748b;
            font-size: 12px;
            margin-top: 6px;
        }
    </style>
</head>
<body>
    <div class="container-driver">
        <!-- Cabeçalho -->
        <header class="driver-header">
            <div class="driver-brand">
                <img src="<?= \App\Core\View::logoUrl() ?>" alt="Player99" onerror="this.src='/assets/images/logo.png'">
                <div style="min-width: 0;">
                    <div class="driver-welcome"><i class="fas fa-car"></i> Você está viajando com</div>
                    <div class="driver-name"><?= htmlspecialchars($driverName) ?></div>
                </div>
            </div>
        </header>

        <!-- Player Automático com a Mesma Playlist do Tablet -->
        <?php $screenRotation = (int)($screen['rotation'] ?? 0); ?>
        <section class="player-card">
            <div class="player-stage <?= in_array($screenRotation, [90, 270], true) ? 'is-portrait' : '' ?>" id="driverStage">
                <?php if (empty($videos)): ?>
                    <div class="player-empty">
                        <i class="fas fa-film fa-3x mb-2" style="color:#34e2e4;"></i>
                        <div style="font-weight:700; color:#fff; margin-bottom:4px;">Nenhum anúncio na playlist agora</div>
                        <small>Aproveite as ofertas exclusivas da Player99 logo abaixo!</small>
                    </div>
                <?php else: ?>
                    <?php foreach ($videos as $idx => $v):
                        $fn = (string)($v['filename'] ?? '');
                        $titleItem = trim((string)($v['title'] ?? 'Anúncio ' . ($idx + 1)));
                        $durMs = ((int)($v['duration'] ?? 15) ?: 15) * 1000;
                        $isWidget = (strpos($fn, '/widgets/') === 0);
                        $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                        $mediaUrl = ($isWidget || strpos($fn, '/') === 0) ? $fn : ('/uploads/clientes/' . $clientId . '/' . $fn);
                    ?>
                        <?php if ($isWidget):
                            $sep = (strpos($mediaUrl, '?') !== false) ? '&' : '?';
                            $widgetUrl = $mediaUrl . $sep . 'dur=' . $durMs . '&autostart=' . ($idx === 0 ? '1' : '0');
                        ?>
                            <iframe src="<?= htmlspecialchars($widgetUrl) ?>" class="stage-item <?= $idx === 0 ? 'active' : '' ?>" data-type="widget" data-duration="<?= $durMs ?>" data-title="<?= htmlspecialchars($titleItem) ?>"></iframe>
                        <?php elseif ($isImg): ?>
                            <img src="<?= htmlspecialchars($mediaUrl) ?>" class="stage-item <?= $idx === 0 ? 'active' : '' ?>" data-type="image" data-duration="<?= $durMs ?>" data-title="<?= htmlspecialchars($titleItem) ?>" alt="<?= htmlspecialchars($titleItem) ?>">
                        <?php else: ?>
                            <video src="<?= htmlspecialchars($mediaUrl) ?>" class="stage-item <?= $idx === 0 ? 'active' : '' ?>" data-type="video" data-title="<?= htmlspecialchars($titleItem) ?>" playsinline webkit-playsinline muted></video>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($videos)): ?>
                <div class="player-controls">
                    <div class="now-playing">
                        <div class="now-playing-label"><i class="fas fa-play-circle"></i> Passando no Tablet</div>
                        <div class="now-playing-title" id="nowPlayingTitle"><?= htmlspecialchars((string)($videos[0]['title'] ?? 'Anúncio 1')) ?></div>
                    </div>
                    <div class="ctrl-btns">
                        <button type="button" class="ctrl-btn" onclick="prevSlide()" title="Anúncio anterior"><i class="fas fa-step-backward"></i></button>
                        <button type="button" class="ctrl-btn" id="btnPlayPause" onclick="togglePlayPause()" title="Pausar/Continuar"><i class="fas fa-pause"></i></button>
                        <button type="button" class="ctrl-btn" onclick="nextSlide()" title="Próximo anúncio"><i class="fas fa-step-forward"></i></button>
                        <button type="button" class="ctrl-btn" id="btnMute" onclick="toggleMute()" title="Som"><i class="fas fa-volume-mute"></i></button>
                    </div>
                </div>

                <div class="playlist-strip">
                    <div class="playlist-strip-title">
                        <i class="fas fa-history" style="color:#34e2e4;"></i>
                        Quer rever algum anúncio? Toque abaixo para assistir:
                    </div>
                    <div class="playlist-items" id="playlistChips">
                        <?php foreach ($videos as $idx => $v):
                            $tLabel = trim((string)($v['title'] ?? 'Anúncio ' . ($idx + 1)));
                        ?>
                            <button type="button" class="pl-chip <?= $idx === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $idx ?>)">
                                <i class="fas fa-play" style="font-size:10px; color:#34e2e4;"></i>
                                <span><?= htmlspecialchars($tLabel) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Chamada Principal para os Achadinhos da Player99 -->
        <section class="achadinhos-hero">
            <div class="achadinhos-badge">
                <i class="fas fa-fire"></i> Ofertas Exclusivas de Viagem
            </div>
            <h1 class="achadinhos-title">
                Acesse os <span>Achadinhos da Player99</span> e descubra produtos com descontos exclusivos para você!
            </h1>
            <p class="achadinhos-sub">
                Selecionamos diariamente promoções imperdíveis, eletrônicos, utilidades e serviços com condições especiais para passageiros.
            </p>
            <a href="<?= htmlspecialchars($lojaUrl) ?>" class="btn-achadinhos">
                <i class="fas fa-shopping-bag"></i>
                Acessar Achadinhos &amp; Loja Player99
            </a>
        </section>

        <footer class="driver-footer">
            Player99 DOOH • Experiência interativa para passageiros
        </footer>
    </div>

    <script>
        const items = Array.from(document.querySelectorAll('.stage-item'));
        const chips = Array.from(document.querySelectorAll('.pl-chip'));
        const titleEl = document.getElementById('nowPlayingTitle');
        const btnPlayPause = document.getElementById('btnPlayPause');
        const btnMute = document.getElementById('btnMute');

        let currentIdx = 0;
        let slideTimer = null;
        let isPaused = false;
        let isMuted = true;

        const defaultPortrait = <?= in_array((int)($screen['rotation'] ?? 0), [90, 270], true) ? 'true' : 'false' ?>;
        const stageEl = document.getElementById('driverStage');

        function syncStageAspect(el) {
            if (!stageEl || !el) return;
            if (el.tagName === 'VIDEO') {
                if (el.videoWidth > 0 && el.videoHeight > 0) {
                    stageEl.classList.toggle('is-portrait', el.videoHeight > el.videoWidth);
                } else {
                    el.addEventListener('loadedmetadata', function onMeta() {
                        el.removeEventListener('loadedmetadata', onMeta);
                        if (el.classList.contains('active') && el.videoWidth > 0 && el.videoHeight > 0) {
                            stageEl.classList.toggle('is-portrait', el.videoHeight > el.videoWidth);
                        }
                    });
                }
            } else if (el.tagName === 'IMG') {
                if (el.naturalWidth > 0 && el.naturalHeight > 0) {
                    stageEl.classList.toggle('is-portrait', el.naturalHeight > el.naturalWidth);
                } else {
                    el.addEventListener('load', function onLoad() {
                        el.removeEventListener('load', onLoad);
                        if (el.classList.contains('active') && el.naturalWidth > 0 && el.naturalHeight > 0) {
                            stageEl.classList.toggle('is-portrait', el.naturalHeight > el.naturalWidth);
                        }
                    });
                }
            } else {
                stageEl.classList.toggle('is-portrait', defaultPortrait);
            }
        }

        function startSlide(idx) {
            if (items.length === 0) return;
            if (slideTimer) clearTimeout(slideTimer);

            currentIdx = (idx + items.length) % items.length;

            items.forEach((el, i) => {
                el.classList.toggle('active', i === currentIdx);
                if (el.tagName === 'VIDEO' && i !== currentIdx) {
                    el.pause();
                    el.currentTime = 0;
                }
            });

            chips.forEach((c, i) => c.classList.toggle('active', i === currentIdx));

            const active = items[currentIdx];
            syncStageAspect(active);
            if (titleEl && active) {
                titleEl.textContent = active.getAttribute('data-title') || ('Anúncio ' + (currentIdx + 1));
            }

            if (isPaused) return;

            if (active.tagName === 'VIDEO') {
                active.muted = isMuted;
                active.currentTime = 0;
                active.play().catch(() => {
                    active.muted = true;
                    isMuted = true;
                    updateMuteIcon();
                    active.play().catch(() => {
                        slideTimer = setTimeout(nextSlide, 5000);
                    });
                });
                active.onended = nextSlide;
            } else {
                const dur = parseInt(active.getAttribute('data-duration') || '12000', 10) || 12000;
                slideTimer = setTimeout(nextSlide, dur);
            }
        }

        function nextSlide() {
            startSlide(currentIdx + 1);
        }

        function prevSlide() {
            startSlide(currentIdx - 1);
        }

        function goToSlide(idx) {
            isPaused = false;
            updatePlayIcon();
            startSlide(idx);
        }

        function togglePlayPause() {
            if (items.length === 0) return;
            isPaused = !isPaused;
            updatePlayIcon();
            const active = items[currentIdx];
            if (isPaused) {
                if (slideTimer) clearTimeout(slideTimer);
                if (active && active.tagName === 'VIDEO') active.pause();
            } else {
                if (active && active.tagName === 'VIDEO') {
                    active.play().catch(() => {});
                } else {
                    startSlide(currentIdx);
                }
            }
        }

        function toggleMute() {
            isMuted = !isMuted;
            updateMuteIcon();
            const active = items[currentIdx];
            if (active && active.tagName === 'VIDEO') {
                active.muted = isMuted;
            }
        }

        function updatePlayIcon() {
            if (!btnPlayPause) return;
            btnPlayPause.innerHTML = isPaused ? '<i class="fas fa-play"></i>' : '<i class="fas fa-pause"></i>';
        }

        function updateMuteIcon() {
            if (!btnMute) return;
            btnMute.innerHTML = isMuted ? '<i class="fas fa-volume-mute"></i>' : '<i class="fas fa-volume-up"></i>';
        }

        if (items.length > 0) {
            startSlide(0);
        }
    </script>
</body>
</html>
