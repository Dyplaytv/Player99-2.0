<?php
$frases = [
    "O sucesso é a soma de pequenos esforços repetidos dia após dia.",
    "Acredite em si próprio e chegará um dia em que os outros não terão outra escolha senão acreditar com você.",
    "O único lugar onde o sucesso vem antes do trabalho é no dicionário.",
    "A persistência é o caminho do êxito.",
    "Tudo o que um sonho precisa para ser realizado é alguém que acredite que ele possa ser realizado.",
    "O insucesso é apenas uma oportunidade para recomeçar com mais inteligência."
];
$frase = $frases[array_rand($frases)];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Frases e Pensamentos</title>
    <style>
        body { margin: 0; padding: 0; background: linear-gradient(135deg, #1f1c2c, #928dab); color: #fff; font-family: 'Georgia', serif; display: flex; justify-content: center; align-items: center; height: 100vh; text-align: center; overflow: hidden; padding: 50px; box-sizing: border-box; }
        .quote { font-size: 6vw; font-style: italic; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); line-height: 1.4; }
        .quote::before { content: '“'; font-size: 10vw; color: rgba(255,255,255,0.3); vertical-align: bottom; line-height: 0; margin-right: 10px; }
        .quote::after { content: '”'; font-size: 10vw; color: rgba(255,255,255,0.3); vertical-align: top; line-height: 0; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="quote"><?= htmlspecialchars($frase) ?></div>
</body>
</html>
