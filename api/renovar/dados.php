<?php
session_start();



// Caminho do JSON
$jsonFile = __DIR__ . '/mp.json';

// Lê arquivo existente
if (file_exists($jsonFile)) {
  $config = json_decode(file_get_contents($jsonFile), true);
} else {
  $config = [
    'access_token' => '',
    'title' => '',
    'quantity' => 1,
    'unit_price' => 0.00
  ];
}

// Salvar alterações
if (isset($_POST["submit"])) {
  $config = [
    'access_token' => trim($_POST["access_token"]),
    'title' => trim($_POST["title"]),
    'quantity' => (int)$_POST["quantity"],
    'unit_price' => (float)$_POST["unit_price"]
  ];
  file_put_contents($jsonFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  $msg = "Configurações salvas com sucesso!";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configurações Mercado Pago</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
  * {
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
  }
  body {
    background: #0d1117;
    color: #e6edf3;
    margin: 0;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  .container {
    background: #161b22;
    padding: 40px 30px;
    border-radius: 15px;
    box-shadow: 0 0 20px rgba(0,0,0,0.4);
    width: 100%;
    max-width: 470px;
  }
  h2 {
    text-align: center;
    margin-bottom: 25px;
    font-weight: 600;
    color: #58a6ff;
  }
  label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    font-size: 14px;
    color: #c9d1d9;
  }
  input {
    width: 100%;
    padding: 10px 12px;
    background: #0d1117;
    border: 1px solid #30363d;
    border-radius: 8px;
    color: #e6edf3;
    font-size: 15px;
    margin-bottom: 18px;
    transition: border-color 0.2s;
  }
  input:focus {
    outline: none;
    border-color: #58a6ff;
  }
  button {
    width: 100%;
    background: #238636;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
    margin-top: 10px;
  }
  button:hover {
    background: #2ea043;
  }
  .msg {
    text-align: center;
    background: #23863633;
    border: 1px solid #238636;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
    color: #58a6ff;
  }
  .btn-voltar {
    background: #30363d;
  }
  .btn-voltar:hover {
    background: #444c56;
  }
  .btn-link {
    background: #1f6feb;
    color: white;
    font-size: 12px;
    padding: 5px 10px;
    border-radius: 6px;
    text-decoration: none;
    transition: background 0.3s;
  }
  .btn-link:hover {
    background: #388bfd;
  }
  @media (max-width: 480px) {
    .container {
      padding: 25px 20px;
      border-radius: 10px;
    }
    label {
      flex-direction: column;
      align-items: flex-start;
      gap: 5px;
    }
    .btn-link {
      width: 100%;
      text-align: center;
    }
  }
</style>
</head>
<body>
  <div class="container">
    <h2>Dados Mercado Pago</h2>

    <?php if (!empty($msg)) echo "<div class='msg'>$msg</div>"; ?>

    <form method="post">
      <label for="access_token">
        Access Token
        <a href="https://www.mercadopago.com.br/developers/panel/app" target="_blank" class="btn-link">
          Pegar Access Token
        </a>
      </label>
      <input type="text" id="access_token" name="access_token"
             placeholder="APP_USR-xxxxxxxxxxxxxxxxxx"
             value="<?=htmlspecialchars($config['access_token']);?>" required>

      <label for="title">Título do Plano</label>
      <input type="text" id="title" name="title"
             placeholder="Ex: Renovação 30 dias"
             value="<?=htmlspecialchars($config['title']);?>" required>

      <label for="quantity"></label>
      <input type="hidden" id="quantity" name="quantity" value="1">


      <label for="unit_price">Valor Plano (R$)</label>
      <input type="number" step="0.01" id="unit_price" name="unit_price"
             placeholder="1.80" value="<?=htmlspecialchars($config['unit_price']);?>" required>

      <button type="submit" name="submit">Salvar</button>
      <button type="button" class="btn-voltar" onclick="window.history.back()">Voltar</button>
    </form>
  </div>
</body>
</html>
