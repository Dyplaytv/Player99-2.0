<?php
session_start();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['consultar_mac'])) {
        $username = trim($_POST['mac_address']);
        if (!empty($username)) {
            $db = new SQLite3('../db/studiolivecode_clientes.db');
            $stmt = $db->prepare("SELECT * FROM ibo WHERE username = :username");
            $stmt->bindValue(':username', $username, SQLITE3_TEXT);
            $res = $stmt->execute();
            $row = $res->fetchArray(SQLITE3_ASSOC);
            
            if ($row) {
                $_SESSION['mpg_username'] = $row['username'];
                unset($_SESSION['mpg_multi_codigo']); // clear previous
                header("Location: renovar.php");
                exit;
            } else {
                $erro = "Usuário não encontrado!";
            }
        } else {
            $erro = "Por favor, digite seu Usuário.";
        }
    } elseif (isset($_POST['consultar_codigo'])) {
        $codigo = strtoupper(trim($_POST['codigo_multimac']));
        if (!empty($codigo)) {
            $config_file = 'config.json';
            $config = json_decode(file_get_contents($config_file), true);
            $encontrado = false;
            foreach ($config['multi_macs'] ?? [] as $m) {
                if (strtoupper($m['codigo']) === $codigo) {
                    $_SESSION['mpg_multi_codigo'] = $codigo;
                    unset($_SESSION['mpg_mac']); // clear previous
                    header("Location: renovar.php");
                    exit;
                }
            }
            $erro = "Código Multi MACs não encontrado!";
        } else {
            $erro = "Por favor, digite seu código.";
        }
    }
}

// Auto-login se a URL trouxer o parâmetro ?mac=
$mac_get = isset($_GET['mac']) ? trim($_GET['mac']) : '';
if (!empty($mac_get) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $db = new SQLite3('../db/studiolivecode_clientes.db');
    $stmt = $db->prepare("SELECT * FROM ibo WHERE username = :mac OR mac_address = :mac");
    $stmt->bindValue(':mac', $mac_get, SQLITE3_TEXT);
    $res = $stmt->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    
    if ($row) {
        $_SESSION['mpg_username'] = $row['username'];
        unset($_SESSION['mpg_multi_codigo']);
        header("Location: renovar.php");
        exit;
    }
}
$mac_get_html = htmlspecialchars($mac_get);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renovação Mercado Pago Gold</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0d1117;
            color: #c9d1d9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
        }
        .container-box {
            background: #161b22;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 0 20px rgba(0, 195, 255, 0.2), 0 0 40px rgba(0, 195, 255, 0.1);
            border: 1px solid rgba(0, 195, 255, 0.3);
            position: relative;
        }
        .container-box::before {
            content: '';
            position: absolute;
            top: -2px; left: -2px; right: -2px; bottom: -2px;
            background: linear-gradient(45deg, #00c3ff, #0051ff);
            z-index: -1;
            border-radius: 22px;
            filter: blur(10px);
            opacity: 0.6;
        }
        h3 {
            color: #00c3ff;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-size: 24px;
            margin-bottom: 30px;
            text-align: center;
            text-shadow: 0 0 10px rgba(0, 195, 255, 0.5);
            font-weight: bold;
        }
        .btn-gold {
            background: linear-gradient(90deg, #00c3ff, #0051ff);
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 30px;
            text-transform: uppercase;
            box-shadow: 0 0 15px rgba(0, 195, 255, 0.4);
            transition: all 0.3s ease;
            padding: 12px;
        }
        .btn-gold:hover {
            background: linear-gradient(90deg, #0051ff, #00c3ff);
            color: white;
            box-shadow: 0 0 25px rgba(0, 195, 255, 0.8);
            transform: scale(1.02);
        }
        .nav-tabs { border-bottom: 1px solid rgba(0, 195, 255, 0.2); }
        .nav-tabs .nav-link { color: #8b949e; border: none; background: transparent; }
        .nav-tabs .nav-link:hover { color: #c9d1d9; }
        .nav-tabs .nav-link.active { color: #00c3ff; border-bottom: 2px solid #00c3ff; background: transparent; font-weight: bold; text-shadow: 0 0 5px rgba(0, 195, 255, 0.5); }
        .form-control { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(0, 195, 255, 0.3); color: #fff; border-radius: 10px; padding: 12px; }
        .form-control:focus { background: rgba(0, 0, 0, 0.5); border-color: #00c3ff; color: #fff; box-shadow: 0 0 10px rgba(0, 195, 255, 0.3); outline: none; }
        .form-label { color: #c9d1d9; font-weight: 500; font-size: 15px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container-box">
        <h3>Renovar Vencimento</h3>
        <?php if($erro) echo "<div class='alert alert-danger'>$erro</div>"; ?>
        
        <form method="POST">
            <div class="mb-4 mt-4">
                <label class="form-label text-white-50">Usuário de Acesso à TV</label>
                <input type="text" name="mac_address" id="mac_address" class="form-control bg-dark text-white border-secondary" placeholder="Ex: joaosilva" value="<?php echo $mac_get_html; ?>" required style="padding: 12px; border-radius: 10px;">
            </div>
            <button type="submit" name="consultar_mac" id="btn_consultar" class="btn btn-gold w-100">Consultar Login</button>
        </form>
    </div>
    
    <?php if(!empty($mac_get)): ?>
    <script>
        document.getElementById('btn_consultar').click();
    </script>
    <?php endif; ?>
</body>
</html>
