<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');
if (!isset($_SESSION["cliente_logado"]) || $_SESSION["cliente_logado"] !== true) {
    header("Location: login_cliente.php");
    exit;
}

require_once './includes/db_cliente.php';
require_once './includes/db_financeiro.php';

$idCliente = $_SESSION["cliente_id"];

// Validar status no banco novamente (bloqueio ou vencimento)
if (function_exists('checkUserStatus') && checkUserStatus($idCliente)) {
    session_destroy();
    header("Location: login_cliente.php");
    exit;
}

// Proxy Backend para Busca de Rádios (Evita CORS e bloqueios no cliente)
if (isset($_GET['action']) && $_GET['action'] === 'search_radios' && isset($_GET['q'])) {
    header('Content-Type: application/json');
    $q = urlencode(trim($_GET['q']));
    $servers = ['de1.api.radio-browser.info', 'at1.api.radio-browser.info', 'nl1.api.radio-browser.info'];
    $response = '[]';
    
    foreach ($servers as $server) {
        $url = "https://$server/json/stations/search?name=$q&limit=15";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Timeout curto para tentar o próximo rápido
        curl_setopt($ch, CURLOPT_USERAGENT, 'Player99/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita problemas de certificado no cURL
        $result = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($result !== false && $httpcode == 200) {
            $response = $result;
            break; // Sucesso, para de procurar
        }
    }
    echo $response;
    exit;
}

$clienteUser = $_SESSION["cliente_user"];
$playerCode = getPlayerCode($dbCliente, $idCliente);

$uploadDir = __DIR__ . '/uploads/clientes/' . $idCliente . '/';
$uploadUrl = './uploads/clientes/' . $idCliente . '/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0777, true);
}
@chmod($uploadDir, 0777);

// Obter cotas de vídeos e limite de tamanho do cliente
$mediaLimits = function_exists('getClienteMediaLimits') ? getClienteMediaLimits($idCliente) : ['max_videos' => 10, 'max_video_size_mb' => 50];
$maxVideosAllowed = $mediaLimits['max_videos'];
$maxVideoSizeMb = $mediaLimits['max_video_size_mb'];
$maxVideoSizeBytes = $maxVideoSizeMb * 1024 * 1024;

// Função auxiliar para contar estritamente arquivos de VÍDEO do cliente (ignora imagens, entretenimentos widgets e rádios)
function countClientVideos($dbCliente, $idCliente) {
    // Extensões de vídeo consideradas na cota
    return (int)$dbCliente->querySingle("
        SELECT COUNT(*) FROM videos 
        WHERE id_cliente = " . (int)$idCliente . "
        AND filename NOT LIKE '/widgets/%'
        AND filename NOT LIKE 'http://%'
        AND filename NOT LIKE 'https://%'
        AND (
            LOWER(filename) LIKE '%.mp4' OR 
            LOWER(filename) LIKE '%.webm' OR 
            LOWER(filename) LIKE '%.ogg' OR
            LOWER(filename) LIKE '%.avi' OR
            LOWER(filename) LIKE '%.mov' OR
            LOWER(filename) LIKE '%.mkv'
        )
    ");
}

// Contagem atual de VÍDEOS do cliente (imagens e entretenimentos NÃO consom a cota)
$currentVideoCount = countClientVideos($dbCliente, $idCliente);

$msg = "";
$tab = $_GET['tab'] ?? 'telas';
$editListId = isset($_GET['edit_list']) ? (int)$_GET['edit_list'] : 0;

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'vinculada') $msg = "<div class='alert alert-success'>Nova tela vinculada com sucesso! A TV começará a tocar automaticamente.</div>";
    if ($_GET['msg'] == 'reloaded') $msg = "<div class='alert alert-success'>Sinal de recarregamento enviado para a tela!</div>";
}
if (isset($_GET['err'])) {
    if ($_GET['err'] == 'uso') $msg = "<div class='alert alert-danger'>Erro: Este código já está em uso.</div>";
    if ($_GET['err'] == 'vazio') $msg = "<div class='alert alert-danger'>Erro: O código da tela não pode ser vazio.</div>";
}
if (isset($_SESSION['sys_msg'])) {
    $msg = $_SESSION['sys_msg'];
    unset($_SESSION['sys_msg']);
}

