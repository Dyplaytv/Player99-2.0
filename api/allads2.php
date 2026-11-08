<?php
$db_path = __DIR__ . '/.adb.db';
try {
    $db = new PDO("sqlite:$db_path");
    // Offset 1 significa pegar o 2º banner da lista (Anúncio 2)
    $query = "SELECT * FROM ads2 ORDER BY id ASC LIMIT 1 OFFSET 1";
    $result = $db->query($query);
    $ad = $result ? $result->fetch(PDO::FETCH_ASSOC) : false;
} catch (Exception $e) {
    $ad = false;
}

$img_src = "";
$link = "";
$title = "Anúncio 2";

if ($ad) {
    // Verifica se a imagem foi feita upload (image_path) ou se é url externa
    if (!empty($ad['image_path'])) {
        if (strpos($ad['image_path'], 'http') === 0) {
            $img_src = $ad['image_path'];
        } else {
            $img_src = '../' . $ad['image_path'];
        }
    } else {
        $img_src = $ad['url'];
    }
    // O link de redirecionamento fica no url (caso tenha sido preenchido e seja válido)
    $link = (!empty($ad['url']) && strpos($ad['url'], 'http') === 0) ? $ad['url'] : '#';
    $title = $ad['title'];
}
?>
<!DOCTYPE html>
<html>
<head>
	<title><?=$title?></title>
	<style>
		body {
			margin: 0;
			padding: 0;
            background: transparent;
            overflow: hidden;
		}
		img, video {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			object-fit: cover;
		}
	</style>
</head>
<body>
    <?php if ($img_src) { ?>
    <div class="box">
        <a href="<?=$link?>" target="_blank">
            <img src="<?=$img_src?>" alt="<?=$title?>">
        </a>
    </div>
    <?php } else { ?>
        <p style="color:white; text-align:center; padding: 10px;">Nenhum Anúncio 2 cadastrado.</p>
    <?php } ?>
</body>
</html>