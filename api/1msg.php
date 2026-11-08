<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exibir Texto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: transparent;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .message-container {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: 25px;
        }
        .message {
            top: 0;
            font-size: 30px;
            white-space: nowrap;
            position: absolute;
            top: 50%;
            
            animation: scrollText 50s linear;
            text-shadow: 0 0 10px rgba(0, 176, 255, 0.8);
            padding: 5px 10px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 5px;
        }
        @keyframes scrollText {
            from { left: 100%; }
            to { left: -100%; }
        }
    </style>
</head>
<body>
    <div class="message-container">
        <div class="message" id="currentMessage">
            <!-- O texto será carregado aqui -->
        </div>
    </div>

    <script>
        function exibirTexto() {
            var xhr = new XMLHttpRequest();
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    var data = JSON.parse(xhr.responseText);
                    var texto = data.message;
                    var cor = data.color;
                    var messageElement = document.getElementById('currentMessage');
                    messageElement.textContent = texto;
                    messageElement.style.color = cor;
                    messageElement.style.display = 'block';
                    setTimeout(function() {
                        messageElement.style.display = 'none';
                    }, 50000); // 50 segundos
                }
            };
            xhr.open("GET", "../texto.txt", true);
            xhr.send();
        }

        exibirTexto();
    </script>
</body>
</html>