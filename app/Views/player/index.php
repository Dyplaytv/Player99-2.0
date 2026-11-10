<?php
$rotation = (int)($screen['rotation'] ?? 0);
$audioMode = $radioInfo['audio_mode'] ?? ($screen['audio_mode'] ?? 'video');
$radioUrl = $radioInfo['radio_url'] ?? '';
$radioName = $radioInfo['radio_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/images/icon-192.png">
    <link rel="apple-touch-icon" href="/assets/images/icon-192.png">
    <title>Player99 DOOH - Tela <?= htmlspecialchars($codigo) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* A) Modo Horizontal Padrão (Landscape) */
        body, html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background-color: #000;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: sans-serif;
            user-select: none;
            -webkit-user-select: none;
        }

        #playerContainer {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background-color: #000;
        }

        video, img, iframe.media-item {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Mantém a proporção exata sem cortar detalhes */
            background-color: #000;
            pointer-events: none; /* Bloqueia toques acidentais do passageiro */
            border: none;
            display: none;
        }

        video.active, img.active, iframe.media-item.active {
            display: block;
        }

        /* Esconder todos os controles nativos do Android/WebView */
        video::-webkit-media-controls,
        video::-webkit-media-controls-enclosure,
        video::-webkit-media-controls-panel,
        video::-webkit-media-controls-play-button,
        video::-webkit-media-controls-start-playback-button {
            display: none !important;
            opacity: 0 !important;
        }

        /* Modo Horizontal Invertido (180°) */
        body.rot-180 {
            transform: rotate(180deg) !important;
            transform-origin: center center !important;
        }

        /* B) Modo Rotação 90° / 270° (Tablet na Vertical / Retrato) */
        body.rot-90 {
            width: 100vh !important;
            height: 100vw !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) rotate(90deg) !important;
            transform-origin: center center !important;
            overflow: hidden;
            background-color: #000;
        }

        body.rot-270 {
            width: 100vh !important;
            height: 100vw !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) rotate(270deg) !important;
            transform-origin: center center !important;
            overflow: hidden;
            background-color: #000;
        }

        body.rot-90 video, body.rot-90 img,
        body.rot-270 video, body.rot-270 img {
            object-fit: cover !important; /* Na vertical preenche 100% da tela sem faixas pretas */
        }

        <?php if ($rotation === 90 || $rotation === 270): ?>
        body {
            width: 100vh !important;
            height: 100vw !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) rotate(<?= $rotation ?>deg) !important;
            transform-origin: center center !important;
            overflow: hidden;
            background-color: #000;
        }
        video, img {
            object-fit: cover !important; /* Na vertical preenche 100% da tela sem faixas pretas */
        }
        <?php endif; ?>

        .no-media {
            color: #fff;
            text-align: center;
            padding: 30px;
        }
        .no-media h2 { font-size: 2rem; margin-bottom: 10px; color: #34e2e4; }
        .no-media p { font-size: 1.1rem; color: #8b92b2; }

        .telemetry-badge {
            position: absolute;
            bottom: 10px;
            right: 15px;
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            font-family: monospace;
            pointer-events: none;
            z-index: 1000;
        }

        #audioUnlockBanner {
            display: none;
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.92);
            color: #00f3ff;
            border: 1px solid #00f3ff;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            z-index: 2000;
            box-shadow: 0 4px 20px rgba(0, 243, 255, 0.35);
            pointer-events: auto;
        }

        /* Banner Flutuante de Achadinhos / Ofertas E-commerce */
        #dealBanner {
            display: none;
            position: absolute;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(10, 14, 26, 0.94);
            border: 2px solid #ffaa00;
            border-radius: 50px;
            padding: 6px 18px 6px 10px;
            color: #fff;
            z-index: 3000;
            box-shadow: 0 8px 30px rgba(255, 170, 0, 0.4);
            cursor: pointer;
            backdrop-filter: blur(10px);
            align-items: center;
            gap: 12px;
            max-width: 90%;
            pointer-events: auto;
            animation: pulseBanner 2.5s infinite;
        }
        @keyframes pulseBanner {
            0%, 100% { box-shadow: 0 8px 30px rgba(255, 170, 0, 0.35); transform: translateX(-50%) scale(1); }
            50% { box-shadow: 0 10px 40px rgba(255, 170, 0, 0.7); transform: translateX(-50%) scale(1.02); }
        }
        #dealBanner img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffaa00;
            display: block;
        }
        .deal-btn-touch {
            background: linear-gradient(135deg, #ffaa00, #ff4500);
            color: #fff;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        /* Modal Tela Cheia com QR Code Ampliado e Timer */
        #dealModal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(5, 8, 16, 0.96);
            z-index: 9000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
            text-align: center;
            pointer-events: auto;
        }
        .deal-modal-card {
            background: #101422;
            border: 2px solid #00f3ff;
            border-radius: 24px;
            padding: 24px;
            max-width: 440px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 243, 255, 0.3);
            position: relative;
        }
        .deal-timer-bar-wrap {
            width: 100%;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            margin-top: 16px;
            overflow: hidden;
        }
        .deal-timer-bar {
            height: 100%;
            width: 100%;
            background: #00f3ff;
            transition: width 1s linear;
        }
        .deal-close-btn {
            position: absolute;
            top: 12px;
            right: 14px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #fff;
            font-size: 18px;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body class="rot-<?= $rotation ?>">

    <div id="playerContainer">
        <div id="audioUnlockBanner" onclick="unlockAudioNow()">
            🔊 Toque aqui para ativar o som da Rádio <span id="unlockRadioName"><?= htmlspecialchars($radioName) ?></span>
        </div>

        <?php if (empty($videos)): ?>
            <div class="no-media">
                <?php if (!empty($locked)): ?>
                <h2>Tela Bloqueada</h2>
                <p>Tela: <b><?= htmlspecialchars($codigo) ?></b></p>
                <p>Plano vencido. Renove pelo Portal do Motorista para voltar a exibir a programação.</p>
                <?php else: ?>
                <h2>Player99 em Operação</h2>
                <p>Tela Conectada: <b><?= htmlspecialchars($codigo) ?></b></p>
                <p>Nenhuma mídia vinculada no momento. Envie vídeos ou imagens pelo painel.</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($videos as $idx => $v):
                $fn = (string)($v['filename'] ?? '');
                $durMs = ((int)($v['duration'] ?? 15) ?: 15) * 1000;
                $isWidget = (strpos($fn, '/widgets/') === 0);
                $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                $mediaUrl = ($isWidget || strpos($fn, '/') === 0) ? $fn : ('/uploads/clientes/' . $screen['id_cliente'] . '/' . $fn);
            ?>
                <?php if ($isWidget):
                    $sep = (strpos($mediaUrl, '?') !== false) ? '&' : '?';
                    $widgetUrlWithParams = $mediaUrl . $sep . 'dur=' . $durMs . '&autostart=' . ($idx === 0 ? '1' : '0');
                ?>
                    <iframe src="<?= htmlspecialchars($widgetUrlWithParams) ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="widget" data-duration="<?= $durMs ?>" id="media_<?= $idx ?>"></iframe>
                <?php elseif ($isImg): ?>
                    <img src="<?= htmlspecialchars($mediaUrl) ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="image" data-duration="<?= $durMs ?>" id="media_<?= $idx ?>">
                <?php else: ?>
                    <video src="<?= htmlspecialchars($mediaUrl) ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="video" id="media_<?= $idx ?>" playsinline webkit-playsinline disablePictureInPicture controlsList="nodownload nofullscreen noremoteplayback" <?= ($audioMode !== 'video') ? 'muted' : '' ?>></video>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Banner Flutuante de Oferta / Achadinho -->
        <div id="dealBanner" onclick="openDealModal()">
            <img id="dealBannerImg" src="" alt="Produto">
            <div style="text-align: left; line-height: 1.2;">
                <div style="font-size: 11px; color: #ffaa00; font-weight: 700; text-transform: uppercase;">
                    🔥 Achadinho no Carro <span id="dealBannerDiscount" style="background:#ff3333; color:#fff; padding:1px 5px; border-radius:4px; font-size:10px; margin-left:4px;"></span>
                </div>
                <div id="dealBannerTitle" style="font-size: 13px; font-weight: 700; max-width: 260px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"></div>
                <div id="dealBannerPrice" style="font-size: 12px; color: #00ffaa; font-weight: 800;"></div>
            </div>
            <div class="deal-btn-touch">Toque para Comprar</div>
        </div>

        <div class="telemetry-badge">
            Player99 • Cod: <?= htmlspecialchars($codigo) ?>
            <?php if ($audioMode === 'radio' && !empty($radioName)): ?>
                • 📻 <span id="badgeRadioName"><?= htmlspecialchars($radioName) ?></span>
            <?php endif; ?>
            • <span id="pingStatus">Online</span>
        </div>
    </div>

    <!-- Modal Interativo do Passageiro com QR Code Ampliado -->
    <div id="dealModal">
        <div class="deal-modal-card">
            <button type="button" class="deal-close-btn" onclick="closeDealModal()">&times;</button>
            <div style="font-size: 12px; color: #ffaa00; font-weight: 800; text-transform: uppercase; margin-bottom: 6px;">
                🔥 Oferta Especial Para Passageiros
            </div>
            <h3 id="dealModalTitle" style="font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 12px; line-height: 1.3;"></h3>
            
            <div style="background: #fff; padding: 12px; border-radius: 16px; display: inline-block; margin-bottom: 12px;">
                <img id="dealModalQrCode" src="" style="width: 180px; height: 180px; display: block;" alt="QR Code">
            </div>

            <div style="margin-bottom: 8px;">
                <span id="dealModalOldPrice" style="text-decoration: line-through; color: #888; font-size: 13px; margin-right: 8px;"></span>
                <span id="dealModalPrice" style="color: #00ffaa; font-weight: 800; font-size: 22px;"></span>
            </div>

            <p style="font-size: 12px; color: #a0aec0; margin-bottom: 4px;">
                📱 Aponte a câmera do celular para comprar com desconto direto na loja oficial!
            </p>

            <div class="deal-timer-bar-wrap">
                <div id="dealTimerBar" class="deal-timer-bar"></div>
            </div>
            <div style="font-size: 11px; color: #718096; margin-top: 6px;">
                Voltando à programação em <span id="dealCountdown">15</span>s...
            </div>
        </div>
    </div>

    <audio id="radioPlayer" preload="auto"></audio>

    <script>
        const codigo = <?= json_encode($codigo) ?>;
        const pageLocked = <?= !empty($locked) ? 'true' : 'false' ?>;
        let currentRotation = <?= $rotation ?>;
        let currentAudioMode = <?= json_encode($audioMode) ?>;
        let currentRadioUrl = <?= json_encode($radioUrl) ?>;
        let currentRadioName = <?= json_encode($radioName) ?>;
        let currentIndex = 0;
        let slideTimer = null;
        const mediaItems = document.querySelectorAll('.media-item');
        const radioPlayer = document.getElementById('radioPlayer');
        const unlockBanner = document.getElementById('audioUnlockBanner');

        function syncAudioState() {
            const videos = document.querySelectorAll('video.media-item');
            if (currentAudioMode === 'radio' && currentRadioUrl) {
                videos.forEach(v => { v.muted = true; });
                if (radioPlayer.getAttribute('data-src') !== currentRadioUrl) {
                    radioPlayer.setAttribute('data-src', currentRadioUrl);
                    radioPlayer.src = currentRadioUrl;
                    radioPlayer.load();
                }
                if (radioPlayer.paused) {
                    radioPlayer.play().then(() => {
                        unlockBanner.style.display = 'none';
                    }).catch(() => {
                        document.getElementById('unlockRadioName').innerText = currentRadioName ? ('(' + currentRadioName + ')') : '';
                        unlockBanner.style.display = 'block';
                    });
                }
            } else if (currentAudioMode === 'mute') {
                videos.forEach(v => { v.muted = true; });
                radioPlayer.pause();
                unlockBanner.style.display = 'none';
            } else {
                radioPlayer.pause();
                unlockBanner.style.display = 'none';
                videos.forEach(v => { v.muted = false; });
            }
        }

        function tryPlayerFullScreen() {
            try {
                if ('wakeLock' in navigator) {
                    navigator.wakeLock.request('screen').catch(function() {});
                }
            } catch (e) {}
            try {
                const targetDoc = (window.top && window.top.document) ? window.top.document : document;
                const isFs = !!(targetDoc.fullscreenElement || targetDoc.webkitFullscreenElement || targetDoc.mozFullScreenElement || targetDoc.msFullscreenElement);
                if (!isFs) {
                    const el = targetDoc.documentElement;
                    if (el.requestFullscreen) el.requestFullscreen({ navigationUI: 'hide' }).catch(function() {});
                    else if (el.webkitRequestFullscreen) el.webkitRequestFullscreen();
                    else if (el.mozRequestFullScreen) el.mozRequestFullScreen();
                    else if (el.msRequestFullscreen) el.msRequestFullscreen();
                }
            } catch (e) {}
        }

        window.addEventListener('DOMContentLoaded', tryPlayerFullScreen);
        window.addEventListener('load', tryPlayerFullScreen);

        function unlockAudioNow() {
            tryPlayerFullScreen();
            unlockBanner.style.display = 'none';
            if (currentAudioMode === 'radio' && currentRadioUrl) {
                radioPlayer.play().catch(() => {});
            } else if (currentAudioMode === 'video') {
                const activeVid = document.querySelector('video.media-item.active');
                if (activeVid) {
                    activeVid.muted = false;
                    activeVid.play().catch(() => {});
                }
            }
        }

        document.addEventListener('click', unlockAudioNow, { passive: true });
        document.addEventListener('touchstart', unlockAudioNow, { passive: true });
        document.addEventListener('pointerdown', unlockAudioNow, { passive: true });
        document.addEventListener('keydown', unlockAudioNow, { passive: true });

        function applyRotation(rot) {
            rot = parseInt(rot, 10) || 0;
            if (rot === currentRotation) return;
            window.location.reload();
        }

        function startCurrentItem(item) {
            if (slideTimer) clearTimeout(slideTimer);
            if (!item) return;

            if (item.tagName === 'VIDEO') {
                item.muted = (currentAudioMode !== 'video');
                item.currentTime = 0;
                item.play().catch(() => {
                    item.muted = true;
                    item.play().catch(() => {
                        slideTimer = setTimeout(playNext, 5000);
                    });
                });
                item.onended = playNext;
            } else {
                const durMs = parseInt(item.getAttribute('data-duration') || '15000', 10) || 15000;
                if (item.getAttribute('data-type') === 'widget' && item.contentWindow) {
                    try {
                        item.contentWindow.postMessage({ type: 'START_WIDGET_CYCLE', durationMs: durMs }, '*');
                    } catch (e) {}
                }
                slideTimer = setTimeout(playNext, durMs);
            }
        }

        function playNext() {
            if (mediaItems.length === 0) return;

            // Reseta trava de toques e exibe a próxima oferta de e-commerce
            dealTouchCount = 0;
            showNextDealBanner();

            mediaItems.forEach(el => {
                el.classList.remove('active');
                if (el.tagName === 'VIDEO') {
                    el.pause();
                    el.currentTime = 0;
                }
            });

            currentIndex = (currentIndex + 1) % mediaItems.length;
            const current = mediaItems[currentIndex];
            current.classList.add('active');
            startCurrentItem(current);
        }

        // ==========================================
        // MÓDULO DE ACHADINHOS & OFERTAS E-COMMERCE
        // ==========================================
        const dealsList = <?= json_encode($deals ?? []) ?>;
        const screenId = <?= (int)($screen['id'] ?? 0) ?>;
        let currentDealIndex = 0;
        let dealCountdownInterval = null;
        let dealTouchCount = 0;
        const MAX_DEAL_TOUCHES = 2; // Trava de segurança anti-abuso

        function showNextDealBanner() {
            if (!dealsList || dealsList.length === 0) return;
            const deal = dealsList[currentDealIndex];
            currentDealIndex = (currentDealIndex + 1) % dealsList.length;

            const banner = document.getElementById('dealBanner');
            const img = document.getElementById('dealBannerImg');
            const title = document.getElementById('dealBannerTitle');
            const price = document.getElementById('dealBannerPrice');
            const disc = document.getElementById('dealBannerDiscount');

            if (deal.image_url) {
                img.src = deal.image_url;
                img.style.display = 'block';
            } else {
                img.style.display = 'none';
            }

            title.innerText = deal.title || 'Oferta Imperdível';
            price.innerText = (deal.price_new > 0) ? ('R$ ' + parseFloat(deal.price_new).toFixed(2).replace('.', ',')) : 'Aproveite o Desconto';
            
            if (deal.discount_pct > 0) {
                disc.innerText = '-' + deal.discount_pct + '%';
                disc.style.display = 'inline-block';
            } else {
                disc.style.display = 'none';
            }

            banner.setAttribute('data-deal-id', deal.id);
            banner.style.display = 'flex';
        }

        function openDealModal() {
            if (dealTouchCount >= MAX_DEAL_TOUCHES) return;
            dealTouchCount++;

            const banner = document.getElementById('dealBanner');
            const dealId = parseInt(banner.getAttribute('data-deal-id') || '0', 10);
            const deal = dealsList.find(d => d.id == dealId) || dealsList[0];
            if (!deal) return;

            // Pausa mídia e timer atual
            const activeVid = document.querySelector('video.media-item.active');
            if (activeVid) activeVid.pause();
            if (slideTimer) clearTimeout(slideTimer);

            // Popula dados no modal
            document.getElementById('dealModalTitle').innerText = deal.title || '';
            document.getElementById('dealModalPrice').innerText = (deal.price_new > 0) ? ('R$ ' + parseFloat(deal.price_new).toFixed(2).replace('.', ',')) : '';
            if (deal.price_old > deal.price_new && deal.price_old > 0) {
                const oldEl = document.getElementById('dealModalOldPrice');
                oldEl.innerText = 'De R$ ' + parseFloat(deal.price_old).toFixed(2).replace('.', ',');
                oldEl.style.display = 'inline';
            } else {
                document.getElementById('dealModalOldPrice').style.display = 'none';
            }

            // Gera QR Code via API pública sem dependências pesadas
            const targetUrl = window.location.origin + '/o/' + deal.id + '?carro=' + screenId;
            const qrApi = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=4&data=' + encodeURIComponent(targetUrl);
            document.getElementById('dealModalQrCode').src = qrApi;

            // Exibe modal e esconde banner
            const modal = document.getElementById('dealModal');
            modal.style.display = 'flex';
            banner.style.display = 'none';

            // Cronômetro regressivo de 15 segundos
            let secondsLeft = 15;
            const countdownEl = document.getElementById('dealCountdown');
            const timerBar = document.getElementById('dealTimerBar');
            countdownEl.innerText = secondsLeft;
            timerBar.style.width = '100%';

            if (dealCountdownInterval) clearInterval(dealCountdownInterval);
            dealCountdownInterval = setInterval(() => {
                secondsLeft--;
                countdownEl.innerText = secondsLeft;
                timerBar.style.width = ((secondsLeft / 15) * 100) + '%';
                if (secondsLeft <= 0) {
                    closeDealModal();
                }
            }, 1000);
        }

        function closeDealModal() {
            if (dealCountdownInterval) clearInterval(dealCountdownInterval);
            document.getElementById('dealModal').style.display = 'none';

            // Retoma a reprodução comercial
            const activeItem = mediaItems[currentIndex];
            if (activeItem) {
                startCurrentItem(activeItem);
            } else {
                playNext();
            }
        }

        // Inicia primeira oferta após carregar
        showNextDealBanner();

        // Inicia reprodução de vídeo/imagem e rádio
        syncAudioState();
        if (mediaItems.length > 0) {
            startCurrentItem(mediaItems[0]);
        }

        // Telemetria e Ping Automático a cada 6 segundos para resposta imediata de rotação e rádio
        function sendPing() {
            fetch('/api/ping?codigo=' + encodeURIComponent(codigo))
                .then(res => res.json())
                .then(data => {
                    document.getElementById('pingStatus').innerText = 'Online ' + new Date().toLocaleTimeString();
                    if (data.force_reload == 1) {
                        window.location.reload();
                        return;
                    }
                    if (data.locked !== undefined && (data.locked == 1) !== pageLocked) {
                        window.location.reload();
                        return;
                    }
                    if (data.rotation !== undefined) {
                        applyRotation(data.rotation);
                    }
                    if (data.audio_mode !== undefined) {
                        const modeChanged = (data.audio_mode !== currentAudioMode) || ((data.radio_url || '') !== currentRadioUrl);
                        currentAudioMode = data.audio_mode;
                        currentRadioUrl = data.radio_url || '';
                        currentRadioName = data.radio_name || '';
                        if (modeChanged) {
                            syncAudioState();
                        }
                    }
                })
                .catch(() => {
                    document.getElementById('pingStatus').innerText = 'Reconectando...';
                });
        }

        setInterval(sendPing, 6000);
        sendPing();

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js', { scope: '/' }).catch(function() {});
        }
    </script>
</body>
</html>
