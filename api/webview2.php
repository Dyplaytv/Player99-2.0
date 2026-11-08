<!DOCTYPE HTML>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ativar / Renovar</title>

    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center; /* Centraliza horizontalmente */
            justify-content: center; /* Centraliza verticalmente */
            height: 100vh; /* Ocupa toda a altura da janela */
            margin: 0; /* Remove margens padrão do body */
            background-color: #7b6e5e; /* Fundo escuro */
            color: white; /* Cor do texto */
            font-family: Arial, sans-serif; /* Fonte padrão */
        }

        /* Animação para o título piscar e mudar de cor */
        h1 {
            text-align: center; /* Alinha o texto ao centro */
            animation: blinkText 3.5s infinite alternate; /* Animação de piscar no texto */
        }

        @keyframes blinkText {
            0% { color: white; } /* Cor inicial branca */
            50% { color: yellow; } /* Cor intermediária amarela */
            100% { color: white; } /* Volta ao branco */
        }

        .box img {
            width: 400px; /* Largura fixa da imagem */
            height: 400px; /* Altura fixa da imagem */
            border-radius: 10px; /* Bordas arredondadas */
            animation: blinkImage 3s infinite alternate; /* Animação de piscar na imagem */
        }

        /* Animação para o piscar da imagem */
        @keyframes blinkImage {
            0% { opacity: 1; }  /* Início com opacidade total */
            50% { opacity: 0.5; } /* Meio com opacidade reduzida */
            100% { opacity: 1; } /* Fim volta à opacidade total */
        }
    </style>
</head>
<body>

    <h1>Escanei o QR Code para Renovar / Ativar seu Acesso!</h1>

   <div class="box">
        <a href="https://admplay.cadtech.com.br/pagamento.php" target="_blank">
            <img src="https://admplay.cadtech.com.br/ABC/qrcodelinkpagamento.jpg" alt="QR Code">
        </a>
    </div>


</body>
</html>
