<?php
require_once __DIR__ . '/error_handler.php';
require_once './includes/db_cliente.php';

if (!isset($_GET['codigo']) || empty($_GET['codigo'])) {
    die("Acesso Negado. Código do player não informado.");
}

$codigo = $_GET['codigo'];
$idCliente = 0;
$idPlaylist = 0;

// Primeiro, tenta achar o código na tabela de TELAS múltiplas
$stmt = $dbCliente->prepare("SELECT * FROM telas WHERE player_code = :codigo");
$stmt->bindValue(':codigo', $codigo, SQLITE3_TEXT);
$res = $stmt->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);

$isMutedInit = 0;
$audioModeInit = 'video';
$idRadioInit = 0;
$radioUrlInit = '';

if ($row) {
    $idCliente = $row['id_cliente'];
    $idPlaylist = $row['id_playlist'];
    $isMutedInit = $row['muted'] ?? 0;
    $audioModeInit = $row['audio_mode'] ?? 'video';
    $rotationInit = $row['rotation'] ?? 0;
    $idRadioInit = $row['id_radio'] ?? 0;

    if ($audioModeInit === 'radio' && $idRadioInit > 0) {
        try {
            $dbR = new SQLite3(__DIR__ . "/api/db/studiolivecode_radios.db");
            $rRow = $dbR->querySingle("SELECT url FROM radios WHERE id = $idRadioInit", true);
            if ($rRow) {
                $radioUrlInit = $rRow['url'];
            }
        } catch(Exception $e) {}
    }
    
    // Gera uma sessão única para barrar acessos simultâneos
    $sessionId = uniqid('sess_');
    $stmtUpd = $dbCliente->prepare("UPDATE telas SET active_session_id = :sess WHERE player_code = :codigo");
    $stmtUpd->bindValue(':sess', $sessionId, SQLITE3_TEXT);
    $stmtUpd->bindValue(':codigo', $codigo, SQLITE3_TEXT);
    $stmtUpd->execute();
} else {
    // Tenta fallback para o player antigo (cliente_settings)
    $sessionId = 'old_player';
    $stmt = $dbCliente->prepare("SELECT id_cliente FROM cliente_settings WHERE player_code = :codigo");
    $stmt->bindValue(':codigo', $codigo, SQLITE3_TEXT);
    $res = $stmt->execute();
    $rowOld = $res->fetchArray(SQLITE3_ASSOC);
    if ($rowOld) {
        $idCliente = $rowOld['id_cliente'];
        // Se for o antigo, pega a playlist "is_active"
        $resList = $dbCliente->querySingle("SELECT id FROM playlists WHERE id_cliente = $idCliente AND is_active = 1");
        if ($resList) {
            $idPlaylist = $resList;
        }
    } else {
        die("Código de Player Inválido ou Inativo.");
    }
}

// Helper status check
$is_bloqueado = false;
if ($idCliente > 0) {
    if (function_exists('checkUserStatus')) {
        $is_bloqueado = checkUserStatus($idCliente);
    } else {
        $is_bloqueado = true;
    }
}

if ($is_bloqueado) {
    // Força desativação da rádio se o cliente estiver bloqueado ou vencido
    $audioModeInit = 'video';
    $idRadioInit = 0;
    $radioUrlInit = '';
    $isMutedInit = 0;
}

// Fetch playlist videos
$playlist = [];

