<?php
session_start();

$vence = date('Y-m-d', strtotime("+1 DAY"));
$limited = false;
$db = new SQLite3("./api/db/studiolivecode_clientes.db");
$db->exec("CREATE TABLE IF NOT EXISTS ibo(
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    mac_address VARCHAR(17),
    username VARCHAR(100),
    password VARCHAR(100),
    expire_date VARCHAR(10),
    dns VARCHAR(100),
    epg_url VARCHAR(100),
    title VARCHAR(100),
    url VARCHAR(100),
    type INTEGER,
    id_user INTEGER,
    active INTEGER,
    key VARCHAR(100),
    link_payment VARCHAR(255),
    affiliateCode TEXT,
    affiliateClicks INTEGER DEFAULT 0,
    pixKey TEXT,
    commissionPercent REAL DEFAULT 20.0,
    referredById INTEGER
)");
$db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_affiliateCode ON ibo(affiliateCode)");


// Migração do sistema de afiliados
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
if (!in_array('whatsapp', $columns)) {
    $db->exec("ALTER TABLE ibo ADD COLUMN whatsapp TEXT DEFAULT ''");
}

$id = $_SESSION['id'];
$dbUsers = new SQLite3("./api/db/studiolivecode_users.db");
$query = $dbUsers->query("SELECT admin FROM users WHERE id = $id");
$userData = $query->fetchArray(SQLITE3_ASSOC);
$isAdmin = $userData['admin'] == 1;

// Caminho do banco WhatsApp
$whatsFile = __DIR__ . "/whats.json";
if (!file_exists($whatsFile)) {
    file_put_contents($whatsFile, json_encode([]));
}

// Lógica de exclusão
if (isset($_POST["delete"])) {
    $mac_address = strtoupper($_POST["mac_address"]); // Garanta uppercase para match exato

    // Deleta do banco de dados
    $deleteQuery = "DELETE FROM ibo WHERE mac_address = '$mac_address'";
    $db->exec($deleteQuery);

    // Remove do JSON
    $whatsData = json_decode(file_get_contents($whatsFile), true);
    if (isset($whatsData[$mac_address])) {
        unset($whatsData[$mac_address]);
        file_put_contents($whatsFile, json_encode($whatsData, JSON_PRETTY_PRINT));
    }

    // Remove da sessão se existir
    if (isset($_SESSION['macs'])) {
        $_SESSION['macs'] = array_filter($_SESSION['macs'], function($row) use ($mac_address) {
            return strtoupper($row['mac_address']) != $mac_address;
        });
    }

    if (!$userData || !$isAdmin) {
        header("Location: users.php?r=deletado");
    } else {
        header("Location: all_users.php?r=deletado");
    }
    exit;
}

