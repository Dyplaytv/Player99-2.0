<?php

session_start();
$id = $_SESSION['id'] ?? 0;
$updateId = intval($_GET["update"] ?? 0);
$db = new SQLite3('./api/db/studiolivecode_clientes.db');
$stmtRes = $db->prepare("SELECT * FROM ibo WHERE id = :id");
$stmtRes->bindValue(':id', $updateId, SQLITE3_INTEGER);
$res = $stmtRes->execute();
$row = $res->fetchArray(SQLITE3_ASSOC);
if (!$row) {
    header("Location: users_teste.php");
    exit;
}
$id_mac = $row["id"];
$mac_address = $row["mac_address"];
$expire_date = $row["expire_date"];
$username = $row["username"];
$password = $row["password"];
$dns = $row["dns"];
$epg_url = $row["epg_url"];
$title = $row["title"];
$url = $row["url"];
$type = $row["type"];

if (isset($_POST["submit"])) {
    $we = strtotime($_POST["expire_date"]);
    $ne = date("Y-m-d", $we);
    $address1 = strtoupper($_POST["mac_address"]);
    if ($_POST["type"] == "1") {
    $line = "";
    } else {
        $line = $_POST["url"];
    }

    // Use a ID da sessão atual do usuário como user_id
    $user_id = $id;

    $db->exec("UPDATE ibo SET
    mac_address='" . $address1 . "',
    expire_date='" . $ne . "',
    username='" . $_POST["username"] . "',
    password='" . $_POST["password"] . "',
    dns='" . $_POST["dns"] . "',
    epg_url='" . $_POST["epg_url"] . "',
    title='" . $_POST["title"] . "',
    url='" . $line . "',
    type='" . $_POST["type"] . "',
    id_user='" . $user_id . "',
    active='1'
    WHERE id='" . $_POST["id"] . "'");

    if (isset($_SESSION['macs'])) {
        $res = $db->query("SELECT * FROM ibo WHERE id = " . $_POST["id"]);

        $row = $res->fetchArray();
        $db->close();

        for ($i = 0; $i < count($_SESSION['macs']); $i++) {
            $session_row = $_SESSION['macs'][$i];
            if ($session_row['id'] == $_POST["id"]) {
                $_SESSION['macs'][$i] = $row;
            }
        }
    }
        header("Location: users.php?r=atualizado");
    $dbUsers->close();
    $db->close();
}

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
                <label class="control-label" for="title"><strong>Nome do Servidor</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="title" placeholder="Digite o nome do servidor" value="<?= $title ?>" id="discription" required/>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="type"><strong>Selecione o Modo de Login:</strong></label>
                <select class="select form-control type" id="type" name="type">
                    <option data-value="op0" value="0">Usar Lista M3U</option>
                    <option data-value="op1" value="1" selected>Usar Xtream Codes</option>
                </select>
            </div>
            <div class="actived">
                <div class="form-group mb-4">
                    <label class="control-label" for="uls"><strong>Lista M3U8</strong></label>
                    <input type="text" class="form-control" placeholder="Digite a lista M3U" name="url" value="<?= $url ?>" id="discription" />
                </div>
            </div>
            <div class="activeu">
                <div class="form-group mb-4">
                    <label class="control-label" for="dns"><strong>DNS</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Digite o DNS" name="dns" value="<?= $dns ?>" id="discription"/>
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="control-label" for="username"><strong>Usuário</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Digite o Usuário" name="username" value="<?= $username ?>" id="discription" />
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="control-label" for="password"><strong>Senha</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Digite a Senha" name="password" value="<?= $password ?>" id="discription" />
                    </div>
                </div>
            </div>
            <div class="form-group mb-4">
                <label class="control-label" for="expire_date"><strong>Vencimento MAC</strong></label>
                <div class="input-group">
                    <input type="date" class="form-control" name="expire_date" placeholder="YYYY-MM-DD" id="datetimepicker" value="<?php
                        echo date('Y-m-d', strtotime($expire_date));
                    ?>" />
                </div>
            </div>
          <!-- <div class="form-group mb-4">
                <label class="control-label" for="epg_url"><strong>EPG Url</strong></label>
                <div class="input-group">
                    <input type="text" class="form-control" name="epg_url" placeholder="Digite o EPG do Servidor" value="<?= $epg_url ?>" id="discription" />
                </div>
            </div> -->
            <div class="form-group mb-4">
                <div>
                    <button type="submit" name="submit" class="btn btn-primary mr-2"><i class='bx bx-check mr-1'></i>Atualizar</button>
                </div>
            </div>
    </form>
</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script>
//hide activecode form
      $('.actived').hide(); 
      $('.activeu').show(); 

//Show/hide activecode select
$(document).ready(function(){
  $('.type').change(function(){
    if($('.type').val() < 1) {
      $('.actived').hide(); 
      $('.activeu').show(); 
    }else {
      $('.activeu').hide();
      $('.actived').show();
    } 
  });
});
</script>

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