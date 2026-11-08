<?php
session_start();
header('Content-Type: application/json');

// Segurança: Apenas clientes logados podem comprimir seus próprios arquivos
if (!isset($_SESSION["cliente_logado"]) || $_SESSION["cliente_logado"] !== true) {
    echo json_encode(["success" => false, "msg" => "Não autenticado"]);
    exit;
}

$idCliente = $_SESSION["cliente_id"];
session_write_close(); // LIBERA A SESSÃO! Impede que o painel do cliente trave enquanto comprime.

$uploadDir = __DIR__ . "/uploads/clientes/" . $idCliente . "/";

if (!is_dir($uploadDir)) {
    echo json_encode(["compressed" => false, "msg" => "Diretório não existe"]);
    exit;
}

// Pega todos os arquivos de imagem
$files = glob($uploadDir . "*.{jpg,jpeg,png,JPG,JPEG,PNG}", GLOB_BRACE);
if (!$files) {
    echo json_encode(["compressed" => false, "msg" => "Nenhuma imagem encontrada"]);
    exit;
}

// Limite de tamanho para considerar que a imagem precisa de compressão (ex: 800 KB)
$tamanhoMaximo = 800 * 1024; 

require_once __DIR__ . '/includes/db_cliente.php';

foreach ($files as $file) {
    // Ignorar arquivos que já foram otimizados (marcados no nome)
    if (strpos($file, '_opt.') !== false) continue;

    if (filesize($file) > $tamanhoMaximo) {
        // Encontrou um arquivo grande. Vamos comprimir.
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $info = @getimagesize($file);
        
        if ($info) {
            $isCompressed = false;
            $newFilename = str_replace("." . pathinfo($file, PATHINFO_EXTENSION), "_opt." . pathinfo($file, PATHINFO_EXTENSION), basename($file));
            $newFile = $uploadDir . $newFilename;
            
            // Tenta aumentar o limite de memória apenas para este script caso a imagem seja gigante
            ini_set('memory_limit', '256M'); 

            if ($info['mime'] == 'image/jpeg') {
                $img = @imagecreatefromjpeg($file);
                if ($img) {
                    $width = imagesx($img);
                    $height = imagesy($img);
                    
                    if ($width > 1920 || $height > 1920) {
                        $ratio = min(1920 / $width, 1920 / $height);
                        $newWidth = round($width * $ratio);
                        $newHeight = round($height * $ratio);
                        
                        $newImg = imagecreatetruecolor($newWidth, $newHeight);
                        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        
                        $isCompressed = imagejpeg($newImg, $newFile, 75);
                        imagedestroy($newImg);
                    } else {
                        $isCompressed = imagejpeg($img, $newFile, 75); 
                    }
                    imagedestroy($img);
                }
            } elseif ($info['mime'] == 'image/png') {
                $img = @imagecreatefrompng($file);
                if ($img) {
                    $width = imagesx($img);
                    $height = imagesy($img);
                    
                    if ($width > 1920 || $height > 1920) {
                        $ratio = min(1920 / $width, 1920 / $height);
                        $newWidth = round($width * $ratio);
                        $newHeight = round($height * $ratio);
                        
                        $newImg = imagecreatetruecolor($newWidth, $newHeight);
                        imagealphablending($newImg, false);
                        imagesavealpha($newImg, true);
                        
                        imagecopyresampled($newImg, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                        
                        $isCompressed = imagepng($newImg, $newFile, 8);
                        imagedestroy($newImg);
                    } else {
                        imagealphablending($img, false);
                        imagesavealpha($img, true);
                        $isCompressed = imagepng($img, $newFile, 8);
                    }
                    imagedestroy($img);
                }
            }
            
            if ($isCompressed) {
                // Atualiza o banco de dados com o novo nome
                $oldFilename = basename($file);
                $stmt = $dbCliente->prepare("UPDATE videos SET filename = :newF WHERE filename = :oldF AND id_cliente = :idc");
                $stmt->bindValue(':newF', $newFilename, SQLITE3_TEXT);
                $stmt->bindValue(':oldF', $oldFilename, SQLITE3_TEXT);
                $stmt->bindValue(':idc', $idCliente, SQLITE3_INTEGER);
                $stmt->execute();
                
                // Apaga o arquivo original pesado
                @unlink($file);

                // Retorna sucesso e aborta o script para só processar 1 imagem por vez
                echo json_encode(["compressed" => true, "file" => $newFilename]);
                exit; 
            }
        }
    }
}

// Se passou por todo o loop e não comprimiu nada, tudo já está leve
echo json_encode(["compressed" => false, "msg" => "Todas as imagens estão otimizadas"]);
?>
