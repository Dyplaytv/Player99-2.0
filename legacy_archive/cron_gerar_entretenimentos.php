<?php
/**
 * Cronjob Diário: Gerador de Imagens para Entretenimento (Offline/TV Box Fix)
 */

$fontFile = __DIR__ . '/assets/fonts/Roboto-Bold.ttf';
$saveDir = __DIR__ . '/uploads/widgets_images/';

if (!file_exists($saveDir)) {
    mkdir($saveDir, 0777, true);
}

// === FUNÇÕES AUXILIARES GD ===
function createGradientBackground($width, $height, $hex1, $hex2) {
    $img = imagecreatetruecolor($width, $height);
    // Cores (simples interpolação vertical)
    list($r1, $g1, $b1) = sscanf($hex1, "#%02x%02x%02x");
    list($r2, $g2, $b2) = sscanf($hex2, "#%02x%02x%02x");
    for ($y = 0; $y < $height; $y++) {
        $r = $r1 + ($r2 - $r1) * ($y / $height);
        $g = $g1 + ($g2 - $g1) * ($y / $height);
        $b = $b1 + ($b2 - $b1) * ($y / $height);
        $color = imagecolorallocate($img, $r, $g, $b);
        imageline($img, 0, $y, $width, $y, $color);
    }
    return $img;
}

function imagettftext_center($image, $size, $y, $color, $fontfile, $text) {
    $bbox = imagettfbbox($size, 0, $fontfile, $text);
    $text_width = $bbox[2] - $bbox[0];
    $x = (imagesx($image) - $text_width) / 2;
    imagettftext($image, $size, 0, $x, $y, $color, $fontfile, $text);
}

function wrapText($fontSize, $fontFile, $text, $maxWidth) {
    $words = explode(" ", $text);
    $lines = [];
    $currentLine = "";
    foreach ($words as $word) {
        $testLine = $currentLine . ($currentLine ? " " : "") . $word;
        $bbox = imagettfbbox($fontSize, 0, $fontFile, $testLine);
        $width = $bbox[2] - $bbox[0];
        if ($width > $maxWidth) {
            if (!empty($currentLine)) {
                $lines[] = $currentLine;
                $currentLine = $word;
            } else {
                $lines[] = $word;
            }
        } else {
            $currentLine = $testLine;
        }
    }
    if ($currentLine) {
        $lines[] = $currentLine;
    }
    return $lines;
}