if (isset($_POST["submit"])) {
    // Gera MAC e Device Key automaticamente caso tenham sido removidos do form
    $mac_address = isset($_POST["mac_address"]) ? strtoupper($_POST["mac_address"]) : sprintf('%02X:%02X:%02X:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
    $device_key = isset($_POST["device_key"]) ? $_POST["device_key"] : substr(md5(mt_rand()), 0, 6);
    $username = $_POST["username"];

    // Verificação de usuário duplicado
    $checkUser = $db->querySingle("SELECT COUNT(*) FROM ibo WHERE username = '$username'");
    if ($checkUser > 0) {
        if (!$userData || !$isAdmin) {
            header("Location: users.php?err=duplicate");
        } else {
            header("Location: all_users.php?err=duplicate");
        }
        exit;
    }

    $password = $_POST["password"];
    $email = isset($_POST["email"]) ? strtolower(trim($_POST["email"])) : '';
    $expire_date = $isAdmin ? $_POST["expire_date"] : $vence;
    $dns = $_POST["dns"] ?? '';
    $title = $_POST["title"];
    $whatsapp = $_POST["whatsapp"];
    $type = 1;

    if (!$userData || !$isAdmin) {       
        $res = $dbUsers->query("SELECT mac_amount FROM users WHERE id = '$id' ");
        $macCount = $res->fetchArray()['mac_amount'];

        $res = $db->query("SELECT COUNT(*) as count FROM ibo WHERE id_user = '$id' AND active = 1 AND expire_date > date('now')");
        $count = $res->fetchArray()['count'];

        if ($count >= $macCount) {
            $limited = true;
        }
    }

    if (!$limited) {
        // Gera a URL de pagamento automaticamente
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $root = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $link_payment = $protocol . $host . $root . "/api/renovar/consulta_mac.php?mac=" . $mac_address;

        $line = "";

        $stmtIns = $db->prepare("INSERT INTO ibo (
            mac_address, key, username, password, email, expire_date, dns, epg_url, title, url, type, id_user, active, link_payment, whatsapp
        ) VALUES (
            :mac, :key, :user, :pass, :email, :exp, :dns, '', :title, :url, :type, :id_user, 1, :link_pay, :whats
        )");
        $stmtIns->bindValue(':mac', $mac_address, SQLITE3_TEXT);
        $stmtIns->bindValue(':key', $device_key, SQLITE3_TEXT);
        $stmtIns->bindValue(':user', $username, SQLITE3_TEXT);
        $stmtIns->bindValue(':pass', $password, SQLITE3_TEXT);
        $stmtIns->bindValue(':email', $email, SQLITE3_TEXT);
        $stmtIns->bindValue(':exp', $expire_date, SQLITE3_TEXT);
        $stmtIns->bindValue(':dns', $dns, SQLITE3_TEXT);
        $stmtIns->bindValue(':title', $title, SQLITE3_TEXT);
        $stmtIns->bindValue(':url', $line, SQLITE3_TEXT);
        $stmtIns->bindValue(':type', $type, SQLITE3_INTEGER);
        $stmtIns->bindValue(':id_user', (int)$id, SQLITE3_INTEGER);
        $stmtIns->bindValue(':link_pay', $link_payment, SQLITE3_TEXT);
        $stmtIns->bindValue(':whats', trim($whatsapp), SQLITE3_TEXT);
        $stmtIns->execute();

        // Salva MAC, WhatsApp e nome no arquivo JSON
        $whatsData = json_decode(file_get_contents($whatsFile), true);
        $whatsData[$mac_address] = [
            'whatsapp' => $whatsapp,
            'name' => $title,
            'mac' => $mac_address
        ];
        file_put_contents($whatsFile, json_encode($whatsData, JSON_PRETTY_PRINT));

        if (!isset($_SESSION['macs'])) {
            $_SESSION['macs'] = [];
        }

        $macRes = $db->query("SELECT * FROM ibo WHERE mac_address = '$mac_address'");
        while($row = $macRes->fetchArray()){
            if(!sessionContains($row)){
                array_push($_SESSION['macs'], $row);   
            }
        }

        if (!$userData || !$isAdmin) {
            header("Location: users.php?r=adicionado");
        } else {
            header("Location: all_users.php?r=adicionado");
        }
    }
}

$db->close();

function sessionContains($searchRow)
{
    foreach ($_SESSION['macs'] as $session_row) {
        if ($session_row['id'] == $searchRow['id']) {
            return true;
        }
    }
    return false;
}

function getDnsOptions() {
    $dbDns = new SQLite3("./api/db/studiolivecode_dns.db");
    $results = $dbDns->query("SELECT url FROM dns");
    $options = "";
    while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
        $options .= "<option value='" . $row['url'] . "'>" . $row['url'] . "</option>";
    }
    $dbDns->close();
    return $options;
}
?>

<?php include './includes/header.php';?>
<?php
if ($limited) {
    ?>
    <div class="alert alert-danger">Limite de MACs excedido!</div>
    <?php
}
?>
<div class="card radius-10">
    <div class="card-body">
        <h4 class="card-title">Adicionar Usuário</h4>
        <form method="post">
            <!-- MAC e Chave do Dispositivo foram removidos deste formulário conforme solicitado -->

            <div class="form-group mb-4">
                <label class="control-label" for="title">
                    <strong>Nome</strong>
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" name="title" placeholder="Digite o nome" id="title" required/>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="control-label" for="whatsapp">
                    <strong>WhatsApp</strong>
                </label>
                <div class="input-group">
                    <input type="text" class="form-control" name="whatsapp" placeholder="Digite o WhatsApp do cliente"/>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="control-label" for="email">
                    <strong>E-mail</strong>
                </label>
                <div class="input-group">
                    <input type="email" class="form-control" name="email" placeholder="cliente@email.com"/>
                </div>
            </div>

            <div class="xtream-fields">
                <div class="form-group mb-4">
                    <label class="control-label" for="username">
                        <strong>Usuário</strong>
                    </label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="username" placeholder="Digite o Usuário" id="discription"/>
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="control-label" for="password">
                        <strong>Senha</strong>
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" name="password" id="passClientCreate" placeholder="Digite a Senha" required/>
                        <button class="btn btn-outline-secondary" type="button" onclick="const p = document.getElementById('passClientCreate'); const i = this.querySelector('i'); if(p.type==='password'){p.type='text'; i.className='bx bx-hide';}else{p.type='password'; i.className='bx bx-show';}"><i class='bx bx-show'></i></button>
                    </div>
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="control-label" for="expire_date">
                    <strong>Vencimento</strong></label>
                <div class="input-group">
                    <input type="date" class="form-control" name="expire_date" value="<?php echo date('Y-m-d', strtotime($vence));?>" placeholder="YYYY-MM-DD" id="datetimepicker" <?php if (!$isAdmin) echo 'readonly'; ?> autocomplete="off"/>
                </div>
            </div>

            <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Adicionar</button>
        </form>
    </div>
</div>

<script type="text/javascript">
/* Script de formatação de MAC removido, pois o campo não existe mais */

function chunk(str, n) {
    var ret = [];
    var i;
    var len;

    for(i = 0, len = str.length; i < len; i += n) {
       ret.push(str.substr(i, n))
    }
    return ret
};
</script>

<?php include './includes/footer.php';?>