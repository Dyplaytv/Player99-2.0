<?php
session_start();
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/includes/auth_helper.php';
$id = $_SESSION['id'] ?? 0;
$updateId = intval($_GET["update"] ?? 0);
$db = new SQLite3('./api/db/studiolivecode_clientes.db');

$stmtGet = $db->prepare("SELECT * FROM ibo WHERE id = :id");
$stmtGet->bindValue(':id', $updateId, SQLITE3_INTEGER);
$res = $stmtGet->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);

if (!$row) {
    header("Location: users.php?r=nao_encontrado");
    exit;
}

$id_mac = $row["id"];
$mac_address = $row["mac_address"];
$expire_date = $row["expire_date"];
$username = $row["username"];
$password = $row["password"];
$email = $row["email"] ?? '';
$dns = $row["dns"];
$epg_url = $row["epg_url"];
$title = $row["title"];
$url = $row["url"];
$type = $row["type"];
$device_key = $row["key"];
$link_payment = $row['link_payment'];
$max_videos = isset($row['max_videos']) && $row['max_videos'] !== null ? (int)$row['max_videos'] : 10;
$max_video_size_mb = isset($row['max_video_size_mb']) && $row['max_video_size_mb'] !== null ? (int)$row['max_video_size_mb'] : 50;

// Caminho do arquivo WhatsApp na raiz
$whatsFile = __DIR__ . '/whats.json';
if (!file_exists($whatsFile)) {
    file_put_contents($whatsFile, json_encode([]));
}
$whatsData = json_decode(file_get_contents($whatsFile), true);

$dbUsers = new SQLite3("./api/db/studiolivecode_users.db");
$stmtUData = $dbUsers->prepare("SELECT admin FROM users WHERE id = :id");
$stmtUData->bindValue(':id', $id, SQLITE3_INTEGER);
$userData = $stmtUData->execute()->fetchArray(SQLITE3_ASSOC);
$is_admin = ($userData && $userData['admin'] == 1);
$dbUsers->close();

$whats = !empty($row['whatsapp']) ? $row['whatsapp'] : (isset($whatsData[$mac_address]['whatsapp']) ? $whatsData[$mac_address]['whatsapp'] : '');