// Processar ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Upload de Arquivos
    if (isset($_POST['upload_video']) || (!empty($_FILES['video_files']))) {
        if (isset($_FILES['video_files'])) {
            $allowed = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
            $successCount = 0;
            $errorCount = 0;
            $totalFiles = count($_FILES['video_files']['name']);
            
            $videoExtensions = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv'];
            
            // Recalcular quantidade atual de vídeos no banco
            $currentVideoCount = countClientVideos($dbCliente, $idCliente);

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($_FILES['video_files']['error'][$i] == 0) {
                    $filename = $_FILES['video_files']['name'][$i];
                    $fileSize = $_FILES['video_files']['size'][$i];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    $isVideo = in_array($ext, $videoExtensions);

                    // Checar limite de quantidade APENAS se o arquivo for de VÍDEO
                    if ($isVideo && $currentVideoCount >= $maxVideosAllowed) {
                        $errorCount++;
                        $msg .= "<div class='alert alert-warning'><strong>Limite atingido:</strong> Você já possui $currentVideoCount vídeos (limite: $maxVideosAllowed). Remova vídeos antigos ou contate o administrador para aumentar sua cota. (Imagens não entram na cota).</div>";
                        continue;
                    }

                    // Checar limite de tamanho por arquivo
                    if ($fileSize > $maxVideoSizeBytes) {
                        $errorCount++;
                        $sizeFormattedMb = number_format($fileSize / (1024 * 1024), 1);
                        $msg .= "<div class='alert alert-danger'>O arquivo <strong>$filename</strong> tem {$sizeFormattedMb}MB e ultrapassa o limite permitido de {$maxVideoSizeMb}MB.</div>";
                        continue;
                    }

                    if (in_array($ext, $allowed)) {
                        $newFilename = uniqid() . '_' . $i . '.' . $ext;
                        
                        $isUploaded = @move_uploaded_file($_FILES['video_files']['tmp_name'][$i], $uploadDir . $newFilename);

                        if ($isUploaded) {
                            @chmod($uploadDir . $newFilename, 0666);
                            $title = pathinfo($filename, PATHINFO_FILENAME);
                            $stmt = $dbCliente->prepare("INSERT INTO videos (id_cliente, title, filename, upload_date) VALUES (:id_cliente, :title, :filename, :upload_date)");
                            $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
                            $stmt->bindValue(':title', $title, SQLITE3_TEXT);
                            $stmt->bindValue(':filename', $newFilename, SQLITE3_TEXT);
                            $stmt->bindValue(':upload_date', date('Y-m-d H:i:s'), SQLITE3_TEXT);
                            $stmt->execute();
                            $successCount++;
                            if ($isVideo) {
                                $currentVideoCount++;
                            }
                        } else { 
                            $errorCount++; 
                            $msg .= "<div class='alert alert-danger'>Falha ao mover arquivo $filename (Permissão negada na pasta uploads/clientes/$idCliente).</div>";
                        }
                    } else { 
                        $errorCount++; 
                        $msg .= "<div class='alert alert-warning'>Extensão não permitida (.$ext) no arquivo: $filename</div>";
                    }
                } else {
                    $errorCount++;
                    $errCode = $_FILES['video_files']['error'][$i];
                    $errReason = "Limite do servidor (upload_max_filesize = " . ini_get('upload_max_filesize') . ", post_max_size = " . ini_get('post_max_size') . ").";
                    if ($errCode == UPLOAD_ERR_INI_SIZE || $errCode == UPLOAD_ERR_FORM_SIZE) {
                        $msg .= "<div class='alert alert-danger'>O arquivo é maior que o limite configurado no PHP do servidor ($errReason).</div>";
                    } else {
                        $msg .= "<div class='alert alert-danger'>Erro no upload do arquivo (Código PHP: $errCode). $errReason</div>";
                    }
                }
            }
            if ($successCount > 0) $msg .= "<div class='alert alert-success'>$successCount arquivo(s) enviado(s) com sucesso!</div>";
            
            $_SESSION['sys_msg'] = $msg;
            $tab = 'arquivos';

            if (isset($_POST['ajax_upload'])) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => ($successCount > 0 && $errorCount == 0),
                    'successCount' => $successCount,
                    'errorCount' => $errorCount,
                    'msg' => strip_tags($msg)
                ]);
                exit;
            }
        } else {
            // Quando o arquivo estoura post_max_size do PHP, $_FILES e $_POST chegam vazios!
            $postMax = ini_get('post_max_size');
            $uploadMax = ini_get('upload_max_filesize');
            $msg = "<div class='alert alert-danger'><strong>O servidor bloqueou o envio!</strong> O tamanho do vídeo ultrapassou o limite do PHP na VPS (<code>post_max_size = $postMax</code> / <code>upload_max_filesize = $uploadMax</code>). Aumente esses valores no PHP do aaPanel para pelo menos 100M.</div>";
            $_SESSION['sys_msg'] = $msg;
            $tab = 'arquivos';
        }
    } elseif (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        // Caso o navegador envie um arquivo maior que post_max_size, o PHP zera $_POST['upload_video'] também!
        $postMax = ini_get('post_max_size');
        $uploadMax = ini_get('upload_max_filesize');
        $sentMb = number_format($_SERVER['CONTENT_LENGTH'] / (1024 * 1024), 1);
        $msg = "<div class='alert alert-danger'><strong>Arquivo muito grande para o PHP da VPS ({$sentMb}MB enviados)!</strong> O PHP do servidor está limitado a <code>post_max_size = $postMax</code> e <code>upload_max_filesize = $uploadMax</code>. Ajuste no aaPanel > App Store > PHP > Upload Limit para 100M.</div>";
        $_SESSION['sys_msg'] = $msg;
        $tab = 'arquivos';
        if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest')) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'msg' => strip_tags($msg)]);
            exit;
        }
    }

    // Apagar Arquivo Individual
    if (isset($_POST['apagar_video'])) {
        $idVideo = (int)$_POST['id_video'];
        $stmt = $dbCliente->prepare("SELECT filename FROM videos WHERE id = :id AND id_cliente = :idc");
        $stmt->bindValue(':id', $idVideo, SQLITE3_INTEGER);
        $stmt->bindValue(':idc', $idCliente, SQLITE3_INTEGER);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $file = $uploadDir . $row['filename'];
            if (file_exists($file)) unlink($file);
            $dbCliente->exec("DELETE FROM videos WHERE id = $idVideo AND id_cliente = $idCliente");
            $dbCliente->exec("DELETE FROM playlist_videos WHERE id_video = $idVideo");
            $msg = "<div class='alert alert-success'>Arquivo apagado com sucesso!</div>";
        }
        $tab = 'arquivos';
    }

    // Apagar Múltiplos Arquivos
    if (isset($_POST['apagar_multiplos'])) {
        if (!empty($_POST['selected_files'])) {
            $apagados = 0;
            foreach ($_POST['selected_files'] as $idVideo) {
                $idVideo = (int)$idVideo;
                $stmt = $dbCliente->prepare("SELECT filename FROM videos WHERE id = :id AND id_cliente = :idc");
                $stmt->bindValue(':id', $idVideo, SQLITE3_INTEGER);
                $stmt->bindValue(':idc', $idCliente, SQLITE3_INTEGER);
                $res = $stmt->execute();
                $row = $res->fetchArray(SQLITE3_ASSOC);
                if ($row) {
                    $file = $uploadDir . $row['filename'];
                    if (file_exists($file) && !is_dir($file)) unlink($file);
                    $dbCliente->exec("DELETE FROM videos WHERE id = $idVideo AND id_cliente = $idCliente");
                    $dbCliente->exec("DELETE FROM playlist_videos WHERE id_video = $idVideo");
                    $apagados++;
                }
            }
            $msg = "<div class='alert alert-success'>$apagados arquivo(s) apagado(s) com sucesso!</div>";
        } else {
            $msg = "<div class='alert alert-warning'>Nenhum arquivo selecionado.</div>";
        }
        $tab = 'arquivos';
    }

    // Adicionar Rádio via Buscador Automático
    if (isset($_POST['add_radio_submit'])) {
        $nomeRadio = trim($_POST['add_radio_name']);
        $urlRadio = trim($_POST['add_radio_url']);
        if (!empty($nomeRadio) && !empty($urlRadio)) {
            $stmt = $dbCliente->prepare("INSERT INTO videos (id_cliente, title, filename, upload_date) VALUES (:id_cliente, :title, :filename, :upload_date)");
            $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
            $stmt->bindValue(':title', "📻 " . $nomeRadio, SQLITE3_TEXT);
            $stmt->bindValue(':filename', $urlRadio, SQLITE3_TEXT);
            $stmt->bindValue(':upload_date', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->execute();
            $msg = "<div class='alert alert-success'>Rádio adicionada com sucesso aos seus arquivos!</div>";
        }
        $tab = 'arquivos';
    }

    // 2. Criar Nova Playlist
    if (isset($_POST['criar_lista'])) {
        $nomeLista = $_POST['nome_lista'] ?? 'Nova Lista';
        $stmt = $dbCliente->prepare("INSERT INTO playlists (id_cliente, title, creation_date, is_active) VALUES (:id, :title, :cdate, 0)");
        $stmt->bindValue(':id', $idCliente, SQLITE3_INTEGER);
        $stmt->bindValue(':title', $nomeLista, SQLITE3_TEXT);
        $stmt->bindValue(':cdate', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $stmt->execute();
        $newListId = $dbCliente->lastInsertRowID();
        
        // Se for a primeira lista, ativa ela
        $res = $dbCliente->querySingle("SELECT COUNT(*) FROM playlists WHERE id_cliente = $idCliente");
        if ($res == 1) {
            $dbCliente->exec("UPDATE playlists SET is_active = 1 WHERE id = $newListId");
        }
        
        header("Location: ?tab=lista&edit_list=$newListId");
        exit;
    }

    // 3. Tornar Playlist Ativa
    if (isset($_POST['set_active'])) {
        $idActive = (int)$_POST['id_playlist'];
        $dbCliente->exec("UPDATE playlists SET is_active = 0 WHERE id_cliente = $idCliente");
        $dbCliente->exec("UPDATE playlists SET is_active = 1 WHERE id = $idActive AND id_cliente = $idCliente");
        $msg = "<div class='alert alert-success'>Lista definida como ativa! O Player tocará esta lista.</div>";
        $tab = 'lista';
    }

    // 4. Apagar Playlist
    if (isset($_POST['apagar_lista'])) {
        $idApagar = (int)$_POST['id_playlist'];
        $dbCliente->exec("DELETE FROM playlists WHERE id = $idApagar AND id_cliente = $idCliente");
        $dbCliente->exec("DELETE FROM playlist_videos WHERE id_playlist = $idApagar");
        $msg = "<div class='alert alert-success'>Playlist apagada com sucesso.</div>";
        $tab = 'lista';
    }
    // 5. Vincular Nova Tela
    if (isset($_POST['vincular_tela'])) {
        $nomeTela = $_POST['nome_tela'] ?? 'Nova Tela';
        $code = $_POST['codigo_tela'] ?? ''; 
        $code = strtoupper(trim($code)); // Limpa e padroniza para maiúsculo
        
        if (!empty($code)) {
            // Verifica se o código já não foi usado por outra tela
            $check = $dbCliente->querySingle("SELECT id FROM telas WHERE player_code = '$code'");
            if (!$check) {
                $stmt = $dbCliente->prepare("INSERT INTO telas (id_cliente, nome_tela, player_code) VALUES (:id_cliente, :nome, :code)");
                $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
                $stmt->bindValue(':nome', $nomeTela, SQLITE3_TEXT);
                $stmt->bindValue(':code', $code, SQLITE3_TEXT);
                $stmt->execute();
                
                // ==========================================
                // LÓGICA DE COBRANÇA PROPORCIONAL E MERCADO PAGO
                // ==========================================
                $countTelas = $dbCliente->querySingle("SELECT COUNT(*) FROM telas WHERE id_cliente = $idCliente");
                
                $hoje = new DateTime();
                $diaAtual = (int)$hoje->format('d');
                $mesAtual = (int)$hoje->format('m');
                $anoAtual = (int)$hoje->format('Y');

                // O ciclo vai do dia 5 ao dia 4 do mês seguinte.
                if ($diaAtual >= 5) {
                    $targetDate = new DateTime("$anoAtual-$mesAtual-04");
                    $targetDate->modify('+1 month');
                } else {
                    $targetDate = new DateTime("$anoAtual-$mesAtual-04");
                }
                
                $diff = $hoje->diff($targetDate);
                $diasRestantes = $diff->days;
                if ($diasRestantes < 1) $diasRestantes = 1; // Pelo menos 1 dia
                
                $mpGoldConfig = json_decode(file_get_contents(dirname(__DIR__) . '/api/mercado_pago_gold/config.json'), true);
                $baseValorPorTela = isset($mpGoldConfig['plano_telas_ativas']['valor']) ? (float)$mpGoldConfig['plano_telas_ativas']['valor'] : 100.00;
                $diasPorTela = isset($mpGoldConfig['plano_telas_ativas']['dias']) ? (int)$mpGoldConfig['plano_telas_ativas']['dias'] : 30;
                
                $multiplo = $dbFinanceiro->querySingle("SELECT multiplo FROM ibo WHERE id = $idCliente") ?: 1.0;
                $valorPorTela = $baseValorPorTela * (float)$multiplo;
                
                $valorProporcional = ($valorPorTela / $diasPorTela) * $diasRestantes;
                $valorProporcional = round($valorProporcional, 2);
                
                $dataGerada = $hoje->format('Y-m-d');
                
                // Se for a primeira tela, dá 1 dia grátis. A fatura vence em 1 dia.
                // Se for tela adicional, o vencimento real é em 3 dias (prazo dado pelo admin) ou imediato?
                // O admin disse "varredura dia 5 vencimento 3 dias apos". Para telas avulsas, vamos dar 3 dias também.
                $dataVencimento = $hoje->modify('+3 days')->format('Y-m-d');
                
                // Grava a fatura pendente
                $stmtFat = $dbFinanceiro->prepare("INSERT INTO faturas (id_cliente, data_gerada, data_vencimento, valor_total, status) VALUES (:id_cliente, :data_gerada, :data_vencimento, :valor, 'PENDENTE')");
                $stmtFat->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
                $stmtFat->bindValue(':data_gerada', $dataGerada, SQLITE3_TEXT);
                $stmtFat->bindValue(':data_vencimento', $dataVencimento, SQLITE3_TEXT);
                $stmtFat->bindValue(':valor', $valorProporcional, SQLITE3_FLOAT);
                $stmtFat->execute();
                $idFatura = $dbFinanceiro->lastInsertRowID();
                
                // Gerar link Mercado Pago
                $config_file = dirname(__DIR__) . '/api/mercado_pago_gold/config.json';
                if (file_exists($config_file)) {
                    $config = json_decode(file_get_contents($config_file), true);
                    $access_token = $config['access_token'] ?? '';
                    
                    if (!empty($access_token)) {
                        $base_url = "https://" . $_SERVER['HTTP_HOST'] . "/api/mercado_pago_gold";
                        $dataMP = [
                            "items" => [
                                [
                                    "title" => "Fatura Proporcional - Tela Adicional",
                                    "quantity" => 1,
                                    "currency_id" => "BRL",
                                    "unit_price" => $valorProporcional
                                ]
                            ],
                            "back_urls" => [
                                "success" => "https://" . $_SERVER['HTTP_HOST'] . "/cliente_dashboard.php?tab=config",
                                "failure" => "https://" . $_SERVER['HTTP_HOST'] . "/cliente_dashboard.php?tab=config",
                                "pending" => "https://" . $_SERVER['HTTP_HOST'] . "/cliente_dashboard.php?tab=config"
                            ],
                            "notification_url" => $base_url . "/webhook.php",
                            "auto_return" => "approved",
                            "external_reference" => "MPGFATURA|" . $idFatura,
                            "payment_methods" => ["excluded_payment_types" => [["id" => "ticket"]]]
                        ];
                        
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/checkout/preferences");
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            "Authorization: Bearer " . $access_token,
                            "Content-Type: application/json"
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($dataMP));
                        $resMP = curl_exec($ch);
                        curl_close($ch);
                        
                        if ($resMP) {
                            $mpDecoded = json_decode($resMP, true);
                            if (isset($mpDecoded['init_point'])) {
                                $link = $mpDecoded['init_point'];
                                $idPref = $mpDecoded['id'];
                                $dbFinanceiro->exec("UPDATE faturas SET link_pagamento = '$link', id_preferencia = '$idPref' WHERE id = $idFatura");
                            }
                        }
                    }
                }
                
                // Lógica de 1 dia grátis para a 1ª tela
                if ($countTelas == 1) {
                    $hojeReal = new DateTime();
                    $expireBonus = $hojeReal->modify('+1 day')->format('Y-m-d');
                    $dbFinanceiro->exec("UPDATE ibo SET expire_date = '$expireBonus', active = 1 WHERE id = $idCliente");
                }
                
                // Redireciona para o financeiro para ele já ver a fatura
                header("Location: cliente_dashboard.php?tab=config&msg=vinculada");
                exit;
            } else {
                header("Location: cliente_dashboard.php?tab=telas&err=uso");
                exit;
            }
        } else {
            header("Location: cliente_dashboard.php?tab=telas&err=vazio");
            exit;
        }
    }

    // 7. Forçar recarregamento da tela
    if (isset($_POST['force_reload'])) {
        $idTela = (int)$_POST['id_tela'];
        $dbCliente->exec("UPDATE telas SET force_reload = 1 WHERE id = $idTela AND id_cliente = $idCliente");
        header("Location: cliente_dashboard.php?tab=telas&msg=reloaded");
        exit;
    }

    // 6. Apagar Tela
    if (isset($_POST['apagar_tela'])) {
        $idTela = (int)$_POST['id_tela'];
        $dbCliente->exec("DELETE FROM telas WHERE id = $idTela AND id_cliente = $idCliente");
        $msg = "<div class='alert alert-success'>Tela removida com sucesso.</div>";
        $tab = 'telas';
    }

    // 8. Atualizar Senha (Perfil)
    if (isset($_POST['atualizar_senha'])) {
        $novaSenha = trim($_POST['nova_senha']);
        if (!empty($novaSenha)) {
            $stmt = $dbFinanceiro->prepare("UPDATE ibo SET password = :senha WHERE id = :id");
            $stmt->bindValue(':senha', $novaSenha, SQLITE3_TEXT);
            $stmt->bindValue(':id', $idCliente, SQLITE3_INTEGER);
            $stmt->execute();
            $msg = "<div class='alert alert-success'>Senha atualizada com sucesso!</div>";
        }
        $tab = 'config';
    }

    // 9. Atualizar Dados Faturamento
    if (isset($_POST['atualizar_faturamento'])) {
        $nome_razao = $_POST['nome_razao'] ?? '';
        $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
        $endereco = $_POST['endereco'] ?? '';
        $whats = $_POST['whats'] ?? '';
        
        $stmt = $dbFinanceiro->prepare("INSERT OR REPLACE INTO faturamento_cliente (id_cliente, nome_razao, cpf_cnpj, endereco, whats) VALUES (:id, :nome, :cpf, :end, :wts)");
        $stmt->bindValue(':id', $idCliente, SQLITE3_INTEGER);
        $stmt->bindValue(':nome', $nome_razao, SQLITE3_TEXT);
        $stmt->bindValue(':cpf', $cpf_cnpj, SQLITE3_TEXT);
        $stmt->bindValue(':end', $endereco, SQLITE3_TEXT);
        $stmt->bindValue(':wts', $whats, SQLITE3_TEXT);
        $stmt->execute();
        
        $msg = "<div class='alert alert-success'>Dados de faturamento atualizados com sucesso!</div>";
        $tab = 'config';
    }
}

