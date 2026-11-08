<?php
// Busca feed RSS simplificado do G1 ou similar. 
$rss_url = "https://g1.globo.com/rss/g1/brasil/";
$rss = @simplexml_load_file($rss_url);
$news = [];
if ($rss && $rss->channel->item) {
    $count = 0;
    foreach ($rss->channel->item as $item) {
        $news[] = (string)$item->title;
        $count++;
        if ($count >= 5) break;
    }
} else {
    $news[] = "Fique bem informado com as principais notícias do dia.";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Notícias</title>
    <style>
        body { margin: 0; padding: 0; background-color: #0b1a2a; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden; }
        .header { background-color: #c00; padding: 20px; font-size: 4vw; font-weight: bold; text-align: center; text-transform: uppercase; letter-spacing: 2px; }
        .news-container { padding: 40px; display: flex; flex-direction: column; justify-content: center; height: calc(100vh - 100px); }
        .news-item { font-size: 3.5vw; margin-bottom: 30px; border-left: 5px solid #c00; padding-left: 20px; line-height: 1.3; }
    </style>
</head>
<body>
    <div class="header">Últimas Notícias</div>
    <div class="news-container">
        <?php foreach ($news as $n): ?>
            <div class="news-item"><?= htmlspecialchars($n) ?></div>
        <?php endforeach; ?>
    </div>
    <script>
        // Recarregar a página a cada 30 minutos para atualizar as notícias se ficar em loop na TV
        setTimeout(() => location.reload(), 1800000);
    </script>
</body>
</html>
