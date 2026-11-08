<?php
require_once __DIR__ . '/error_handler.php';
// cadastro.php
$ref = isset($_GET['ref']) ? strtoupper(trim($_GET['ref'])) : (isset($_COOKIE['affiliate_ref']) ? strtoupper(trim($_COOKIE['affiliate_ref'])) : '');
$referredById = null;

try {
    $db = new SQLite3("./api/db/studiolivecode_clientes.db");
    $db->enableExceptions(true);
    
    // Migrações do Banco de Dados para Afiliados
    $resCols = $db->query("PRAGMA table_info(ibo)");
    $columns = [];
    while ($c = $resCols->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $c['name'];
    }
    if (!in_array('affiliateCode', $columns)) {
        $db->exec("ALTER TABLE ibo ADD COLUMN affiliateCode TEXT");
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_affiliateCode ON ibo(affiliateCode)");
    }
    if (!in_array('affiliateClicks', $columns)) {
        $db->exec("ALTER TABLE ibo ADD COLUMN affiliateClicks INTEGER DEFAULT 0");
    }
    if (!in_array('pixKey', $columns)) {
        $db->exec("ALTER TABLE ibo ADD COLUMN pixKey TEXT");
    }
    if (!in_array('commissionPercent', $columns)) {
        $db->exec("ALTER TABLE ibo ADD COLUMN commissionPercent REAL DEFAULT 20.0");
    }
    if (!in_array('referredById', $columns)) {
        $db->exec("ALTER TABLE ibo ADD COLUMN referredById INTEGER");
    }
    
    // Se tiver 'ref', verifica clique e pega o ID do afiliado
    if (!empty($ref)) {
        // Incrementa o clique caso tenha entrado direto no cadastro e não tenha o cookie de clique
        if (!isset($_COOKIE['clicked_ref_' . $ref])) {
            $stmt = $db->prepare("UPDATE ibo SET affiliateClicks = affiliateClicks + 1 WHERE affiliateCode = :ref");
            $stmt->bindValue(':ref', $ref, SQLITE3_TEXT);
            $stmt->execute();
            setcookie('clicked_ref_' . $ref, '1', time() + 86400, "/");
        }
        
        // Pega o ID do afiliado para salvar depois no novo usuário
        $stmtId = $db->prepare("SELECT id FROM ibo WHERE affiliateCode = :ref");
        $stmtId->bindValue(':ref', $ref, SQLITE3_TEXT);
        $resId = $stmtId->execute();
        if ($rowId = $resId->fetchArray(SQLITE3_ASSOC)) {
            $referredById = $rowId['id'];
        }
    }
    
    $vence = date('Y-m-d', strtotime("+1 DAY"));
    $msg = '';
    $msgType = '';

    if (isset($_POST['submit'])) {
        $mac_address = sprintf('%02X:%02X:%02X:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $device_key = substr(md5(mt_rand()), 0, 6);
        $username = $_POST['username'];
        $password = sprintf('%06d', mt_rand(0, 999999));
        $title = $_POST['title'];
        $whatsapp = $_POST['whatsapp'];
        $refPost = $_POST['referredById']; // Valor oculto

        $stmtChkUser = $db->prepare("SELECT COUNT(*) FROM ibo WHERE username = :username");
        $stmtChkUser->bindValue(':username', $username, SQLITE3_TEXT);
        $checkUser = $stmtChkUser->execute()->fetchArray(SQLITE3_NUM)[0] ?? 0;
        if ($checkUser > 0) {
            $msg = "Este nome de usuário já existe. Tente outro.";
            $msgType = "danger";
        } else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'];
            $root = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
            $link_payment = $protocol . $host . $root . "/api/renovar/consulta_mac.php?mac=" . $mac_address;
            
            $dbDns = new SQLite3("./api/db/studiolivecode_dns.db");
            $resDns = $dbDns->query("SELECT url FROM dns LIMIT 1");
            $dns = "";
            if ($rowDns = $resDns->fetchArray(SQLITE3_ASSOC)) {
                $dns = $rowDns['url'];
            }
            $dbDns->close();

            $line = $dns . "/get.php?username=" . $username . "&password=" . $password . "&type=m3u_plus&output=ts";

            // Garante que as colunas referredById e whatsapp existam
            $resCols = $db->query("PRAGMA table_info(ibo)");
            $hasReferredBy = false;
            $hasWhatsappCol = false;
            while ($c = $resCols->fetchArray(SQLITE3_ASSOC)) {
                if ($c['name'] === 'referredById') $hasReferredBy = true;
                if ($c['name'] === 'whatsapp') $hasWhatsappCol = true;
            }
            if (!$hasReferredBy) {
                $db->exec("ALTER TABLE ibo ADD COLUMN referredById INTEGER");
            }
            if (!$hasWhatsappCol) {
                $db->exec("ALTER TABLE ibo ADD COLUMN whatsapp TEXT DEFAULT ''");
            }

            $whatsEsc = SQLite3::escapeString(trim($whatsapp));
            $insertQuery = "INSERT INTO ibo (
                mac_address, \"key\", username, password, expire_date, dns, epg_url, title, url, type, id_user, active, link_payment, referredById, whatsapp
            ) VALUES (
                '$mac_address', '$device_key', '$username', '$password', '$vence', '$dns', '', '$title', '$line', 1, 0, 1, '$link_payment', " . ($refPost ? "'$refPost'" : "NULL") . ", '$whatsEsc'
            )";

            $db->exec($insertQuery);

            $whatsFile = __DIR__ . "/whats.json";
            if (!file_exists($whatsFile)) {
                file_put_contents($whatsFile, json_encode([]));
            }
            $whatsData = json_decode(file_get_contents($whatsFile), true);
            $whatsData[$mac_address] = [
                'whatsapp' => $whatsapp,
                'name' => $title,
                'mac' => $mac_address
            ];
            file_put_contents($whatsFile, json_encode($whatsData, JSON_PRETTY_PRINT));

            // Enviar Mensagem de Boas Vindas se existir
            if (!empty($whatsapp)) {
                $templateQuery = $db->query("SELECT mensagem FROM whatsapp_templates WHERE tipo = 'Boas Vindas' AND ativo = 1 LIMIT 1");
                $templateRow = $templateQuery->fetchArray(SQLITE3_ASSOC);
                
                $templateMensagem = "";
                if ($templateRow && !empty($templateRow['mensagem'])) {
                    $templateMensagem = $templateRow['mensagem'];
                } else {
                    // Fallback se não existir template criado pelo admin
                    $templateMensagem = "Olá {nome}! Seja muito bem-vindo ao *Player99*.\n\nSeu cadastro foi realizado com sucesso e seu painel já está pronto para uso.";
                }
                
                include_once 'whatsapp_api.php';
                $msgBoasVindas = str_replace(
                    ['{nome}', '{vencimento}', '{link}', '{senha}', '{usuario}'], 
                    [$title, date('d/m/Y', strtotime($vence)), $link_payment, $password, $username], 
                    $templateMensagem
                );
                
                if (strpos($msgBoasVindas, $password) === false) {
                    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                    $host = $_SERVER['HTTP_HOST'];
                    $root = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                    $link_login = $protocol . $host . $root . "/login_cliente";
                    
                    $msgBoasVindas .= "\n\n*Seus dados de acesso:*\n👤 Usuário: $username\n🔑 Senha: *$password*\n🔗 Acesse o painel: $link_login";
                }
                
                $resWhats = enviarMensagemWasender(preg_replace('/[^0-9]/', '', $whatsapp), $msgBoasVindas);
                file_put_contents(__DIR__ . '/whatsapp_debug.log', date('Y-m-d H:i:s') . " - Cadastro: resultado do envio: " . print_r($resWhats, true) . "\n", FILE_APPEND);
            } else {
                file_put_contents(__DIR__ . '/whatsapp_debug.log', date('Y-m-d H:i:s') . " - Cadastro: Número de WhatsApp vazio.\n", FILE_APPEND);
            }

            // header("Location: " . $link_payment);
            // Redireciona para a tela de login unificada ao invés do pagamento
            header("Location: login.php?cadastrado=1");
            exit;
        }
    }
} catch (Exception $e) {
    $msg = "Erro interno: " . $e->getMessage();
    $msgType = "danger";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Player99</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
<?php
    $bgImagePath = "uploads/landing/cadastro_bg.jpg";
    if (file_exists(__DIR__ . "/" . $bgImagePath)) {
        $bgImage = $bgImagePath . "?v=" . filemtime(__DIR__ . "/" . $bgImagePath);
        $bodyBg = "background: url('$bgImage') no-repeat center center fixed; background-size: cover;";
    } else {
        $bodyBg = "background-color: #0f1015;";
    }
?>
    <style>
        body { 
            <?php echo $bodyBg; ?>
            color: #fff; 
            display: flex; 
            flex-direction: column;
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            margin: 0; 
            padding: 20px;
            font-family: sans-serif;
            position: relative;
            box-sizing: border-box;
        }
        
        /* Navbar Glassmorphism */
        .glass-nav {
            width: 100%;
            max-width: 1200px;
            background: rgba(15, 16, 21, 0.6);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px;
        }
        .glass-nav .logo-img { max-height: 45px; width: auto; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.5)); transition: transform 0.3s ease; }
        .glass-nav .logo-img:hover { transform: scale(1.05); }
        .glass-nav .nav-links { display: flex; gap: 15px; align-items: center; }
        .glass-nav .nav-btn { color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 8px 20px; border-radius: 20px; transition: all 0.3s ease; }
        .glass-nav .nav-btn:hover { background: rgba(255, 255, 255, 0.1); }
        .glass-nav .nav-btn-highlight { background: linear-gradient(135deg, #34e2e4, #4721fb); box-shadow: 0 4px 15px rgba(52, 226, 228, 0.4); border: none; }
        .glass-nav .nav-btn-highlight:hover { background: linear-gradient(135deg, #4721fb, #34e2e4); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(71, 33, 251, 0.5); }
        @media (max-width: 768px) {
            body { justify-content: flex-start; }
            .glass-nav { flex-direction: column; padding: 15px; border-radius: 20px; margin-bottom: 20px; }
            .glass-nav .nav-links { margin-top: 15px; flex-wrap: wrap; justify-content: center; }
        }
        
        /* Box de Cadastro Estilizado (Neon/Glassmorphism) */
        .card { 
            background: rgba(15, 16, 21, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px; 
            width: 100%; 
            max-width: 450px; 
            padding: 2.5rem; 
            box-shadow: 0 15px 35px rgba(52, 226, 228, 0.15), 0 5px 15px rgba(71, 33, 251, 0.15); 
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .card-title {
            text-align: center;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 5px;
            background: linear-gradient(135deg, #34e2e4, #4721fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .card-subtitle {
            text-align: center;
            color: #a0a0b0;
            font-size: 0.95rem;
            margin-bottom: 30px;
        }
        
        .form-control { 
            background-color: rgba(255, 255, 255, 0.05); 
            color: #fff; 
            border: 1px solid rgba(255,255,255,0.1); 
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: #34e2e4;
            color: #fff;
            box-shadow: 0 0 10px rgba(52, 226, 228, 0.3);
        }
        
        .btn-primary { 
            background: linear-gradient(135deg, #0A38D1, #4721fb); 
            color: #fff; 
            border: none; 
            font-weight: bold; 
            padding: 12px; 
            border-radius: 10px; 
            width: 100%; 
            font-size: 1.1rem;
            transition: all 0.3s;
            margin-top: 15px;
        }
        
        .btn-primary:hover { 
            background: linear-gradient(135deg, #4721fb, #34e2e4); 
            box-shadow: 0 5px 15px rgba(52, 226, 228, 0.4);
            transform: translateY(-2px);
        }
        
        label {
            color: #ccc;
            font-weight: 600;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Navbar Global Dinâmica -->
    <nav class="glass-nav">
        <a href="index.php">
            <!-- Puxando a logo que é gerenciada no painel (logo.php) -->
            <img src="./banners/logo.png<?php echo '?v='.time(); ?>" alt="Logo" class="logo-img" onerror="this.src='assets/images/logo.png'">
        </a>
        <div class="nav-links">
            <a href="login.php" class="nav-btn">Entrar</a>
            <a href="parceiros.php" class="nav-btn">Parceiros</a>
            <a href="afiliados.php" class="nav-btn">Afiliado</a>
            <a href="loja.php" class="nav-btn nav-btn-highlight">Loja</a>
            <a href="cadastro.php" class="nav-btn nav-btn-register" style="background: linear-gradient(135deg, #0A38D1, #4721fb); box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4); border: 1px solid rgba(255,255,255,0.2);">Cadastre-se Aqui</a>
        </div>
    </nav>

    <div class="card">
        <h2 class="card-title">Criar Conta</h2>
        <p class="card-subtitle">Cadastre-se para aproveitar nossos serviços.</p>
        
        <?php if ($msg): ?>
            <div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="referredById" value="<?php echo htmlspecialchars($referredById); ?>">
            <div class="mb-3">
                <label>Nome Completo</label>
                <input type="text" name="title" class="form-control" required placeholder="Ex: João 99 Porto Alegre">
            </div>
            <div class="mb-3">
                <label>WhatsApp</label>
                <input type="text" name="whatsapp" class="form-control" required placeholder="Ex: 51999999999">
            </div>
            <div class="mb-3">
                <label>Usuário (Login)</label>
                <input type="text" name="username" class="form-control" required placeholder="Ex: joao99">
            </div>
            <div class="mb-3" style="display: none;">
                <label>Senha</label>
                <input type="hidden" name="password" value="">
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Cadastrar Agora</button>
        </form>

        <div class="mt-4 text-center border-top border-secondary pt-4">
            <h6 class="text-muted mb-3" style="font-size: 0.9rem;">Já tem conta ou quer acessar no celular?</h6>
            <a href="https://cadtech.com.br/uploads/Player99v1_1785292708.apk" class="btn btn-secondary w-100" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #fff; padding: 12px; border-radius: 10px; transition: all 0.3s; font-weight: bold; text-decoration: none;">
                Baixar App Android
            </a>
        </div>
    </div>
</body>
</html>
