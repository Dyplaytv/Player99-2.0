<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovar Acesso</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <style>
        body {
            background-color: #121212;
            color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .payment-form {
            max-width: 500px;
            background: #1e1e1e;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        .payment-form h4 {
            margin-bottom: 20px;
            color: #ffffff;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            color: #bbbbbb;
            display: block;
            margin-bottom: 5px;
        }
        .form-control {
            background: #333;
            border: none;
            color: #fff;
            text-align: center;
            font-size: 1.5rem;
            padding: 10px;
            border-radius: 5px;
            letter-spacing: 2px;
        }
        .form-control::placeholder {
            color: #aaaaaa;
        }
        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 12px;
            font-size: 1.2rem;
            border-radius: 5px;
        }
        .btn-primary:hover {
            background-color: #2980b9;
        }
        #loader {
            display: none;
            margin: 20px auto;
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .logo {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
        }
        @keyframes blink {
            50% { border-color: #ffffff; }
        }
        .form-control:focus {
            animation: blink 1s infinite alternate;
        }
    </style>
</head>
<body>

<div class="payment-form">
    <img src="https://apktop.com.br/pay.png" alt="Logo" class="logo">
    <h4>Insira o MAC do Dispositivo</h4>
    <form method="post" action="../renovar/gerar_link.php" id="payment-form">
        <div class="form-group">
            <input type="text" class="form-control" id="input" name="input" placeholder="Digite o MAC" maxlength="17" autofocus>
        </div>
        <button type="submit" class="btn btn-primary w-100">Gerar Link</button>
        <div id="loader"></div>
    </form>
</div>

<script>
document.getElementById('payment-form').addEventListener('submit', function() {
    document.getElementById('loader').style.display = 'block';
    document.querySelector('button[type="submit"]').disabled = true;
});

window.onload = function() {
    const inputField = document.getElementById('input');
    inputField.focus();

    inputField.addEventListener('input', function(e) {
        let value = this.value.toUpperCase().replace(/[^A-F0-9]/g, '');
        let formatted = '';

        for (let i = 0; i < value.length; i++) {
            if (i > 0 && i % 2 === 0) {
                formatted += ':';
            }
            formatted += value[i];
        }

        this.value = formatted;

        // Quando atingir 17 caracteres (MAC completo), envia o formulário
        if (formatted.length === 17) {
            document.getElementById('payment-form').submit();
        }
    });
}
</script>

</body>
</html>