if ($is_bloqueado) {
    try {
        $db_bloqueio = new SQLite3('./api/.db_bloqueio.db');
        $tableExists = $db_bloqueio->querySingle("SELECT name FROM sqlite_master WHERE type='table' AND name='bloqueio'");
        
        if ($tableExists) {
            $resPlay = @$db_bloqueio->query("SELECT caminho FROM bloqueio");
            if ($resPlay) {
                while ($vid = $resPlay->fetchArray(SQLITE3_ASSOC)) {
                    $playlist[] = "./" . ltrim($vid['caminho'], './');
                }
            }
        }
        $db_bloqueio->close();
    } catch (Exception $e) {}
    
    // Fallback diretorio
    if (empty($playlist)) {
        $files = glob('./uploads/bloqueio/*.*');
        if ($files) {
            foreach ($files as $f) {
                $playlist[] = $f;
            }
        }
    }
} else {
    if ($idPlaylist > 0) {
        $query = "
            SELECT v.filename, pv.duration 
            FROM playlist_videos pv 
            JOIN videos v ON pv.id_video = v.id 
            WHERE pv.id_playlist = $idPlaylist
            ORDER BY pv.sequence ASC
        ";
        $resPlay = $dbCliente->query($query);
        while ($vid = $resPlay->fetchArray(SQLITE3_ASSOC)) {
            $filename = $vid['filename'];
            $dur = isset($vid['duration']) ? (int)$vid['duration'] : 15;
            if ($dur <= 0) $dur = 15;
            
            if (strpos($filename, 'http://') === 0 || strpos($filename, 'https://') === 0) {
                $playlist[] = ["url" => $filename, "duration" => $dur];
            } else if (strpos($filename, 'widgets/') !== false) {
                $tipoWidget = str_replace(['/widgets/', 'widgets/', '.php'], '', $filename);
                if ($tipoWidget == 'frases') {
                    for ($w = 1; $w <= 5; $w++) {
                        if (file_exists(__DIR__ . "/uploads/widgets_images/frase_$w.jpg")) {
                            $playlist[] = ["url" => "./uploads/widgets_images/frase_$w.jpg?v=" . date('Ymd'), "duration" => $dur];
                        }
                    }
                } else if ($tipoWidget == 'noticias') {
                    for ($w = 1; $w <= 5; $w++) {
                        if (file_exists(__DIR__ . "/uploads/widgets_images/noticia_$w.jpg")) {
                            $playlist[] = ["url" => "./uploads/widgets_images/noticia_$w.jpg?v=" . date('Ymd'), "duration" => $dur];
                        }
                    }
                } else if ($tipoWidget == 'carros') {
                    for ($w = 1; $w <= 5; $w++) {
                        if (file_exists(__DIR__ . "/uploads/widgets_images/carro_$w.jpg")) {
                            $playlist[] = ["url" => "./uploads/widgets_images/carro_$w.jpg?v=" . date('Ymd'), "duration" => $dur];
                        }
                    }
                } else if ($tipoWidget == 'horoscopo') {
                    for ($w = 1; $w <= 5; $w++) {
                        if (file_exists(__DIR__ . "/uploads/widgets_images/horoscopo_$w.jpg")) {
                            $playlist[] = ["url" => "./uploads/widgets_images/horoscopo_$w.jpg?v=" . date('Ymd'), "duration" => $dur];
                        }
                    }
                } else if (strpos($tipoWidget, 'rss_') === 0) {
                    for ($w = 1; $w <= 5; $w++) {
                        if (file_exists(__DIR__ . "/uploads/widgets_images/{$tipoWidget}_$w.jpg")) {
                            $playlist[] = ["url" => "./uploads/widgets_images/{$tipoWidget}_$w.jpg?v=" . date('Ymd'), "duration" => $dur];
                        }
                    }
                }
            } else {
                $playlist[] = ["url" => "./uploads/clientes/" . $idCliente . "/" . $filename, "duration" => $dur];
            }
        }
    }
}

if (empty($playlist)) {
    $errorPlay = "Nenhum vídeo na playlist no momento ou Nenhuma lista vinculada a esta tela.";
}

