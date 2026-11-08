<?php
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

require_once './includes/db_cliente.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ==== LÓGICA DO PAINEL (SALVAR ORDEM) ====
    if (!isset($_SESSION["cliente_logado"]) || $_SESSION["cliente_logado"] !== true) {
        echo json_encode(["success" => false, "msg" => "Não autenticado"]);
        exit;
    }
    
    $idCliente = $_SESSION["cliente_id"];
    $action = $_POST['action'] ?? '';

    if ($action === 'save_order') {
        $idPlaylist = (int)($_POST['id_playlist'] ?? 0);
        $videoIds = $_POST['video_ids'] ?? []; // Array de IDs ordenado

        // Verifica se a playlist pertence ao cliente
        $stmt = $dbCliente->prepare("SELECT id FROM playlists WHERE id = :id AND id_cliente = :id_cliente");
        $stmt->bindValue(':id', $idPlaylist, SQLITE3_INTEGER);
        $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
        $res = $stmt->execute();
        
        if ($res->fetchArray()) {
            // Limpa vídeos antigos
            $dbCliente->exec("DELETE FROM playlist_videos WHERE id_playlist = $idPlaylist");
            
            // Salva nova ordem
            $videoDataStr = $_POST['video_data'] ?? ''; // Nova forma com duração
            if (!empty($videoDataStr)) {
                $dataArr = json_decode($videoDataStr, true);
                if (is_array($dataArr)) {
                    $seq = 1;
                    foreach ($dataArr as $item) {
                        $vid = (int)$item['id'];
                        $dur = (int)($item['duration'] ?? 15);
                        $stmt = $dbCliente->prepare("INSERT INTO playlist_videos (id_playlist, id_video, sequence, duration) VALUES (:id_playlist, :id_video, :sequence, :duration)");
                        $stmt->bindValue(':id_playlist', $idPlaylist, SQLITE3_INTEGER);
                        $stmt->bindValue(':id_video', $vid, SQLITE3_INTEGER);
                        $stmt->bindValue(':sequence', $seq, SQLITE3_INTEGER);
                        $stmt->bindValue(':duration', $dur, SQLITE3_INTEGER);
                        $stmt->execute();
                        $seq++;
                    }
                }
            } else {
                // Fallback legado
                $seq = 1;
                foreach ($videoIds as $vid) {
                    $vid = (int)$vid;
                    $stmt = $dbCliente->prepare("INSERT INTO playlist_videos (id_playlist, id_video, sequence) VALUES (:id_playlist, :id_video, :sequence)");
                    $stmt->bindValue(':id_playlist', $idPlaylist, SQLITE3_INTEGER);
                    $stmt->bindValue(':id_video', $vid, SQLITE3_INTEGER);
                    $stmt->bindValue(':sequence', $seq, SQLITE3_INTEGER);
                    $stmt->execute();
                    $seq++;
                }
            }
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "msg" => "Playlist não encontrada."]);
        }
    } else {
        echo json_encode(["success" => false, "msg" => "Ação inválida."]);
    }
} else {
    // ==== LÓGICA DO PLAYER (TV PEDINDO A LISTA) ====
    $codigo = $_GET['codigo'] ?? '';

    if (empty($codigo)) {
        echo json_encode(["success" => false, "msg" => "Código não informado"]);
        exit;
    }

    $idCliente = 0;
    $idPlaylist = 0;
    $isMuted = 0;
    $audioMode = 'video';
    $idRadio = 0;
    $radioUrl = '';

    // Ajustado para não dar erro se as colunas não existirem (já que os players podem consultar antes de rodar o alter table)
    $stmt = $dbCliente->prepare("SELECT * FROM telas WHERE player_code = :codigo");
    $stmt->bindValue(':codigo', $codigo, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);

    if ($row) {
        $idCliente = $row['id_cliente'];
        $idPlaylist = $row['id_playlist'];
        $isMuted = $row['muted'] ?? 0;
        $audioMode = $row['audio_mode'] ?? 'video';
        $idRadio = $row['id_radio'] ?? 0;

        if ($audioMode === 'radio' && $idRadio != 0) {
            if ($idRadio > 0) {
                try {
                    $dbR = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_radios.db");
                    $rRow = $dbR->querySingle("SELECT url FROM radios WHERE id = $idRadio", true);
                    if ($rRow) {
                        $radioUrl = $rRow['url'];
                    }
                } catch(Exception $e) {}
            } else {
                // Rádios customizadas do cliente (IDs negativos)
                $idCustom = abs($idRadio);
                $stmt = $dbCliente->prepare("SELECT filename FROM videos WHERE id = :id");
                $stmt->bindValue(':id', $idCustom, SQLITE3_INTEGER);
                $vRow = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
                if ($vRow) {
                    $radioUrl = $vRow['filename'];
                }
            }
        }
    } else {
        // Fallback antigo
        $stmt = $dbCliente->prepare("SELECT id_cliente FROM cliente_settings WHERE player_code = :codigo");
        $stmt->bindValue(':codigo', $codigo, SQLITE3_TEXT);
        $res = $stmt->execute();
        $rowOld = $res->fetchArray(SQLITE3_ASSOC);
        if ($rowOld) {
            $idCliente = $rowOld['id_cliente'];
            $resList = $dbCliente->querySingle("SELECT id FROM playlists WHERE id_cliente = $idCliente AND is_active = 1");
            if ($resList) {
                $idPlaylist = $resList;
            }
        }
    }

    $playlist = [];
    $is_bloqueado = false;

    if ($idCliente > 0) {
        if (function_exists('checkUserStatus')) {
            $is_bloqueado = checkUserStatus($idCliente);
        } else {
            $is_bloqueado = true;
        }
    }

    if ($is_bloqueado) {
        try {
            $db_bloqueio = new SQLite3('./api/.db_bloqueio.db');
            $resPlay = $db_bloqueio->query("SELECT caminho FROM bloqueio");
            if ($resPlay) {
                while ($vid = $resPlay->fetchArray(SQLITE3_ASSOC)) {
                    $playlist[] = "./" . ltrim($vid['caminho'], './');
                }
            }
            $db_bloqueio->close();
        } catch (Exception $e) {}
        
        // Fallback: se o banco de bloqueio não retornar nada, carrega direto da pasta
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
                    $tipoWidget = str_replace(['/widgets/', 'widgets/', '.php'], '', $filename); // ex: 'frases', 'noticias'
                    // Em vez do link do widget, vamos colocar as imagens JPEG geradas
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

    echo json_encode(["success" => true, "playlist" => $playlist, "muted" => $isMuted, "audio_mode" => $audioMode, "radio_url" => $radioUrl]);
}
?>