// SINCRONIZAR ENTRETENIMENTOS GLOBAIS PARA O CLIENTE
try {
    $dbR = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_radios.db");
    
    // Garantir que a tabela exista
    $dbR->exec("CREATE TABLE IF NOT EXISTS entretenimentos (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nome TEXT NOT NULL,
        url TEXT NOT NULL,
        icon TEXT DEFAULT 'bx bx-globe',
        is_visible INTEGER DEFAULT 1
    )");
    
    $countEnt = $dbR->querySingle("SELECT COUNT(*) FROM entretenimentos");
    if ($countEnt == 0) {
        $defaultEnt = [
            ['Hora Atual', '/widgets/hora.php', 'fas fa-clock'],
            ['Frases e Pensamentos', '/widgets/frases.php', 'fas fa-quote-left'],
            ['Notícias (Últimas)', '/widgets/noticias.php', 'fas fa-newspaper'],
            ['Notícias de Carros', '/widgets/carros.php', 'fas fa-car']
        ];
        $stmtE = $dbR->prepare("INSERT INTO entretenimentos (nome, url, icon) VALUES (:nome, :url, :icon)");
        foreach ($defaultEnt as $ent) {
            $stmtE->bindValue(':nome', $ent[0], SQLITE3_TEXT);
            $stmtE->bindValue(':url', $ent[1], SQLITE3_TEXT);
            $stmtE->bindValue(':icon', $ent[2], SQLITE3_TEXT);
            $stmtE->execute();
        }
    } else {
        // Assegurar que os widgets novos existam na tabela
        $checkCarros = $dbR->querySingle("SELECT count(*) FROM entretenimentos WHERE url = '/widgets/carros.php'");
        if ($checkCarros == 0) {
            $dbR->exec("INSERT INTO entretenimentos (nome, url, icon) VALUES ('Notícias de Carros', '/widgets/carros.php', 'fas fa-car')");
        }
    }

    $activeUrls = [];
    $resEnt = $dbR->query("SELECT * FROM entretenimentos WHERE is_visible = 1");
    while ($ent = $resEnt->fetchArray(SQLITE3_ASSOC)) {
        $nome = $ent['nome'];
        $url = $ent['url'];
        $activeUrls[] = "'" . $url . "'";
        // Verifica se já existe nos videos do cliente
        $check = $dbCliente->querySingle("SELECT id FROM videos WHERE id_cliente = $idCliente AND filename = '$url'");
        if (!$check) {
            $stmt = $dbCliente->prepare("INSERT INTO videos (id_cliente, title, filename, upload_date) VALUES (:id_cliente, :title, :filename, :upload_date)");
            $stmt->bindValue(':id_cliente', $idCliente, SQLITE3_INTEGER);
            $stmt->bindValue(':title', "⭐ " . $nome, SQLITE3_TEXT);
            $stmt->bindValue(':filename', $url, SQLITE3_TEXT);
            $stmt->bindValue(':upload_date', date('Y-m-d H:i:s'), SQLITE3_TEXT);
            $stmt->execute();
        }
    }
    
    // Remove as categorias que foram deletadas ou ocultadas pelo admin
    if (!empty($activeUrls)) {
        $urlsList = implode(',', $activeUrls);
        $dbCliente->exec("DELETE FROM videos WHERE id_cliente = $idCliente AND filename LIKE '/widgets/%' AND filename NOT IN ($urlsList)");
    } else {
        // Se nenhum estiver ativo, deleta todos os widgets
        $dbCliente->exec("DELETE FROM videos WHERE id_cliente = $idCliente AND filename LIKE '/widgets/%'");
    }
} catch (Exception $e) {
    // Registra erro silencioso caso falhe
    error_log("Erro ao carregar entretenimentos: " . $e->getMessage());
}

// DADOS DO CLIENTE
$telasList = [];
$res = $dbCliente->query("SELECT * FROM telas WHERE id_cliente = $idCliente ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $telasList[] = $row;
}
$videosList = [];
$arquivosOnly = [];
$entOnly = [];
$res = $dbCliente->query("SELECT * FROM videos WHERE id_cliente = $idCliente ORDER BY id DESC");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $videosList[] = $row;
    if (strpos($row['filename'], '/widgets/') === 0 || strpos($row['filename'], 'http') === 0) {
        $entOnly[] = $row;
    } else {
        $arquivosOnly[] = $row;
    }
}

$playlistsList = [];
$res = $dbCliente->query("
    SELECT p.*, (SELECT COUNT(*) FROM playlist_videos pv WHERE pv.id_playlist = p.id) as qtd_videos 
    FROM playlists p WHERE p.id_cliente = $idCliente ORDER BY p.id DESC
");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $playlistsList[] = $row;
}

// Busca rádios globais
$radiosGlobais = [];
try {
    $dbR = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_radios.db");
    $resR = $dbR->query("SELECT id, nome FROM radios WHERE is_visible = 1 ORDER BY nome ASC");
    if ($resR) {
        while ($r = $resR->fetchArray(SQLITE3_ASSOC)) {
            $radiosGlobais[] = $r;
        }
    }
} catch(Exception $e) {}

// Busca rádios customizadas do cliente
$radiosCliente = [];
foreach ($videosList as $v) {
    if (strpos($v['filename'], 'http') === 0) {
        $radiosCliente[] = $v;
    }
}