// Convert to JSON for Javascript
$playlistJson = json_encode($playlist);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="manifest" href="manifest.json">
    <title>Player Exclusivo</title>
    <style>
        <?php if (isset($rotationInit) && $rotationInit == 90): ?>
        body {
            width: 100vh !important;
            height: 100vw !important;
            position: absolute !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) rotate(90deg) !important;
            transform-origin: center center !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #000;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            font-family: Arial, sans-serif;
        }
        html {
            width: 100vw;
            height: 100vh;
            background-color: #000;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        video, img {
            object-fit: cover !important; 
        }
        <?php else: ?>
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
            color: #fff;
            font-family: Arial, sans-serif;
        }
        <?php endif; ?>
        video {
            width: 100%;
            height: 100%;
            object-fit: contain; 
            background-color: #000;
            pointer-events: none;
        }
        video::-webkit-media-controls {
            display: none !important;
            opacity: 0 !important;
        }
        video::-webkit-media-controls-enclosure {
            display: none !important;
        }
        video::-webkit-media-controls-panel {
            display: none !important;
        }
        video::-webkit-media-controls-play-button {
            display: none !important;
        }
        video::-webkit-media-controls-start-playback-button {
            display: none !important;
        }
        .error-msg {
            font-size: 24px;
            color: #ff4d4d;
            text-align: center;
            margin-bottom: 10px;
            text-shadow: 0 0 10px rgba(255, 77, 77, 0.5);
        }
        .empty-state-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #111 0%, #000 100%);
            width: 100vw;
            height: 100vh;
        }
        .loader {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left-color: #00ffcc;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
            box-shadow: 0 0 15px rgba(0, 255, 204, 0.3);
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .waiting-text {
            color: #888;
            font-size: 16px;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { opacity: 0.6; }
            50% { opacity: 1; }
            100% { opacity: 0.6; }
        }
    </style>
</head>
<body>

<?php if (!empty($errorPlay)): ?>
    <div class="empty-state-container">
        <div class="loader"></div>
        <div class="error-msg"><?php echo $errorPlay; ?></div>
        <div class="waiting-text">Aguardando vinculação no sistema...</div>
        <div class="fallback-text" style="margin-top: 15px; font-size: 14px; color: #666;">Após a adição, feche o aplicativo e abra novamente caso não atualize sozinho.</div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        var playerCode = "<?php echo htmlspecialchars($codigo); ?>";
        var sessionId = "<?php echo htmlspecialchars($sessionId); ?>";
        
        function checkPlaylist() {
            $.post('api_ping.php', { codigo: playerCode, session_id: sessionId, check_playlist: 1 }, function(res) {
                if (res && res.has_playlist) {
                    window.location.reload(true);
                } else if (res && res.action === 'reload') {
                    window.location.reload(true);
                }
            }, 'json').fail(function() {
                console.log("Falha ao consultar servidor.");
            });
        }
        
        // Pergunta ao servidor a cada 10 segundos
        setInterval(checkPlaylist, 10000);
        checkPlaylist(); // Primeira chamada
    </script>
<?php else: ?>
    <div id="playerContainer" style="width: 100%; height: 100%; position: relative; background-color: #000; overflow: hidden;">
        <video id="videoPlayer1" preload="auto" muted style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; display:none; z-index: 1;"></video>
        <video id="videoPlayer2" preload="auto" muted style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; display:none; z-index: 1;"></video>
        
        <img id="imagePlayer1" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; display:none; z-index: 2;" />
        <img id="imagePlayer2" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: contain; display:none; z-index: 2;" />
    </div>
    <audio id="radioPlayer" style="display:none;"></audio>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script>
        // ==== VARIÁVEIS DO WATCHDOG ====
        var stuckWatchdog = null;
        var lastCurrentTime = -1;
        var stuckCounter = 0;

        function startWatchdog(videoElem) {
            stopWatchdog();
            lastCurrentTime = -1;
            stuckCounter = 0;
            stuckWatchdog = setInterval(function() {
                if (!videoElem.paused) {
                    if (videoElem.currentTime === lastCurrentTime) {
                        stuckCounter++;
                    } else {
                        stuckCounter = 0;
                        lastCurrentTime = videoElem.currentTime;
                    }
                } else {
                    stuckCounter++;
                }
                
                // Se ficar travado no mesmo segundo por 8 contagens (8 segundos)
                if (stuckCounter >= 8) {
                    console.log('Video travado na memoria (Codec), pulando de forma forçada...');
                    stopWatchdog();
                    nextItem();
                }
            }, 1000);
        }

        function stopWatchdog() {
            if (stuckWatchdog) {
                clearInterval(stuckWatchdog);
                stuckWatchdog = null;
            }
        }

        // ==== VARIÁVEIS DO PLAYER ====
        var playlist = <?php echo $playlistJson; ?>;
        
        // Registrar pareamento na memória do APK para boot offline
        try { localStorage.setItem('is_paired', 'true'); } catch(e) {}

        // Garantia de segurança offline: Se o cache falhar ou retornar lista vazia
        if (playlist.length === 0) {
            try {
                var savedList = localStorage.getItem('offline_playlist_<?php echo $codigo; ?>');
                if (savedList) {
                    playlist = JSON.parse(savedList);
                    console.log('Playlist recuperada da memória de segurança.');
                }
            } catch(e) {}
        } else {
            try { localStorage.setItem('offline_playlist_<?php echo $codigo; ?>', JSON.stringify(playlist)); } catch(e) {}
        }
        var currentVideoIndex = 0;
        var activePlayer = 1;
        
        var video1 = document.getElementById('videoPlayer1');
        var video2 = document.getElementById('videoPlayer2');
        var image1 = document.getElementById('imagePlayer1');
        var image2 = document.getElementById('imagePlayer2');
        var radioPlayer = document.getElementById('radioPlayer');
        
        var playerCode = "<?php echo htmlspecialchars($codigo); ?>";
        var sessionId = "<?php echo htmlspecialchars($sessionId); ?>";
        
        var audioModeServer = "<?php echo $audioModeInit; ?>";
        var radioUrlServer = "<?php echo htmlspecialchars($radioUrlInit); ?>";
        var isMutedServer = <?php echo $isMutedInit; ?>; // Fallback
        
        // Define o áudio inicial
        function applyAudioMode() {
            var currentVideo = (activePlayer === 1) ? video1 : video2;
            var hiddenVideo = (activePlayer === 1) ? video2 : video1;
            
            // Oculto sempre mudo para não sobrepor áudio
            hiddenVideo.muted = true;
            
            if (audioModeServer === 'mute' || (audioModeServer === 'video' && isMutedServer === 1)) {
                currentVideo.muted = true;
                if (radioPlayer) radioPlayer.pause();
            } else if (audioModeServer === 'radio') {
                currentVideo.muted = true;
                if (radioPlayer && radioUrlServer) {
                    if (radioPlayer.src !== radioUrlServer) {
                        radioPlayer.src = radioUrlServer;
                    }
                    radioPlayer.play().catch(function(e) {
                        console.log('Autoplay da rádio bloqueado pelo navegador.');
                    });
                }
            } else {
                currentVideo.muted = false;
                if (radioPlayer) radioPlayer.pause();
            }
        }
        
        applyAudioMode();

        var imageTimeout;
        
        function getNextIndex(currentIndex) {
            let nextIndex = currentIndex + 1;
            if (nextIndex >= playlist.length) {
                nextIndex = 0;
            }
            return nextIndex;
        }

        function playCurrentItem() {
            if (playlist.length === 0) return;
            
            clearTimeout(imageTimeout);
            
            var currentItem = playlist[currentVideoIndex];
            var url = typeof currentItem === 'string' ? currentItem : currentItem.url;
            var durationMs = (typeof currentItem === 'object' && currentItem.duration) ? (currentItem.duration * 1000) : 15000;
            var isImage = url.match(/\.(jpeg|jpg|gif|png|webp)(\?.*)?$/i) != null;
            
            // Determinar quais elementos usar (Ativo vs Escondido)
            var currentVideo = (activePlayer === 1) ? video1 : video2;
            var hiddenVideo = (activePlayer === 1) ? video2 : video1;
            var currentImg = (activePlayer === 1) ? image1 : image2;
            var hiddenImg = (activePlayer === 1) ? image2 : image1;
            
            // Ocultar a mídia que acabou (do player escondido agora)
            hiddenVideo.style.display = 'none';
            hiddenImg.style.display = 'none';
            try { hiddenVideo.pause(); } catch(e){}
            
            // LIBERAR HARDWARE DECODER DA TV BOX (MUITO IMPORTANTE PARA ANDROID WEBVIEW)
            // Mantendo apenas 1 vídeo na memória da GPU por vez.
            hiddenVideo.removeAttribute('src');
            hiddenVideo.load();
            
            // Tocar a mídia atual
            if (isImage) {
                stopWatchdog(); // Para de monitorar vídeo
                
                currentVideo.style.display = 'none';
                try { currentVideo.pause(); } catch(e){}
                
                // Força a imagem a recarregar para widgets dinâmicos se for cache local
                if (url.includes('?v=')) {
                    currentImg.src = url.split('?')[0] + '?v=' + new Date().getTime();
                } else {
                    currentImg.src = url;
                }
                
                currentImg.style.display = 'block';
                currentImg.style.zIndex = "10";
                hiddenImg.style.zIndex = "1";
                
                imageTimeout = setTimeout(nextItem, durationMs);
            } else {
                currentImg.style.display = 'none';
                currentImg.removeAttribute('src');
                
                currentVideo.style.display = 'block';
                currentVideo.style.zIndex = "10";
                hiddenVideo.style.zIndex = "1";
                
                // Se a source for diferente (primeira vez), atribui
                // Se já estiver igual, significa que o preload já fez o trabalho
                if (!currentVideo.src.endsWith(url.replace('./', '/'))) {
                    currentVideo.src = url;
                }
                
                var playPromise = currentVideo.play();
                if (playPromise !== undefined) {
                    playPromise.then(function() {
                        startWatchdog(currentVideo);
                    }).catch(function(error) {
                        console.log("Erro de Autoplay ou Codec incompatível detectado. Pulando para segurança.", error);
                        setTimeout(nextItem, 2000);
                    });
                } else {
                    startWatchdog(currentVideo);
                }
            }
            
            // --- INICIAR PRELOAD DO PRÓXIMO ITEM ---
            if (playlist.length > 1) {
                var nextIndex = getNextIndex(currentVideoIndex);
                var nextItemData = playlist[nextIndex];
                var nextUrl = typeof nextItemData === 'string' ? nextItemData : nextItemData.url;
                var nextIsImage = nextUrl.match(/\.(jpeg|jpg|gif|png|webp)(\?.*)?$/i) != null;
                
                if (nextIsImage) {
                    // O navegador já baixa a imagem pra cache ao colocar no src
                    if (nextUrl.includes('?v=')) {
                        hiddenImg.src = nextUrl.split('?')[0] + '?v=' + new Date().getTime();
                    } else {
                        hiddenImg.src = nextUrl; 
                    }
                } else {
                    // REMOVIDO: O Preload de um 2º vídeo simultâneo causava colapso
                    // no Codec de Hardware (GPU) de TV Boxes Android com poucos recursos,
                    // fazendo um vídeo ficar tela preta ou mesclar.
                }
            }
        }
        
        function nextItem() {
            activePlayer = (activePlayer === 1) ? 2 : 1;
            applyAudioMode(); // Atualiza o áudio garantindo que o novo escondido fique mudo
            currentVideoIndex = getNextIndex(currentVideoIndex);
            playCurrentItem();
        }

        if(playlist.length > 0) {
            playCurrentItem();
        }

        // Continua para o próximo item quando o vídeo acabar (Faz o Loop)
        video1.addEventListener('ended', function() { if (activePlayer === 1) nextItem(); });
        video2.addEventListener('ended', function() { if (activePlayer === 2) nextItem(); });
        
        // Pula para o próximo item se der erro (arquivo corrompido, faltando, ou erro de Codec)
        video1.addEventListener('error', function() { if (activePlayer === 1 && video1.getAttribute('src')) { console.log('Erro no video 1, pulando...'); setTimeout(nextItem, 1000); } });
        video2.addEventListener('error', function() { if (activePlayer === 2 && video2.getAttribute('src')) { console.log('Erro no video 2, pulando...'); setTimeout(nextItem, 1000); } });
        
        var img1 = document.getElementById('imagePlayer1');
        var img2 = document.getElementById('imagePlayer2');
        img1.addEventListener('error', function() { if (activePlayer === 1 && img1.getAttribute('src')) { console.log('Erro na imagem 1, pulando...'); clearTimeout(imageTimeout); setTimeout(nextItem, 1000); } });
        img2.addEventListener('error', function() { if (activePlayer === 2 && img2.getAttribute('src')) { console.log('Erro na imagem 2, pulando...'); clearTimeout(imageTimeout); setTimeout(nextItem, 1000); } });
        
        // Bloquear legendas embutidas nos vídeos (comum quando o Android liga a GPU)
        function hideSubtitles(vid) {
            if (vid && vid.textTracks) {
                for (var i = 0; i < vid.textTracks.length; i++) {
                    vid.textTracks[i].mode = 'hidden';
                }
            }
        }
        video1.addEventListener('loadedmetadata', function() { hideSubtitles(video1); });
        video2.addEventListener('loadedmetadata', function() { hideSubtitles(video2); });
        
        document.body.addEventListener('click', function() {
            applyAudioMode();
        });

        // Ping de Status a cada 30 segundos
        function sendPing() {
            $.post('api_ping.php', { codigo: playerCode, session_id: sessionId }, function(res) {
                if (res) {
                    if (res.action === 'stop') {
                        // Outra tela assumiu esse código ou o usuário foi bloqueado/vencido. Pára a reprodução.
                        video1.pause();
                        video2.pause();
                        if (typeof radioPlayer !== 'undefined' && radioPlayer) {
                            radioPlayer.pause();
                        }
                        clearTimeout(imageTimeout);
                        document.body.innerHTML = "<div class='error-msg'>A reprodução foi interrompida (Acesso Bloqueado/Vencido ou Usado em outra Tela).</div>";
                    } else if (res.action === 'reload') {
                        // O painel enviou um sinal de recarregamento
                        window.location.reload(true);
                    }
                }
            }, 'json');
        }
        sendPing(); // Ping inicial
        setInterval(sendPing, 30000);

        // ==== MODO OFFLINE / SERVICE WORKER ====
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js').then(function(reg) {
                console.log('Service Worker Registrado com escopo:', reg.scope);
                
                // Forçar download / cache de todos os vídeos e imagens da playlist atual
                playlist.forEach(function(item) {
                    var u = typeof item === 'string' ? item : item.url;
                    // Remove query strings como ?v= para não duplicar no cache
                    var cacheUrl = u.split('?')[0];
                    fetch(cacheUrl).catch(() => console.log('Erro ao pre-fetcher (já pode estar no cache)'));
                });

                // Enviar mensagem para o SW apagar arquivos antigos da memória da TV
                if(navigator.serviceWorker.controller) {
                    var urlsToKeep = playlist.map(function(item) {
                        var u = typeof item === 'string' ? item : item.url;
                        return u.split('?')[0];
                    });
                    
                    navigator.serviceWorker.controller.postMessage({
                        type: 'CLEANUP_CACHE',
                        urls: urlsToKeep
                    });
                }
            }).catch(function(err) {
                console.error('Falha ao registrar Service Worker:', err);
            });
        }

        // ==== SINCRONIZAÇÃO EM SEGUNDO PLANO (A CADA 10 MINUTOS) ====
        function syncPlaylist() {
            $.get('api_playlist.php', { codigo: playerCode }, function(res) {
                if (res && res.success) {
                    var newPlaylist = res.playlist;
                    
                    var modeChanged = false;
                    if (res.audio_mode !== undefined && res.audio_mode !== audioModeServer) {
                        audioModeServer = res.audio_mode;
                        modeChanged = true;
                    }
                    if (res.radio_url !== undefined && res.radio_url !== radioUrlServer) {
                        radioUrlServer = res.radio_url;
                        modeChanged = true;
                    }
                    if (res.muted !== undefined && res.muted !== isMutedServer) {
                        isMutedServer = res.muted;
                        modeChanged = true;
                    }
                    
                    if (modeChanged) {
                        console.log('Configurações de áudio alteradas.');
                        applyAudioMode();
                    }
                    
                    // Compara as listas (simples stringify já resolve para arrays de strings)
                    if (JSON.stringify(newPlaylist) !== JSON.stringify(playlist)) {
                        console.log('Nova playlist detectada! Sincronizando...');
                        
                        // 1. Atualiza a fila
                        playlist = newPlaylist;
                        
                        // 2. Avisa o Service Worker para limpar os arquivos removidos
                        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                            navigator.serviceWorker.controller.postMessage({
                                type: 'CLEANUP_CACHE',
                                urls: playlist
                            });
                        }
                        
                        // Atualiza a memória de segurança
                        try { localStorage.setItem('offline_playlist_' + playerCode, JSON.stringify(playlist)); } catch(e) {}
                        
                        // 3. Força o download dos vídeos novos para o cache
                        playlist.forEach(function(url) {
                            fetch(url).catch(() => console.log('Erro ao baixar vídeo novo'));
                        });
                        
                        // Se a tela estiver presa (ex: lista anterior estava vazia), forca o play
                        if (playlist.length > 0) {
                            var url = playlist[currentVideoIndex];
                            if (!url) {
                                currentVideoIndex = 0;
                            }
                            if (player.paused && !document.getElementById('myImage').src) {
                                currentVideoIndex = 0;
                                playCurrentItem();
                            }
                        }
                    }
                }
            }, 'json');
        }

        // ==== EVITAR QUE A TELA APAGUE (WAKE LOCK API) ====
        let wakeLock = null;
        async function requestWakeLock() {
            try {
                if ('wakeLock' in navigator) {
                    wakeLock = await navigator.wakeLock.request('screen');
                    wakeLock.addEventListener('release', () => {
                        console.log('Wake Lock liberado (tela pode apagar)');
                    });
                    console.log('Wake Lock ativo: Tela ficará sempre acesa.');
                }
            } catch (err) {
                console.error('Erro ao ativar Wake Lock:', err.name, err.message);
            }
        }
        
        // Tenta ativar logo no início
        requestWakeLock();
        
        // Retoma o Wake Lock se a aba/app for minimizada e voltar
        document.addEventListener('visibilitychange', async () => {
            if (wakeLock !== null && document.visibilityState === 'visible') {
                requestWakeLock();
            }
        });

        // Executa a cada 1 minuto (60.000 ms)
        setInterval(syncPlaylist, 60000);
    </script>
<?php endif; ?>

</body>
</html>
