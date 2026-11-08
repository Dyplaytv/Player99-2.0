<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <title>Player99 DOOH - Tela <?= htmlspecialchars($codigo) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body, html {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background: #000;
            font-family: sans-serif;
        }

        #playerContainer {
            width: 100vw;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            transform-origin: center center;
            transition: transform 0.4s ease;
        }

        video, img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }
        video.active, img.active {
            display: block;
        }

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
<body>

    <div id="playerContainer" style="transform: rotate(<?= (int)($screen['rotation'] ?? 0) ?>deg);">
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
                    <video src="<?= $mediaUrl ?>" class="media-item <?= $idx === 0 ? 'active' : '' ?>" data-type="video" id="media_<?= $idx ?>" playsinline <?= ($screen['audio_mode'] ?? 'video') === 'mute' ? 'muted' : '' ?>></video>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="telemetry-badge">
            Player99 • Cod: <?= htmlspecialchars($codigo) ?> • <span id="pingStatus">Online</span>
        </div>
    </div>

    <script>
        const codigo = <?= json_encode($codigo) ?>;
        let currentIndex = 0;
        const mediaItems = document.querySelectorAll('.media-item');

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
                    }
                    if (data.rotation !== undefined) {
                        document.getElementById('playerContainer').style.transform = 'rotate(' + data.rotation + 'deg)';
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
