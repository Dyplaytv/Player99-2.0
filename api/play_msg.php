<?php
// Função para converter HEX para RGB
function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    return "$r, $g, $b";
}

if (file_exists('../play_mensagem.txt')) {
    $data = json_decode(file_get_contents('../play_mensagem.txt'), true);
    $mensagem = htmlspecialchars($data['mensagem']);
    $cor = htmlspecialchars($data['cor']);
    $bgcolor = htmlspecialchars($data['bgcolor']);
    $opacity = htmlspecialchars($data['opacity']);
    $fontSize = isset($data['fontSize']) ? htmlspecialchars($data['fontSize']) : "16";
    $efeito = isset($data['efeito']) ? htmlspecialchars($data['efeito']) : "scroll";
} else {
    $mensagem = "Nenhuma mensagem gravada.";
    $cor = "#000000";
    $bgcolor = "#ffffff";
    $opacity = "1";
    $fontSize = "16";
    $efeito = "scroll";
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body {  
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .mensagem-container {
            width: 100%;
            overflow: hidden;
            white-space: nowrap;
        }
        .mensagem {
            color: <?php echo $cor; ?>;
            background-color: rgba(<?php echo hexToRgb($bgcolor); ?>, <?php echo $opacity; ?>);
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: <?php echo $fontSize; ?>px;
            font-weight: bold;
            border-radius: 3px;
            display: inline-block;
        }
        @keyframes typing {
            from { width: 0; }
            to { width: 100%; }
        }
        @keyframes blink-caret {
            from, to { border-color: transparent; }
            50% { border-color: black; }
        }
        @keyframes scroll {
            from { transform: translateX(100%); }
            to { transform: translateX(-100%); }
        }
        .mensagem-typing {
            overflow: hidden; 
            white-space: nowrap; 
            border-right: .15em solid black; 
            animation: typing 4s steps(40, end) infinite, blink-caret .75s step-end infinite;
        }
        .mensagem-scroll {
            animation: scroll 10s linear infinite;
        }
        .mensagem-blink {
            animation: blink 1s step-start infinite;
        }
        .mensagem-flash {
            animation: flash 1s step-start infinite;
        }
        @keyframes blink {
            0%, 50%, 100% { opacity: 1; }
            25%, 75% { opacity: 0; }
        }
        @keyframes flash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
    </style>
</head>
<body>
    <div class="mensagem-container">
        <div class="mensagem mensagem-<?php echo $efeito; ?>">
            <?php echo $mensagem; ?>
        </div>
    </div>
</body>
</html>
