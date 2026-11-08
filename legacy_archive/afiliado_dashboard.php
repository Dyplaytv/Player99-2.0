<?php
session_start();
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login_cliente.php");
    exit;
}

$id = $_SESSION['cliente_id'];
$db = new SQLite3("./api/db/studiolivecode_clientes.db");

// Bloqueio manual
$stmtUser = $db->prepare("SELECT username FROM ibo WHERE id = :id");
$stmtUser->bindValue(':id', $id, SQLITE3_INTEGER);
$resUser = $stmtUser->execute();
if ($rowUser = $resUser->fetchArray()) {
    if ($rowUser['username'] === 'cemet@cemet.com.br') {
        header("Location: cliente_dashboard.php");
        exit;
    }
}

// Fallback de migração caso a página seja acessada diretamente no servidor
$res = $db->query("PRAGMA table_info(ibo)");
$columns = [];
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $columns[] = $row['name'];
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
$db->exec("CREATE TABLE IF NOT EXISTS affiliate_transactions(
    id INTEGER PRIMARY KEY AUTOINCREMENT, 
    userId INTEGER, 
    amount REAL, 
    type TEXT, 
    description TEXT, 
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Verifica e cria código de afiliado se não tiver
$stmt = $db->prepare("SELECT * FROM ibo WHERE id = :id");
$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
$res = $stmt->execute();
$user = $res->fetchArray(SQLITE3_ASSOC);

if (!$user) {
    die("Usuário não encontrado.");
}

if (empty($user['affiliateCode'])) {
    $code = 'REF-' . strtoupper(substr(md5(uniqid()), 0, 6));
    $stmtUpd = $db->prepare("UPDATE ibo SET affiliateCode = :code WHERE id = :id");
    $stmtUpd->bindValue(':code', $code, SQLITE3_TEXT);
    $stmtUpd->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmtUpd->execute();
    $user['affiliateCode'] = $code;
}

$msg = '';

if (isset($_POST['update_pix'])) {
    $pixKey = trim($_POST['pixKey']);
    $stmtUpd = $db->prepare("UPDATE ibo SET pixKey = :pixKey WHERE id = :id");
    $stmtUpd->bindValue(':pixKey', $pixKey, SQLITE3_TEXT);
    $stmtUpd->bindValue(':id', $id, SQLITE3_INTEGER);
    $stmtUpd->execute();
    $user['pixKey'] = $pixKey;
    $msg = "Chave PIX atualizada com sucesso!";
}

// Calcular Saldo
$resTx = $db->query("SELECT amount, type FROM affiliate_transactions WHERE userId = $id");
$saldo = 0;
while ($tx = $resTx->fetchArray(SQLITE3_ASSOC)) {
    if ($tx['type'] == 'COMMISSION') {
        $saldo += $tx['amount'];
    } else if ($tx['type'] == 'PAYOUT' || $tx['type'] == 'WITHDRAW_PENDING') {
        $saldo -= $tx['amount'];
    }
}

if (isset($_POST['withdraw'])) {
    if ($saldo > 0) {
        if (empty($user['pixKey'])) {
            $msg = "Configure sua chave PIX antes de sacar.";
        } else {
            $stmtSaque = $db->prepare("INSERT INTO affiliate_transactions (userId, amount, type, description) VALUES (:id, :amount, 'WITHDRAW_PENDING', 'Saque Solicitado')");
            $stmtSaque->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmtSaque->bindValue(':amount', $saldo, SQLITE3_FLOAT);
            $stmtSaque->execute();
            $msg = "Saque de R$ " . number_format($saldo, 2, ',', '.') . " solicitado com sucesso!";
            $saldo = 0;
        }
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$root = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$affiliateLink = $protocol . $host . $root . "/index.php?ref=" . $user['affiliateCode'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel de Afiliado</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/app.css" rel="stylesheet">
    <style>
        body { background-color: #1a1a2e; color: #fff; padding-top: 2rem; }
        .card { background-color: #16213e; border: none; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .form-control { background-color: #0f3460; color: #fff; border: 1px solid #0f3460; }
        .form-control:focus { background-color: #0f3460; color: #fff; }
        .btn-primary { background-color: #e94560; border: none; }
        .btn-primary:hover { background-color: #d13d56; }
        .stat-box { background-color: #0f3460; border-radius: 10px; padding: 20px; text-align: center; }
        .stat-box h3 { margin: 0; font-size: 2rem; color: #e94560; }
        .qr-container { background: white; padding: 10px; border-radius: 15px; display: inline-block; box-shadow: 0 4px 10px rgba(0,0,0,0.3); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Meu Painel de Afiliado</h2>
            <a href="cliente_dashboard.php" class="btn btn-outline-light">Voltar</a>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-info"><?php echo $msg; ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="stat-box">
                    <p class="text-uppercase mb-1">Saldo Disponível</p>
                    <h3>R$ <?php echo number_format($saldo, 2, ',', '.'); ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <p class="text-uppercase mb-1">Cliques no Link</p>
                    <h3><?php echo $user['affiliateClicks']; ?></h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-box">
                    <p class="text-uppercase mb-1">Minha Comissão</p>
                    <h3><?php echo number_format($user['commissionPercent'], 0); ?>%</h3>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-body">
                <h4>Seu Link de Convite</h4>
                <p>Compartilhe este link com seus amigos. Você ganha <?php echo number_format($user['commissionPercent'], 0); ?>% toda vez que eles pagarem!</p>
                
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="affLink" value="<?php echo $affiliateLink; ?>" readonly>
                            <button class="btn btn-primary" onclick="copiarLink()">Copiar Link</button>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <div id="qrcode" class="qr-container"></div>
                        <p class="mt-2 mb-0 text-muted"><small>Seu QR Code de Convite</small></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h4>Dados de Recebimento</h4>
                        <form method="post">
                            <div class="mb-3">
                                <label>Minha Chave PIX</label>
                                <input type="text" name="pixKey" class="form-control" value="<?php echo htmlspecialchars($user['pixKey'] ?? ''); ?>" placeholder="CPF, Email, Telefone..." required>
                            </div>
                            <button type="submit" name="update_pix" class="btn btn-primary w-100">Salvar Chave PIX</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center">
                        <h4>Solicitar Saque</h4>
                        <p>Transferiremos o saldo disponível para sua chave PIX informada.</p>
                        <form method="post">
                            <button type="submit" name="withdraw" class="btn btn-success btn-lg mt-3" <?php echo $saldo <= 0 ? 'disabled' : ''; ?>>Sacar R$ <?php echo number_format($saldo, 2, ',', '.'); ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function copiarLink() {
            var copyText = document.getElementById("affLink");
            copyText.select();
            copyText.setSelectionRange(0, 99999); 
            navigator.clipboard.writeText(copyText.value);
            alert("Link copiado para a área de transferência!");
        }

        const qrCode = new QRCodeStyling({
            width: 150,
            height: 150,
            data: "<?php echo $affiliateLink; ?>",
            image: "data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCIgZmlsbD0iI2U5NDU2MCI+PHBhdGggZD0iTTEwIDIwdi02aDR2Nmg1di04aDNMMTIgMyAyIDEyaDN2OHoiLz48L3N2Zz4=",
            dotsOptions: {
                color: "#0f3460",
                type: "rounded"
            },
            backgroundOptions: {
                color: "#ffffff",
            },
            imageOptions: {
                crossOrigin: "anonymous",
                margin: 5
            }
        });
        qrCode.append(document.getElementById("qrcode"));
    </script>
</body>
</html>
