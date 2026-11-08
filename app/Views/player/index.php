<?php
$rotation = (int)($screen['rotation'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
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

        video, img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Mantém a proporção exata sem cortar detalhes */
            background-color: #000;
            pointer-events: none; /* Bloqueia toques acidentais do passageiro */
            display: none;
        }

        video.active, img.active {
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
    </style>
</head>
<body class="rot-<?= $rotation ?>">

    <div id="playerContainer">
        <?php if (empty($videos)): ?>
            <div class="no-media">
                <h2>Player99 em Operação</h2>
                <p>Tela Conectada: <b><?= htmlspecialchars($codigo) ?></b></p>
                <p>Nenhuma mídia vinculada no momento. Envie vídeos ou imagens pelo painel.</p>
            </div>
        <?php else: ?>
            <?php foreach ($videos as $idx => $v): 
                $ext = strtolower(pathinfo($v['filename'], PATHINFO_EXTENSION));
                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                $mediaUrl = '/uploads/clientes/' . $screen['id_cliente'] . '/' . $v['filename'];
            ?>
                <?php if ($isImg): ?>
                    <img src="<?= $mediaUrl ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="image" data-duration="10000" id="media_<?= $idx ?>">
                <?php else: ?>
                    <video src="<?= $mediaUrl ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="video" id="media_<?= $idx ?>" playsinline webkit-playsinline disablePictureInPicture controlsList="nodownload nofullscreen noremoteplayback" <?= ($screen['audio_mode'] ?? 'video') === 'mute' ? 'muted' : '' ?>></video>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="telemetry-badge">
            Player99 • Cod: <?= htmlspecialchars($codigo) ?> • <span id="pingStatus">Online</span>
        </div>
    </div>

    <script>
        const codigo = <?= json_encode($codigo) ?>;
        let currentRotation = <?= $rotation ?>;
        let currentIndex = 0;
        const mediaItems = document.querySelectorAll('.media-item');

        function applyRotation(rot) {
            rot = parseInt(rot, 10) || 0;
            if (rot === currentRotation) return;
            // Se mudou entre modo horizontal e vertical (ou vice-versa), recarrega para aplicar o layout limpo
            window.location.reload();
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

            if (current.tagName === 'VIDEO') {
                current.play().catch(e => {
                    console.log("Autoplay bloqueado:", e);
                    setTimeout(playNext, 5000);
                });
                current.onended = playNext;
            } else {
                setTimeout(playNext, parseInt(current.getAttribute('data-duration') || '10000'));
            }
        }

        // Inicia reprodução
        if (mediaItems.length > 0) {
            const first = mediaItems[0];
            if (first.tagName === 'VIDEO') {
                first.play().catch(() => {});
                first.onended = playNext;
            } else {
                setTimeout(playNext, parseInt(first.getAttribute('data-duration') || '10000'));
            }
        }

        // Telemetria e Ping Automático a cada 45 segundos
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
                })
                .catch(() => {
                    document.getElementById('pingStatus').innerText = 'Reconectando...';
                });
        }

        setInterval(sendPing, 45000);
        sendPing();
    </script>
</body>
</html>
