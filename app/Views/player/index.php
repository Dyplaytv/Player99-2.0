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
    <link rel="manifest" href="/manifest.json">
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
    </style>
</head>
<body class="rot-<?= $rotation ?>">

    <div id="playerContainer">
        <div id="audioUnlockBanner" onclick="unlockAudioNow()">
            🔊 Toque aqui para ativar o som da Rádio <span id="unlockRadioName"><?= htmlspecialchars($radioName) ?></span>
        </div>

        <?php if (empty($videos)): ?>
            <div class="no-media">
                <h2>Player99 em Operação</h2>
                <p>Tela Conectada: <b><?= htmlspecialchars($codigo) ?></b></p>
                <p>Nenhuma mídia vinculada no momento. Envie vídeos ou imagens pelo painel.</p>
            </div>
        <?php else: ?>
            <?php foreach ($videos as $idx => $v):
                $fn = (string)($v['filename'] ?? '');
                $durMs = ((int)($v['duration'] ?? 15) ?: 15) * 1000;
                $isWidget = (strpos($fn, '/widgets/') === 0);
                $ext = strtolower(pathinfo($fn, PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                $mediaUrl = $isWidget ? $fn : ('/uploads/clientes/' . $screen['id_cliente'] . '/' . $fn);
            ?>
                <?php if ($isWidget): ?>
                    <iframe src="<?= htmlspecialchars($mediaUrl) ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="widget" data-duration="<?= $durMs ?>" id="media_<?= $idx ?>"></iframe>
                <?php elseif ($isImg): ?>
                    <img src="<?= htmlspecialchars($mediaUrl) ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="image" data-duration="<?= $durMs ?>" id="media_<?= $idx ?>">
                <?php else: ?>
                    <video src="<?= htmlspecialchars($mediaUrl) ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="video" id="media_<?= $idx ?>" playsinline webkit-playsinline disablePictureInPicture controlsList="nodownload nofullscreen noremoteplayback" <?= ($audioMode !== 'video') ? 'muted' : '' ?>></video>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="telemetry-badge">
            Player99 • Cod: <?= htmlspecialchars($codigo) ?>
            <?php if ($audioMode === 'radio' && !empty($radioName)): ?>
                • 📻 <span id="badgeRadioName"><?= htmlspecialchars($radioName) ?></span>
            <?php endif; ?>
            • <span id="pingStatus">Online</span>
        </div>
    </div>

    <audio id="radioPlayer" preload="auto"></audio>

    <script>
        const codigo = <?= json_encode($codigo) ?>;
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

        function unlockAudioNow() {
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
                slideTimer = setTimeout(playNext, parseInt(item.getAttribute('data-duration') || '15000', 10));
            }
        }

        function playNext() {
            if (mediaItems.length === 0) return;

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