function createTextSlide($filename, $title, $content, $hex1, $hex2, $bg_image_path = null) {
    global $fontFile, $saveDir;
    $width = 1920;
    $height = 1080;
    
    $img = null;
    if ($bg_image_path && file_exists($bg_image_path)) {
        $ext = strtolower(pathinfo($bg_image_path, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg'])) {
            $img = @imagecreatefromjpeg($bg_image_path);
        } elseif ($ext == 'png') {
            $img = @imagecreatefrompng($bg_image_path);
        }
        
        if ($img) {
            // Resize to 1920x1080 if needed
            $origW = imagesx($img);
            $origH = imagesy($img);
            if ($origW != $width || $origH != $height) {
                $newImg = imagecreatetruecolor($width, $height);
                imagecopyresampled($newImg, $img, 0, 0, 0, 0, $width, $height, $origW, $origH);
                imagedestroy($img);
                $img = $newImg;
            }
            
            // Add a dark overlay to make text readable
            $overlay = imagecreatetruecolor($width, $height);
            $black = imagecolorallocate($overlay, 0, 0, 0);
            imagefill($overlay, 0, 0, $black);
            imagecopymerge($img, $overlay, 0, 0, 0, 0, $width, $height, 60); // 60% opacity black overlay
            imagedestroy($overlay);
        }
    }
    
    if (!$img) {
        $img = createGradientBackground($width, $height, $hex1, $hex2);
    }
    
    $textColor = imagecolorallocate($img, 255, 255, 255);
    $titleColor = imagecolorallocate($img, 0, 255, 204);
    
    // Título
    imagettftext_center($img, 60, 200, $titleColor, $fontFile, mb_strtoupper($title, 'UTF-8'));
    
    // Conteúdo (Wrap Text)
    $lines = wrapText(70, $fontFile, $content, $width - 400);
    $lineHeight = 100;
    $startY = ($height - (count($lines) * $lineHeight)) / 2 + 50;
    
    foreach ($lines as $line) {
        imagettftext_center($img, 70, $startY, $textColor, $fontFile, $line);
        $startY += $lineHeight;
    }
    
    // Assinatura
    imagettftext_center($img, 30, $height - 100, $textColor, $fontFile, date('d/m/Y') . " - Player 99");
    
    imagejpeg($img, $saveDir . $filename, 90);
    imagedestroy($img);
}

// === GERAR FRASES ===
$frasesDB = [
    "O sucesso é a soma de pequenos esforços repetidos dia após dia.",
    "Acredite em si próprio e chegará um dia em que os outros não terão outra escolha senão acreditar com você.",
    "O único lugar onde o sucesso vem antes do trabalho é no dicionário.",
    "A persistência é o caminho do êxito.",
    "Tudo o que um sonho precisa para ser realizado é alguém que acredite que ele possa ser realizado.",
    "O insucesso é apenas uma oportunidade para recomeçar com mais inteligência.",
    "O seu tempo é limitado, então não o gaste vivendo a vida de outra pessoa.",
    "A melhor forma de prever o futuro é criá-lo.",
    "Quem quer vencer um obstáculo deve armar-se da força do leão e da prudência da serpente.",
    "A coragem não é ausência do medo; é a persistência apesar do medo."
];
shuffle($frasesDB);
for ($i=1; $i<=5; $i++) {
    createTextSlide("frase_$i.jpg", "Pensamento do Dia", $frasesDB[$i], "#1f1c2c", "#928dab");
}

// === GERAR NOTÍCIAS (G1 RSS) ===
$noticias = [];
try {
    $rss = @simplexml_load_file('https://g1.globo.com/rss/g1/');
    if ($rss) {
        $count = 1;
        foreach ($rss->channel->item as $item) {
            if ($count > 5) break;
            $noticias[] = strip_tags((string)$item->title);
            $count++;
        }
    }
} catch (Exception $e) {}

if (empty($noticias)) {
    // Fallback
    $noticias = [
        "Economia em alta neste semestre.",
        "Novos avanços na tecnologia global.",
        "Clima apresenta mudanças nesta semana.",
        "Dólar tem leve queda no fechamento.",
        "Esporte: Grandes expectativas para o campeonato."
    ];
}

for ($i=1; $i<=5; $i++) {
    $noticia = isset($noticias[$i-1]) ? $noticias[$i-1] : $noticias[0];
    createTextSlide("noticia_$i.jpg", "Últimas Notícias", $noticia, "#0f2027", "#203a43");
}

// === GERAR CARROS (G1 RSS) ===
$carros = [];
try {
    $rss_carros = @simplexml_load_file('https://g1.globo.com/dynamo/carros/rss2.xml');
    if ($rss_carros) {
        $count = 1;
        foreach ($rss_carros->channel->item as $item) {
            if ($count > 5) break;
            $carros[] = strip_tags((string)$item->title);
            $count++;
        }
    }
} catch (Exception $e) {}

if (empty($carros)) {
    // Fallback
    $carros = [
        "Novos lançamentos automotivos chegam ao mercado.",
        "Dicas de manutenção para aumentar a vida útil do seu carro.",
        "Carros elétricos ganham espaço nas concessionárias.",
        "Como economizar combustível no dia a dia.",
        "Os modelos de carros mais vendidos do ano."
    ];
}

for ($i=1; $i<=5; $i++) {
    $carro = isset($carros[$i-1]) ? $carros[$i-1] : $carros[0];
    createTextSlide("carro_$i.jpg", "Notícias de Carros", $carro, "#4a0000", "#000000");
}

// === GERAR CATEGORIAS DINÂMICAS (RSS) ===
try {
    $dbRadios = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_radios.db");
    
    // Assegura que a tabela entretenimentos possui as colunas dinâmicas (Migração via Cron)
    $resEntCols = $dbRadios->query("PRAGMA table_info(entretenimentos)");
    $colsEnt = [];
    while ($rowE = $resEntCols->fetchArray(SQLITE3_ASSOC)) {
        $colsEnt[] = $rowE['name'];
    }
    if (!in_array('rss_url', $colsEnt)) {
        $dbRadios->exec("ALTER TABLE entretenimentos ADD COLUMN rss_url TEXT");
    }
    if (!in_array('bg_image', $colsEnt)) {
        $dbRadios->exec("ALTER TABLE entretenimentos ADD COLUMN bg_image TEXT");
    }

    $resDyn = $dbRadios->query("SELECT * FROM entretenimentos WHERE rss_url IS NOT NULL AND rss_url != ''");
    while ($dyn = $resDyn->fetchArray(SQLITE3_ASSOC)) {
        $dynId = $dyn['id'];
        $dynNome = $dyn['nome'];
        $dynRss = $dyn['rss_url'];
        $dynBg = $dyn['bg_image'];
        
        $bg_path = null;
        if (!empty($dynBg)) {
            $bg_path = __DIR__ . '/uploads/bg_entretenimentos/' . $dynBg;
        }

        $itemsText = [];
        try {
            $rss_data = @simplexml_load_file($dynRss);
            if ($rss_data) {
                $count = 1;
                foreach ($rss_data->channel->item as $item) {
                    if ($count > 5) break;
                    $itemsText[] = strip_tags((string)$item->title);
                    $count++;
                }
            }
        } catch(Exception $e) {}

        if (empty($itemsText)) {
            $itemsText = ["Sem novas atualizações no momento para " . $dynNome . "."];
        }

        for ($i=1; $i<=5; $i++) {
            $textContent = isset($itemsText[$i-1]) ? $itemsText[$i-1] : $itemsText[0];
            createTextSlide("rss_{$dynId}_{$i}.jpg", $dynNome, $textContent, "#000000", "#333333", $bg_path);
        }
    }
} catch(Exception $e) {}

echo "Imagens geradas com sucesso em " . date('Y-m-d H:i:s') . "\n";
?>