if (isset($_POST["submit"])) {
    $we = strtotime($_POST["expire_date"]);
    $ne = date("Y-m-d", $we);
    $address1 = strtoupper($_POST["mac_address"]);
    $line = "";
    $user_id = $id;
    $emailPost = isset($_POST["email"]) ? strtolower(trim($_POST["email"])) : '';
    $whatsClean = trim($_POST['whatsapp'] ?? '');

    $mv = $max_videos;
    $ms = $max_video_size_mb;
    if ($is_admin) {
        $mv = isset($_POST["max_videos"]) ? (int)$_POST["max_videos"] : 10;
        $ms = isset($_POST["max_video_size_mb"]) ? (int)$_POST["max_video_size_mb"] : 50;
    }

    $stmtUpd = $db->prepare("UPDATE ibo SET
        mac_address = :mac,
        key = :key,
        expire_date = :exp,
        username = :user,
        password = :pwd,
        email = :email,
        dns = :dns,
        epg_url = :epg,
        title = :title,
        url = :url,
        link_payment = :link_pay,
        whatsapp = :whats,
        max_videos = :mv,
        max_video_size_mb = :ms
        WHERE id = :id");

    $stmtUpd->bindValue(':mac', $address1, SQLITE3_TEXT);
    $stmtUpd->bindValue(':key', $_POST["device_key"], SQLITE3_TEXT);
    $stmtUpd->bindValue(':exp', ($is_admin ? $ne : $expire_date), SQLITE3_TEXT);
    $stmtUpd->bindValue(':user', $_POST["username"], SQLITE3_TEXT);
    $stmtUpd->bindValue(':pwd', $_POST["password"], SQLITE3_TEXT);
    $stmtUpd->bindValue(':email', $emailPost, SQLITE3_TEXT);
    $stmtUpd->bindValue(':dns', $_POST["dns"], SQLITE3_TEXT);
    $stmtUpd->bindValue(':epg', $_POST["epg_url"], SQLITE3_TEXT);
    $stmtUpd->bindValue(':title', $_POST["title"], SQLITE3_TEXT);
    $stmtUpd->bindValue(':url', $line, SQLITE3_TEXT);
    $stmtUpd->bindValue(':link_pay', $_POST["link_payment"], SQLITE3_TEXT);
    $stmtUpd->bindValue(':whats', $whatsClean, SQLITE3_TEXT);
    $stmtUpd->bindValue(':mv', $mv, SQLITE3_INTEGER);
    $stmtUpd->bindValue(':ms', $ms, SQLITE3_INTEGER);
    $stmtUpd->bindValue(':id', $id_mac, SQLITE3_INTEGER);
    $stmtUpd->execute();

    // Salva MAC, WhatsApp e nome no arquivo JSON
    $whatsData = json_decode(file_get_contents($whatsFile), true);
    $whatsData[$address1] = [
        'whatsapp' => $_POST['whatsapp'],
        'name' => $_POST['title'],
        'mac' => $address1
    ];
    // Se o MAC mudou, remove a entrada antiga
    if ($address1 !== $mac_address && isset($whatsData[$mac_address])) {
        unset($whatsData[$mac_address]);
    }
    file_put_contents($whatsFile, json_encode($whatsData, JSON_PRETTY_PRINT));

    if (isset($_SESSION['macs'])) {
        $res = $db->query("SELECT * FROM ibo WHERE id = " . $_POST["id"]);

        $row = $res->fetchArray();

        for ($i = 0; $i < count($_SESSION['macs']); $i++) {
            $session_row = $_SESSION['macs'][$i];
            if ($session_row['id'] == $_POST["id"]) {
                $_SESSION['macs'][$i] = $row;
            }
        }
    }

    if (!$is_admin) {
        header("Location: users.php?r=atualizado");
    } else {
        header("Location: all_users.php?r=atualizado");
    }
    $dbUsers->close();
    $db->close();
}

// Função para obter as DNS do banco de dados e criar o seletor

?>
<?php include './includes/header.php';?>
<div class="card radius-10">
    <div class="card-body">
    <h4 class="card-title">Atualizar Usuário</h4>
    <form method="post">
            <div class="form-group mb-4">
                <label class="control-label" for="mac_address"><strong>MAC do Dispositivo</strong></label>
                <div class="input-group">
                    <input type="hidden" name="id" value="<?= $id_mac ?>">
                    <input class="form-control" id="description" placeholder="Digite o MAC: 00:11:22:33:44:55" name="mac_address" value="<?= $mac_address ?>" type="text" maxlength="17" required/>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="device_key"><strong>Chave do Dispositivo</strong></label>
                <div class="input-group">
                    <input class="form-control" id="device_key" placeholder="Digite a Chave do Dispositivo" name="device_key" value="<?= $device_key ?>" type="text" required/>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="title"><strong>Nome</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="title" placeholder="Digite o nome" value="<?= $title ?>" id="discription" required/>
                </div>
            </div>
            <div class="activeu">
                <div class="form-group mb-4">
                    <label class="control-label" for="username"><strong>Usuário</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Digite o Usuário" name="username" value="<?= $username ?>" id="discription" />
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="control-label" for="password"><strong>Senha</strong></label>
                    <div class="input-group">
                        <input type="password" class="form-control" placeholder="Digite a Senha" name="password" value="<?= htmlspecialchars($password) ?>" id="passClientUpdate" required/>
                        <button class="btn btn-outline-secondary" type="button" onclick="const p = document.getElementById('passClientUpdate'); const i = this.querySelector('i'); if(p.type==='password'){p.type='text'; i.className='bx bx-hide';}else{p.type='password'; i.className='bx bx-show';}"><i class='bx bx-show'></i></button>
                    </div>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="expire_date"><strong>Vencimento</strong></label>
                <div class="input-group">
                    <input type="date" class="form-control" name="expire_date" placeholder="YYYY-MM-DD" id="datetimepicker" value="<?php
                        echo date('Y-m-d', strtotime($expire_date));
                    ?>" <?= $is_admin ? '' : 'readonly' ?> />
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="link_payment"><strong>Link de pagamento</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="link_payment" placeholder="Digite o seu link do qpanel" id="link_payment" value="<?= $link_payment ?>" />
                </div>
            </div>
            <!-- Novo campo WhatsApp -->
            <div class="form-group mb-4">
                <label class="control-label" for="whatsapp"><strong>WhatsApp</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="whatsapp" placeholder="Digite o WhatsApp do cliente" id="whatsapp" value="<?= $whats ?>" />
                </div>
            </div>

            <!-- Campo E-mail -->
            <div class="form-group mb-4">
                <label class="control-label" for="email"><strong>E-mail</strong></label>
                <div class="input-group">
                    <input type="email" class="form-control" name="email" placeholder="cliente@email.com" id="email" value="<?= htmlspecialchars($email) ?>" />
                </div>
            </div>

            <?php if ($is_admin): ?>
            <!-- Configurações de Cota de Mídia / Vídeos (Apenas Admin) -->
            <div class="card p-3 mb-4" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;">
                <h5 class="text-info mb-3"><i class="bx bx-film mr-1"></i> Cota de Mídia do Cliente (Admin)</h5>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="control-label" for="max_videos"><strong>Limite Máximo de Vídeos / Arquivos</strong></label>
                        <div class="input-group">
                            <input type="number" min="1" max="500" class="form-control" name="max_videos" id="max_videos" value="<?= $max_videos ?>" required />
                            <div class="input-group-append">
                                <span class="input-group-text">arquivos</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Padrão: 10 vídeos por cliente.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="control-label" for="max_video_size_mb"><strong>Tamanho Máximo por Arquivo (MB)</strong></label>
                        <div class="input-group">
                            <input type="number" min="1" max="1024" class="form-control" name="max_video_size_mb" id="max_video_size_mb" value="<?= $max_video_size_mb ?>" required />
                            <div class="input-group-append">
                                <span class="input-group-text">MB</span>
                            </div>
                        </div>
                        <small class="form-text text-muted">Padrão: 50 MB por vídeo/arquivo.</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="form-group mb-4">
                <div>
                    <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Atualizar</button>
                </div>
            </div>
    </form>
</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script type="text/javascript">
document.getElementById("description").addEventListener('keyup', function() { 
  var mac = document.getElementById('description').value;
  var macs = mac.split(':').join('');
  macs = chunk(macs, 2).join(':');
  document.getElementById('description').value = macs.toString();
});

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