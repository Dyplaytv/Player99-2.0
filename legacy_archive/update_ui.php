<?php
$nav_files = [
    "afiliados.php",
    "cadastro.php",
    "index.php",
    "login_cliente.php",
    "loja.php",
    "parceiros.php"
];

$nav_button = '            <a href="cadastro.php" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #4721fb); box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4); border: 1px solid rgba(255,255,255,0.2);">Cadastre-se Aqui</a>';

foreach ($nav_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, '<div class="nav-links">') !== false && strpos($content, 'Cadastre-se Aqui') === false) {
            $content = str_replace('<div class="nav-links">', '<div class="nav-links">' . "\n" . $nav_button, $content);
            file_put_contents($file, $content);
            echo "Updated nav in $file\n";
        }
    }
}

$landing_files = ["afiliados.php", "index.php", "parceiros.php"];
$old_css = '        .landing-img {
            width: 100%;
            height: auto;
            display: block; /* remove espaçamento indesejado abaixo da imagem */
        }
        a {
            display: block;
            text-decoration: none;
        }';

$new_css = '        .landing-img {
            width: 100%;
            max-width: 900px;
            height: auto;
            display: block;
            margin: 0 auto;
        }
        a:first-of-type .landing-img {
            margin-top: 100px;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }
        a:last-of-type .landing-img {
            margin-bottom: 50px;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
        }
        a {
            display: block;
            text-decoration: none;
        }';

foreach ($landing_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $old_css) !== false) {
            $content = str_replace($old_css, $new_css, $content);
            file_put_contents($file, $content);
            echo "Updated CSS in $file\n";
        }
    }
}
?>