// Se estiver editando uma lista específica
$playlistEdit = null;
$playlistVideosData = [];
if ($editListId > 0) {
    $stmt = $dbCliente->prepare("SELECT * FROM playlists WHERE id = :id AND id_cliente = :idc");
    $stmt->bindValue(':id', $editListId, SQLITE3_INTEGER);
    $stmt->bindValue(':idc', $idCliente, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $playlistEdit = $res->fetchArray(SQLITE3_ASSOC);
    
    if ($playlistEdit) {
        $resVid = $dbCliente->query("SELECT v.*, pv.sequence, pv.duration FROM playlist_videos pv JOIN videos v ON pv.id_video = v.id WHERE pv.id_playlist = $editListId ORDER BY pv.sequence ASC");
        while ($row = $resVid->fetchArray(SQLITE3_ASSOC)) {
            $playlistVideosData[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Painel do Cliente - Player99</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Configuração PWA (Progressive Web App) -->
    <link rel="manifest" href="manifest_cliente.json">
    <meta name="theme-color" content="#0A38D1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Player99">
    <link rel="apple-touch-icon" href="./banners/logo.png">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        body { font-family: 'Roboto', sans-serif; background-color: #181b21; color: #333; margin: 0; }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: #0f172a; color: #fff; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.05); }
        .sidebar-brand { padding: 25px 20px; font-size: 24px; font-weight: bold; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; gap: 15px; }
        .sidebar-status { 
            display: block !important;
            margin: 20px 15px !important;
            padding: 10px !important;
            text-align: center !important;
            border-radius: 8px !important;
            background: #0f172a !important;
            color: #00f3ff !important;
            border: 1px solid #00f3ff !important;
            box-shadow: 0 0 5px #00f3ff, 0 0 10px #00f3ff inset !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            text-transform: uppercase !important;
            font-weight: bold !important;
            letter-spacing: 1px !important;
        }
        .sidebar-status:hover {
            background: #00f3ff !important;
            color: #000 !important;
            box-shadow: 0 0 15px #00f3ff, 0 0 20px #00f3ff inset !important;
        }
        .sidebar-nav { flex: 1; padding: 10px 0; }
        .sidebar-link { display: flex; align-items: center; padding: 15px 25px; color: #d1e0fc; text-decoration: none; transition: all 0.3s; font-size: 15px; border-left: 3px solid transparent; }
        .sidebar-link i { width: 35px; font-size: 20px; }
        .sidebar-link:hover { color: #fff; background-color: rgba(255,255,255,0.05); text-decoration: none; }
        .sidebar-link.active { color: #fff; background-color: rgba(255,255,255,0.05); border-left-color: #00f3ff; font-weight: 600; }
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; }
        .topbar { background: #0f172a; height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #fff; }
        .topbar h4.text-dark { color: #fff !important; margin: 0; }
        .topbar .logout-btn { color: #fff; text-decoration: none; }
        .content-area { padding: 30px; }
        .table-responsive { width: 100%; overflow-x: auto; }
        
        .bottom-nav { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -260px; top: 0; bottom: 0; z-index: 1040; transition: left 0.3s ease; }
            .sidebar.show { left: 0; }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1030; }
            .sidebar-overlay.show { display: block; }
            .topbar { padding: 0 15px; }
            .content-area { padding: 15px; }
            .dnd-container { flex-direction: column; }
            .dnd-panel { height: 400px; }

            /* ===== MOBILE (motorista): tabela de telas vira cartões ===== */
            .content-area { padding-bottom: 90px; }
            .card-header { flex-wrap: wrap; gap: 10px; padding: 15px; font-size: 16px; }
            .card-header .btn { width: 100%; min-height: 48px; font-size: 15px; }
            .table-telas thead { display: none; }
            .table-telas, .table-telas tbody, .table-telas tr, .table-telas td { display: block; width: 100%; }
            .table-telas tr { border: 1px solid #e3e6ea; border-radius: 12px; margin-bottom: 14px; padding: 12px; background: #fff !important; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
            .table-telas td { border: none !important; padding: 6px 0 !important; text-align: left !important; }
            .table-telas td[data-label]:before { content: attr(data-label); display: block; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 3px; }
            .table-telas td.tela-nome { font-size: 18px; }
            .table-telas td.tela-nome:before { display: none; }
            .table-telas .form-control, .table-telas .form-control-sm { min-height: 46px; font-size: 16px; }
            .table-telas .btn, .table-telas .btn-sm { min-height: 46px; min-width: 46px; font-size: 15px; }
            .table-telas td.tela-acoes { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
            .table-telas td.tela-acoes:before { width: 100%; }
            .table-telas td.tela-acoes form { display: inline-block; }
            .modal-dialog { margin: 10px; }
            .modal-body .btn-lg { width: 100%; margin: 6px 0 !important; }

            /* ===== Barra de navegação inferior (polegar) ===== */
            .bottom-nav { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 1020; background: #0b1424; border-top: 1px solid rgba(255,255,255,0.12); padding-bottom: env(safe-area-inset-bottom); }
            .bottom-nav a { flex: 1; text-align: center; padding: 8px 2px 6px; color: #9fb3d9; text-decoration: none; font-size: 11px; }
            .bottom-nav a i { display: block; font-size: 22px; margin-bottom: 2px; }
            .bottom-nav a.active { color: #00f3ff; font-weight: 700; }
        }
        .card { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; color: #333; }
        .card-header { background: #ffffff; border-bottom: 1px solid #eee; padding: 20px 25px; font-weight: 700; font-size: 18px; display: flex; justify-content: space-between; align-items: center; border-radius: 8px 8px 0 0 !important; color: #333; text-transform: uppercase; }
        .table { color: #333; margin-bottom: 0; }
        .table th { border-top: none; color: #555; font-weight: 700; font-size: 13px; text-transform: uppercase; border-bottom: 2px solid #eee; }
        .table td { border-top: 1px solid #eee; vertical-align: middle; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: #f8f9fa; }
        
        /* Modal theme */
        .modal-content { background-color: #ffffff; color: #333; border: none; border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); }
        .modal-header { border-bottom: 1px solid #eee; background-color: #f8f9fa !important; border-radius: 8px 8px 0 0; }
        .modal-title { color: #333; font-weight: bold; }
        .close { color: #333; }
        .form-control { background-color: #fff; border: 1px solid #ced4da; color: #495057; }
        .form-control:focus { background-color: #fff; border-color: #80bdff; color: #495057; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
        
        /* Drag and Drop Styles */
        .dnd-container { display: flex; gap: 20px; }
        .dnd-panel { flex: 1; background: #ffffff; border: 1px solid #eee; border-radius: 8px; padding: 15px; height: 600px; display: flex; flex-direction: column; }
        
        /* Correção para as abas dentro do dnd-panel usarem o espaço corretamente */
        .dnd-panel .tab-content { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .dnd-panel .tab-pane { flex: 1; flex-direction: column; min-height: 0; }
        .dnd-panel .tab-pane.active { display: flex !important; }
        
        .dnd-header { font-weight: bold; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; color: #333; }
        .video-list { flex: 1; overflow-y: auto; padding-right: 5px; min-height: 50px; }
        .video-item { background: #f8f9fa; border: 1px solid #ddd; padding: 10px 15px; margin-bottom: 8px; border-radius: 5px; cursor: grab; display: flex; align-items: center; font-weight: 500; font-size: 14px; color: #333; }
        .video-item:active { cursor: grabbing; }
        .video-item i.drag-handle { color: #aaa; margin-right: 15px; cursor: grab; }
        .video-item .vid-title { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ghost-class { opacity: 0.4; background: #e9ecef; color: #333; }
        .empty-placeholder { text-align: center; padding: 40px 20px; color: #aaa; border: 2px dashed #ddd; border-radius: 8px; margin-top: 10px; }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Navegação inferior (somente celular) -->
<nav class="bottom-nav">
    <a href="?tab=telas" class="<?php echo $tab=='telas'?'active':''; ?>"><i class="fas fa-desktop"></i>Telas</a>
    <a href="?tab=arquivos" class="<?php echo $tab=='arquivos'?'active':''; ?>"><i class="fas fa-image"></i>Arquivos</a>
    <a href="?tab=lista" class="<?php echo $tab=='lista'?'active':''; ?>"><i class="fas fa-list-ul"></i>Listas</a>
    <a href="?tab=config" class="<?php echo $tab=='config'?'active':''; ?>"><i class="fas fa-cog"></i>Config</a>
</nav>

<div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand"><img src="banners/logo.png" style="max-height: 40px;" alt="Logo da Empresa"></div>
        <div class="sidebar-status"><i class="fas fa-check-circle"></i> Todas funcionando</div>
        <div class="sidebar-nav">
            <a href="?tab=telas" class="sidebar-link <?php echo $tab=='telas'?'active':''; ?>"><i class="fas fa-desktop"></i> Telas (Player)</a>
            <a href="?tab=arquivos" class="sidebar-link <?php echo $tab=='arquivos'?'active':''; ?>"><i class="fas fa-image"></i> Arquivos</a>
            <a href="?tab=lista" class="sidebar-link <?php echo $tab=='lista'?'active':''; ?>"><i class="fas fa-list-ul"></i> Lista de Reprodução</a>
            <a href="?tab=config" class="sidebar-link <?php echo $tab=='config'?'active':''; ?>"><i class="fas fa-cog"></i> Configurações</a>
            <?php if ($clienteUser !== 'cemet@cemet.com.br'): ?>
            <a href="afiliado_dashboard.php" class="sidebar-link text-warning mt-2"><i class="fas fa-hand-holding-usd"></i> Meu Link Afiliado</a>
            <?php endif; ?>
            <a href="javascript:void(0);" onclick="installClientPWA()" id="btnInstallDashboardPWA" class="sidebar-link text-info mt-2" style="display:none; background: rgba(0, 243, 255, 0.08); border-radius: 6px; margin: 5px 10px; padding: 10px 15px;">
                <i class="fas fa-download"></i> Instalar App PWA
            </a>
            <a href="logout_cliente.php" class="sidebar-link text-danger mt-3" style="border-top: 1px solid rgba(255,255,255,0.1);"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-light d-md-none mr-3" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
                <h4 class="m-0 text-dark" style="font-weight: 700;">Área do Cliente</h4>
            </div>
            <div class="topbar-user"><i class="fas fa-user-circle"></i> Olá, <?php echo htmlspecialchars($clienteUser); ?></div>
        </div>

        <div class="content-area">
            <?php echo $msg; ?>

            <!-- ABA TELAS -->
            <?php if($tab == 'telas'): ?>
            <div class="card">
                <div class="card-header">
                    <div><i class="fas fa-tv text-primary mr-2"></i> Telas</div>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#novaTelaModal"><i class="fas fa-plus"></i> VINCULAR TELA</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                    <table class="table table-striped table-telas">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Lista de Reprodução</th>
                                <th>Código do Player</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($telasList)): ?>
                            <tr><td colspan="5" class="text-center py-4">Nenhuma tela vinculada. Clique em Vincular Tela!</td></tr>
                            <?php else: ?>
                                <?php 
                                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                                    $baseUrl = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/player.php?codigo=";
                                    
                                    foreach($telasList as $tela): 
                                        $playerLink = $baseUrl . $tela['player_code'];
                                        
                                        // Calcular status online (ping nos últimos 2 minutos)
                                        $isOnline = false;
                                        if (!empty($tela['ultimo_ping'])) {
                                            $lastPing = strtotime($tela['ultimo_ping']);
                                            $now = time();
                                            if (($now - $lastPing) <= 120) { // 120 seconds
                                                $isOnline = true;
                                            }
                                        }
                                ?>
                                <tr>
                                    <td class="font-weight-bold align-middle tela-nome" data-label="Tela"><?php echo htmlspecialchars($tela['nome_tela']); ?></td>
                                    <td class="align-middle" data-label="Playlist">
                                        <select class="form-control form-control-sm select-playlist" data-idtela="<?php echo $tela['id']; ?>">
                                            <option value="0">-- Selecione --</option>
                                            <?php foreach($playlistsList as $pl): ?>
                                                <option value="<?php echo $pl['id']; ?>" <?php echo ($tela['id_playlist'] == $pl['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($pl['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="align-middle" data-label="Código">
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control font-weight-bold text-center" value="<?php echo htmlspecialchars($tela['player_code']); ?>" readonly>
                                            <div class="input-group-append">
                                                <?php $currentRotation = isset($tela['rotation']) ? $tela['rotation'] : 0; ?>
                                                <button type="button" class="btn btn-outline-secondary" onclick="toggleRotation(<?php echo $tela['id']; ?>, <?php echo $currentRotation == 90 ? 0 : 90; ?>)" title="<?php echo $currentRotation == 90 ? 'Voltar ao normal (0º)' : 'Girar tela (90º)'; ?>">
                                                    <i class="fas fa-sync-alt" style="<?php echo $currentRotation == 90 ? 'color: #34e2e4;' : ''; ?>"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('<?php echo $playerLink; ?>'); alert('Link completo copiado para a área de transferência!');" title="Copiar Link Completo">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                                <a href="<?php echo $playerLink; ?>" target="_blank" class="btn btn-outline-secondary" title="Abrir no Navegador"><i class="fas fa-external-link-alt"></i></a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center" data-label="Status">
                                        <?php if($isOnline): ?>
                                            <i class="fas fa-circle text-success" title="Funcionando corretamente"></i> Online
                                        <?php else: ?>
                                            <i class="fas fa-circle text-danger" title="Sem Comunicação"></i> Offline
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle tela-acoes" data-label="Ações">
                                        <?php 
                                            $audioMode = isset($tela['audio_mode']) ? $tela['audio_mode'] : 'video';
                                            $idRadio = isset($tela['id_radio']) ? $tela['id_radio'] : 0;
                                            
                                            $btnClass = 'btn-info';
                                            $btnIcon = 'fa-volume-up';
                                            $btnTitle = 'Áudio do Vídeo';
                                            if ($audioMode === 'mute' || (isset($tela['muted']) && $tela['muted'] == 1 && $audioMode == 'video')) {
                                                // Fallback to mute if the old 'muted' column was 1 and no audio_mode was saved yet
                                                if ($audioMode == 'video' && isset($tela['muted']) && $tela['muted'] == 1) $audioMode = 'mute';
                                                
                                                $btnClass = 'btn-secondary';
                                                $btnIcon = 'fa-volume-mute';
                                                $btnTitle = 'Mudo';
                                            } elseif ($audioMode === 'radio') {
                                                $btnClass = 'btn-warning';
                                                $btnIcon = 'fa-broadcast-tower'; // Trocado para antena/rádio que as vezes renderiza melhor
                                                $btnTitle = 'Rádio Web';
                                            }
                                        ?>
                                        <button class="btn btn-sm <?php echo $btnClass; ?>" onclick="openAudioModal(<?php echo $tela['id']; ?>, '<?php echo $audioMode; ?>', <?php echo $idRadio; ?>)" title="Áudio: <?php echo $btnTitle; ?>">
                                            <i class="fas <?php echo $btnIcon; ?>"></i>
                                            <?php if($audioMode === 'radio') echo ' <span class="font-weight-bold ml-1">Rádio</span>'; ?>
                                        </button>
                                        <form method="post" style="display:inline-block;" onsubmit="return confirm('Deseja enviar um sinal para a TV recarregar agora? (Útil se você mudou os vídeos ou a rádio e quer que atualize na hora)');">
                                            <input type="hidden" name="id_tela" value="<?php echo $tela['id']; ?>">
                                            <button type="submit" name="force_reload" class="btn btn-sm btn-primary" title="Forçar Atualização na TV">
                                                <i class="fas fa-sync-alt"></i>
                                            </button>
                                        </form>
                                        <a href="#" onclick="alert('Use um app como AppGeyser ou instale este site como PWA na TV usando o Chrome.'); return false;" class="btn btn-sm btn-success" title="Baixar App Android">
                                            <i class="fab fa-android"></i> APK
                                        </a>
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-light" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                                <form method="post" onsubmit="return confirm('Tem certeza que deseja remover esta tela? A TV parará de tocar.');">
                                                    <input type="hidden" name="id_tela" value="<?php echo $tela['id']; ?>">
                                                    <button type="submit" name="apagar_tela" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash-alt mr-2"></i> Remover Tela
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
            <!-- Modal Configurar Áudio -->
            <div class="modal fade" id="audioModal" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header bg-light">
                    <h5 class="modal-title">Configurar Áudio da Tela</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body">
                    <form id="formAudio">
                        <input type="hidden" id="audio_id_tela" name="id_tela">
                        
                        <div class="form-group mb-3 text-left">
                            <label class="font-weight-bold">Modo de Áudio</label>
                            <select class="form-control form-control-lg" id="audio_mode" name="audio_mode" onchange="toggleRadioSelect()">
                                <option value="video">🎥 Tocar Áudio do Vídeo Original</option>
                                <option value="mute">🔇 Silenciado (Mudo)</option>
                                <option value="radio">📻 Rádio Web de Fundo</option>
                            </select>
                        </div>
                        
                        <div class="form-group mb-4 text-left" id="div_radio_select" style="display:none;">
                            <label class="font-weight-bold">Escolha a Rádio</label>
                            <select class="form-control form-control-lg" id="id_radio" name="id_radio" style="background-color: #f8f9fa; border: 2px solid #ddd; font-weight: 500;">
                                <option value="0" style="color: #666;">-- Selecione uma rádio --</option>
                                <optgroup label="⭐ Minhas Rádios Favoritas" style="background-color: #e3f2fd; color: #0d47a1; font-weight: bold;">
                                    <?php foreach($radiosCliente as $r): ?>
                                        <option value="-<?php echo $r['id']; ?>" style="background-color: #fff; color: #333; font-weight: normal;"><?php echo htmlspecialchars(str_replace('📻 ', '', $r['title'])); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="🌍 Rádios Globais" style="background-color: #fce4ec; color: #880e4f; font-weight: bold;">
                                    <?php foreach($radiosGlobais as $r): ?>
                                        <option value="<?php echo $r['id']; ?>" style="background-color: #fff; color: #333; font-weight: normal;"><?php echo htmlspecialchars($r['nome']); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>

                        <div class="d-flex justify-content-center gap-2 mt-4">
                            <button type="button" class="btn btn-primary btn-lg mx-2 px-4" onclick="saveAudioConfig()">SALVAR</button>
                            <button type="button" class="btn btn-light btn-lg mx-2 px-4" data-dismiss="modal">FECHAR</button>
                        </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
            <!-- Modal Nova Tela -->
            <div class="modal fade" id="novaTelaModal" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header bg-light">
                    <h5 class="modal-title">Vincular Nova Tela</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body">
                    <form method="post">
                        <div class="form-group mb-3 text-left">
                            <label class="font-weight-bold">Nome da Tela</label>
                            <input type="text" name="nome_tela" class="form-control form-control-lg" placeholder="Ex: TV Recepção" required>
                        </div>
                        <div class="form-group mb-4 text-left">
                            <label class="font-weight-bold">Código exibido na TV</label>
                            <input type="text" name="codigo_tela" class="form-control form-control-lg text-uppercase" placeholder="Ex: X9BF21" required style="letter-spacing: 5px; font-weight: bold;">
                        </div>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="submit" name="vincular_tela" class="btn btn-primary btn-lg mx-2 px-4">VINCULAR TELA</button>
                            <button type="button" class="btn btn-light btn-lg mx-2 px-4" data-dismiss="modal">CANCELAR</button>
                        </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>

            <script>
                document.querySelectorAll('.select-playlist').forEach(select => {
                    select.addEventListener('change', function() {
                        let idTela = this.getAttribute('data-idtela');
                        let idPlaylist = this.value;
                        
                        $.post('api_telas.php', {
                            action: 'vincular_playlist',
                            id_tela: idTela,
                            id_playlist: idPlaylist
                        }, function(res) {
                            if(res.success) {
                                // Exibir pequeno feedback (Toast) ou apenas mudar a borda
                                select.style.borderColor = '#28a745';
                                setTimeout(() => select.style.borderColor = '', 2000);
                            } else {
                                alert('Erro ao vincular playlist.');
                            }
                        }, 'json');
                    });
                });
            </script>
            <?php endif; ?>

            <!-- ABA ARQUIVOS -->
            <?php if($tab == 'arquivos'): ?>
            <!-- Buscador Automático de Rádios -->
            <div class="card mb-4 shadow-sm" style="background-color: #1b1e24; border: 1px solid #2a2d35;">
                <div class="card-header border-bottom-0 pb-0" style="background-color: #1b1e24; color: #fff;">
                    <h5 class="m-0 font-weight-bold"><i class="fas fa-search"></i> Buscador Automático de Rádios</h5>
                </div>
                <div class="card-body pt-2 text-white">
                    <label style="color: #bbb;">Buscar por Nome</label>
                    <div class="input-group">
                        <input type="text" id="radioSearchInput" class="form-control text-white border-secondary" style="background-color: #2a2d35;" placeholder="Ex: Jovem Pan" onkeypress="if(event.keyCode===13){searchRadio();}">
                        <div class="input-group-append">
                            <button class="btn btn-success" type="button" onclick="searchRadio()">Buscar</button>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block">Digite o nome de uma rádio e clique em buscar.</small>
                    <div id="radioSearchResults" class="mt-3"></div>
                </div>
            </div>
            <script>
            function searchRadio() {
                let query = document.getElementById('radioSearchInput').value.trim();
                if (!query) return;
                let resDiv = document.getElementById('radioSearchResults');
                resDiv.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin text-success" style="font-size:24px;"></i><br>Buscando...</div>';
                
                // Usando nosso proxy backend local
                fetch('cliente_dashboard.php?action=search_radios&q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    if (data.length === 0) {
                        resDiv.innerHTML = '<div class="text-warning text-center py-2">Nenhuma rádio encontrada.</div>';
                        return;
                    }
                    let html = '<ul class="list-group list-group-flush border border-secondary rounded" style="max-height: 300px; overflow-y: auto;">';
                    data.forEach(station => {
                        if(station.url_resolved) {
                            let safeName = station.name.replace(/"/g, '&quot;');
                            html += `
                            <li class="list-group-item d-flex justify-content-between align-items-center" style="background-color: #23262d; border-color: #2a2d35;">
                                <div>
                                    <strong class="text-white">${station.name}</strong><br>
                                    <small class="text-muted">${station.country || 'Desconhecido'} | ${station.tags ? station.tags.substring(0, 30)+'...' : 'Sem tags'}</small>
                                </div>
                                <form method="post" action="cliente_dashboard.php" class="m-0">
                                    <input type="hidden" name="add_radio_name" value="${safeName}">
                                    <input type="hidden" name="add_radio_url" value="${station.url_resolved}">
                                    <button type="submit" name="add_radio_submit" class="btn btn-sm btn-success">Adicionar</button>
                                </form>
                            </li>`;
                        }
                    });
                    html += '</ul>';
                    resDiv.innerHTML = html;
                }).catch(e => {
                    console.error(e);
                    resDiv.innerHTML = '<div class="text-danger text-center py-2">Erro ao buscar rádios. Tente novamente mais tarde.</div>';
                });
            }
            </script>

            <div class="card">
                <div class="card-header">
                    <div>
                        <i class="fas fa-folder-open text-primary mr-2"></i> Meus Arquivos
                        <span class="badge badge-<?php echo ($currentVideoCount >= $maxVideosAllowed) ? 'danger' : 'info'; ?> ml-2" style="font-size: 13px;">
                            <?php echo $currentVideoCount; ?> / <?php echo $maxVideosAllowed; ?> vídeos
                        </span>
                        <span class="badge badge-secondary ml-1" style="font-size: 13px;">
                            Máx: <?php echo $maxVideoSizeMb; ?>MB por arquivo
                        </span>
                    </div>
                    <?php if ($currentVideoCount < $maxVideosAllowed): ?>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#uploadModal"><i class="fas fa-upload"></i> ADICIONAR ARQUIVOS</button>
                    <?php else: ?>
                    <button class="btn btn-secondary" onclick="alert('Você atingiu o limite de <?php echo $maxVideosAllowed; ?> vídeos. Apague arquivos antigos ou fale com o administrador para solicitar mais espaço.');"><i class="fas fa-ban"></i> COTA CHEIA (<?php echo $maxVideosAllowed; ?>/<?php echo $maxVideosAllowed; ?>)</button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <form method="post" id="form-multi-select">
                        <div class="mb-3">
                            <button type="submit" name="apagar_multiplos" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja apagar os arquivos selecionados? Eles serão removidos de todas as playlists.');"><i class="fas fa-trash"></i> Apagar Selecionados</button>
                        </div>
                        <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th width="40"><input type="checkbox" id="check-all" onclick="document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = this.checked);"></th>
                                    <th>Nome do Arquivo</th>
                                    <th>Data de Envio</th>
                                    <th width="120">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($arquivosOnly)): ?>
                                <tr><td colspan="4" class="text-center py-4">Nenhum arquivo.</td></tr>
                                <?php else: ?>
                                    <?php foreach($arquivosOnly as $vid): ?>
                                    <tr>
                                        <td><input type="checkbox" name="selected_files[]" value="<?php echo $vid['id']; ?>" class="file-checkbox"></td>
                                        <td><?php echo htmlspecialchars($vid['title']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($vid['upload_date'])); ?></td>
                                        <td>
                                            <a href="./uploads/clientes/<?php echo $idCliente; ?>/<?php echo $vid['filename']; ?>" target="_blank" class="btn btn-sm btn-info mb-1" title="Visualizar"><i class="fas fa-eye"></i></a>
                                            <button type="submit" formaction="cliente_dashboard.php" formmethod="post" name="apagar_video" value="1" class="btn btn-sm btn-danger mb-1" title="Apagar" onclick="document.getElementById('id_video_to_delete').value = '<?php echo $vid['id']; ?>'; return confirm('Apagar este arquivo?');"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                        <input type="hidden" name="id_video" id="id_video_to_delete" value="">
                    </form>
                </div>
            </div>

            <!-- Modal Upload -->
            <div class="modal fade" id="uploadModal" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                  <div class="modal-header bg-light">
                    <h5 class="modal-title text-dark">Adicionar Arquivos</h5>
                    <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                  </div>
                  <div class="modal-body">
                    <style>
                    @keyframes spinLiquid {
                        0% { transform: rotate(0deg); }
                        100% { transform: rotate(360deg); }
                    }
                    </style>
                    <form id="uploadForm" method="post" enctype="multipart/form-data">
                        <div class="form-group mb-4 text-left" id="uploadInputGroup">
                            <label class="font-weight-bold text-dark">Selecione os vídeos ou imagens</label>
                            <input type="file" name="video_files[]" id="videoFileInput" class="form-control form-control-lg" accept="video/*,image/*" multiple required>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <small class="text-muted">Formatos: MP4, WEBM, OGG, JPG, PNG.</small>
                                <small class="text-info font-weight-bold">
                                    Limite: Máx <?php echo $maxVideoSizeMb; ?>MB por vídeo | Cota: <?php echo $currentVideoCount; ?>/<?php echo $maxVideosAllowed; ?>
                                </small>
                            </div>
                        </div>
                        
                        <!-- Progress UI -->
                        <div id="progressContainer" style="display:none; text-align: center;">
                            <div style="width: 120px; height: 120px; border-radius: 50%; border: 3px solid #333; position: relative; overflow: hidden; margin: 20px auto; background: #111; box-shadow: 0 0 20px rgba(57, 255, 20, 0.4);">
                                <div id="liquidFill" style="position: absolute; top: 100%; left: -50%; width: 200%; height: 200%; background: #39ff14; border-radius: 40%; box-shadow: 0 0 20px #39ff14; transition: top 0.2s linear; animation: spinLiquid 4s linear infinite;"></div>
                                <div style="position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 32px; font-weight: 900; z-index: 10; text-shadow: 2px 2px 5px rgba(0,0,0,0.8);">UP</div>
                                <div id="progressText" style="position: absolute; top: 75%; left: 50%; transform: translate(-50%, -50%); color: white; font-size: 16px; font-weight: bold; z-index: 10; text-shadow: 1px 1px 3px rgba(0,0,0,0.8);">0%</div>
                            </div>
                            <p class="text-muted font-weight-bold mt-3" id="uploadStatusText">Enviando arquivos...</p>
                        </div>

                        <div class="d-flex justify-content-center gap-2" id="uploadButtons">
                            <button type="submit" name="upload_video" class="btn btn-primary btn-lg mx-2 px-4">ENVIAR ARQUIVOS</button>
                            <button type="button" class="btn btn-secondary btn-lg mx-2 px-4" data-dismiss="modal">CANCELAR</button>
                        </div>
                    </form>
                    <script>
                    var clientMaxVideos = <?php echo (int)$maxVideosAllowed; ?>;
                    var clientCurrentVideos = <?php echo (int)$currentVideoCount; ?>;
                    var clientMaxSizeBytes = <?php echo (int)$maxVideoSizeBytes; ?>;
                    var clientMaxSizeMb = <?php echo (int)$maxVideoSizeMb; ?>;
                    var videoExts = ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv'];

                    document.getElementById('uploadForm').addEventListener('submit', function(e) {
                        e.preventDefault();
                        var files = document.getElementById('videoFileInput').files;
                        if (files.length === 0) return;

                        // Conta quantos arquivos sendo enviados são VÍDEOS (imagens não contam para o limite)
                        var newVideosCount = 0;
                        for (var f = 0; f < files.length; f++) {
                            var ext = files[f].name.split('.').pop().toLowerCase();
                            if (videoExts.indexOf(ext) !== -1) {
                                newVideosCount++;
                            }
                        }

                        // Validação no frontend: Limite de quantidade apenas para VÍDEOS
                        if (newVideosCount > 0 && (clientCurrentVideos + newVideosCount > clientMaxVideos)) {
                            var restante = Math.max(0, clientMaxVideos - clientCurrentVideos);
                            alert('Limite excedido! Você já possui ' + clientCurrentVideos + ' vídeo(s) e sua cota permite apenas mais ' + restante + ' vídeo(s). (Limite: ' + clientMaxVideos + ' vídeos. Obs: Imagens não possuem esse limite).');
                            return;
                        }

                        // Validação no frontend: Limite de tamanho de cada arquivo
                        for (var f = 0; f < files.length; f++) {
                            if (files[f].size > clientMaxSizeBytes) {
                                var fileMb = (files[f].size / (1024 * 1024)).toFixed(1);
                                alert('O arquivo "' + files[f].name + '" tem ' + fileMb + 'MB e excede o limite permitido de ' + clientMaxSizeMb + 'MB!');
                                return;
                            }
                        }
                        
                        document.getElementById('uploadInputGroup').style.display = 'none';
                        document.getElementById('uploadButtons').style.display = 'none';
                        document.getElementById('progressContainer').style.display = 'block';
                        
                        var formData = new FormData(this);
                        formData.append('upload_video', '1');
                        formData.append('ajax_upload', '1');
                        
                        var xhr = new XMLHttpRequest();
                        // Usa window.location.pathname para funcionar com ou sem .php (evita 301 Redirect)
                        xhr.open('POST', window.location.pathname + '?tab=arquivos&ajax=1', true);
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        
                        xhr.upload.onprogress = function(e) {
                            if (e.lengthComputable) {
                                var percentComplete = Math.round((e.loaded / e.total) * 100);
                                var topValue = 100 - percentComplete;
                                document.getElementById('liquidFill').style.top = topValue + '%';
                                document.getElementById('progressText').innerText = percentComplete + '%';
                                if(percentComplete === 100) {
                                    document.getElementById('uploadStatusText').innerText = 'Processando arquivos no servidor...';
                                }
                            }
                        };
                        
                        xhr.onload = function() {
                            if (xhr.status >= 200 && xhr.status < 300) {
                                try {
                                    var resp = JSON.parse(xhr.responseText);
                                    if (!resp.success && resp.msg) {
                                        document.getElementById('uploadStatusText').innerText = resp.msg;
                                        document.getElementById('uploadStatusText').classList.add('text-danger');
                                        setTimeout(function() { window.location.href = '?tab=arquivos'; }, 2500);
                                        return;
                                    }
                                } catch(e) {}
                                document.getElementById('uploadStatusText').innerText = 'Concluído!';
                                window.location.href = '?tab=arquivos';
                            } else if (xhr.status === 413) {
                                document.getElementById('uploadStatusText').innerText = 'Erro 413: Vídeo muito grande para o servidor Web (Nginx/Apache).';
                                document.getElementById('uploadStatusText').classList.add('text-danger');
                            } else {
                                document.getElementById('uploadStatusText').innerText = 'Erro ao enviar (HTTP ' + xhr.status + ').';
                                document.getElementById('uploadStatusText').classList.add('text-danger');
                            }
                        };
                        
                        xhr.onerror = function() {
                            document.getElementById('uploadStatusText').innerText = 'Erro de conexão.';
                            document.getElementById('uploadStatusText').classList.add('text-danger');
                        };
                        
                        xhr.send(formData);
                    });
                    </script>
                  </div>
                </div>
              </div>
            </div>
            <?php endif; ?>

            <!-- ABA LISTAS DE REPRODUÇÃO -->
            <?php if($tab == 'lista'): ?>
                
                <?php if($editListId > 0 && $playlistEdit): ?>
                <!-- TELA DE EDIÇÃO (DRAG AND DROP) -->
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <h4 class="m-0"><a href="?tab=lista" class="text-secondary"><i class="fas fa-arrow-left"></i> Voltar</a> | Editando: <?php echo htmlspecialchars($playlistEdit['title']); ?></h4>
                    <button class="btn btn-success" id="btnSaveOrder"><i class="fas fa-check"></i> Salvar Ordem</button>
                </div>

                <div class="dnd-container">
                    <!-- PAINEL ESQUERDO: Arquivos Disponíveis -->
                    <div class="dnd-panel">
                        <!-- ABAS -->
                        <ul class="nav nav-tabs mb-2" id="myTab" role="tablist">
                          <li class="nav-item" role="presentation">
                            <button class="nav-link active py-1 px-2" id="arquivos-tab" data-toggle="tab" data-target="#arquivos" type="button" role="tab" aria-controls="arquivos" aria-selected="true" style="font-size:14px;"><i class="fas fa-file-video text-primary"></i> Arquivos</button>
                          </li>
                          <li class="nav-item" role="presentation">
                            <button class="nav-link py-1 px-2" id="entretenimentos-tab" data-toggle="tab" data-target="#entretenimentos" type="button" role="tab" aria-controls="entretenimentos" aria-selected="false" style="font-size:14px;"><i class="fas fa-gamepad text-warning"></i> Entretenimentos</button>
                          </li>
                        </ul>
                        
                        <div class="tab-content" id="myTabContent">
                            <!-- ABA ARQUIVOS -->
                            <div class="tab-pane fade show active" id="arquivos" role="tabpanel" aria-labelledby="arquivos-tab">
                                <div class="dnd-header">
                                    <input type="text" id="searchVideos" class="form-control form-control-sm" placeholder="Procurar arquivo...">
                                </div>
                                <div class="video-list" id="sourceList">
                                    <?php foreach($arquivosOnly as $vid): ?>
                                    <div class="video-item" data-id="<?php echo $vid['id']; ?>">
                                        <i class="fas fa-grip-vertical drag-handle"></i>
                                        <div class="vid-title"><?php echo htmlspecialchars($vid['title']); ?></div>
                                        <i class="fas fa-plus-circle text-success ml-2" style="cursor:pointer;" onclick="moveToTarget(this)"></i>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- ABA ENTRETENIMENTOS -->
                            <div class="tab-pane fade" id="entretenimentos" role="tabpanel" aria-labelledby="entretenimentos-tab">
                                <div class="dnd-header">
                                    <input type="text" id="searchEnt" class="form-control form-control-sm" placeholder="Procurar entretenimento...">
                                </div>
                                <div class="video-list" id="sourceEnt">
                                    <?php foreach($entOnly as $vid): ?>
                                    <div class="video-item" data-id="<?php echo $vid['id']; ?>">
                                        <i class="fas fa-grip-vertical drag-handle"></i>
                                        <div class="vid-title"><?php echo htmlspecialchars($vid['title']); ?></div>
                                        <i class="fas fa-plus-circle text-success ml-2" style="cursor:pointer;" onclick="moveToTarget(this)"></i>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PAINEL DIREITO: Lista Final -->
                    <div class="dnd-panel" style="border: 2px solid #28a745;">
                        <div class="dnd-header bg-success text-white" style="margin:-15px -15px 15px -15px; padding: 15px; border-radius: 8px 8px 0 0;">
                            <span><i class="fas fa-list"></i> Lista Final que será exibida nas Telas</span>
                        </div>
                        <div class="video-list" id="targetList">
                            <?php if(empty($playlistVideosData)): ?>
                                <div class="empty-placeholder" id="emptyMsg">
                                    <i class="fas fa-download" style="font-size:30px; margin-bottom:10px;"></i><br>
                                    ARRASTE ITEMS PARA CÁ
                                </div>
                            <?php else: ?>
                                <?php foreach($playlistVideosData as $vid): ?>
                                <div class="video-item" data-id="<?php echo $vid['id']; ?>" data-duration="<?php echo $vid['duration'] ?? 15; ?>">
                                    <i class="fas fa-grip-vertical drag-handle"></i>
                                    <div class="vid-title"><?php echo htmlspecialchars($vid['title']); ?></div>
                                    <span class="badge badge-secondary ml-auto mr-2 duration-badge" style="cursor:pointer;" onclick="changeDuration(this)" title="Mudar tempo de exibição (se for imagem)"><i class="fas fa-clock"></i> <span class="d-val"><?php echo $vid['duration'] ?? 15; ?></span>s</span>
                                    <i class="fas fa-trash text-danger" style="cursor:pointer;" onclick="removeTarget(this)"></i>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <script>
                    function changeDuration(el) {
                        let item = el.closest('.video-item');
                        let current = item.getAttribute('data-duration') || '15';
                        let val = prompt("Tempo de exibição (em segundos):", current);
                        if(val !== null && !isNaN(val) && parseInt(val) > 0) {
                            item.setAttribute('data-duration', parseInt(val));
                            el.querySelector('.d-val').innerText = parseInt(val);
                        }
                    }
                </script>

                    <script>
                        document.getElementById('searchVideos').addEventListener('keyup', function() {
                            let filter = this.value.toLowerCase();
                            let items = document.querySelectorAll('#sourceList .video-item');
                            items.forEach(item => {
                                let text = item.querySelector('.vid-title').innerText.toLowerCase();
                                item.style.display = text.includes(filter) ? '' : 'none';
                            });
                        });
                        
                        document.getElementById('searchEnt').addEventListener('keyup', function() {
                            let filter = this.value.toLowerCase();
                            let items = document.querySelectorAll('#sourceEnt .video-item');
                            items.forEach(item => {
                                let text = item.querySelector('.vid-title').innerText.toLowerCase();
                                item.style.display = text.includes(filter) ? '' : 'none';
                            });
                        });

                        // Init Sortable for Left Panel (Clone mode)
                        new Sortable(document.getElementById('sourceList'), {
                            group: { name: 'shared', pull: 'clone', put: false },
                            sort: false,
                            animation: 150,
                            handle: '.drag-handle'
                        });
                        
                        new Sortable(document.getElementById('sourceEnt'), {
                            group: { name: 'shared', pull: 'clone', put: false },
                            sort: false,
                            animation: 150,
                            handle: '.drag-handle'
                        });

                    new Sortable(document.getElementById('targetList'), {
                        group: 'shared',
                        animation: 150,
                        handle: '.drag-handle',
                        ghostClass: 'ghost-class',
                        onAdd: function (evt) {
                            let item = evt.item;
                            item.setAttribute('data-duration', '15');
                            // Change plus icon to trash icon
                            let icon = item.querySelector('.fa-plus-circle');
                            if(icon) {
                                let clockBadge = document.createElement('span');
                                clockBadge.className = 'badge badge-secondary ml-auto mr-2 duration-badge';
                                clockBadge.style.cursor = 'pointer';
                                clockBadge.title = 'Mudar tempo de exibição (se for imagem)';
                                clockBadge.innerHTML = '<i class="fas fa-clock"></i> <span class="d-val">15</span>s';
                                clockBadge.onclick = function() { changeDuration(clockBadge); };
                                
                                item.insertBefore(clockBadge, icon);

                                icon.className = 'fas fa-trash text-danger';
                                icon.setAttribute('onclick', 'removeTarget(this)');
                            }
                            document.getElementById('emptyMsg')?.remove();
                        }
                    });

                    function moveToTarget(iconEl) {
                        let item = iconEl.closest('.video-item').cloneNode(true);
                        item.setAttribute('data-duration', '15');
                        let icon = item.querySelector('.fa-plus-circle');
                        
                        let clockBadge = document.createElement('span');
                        clockBadge.className = 'badge badge-secondary ml-auto mr-2 duration-badge';
                        clockBadge.style.cursor = 'pointer';
                        clockBadge.title = 'Mudar tempo de exibição (se for imagem)';
                        clockBadge.innerHTML = '<i class="fas fa-clock"></i> <span class="d-val">15</span>s';
                        clockBadge.onclick = function() { changeDuration(clockBadge); };
                        
                        item.insertBefore(clockBadge, icon);

                        icon.className = 'fas fa-trash text-danger';
                        icon.setAttribute('onclick', 'removeTarget(this)');
                        document.getElementById('targetList').appendChild(item);
                        document.getElementById('emptyMsg')?.remove();
                    }

                    function removeTarget(iconEl) {
                        iconEl.closest('.video-item').remove();
                        if(document.getElementById('targetList').children.length === 0) {
                            document.getElementById('targetList').innerHTML = `<div class="empty-placeholder" id="emptyMsg"><i class="fas fa-download" style="font-size:30px; margin-bottom:10px;"></i><br>ARRASTE ITEMS PARA CÁ</div>`;
                        }
                    }

                    document.getElementById('btnSaveOrder').addEventListener('click', function() {
                        let items = document.querySelectorAll('#targetList .video-item');
                        let dataToSave = [];
                        items.forEach(item => {
                            dataToSave.push({
                                id: item.getAttribute('data-id'),
                                duration: item.getAttribute('data-duration') || 15
                            });
                        });
                        
                        let oldText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                        let btn = this;
                        
                        $.post('api_playlist.php', {
                            action: 'save_order',
                            id_playlist: <?php echo $editListId; ?>,
                            video_data: JSON.stringify(dataToSave)
                        }, function(res) {
                            btn.innerHTML = oldText;
                            if(res.success) {
                                // Exibir feedback temporário
                                btn.classList.replace('btn-success', 'btn-info');
                                btn.innerHTML = '<i class="fas fa-check-double"></i> Salvo!';
                                setTimeout(() => {
                                    btn.classList.replace('btn-info', 'btn-success');
                                    btn.innerHTML = oldText;
                                }, 2000);
                            } else {
                                alert('Erro ao salvar: ' + res.msg);
                            }
                        }, 'json');
                    });
                </script>

                <?php else: ?>
                <!-- TELA PRINCIPAL DE LISTAS -->
                <div class="card">
                    <div class="card-header">
                        <div><i class="fas fa-list text-primary mr-2"></i> Listas de Reprodução</div>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#novaListaModal"><i class="fas fa-plus"></i> CRIAR LISTA</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Qtd. Vídeos</th>
                                    <th>Status no Player</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($playlistsList)): ?>
                                <tr><td colspan="4" class="text-center py-4">Nenhuma lista criada. Clique em Criar Lista!</td></tr>
                                <?php else: ?>
                                    <?php foreach($playlistsList as $pl): ?>
                                    <tr>
                                        <td class="font-weight-bold"><?php echo htmlspecialchars($pl['title']); ?></td>
                                        <td><?php echo $pl['qtd_videos']; ?> arquivos</td>
                                        <td>
                                            <?php if($pl['is_active'] == 1): ?>
                                                <span class="badge badge-success px-3 py-2"><i class="fas fa-play"></i> ATIVA NA TV</span>
                                            <?php else: ?>
                                                <form method="post" style="display:inline;">
                                                    <input type="hidden" name="id_playlist" value="<?php echo $pl['id']; ?>">
                                                    <button type="submit" name="set_active" class="btn btn-sm btn-outline-success">Definir como Ativa</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?tab=lista&edit_list=<?php echo $pl['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-edit"></i> Editar Ordem</a>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Apagar lista?');">
                                                <input type="hidden" name="id_playlist" value="<?php echo $pl['id']; ?>">
                                                <button type="submit" name="apagar_lista" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Nova Lista -->
                <div class="modal fade" id="novaListaModal" tabindex="-1">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow">
                      <div class="modal-header bg-light">
                        <h5 class="modal-title">Digite o nome da Lista de Reprodução</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                      </div>
                      <div class="modal-body">
                        <form method="post">
                            <input type="text" name="nome_lista" class="form-control form-control-lg mb-4 text-center" placeholder="Ex: Playlist Natal" required>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="submit" name="criar_lista" class="btn btn-primary btn-lg mx-2 px-4">CRIAR LISTA</button>
                                <button type="button" class="btn btn-light btn-lg mx-2 px-4" data-dismiss="modal">FECHAR</button>
                            </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>

            <!-- ABA CONFIGURAÇÕES (Perfil e Financeiro) -->
            <?php if($tab == 'config'): ?>
                <?php include 'cliente_config_tab.php'; ?>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<!-- Background Compression -->
<script>
    function checkCompression() {
        fetch('api_compress.php')
        .then(r => r.json())
        .then(data => {
            if(data && data.compressed) {
                // Compressou uma, aguarda 2 segundos e tenta a próxima para não sobrecarregar
                setTimeout(checkCompression, 2000);
            }
        }).catch(e => {
            // Erro silencioso
        });
    }
    // Inicia a verificação de imagens pesadas 5 segundos após abrir o painel
    setTimeout(checkCompression, 5000);
</script>
<script>
function openAudioModal(idTela, mode, idRadio) {
    $('#audio_id_tela').val(idTela);
    $('#audio_mode').val(mode);
    $('#id_radio').val(idRadio);
    toggleRadioSelect();
    $('#audioModal').modal('show');
}

function toggleRadioSelect() {
    if ($('#audio_mode').val() === 'radio') {
        $('#div_radio_select').show();
    } else {
        $('#div_radio_select').hide();
    }
}

function saveAudioConfig() {
    var idTela = $('#audio_id_tela').val();
    var mode = $('#audio_mode').val();
    var idRadio = $('#id_radio').val();
    
    $.post('api_telas.php', { action: 'set_audio', id_tela: idTela, audio_mode: mode, id_radio: idRadio }, function(res) {
        if(res && res.success) {
            location.reload();
        } else {
            alert('Erro ao salvar configuração.');
        }
    }, 'json').fail(function() {
        alert('Erro de comunicação.');
    });
}
</script>
<script>
    // Sidebar toggle for mobile
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const sidebar = document.querySelector('.sidebar');
    
    if (mobileMenuBtn && sidebarOverlay && sidebar) {
        mobileMenuBtn.addEventListener('click', function() {
            sidebar.classList.add('show');
            sidebarOverlay.classList.add('show');
        });
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            this.classList.remove('show');
        });
    }

function toggleRotation(idTela, newRotation) {
    $.post('api_telas.php', {
        action: 'toggle_rotation',
        id_tela: idTela,
        rotation: newRotation
    }, function(res) {
        if(res.success) {
            window.location.reload();
        } else {
            alert('Erro ao alterar rotação.');
        }
    });
}

// Registro e Instalação PWA (Cliente Dashboard)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Player99 PWA Cliente Ativo'))
            .catch(err => console.log('SW Erro:', err));
    });
}

let deferredClientPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredClientPrompt = e;
    const btnPwa = document.getElementById('btnInstallDashboardPWA');
    if (btnPwa) btnPwa.style.display = 'flex';
});

function installClientPWA() {
    if (deferredClientPrompt) {
        deferredClientPrompt.prompt();
        deferredClientPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                const btnPwa = document.getElementById('btnInstallDashboardPWA');
                if (btnPwa) btnPwa.style.display = 'none';
            }
            deferredClientPrompt = null;
        });
    }
}
</script>
</body>
</html>
