<!DOCTYPE html>
<?php
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Banners</title>
    <style>
        /* Estilos para a tabela de banners */
        body, html {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }

        .banner-carousel {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .banner-slide {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            object-fit: contain; 
        }

        .banner-slide.active {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="banner-carousel">
        <?php
        // Caminho para o banco de dados SQLite na mesma pasta
        $db_path = __DIR__ . '/.db_logo.db';

        try {
            // Conexão ao banco de dados
            $db = new PDO("sqlite:$db_path");

            // Consulta à tabela ads para obter os caminhos das imagens ordenadas por ID
            $query = "SELECT caminho FROM ads ORDER BY ID";
            $result = $db->query($query);

            if ($result) {
                // Exibe os banners na tabela
                $index = 0;
                foreach ($result as $row) {
                    // Remover o prefixo 'api/' do caminho
                    $file = htmlspecialchars(str_replace('api/', '', $row['caminho']));
                    $class = $index === 0 ? 'banner-slide active' : 'banner-slide';
                    echo "<img class='$class' src='$file' alt='Banner $index'>";
                    $index++;
                }
            } else {
                echo "<p>Erro ao consultar a tabela de anúncios.</p>";
            }

            // Fecha a conexão com o banco de dados
            $db = null;
        } catch (PDOException $e) {
            echo "<p>Erro ao conectar ao banco de dados: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const slides = document.querySelectorAll('.banner-slide');
            let currentSlide = 0;

            function showSlide(index) {
                slides.forEach(slide => slide.classList.remove('active'));
                slides[index].classList.add('active');
            }

            function nextSlide() {
                const previousSlide = currentSlide;
                currentSlide = (currentSlide + 1) % slides.length;
                slides[previousSlide].style.opacity = 0;
                slides[currentSlide].style.opacity = 1;
            }

            setInterval(nextSlide, 5000); // Troca de slide a cada 5 segundos
        });
    </script>
</body>
</html>
