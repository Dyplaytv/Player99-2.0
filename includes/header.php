<?php
if (file_exists(__DIR__ . '/../error_handler.php')) {
    require_once __DIR__ . '/../error_handler.php';
}
require_once __DIR__ . '/auth_helper.php';
include(__DIR__ . '/functions.php');
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se a variável de sessão "U" está definida
if (!isset($_SESSION["U"])) {
    header("location: login.php");
    exit;
}

// Conexão com o banco de dados de usuários
$dbcheckss = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_users.db");
$username_check = SQLite3::escapeString($_SESSION["U"]);
$rescheckss = $dbcheckss->prepare("SELECT * FROM users WHERE username = :username");

// Conexões para as funções do App (Banners e Customização - herdado do ADMPlay)
$adb = new SQLite3(dirname(__DIR__) . '/api/.adb.db');
$adb3 = new SQLite3(dirname(__DIR__) . '/api/.db_logo.db');
$adb4 = new SQLite3(dirname(__DIR__) . '/api/.db_bg.db');

$rescheckss->bindValue(':username', $username_check, SQLITE3_TEXT);
$resultCheck = $rescheckss->execute();
$rowcheckss = $resultCheck->fetchArray(SQLITE3_ASSOC);

// Verifica se o usuário está autorizado e obtem informações do usuário
if (!$rowcheckss) {
    header("location: login.php");
    exit;
}

// Armazena as informações do usuário em variáveis
$name_header = htmlspecialchars($rowcheckss['name']);
$username_check2 = htmlspecialchars($rowcheckss["username"]);
$logo_header = htmlspecialchars($rowcheckss['logo']);
$nivel_header = intval($rowcheckss['nivel']);
$creditos = intval($rowcheckss['mac_amount']);
$is_root_header = isRootUser($rowcheckss);

// Obtém o ID do usuário da sessão
$id = intval($rowcheckss['id']);

// Fecha a conexão com o banco de dados de usuários
$dbcheckss->close();

// Obtém a parte da URL atual
$dircheck = substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/"));
$dircheck2 = substr($dircheck, 1);

// Cria uma variável para verificar a autorização
$valid = $username_check . $dircheck2;
$nivel_admin = intval($rowcheckss['nivel'] ?? $rowcheckss['admin'] ?? 1);
if (!empty($is_root_header)) { $nivel_admin = 1; }

// Verifica se o usuário está autorizado
if (false && !isset($_SESSION[$valid])) {
    header("location: login.php");
    exit;
}

// Função para inicializar o banco de dados
function initializeDatabase($dbPath, $tableName, $tableStructure) {
    $db = new SQLite3($dbPath);
    $db->exec("CREATE TABLE IF NOT EXISTS $tableName $tableStructure");
    return $db;
}

// Inicializa a tabela 'ibo' no banco de dados 'clientes.db'
$dbans = initializeDatabase(dirname(__DIR__) . "/api/db/studiolivecode_clientes.db", "ibo", "(
    id INTEGER PRIMARY KEY NOT NULL,
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
    whatsapp TEXT DEFAULT ''
)");

// Garante que a coluna whatsapp exista em ibo e migra numeros do whats.json se necessario
try {
    $resColsH = $dbans->query("PRAGMA table_info(ibo)");
    $colsH = [];
    while ($rH = $resColsH->fetchArray(SQLITE3_ASSOC)) {
        $colsH[] = $rH['name'];
    }
    if (!in_array('whatsapp', $colsH)) {
        $dbans->exec("ALTER TABLE ibo ADD COLUMN whatsapp TEXT DEFAULT ''");
    }
    $wFileH = __DIR__ . "/../whats.json";
    if (file_exists($wFileH)) {
        $wDataH = json_decode(file_get_contents($wFileH), true);
        if (is_array($wDataH)) {
            foreach ($wDataH as $wMac => $wInfo) {
                $wNum = trim($wInfo['whatsapp'] ?? '');
                if ($wNum !== '') {
                    $stmtWH = $dbans->prepare("UPDATE ibo SET whatsapp = :w WHERE UPPER(TRIM(mac_address)) = :m AND (whatsapp IS NULL OR whatsapp = '')");
                    $stmtWH->bindValue(':w', $wNum, SQLITE3_TEXT);
                    $stmtWH->bindValue(':m', strtoupper(trim($wMac)), SQLITE3_TEXT);
                    $stmtWH->execute();
                }
            }
        }
    }
} catch (Exception $e) {}

// Inicializa a tabela 'vencimento' no banco de dados 'vencimento.db'
$dbv = initializeDatabase(dirname(__DIR__) . "/api/db/studiolivecode_vencimento.db", "vencimento", "(
    id INTEGER PRIMARY KEY,
    vencimento TEXT
)");

// Verifica se o usuário possui um registro de vencimento
$stmtCheckVencimento = $dbv->prepare("SELECT vencimento FROM vencimento WHERE id = :id");
$stmtCheckVencimento->bindValue(':id', $id, SQLITE3_INTEGER);
$resv = $stmtCheckVencimento->execute();
$rowv = $resv->fetchArray(SQLITE3_ASSOC);

// Apenas lê a data de vencimento, não insere automaticamente
if ($rowv) {
    $vencimento = $rowv["vencimento"];
} else {
    $vencimento = null;
}

// Inicializa a tabela 'theme' no banco de dados 'clientes.db'
$dbans->exec("CREATE TABLE IF NOT EXISTS theme(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100), url VARCHAR(100))");
$rows = $dbans->query("SELECT COUNT(*) as count FROM theme");
$rowt = $rows->fetchArray(SQLITE3_ASSOC);
$numRowst = intval($rowt["count"]);
if ($numRowst == 0) {
    $dbans->exec("INSERT INTO theme(name,url) VALUES('Default','')");
}

// Inicializa a tabela 'whatsapp_templates'
$dbans->exec("CREATE TABLE IF NOT EXISTS whatsapp_templates(
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    tipo VARCHAR(50), 
    dias INTEGER, 
    hora VARCHAR(10), 
    mensagem TEXT, 
    ativo INTEGER DEFAULT 0
)");

// Contagens de MACs
$mac_count = intval($dbans->querySingle("SELECT COUNT(*) as count FROM ibo WHERE id_user = $id"));
$mac_used_by_revenda = $mac_count;
$mac_available = $creditos - $mac_used_by_revenda;

$all_mac_count = intval($dbans->querySingle("SELECT COUNT(*) as count FROM ibo"));
$test_mac_count = intval($dbans->querySingle("SELECT COUNT(*) as count FROM ibo WHERE active = 0"));

// Conexão adicional com o banco de dados de usuários para obter informações específicas
$dbpans = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_users.db");
$countQuery = "SELECT COUNT(*) as count FROM users WHERE store_type = 1";
$rev_count = intval($dbpans->querySingle($countQuery));
$resans = $dbpans->prepare("SELECT * FROM users WHERE id = :id");
$resans->bindValue(':id', $id, SQLITE3_INTEGER);
$resultAns = $resans->execute();
$rowans = $resultAns->fetchArray(SQLITE3_ASSOC);
$nameans = htmlspecialchars($rowans["name"]);
$logoans = htmlspecialchars($rowans["logo"]);
$nivel_admin = intval($rowans["admin"]);
?>

<!doctype html>
<html lang="en" class="color-sidebar dark-theme">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="./banners/logo.png" type="image/png" />
    <link href="assets/plugins/simplebar/css/simplebar.css" rel="stylesheet" />
    <link href="assets/plugins/perfect-scrollbar/css/perfect-scrollbar.css" rel="stylesheet" />
    <link href="assets/plugins/metismenu/css/metisMenu.min.css" rel="stylesheet" />
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="assets/css/pace.min.css" rel="stylesheet" />
    <script src="assets/js/pace.min.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap-extended.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="assets/css/geral.css" rel="stylesheet">
    <link href="assets/css/icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tema-escuro.css" />
    <link rel="stylesheet" href="assets/css/semi-dark.css" />
    <link rel="stylesheet" href="assets/css/cores-do-menu.css" />
    <link rel="stylesheet" href="assets/css/apps_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-default/default.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <title>Player 99</title>
    <meta content="./banners/logo.png" property="og:image" />
<style>
    .blink {
        animation: blinker 1s linear infinite;
        color: red !important;
    }
    @keyframes blinker {
        50% {
            opacity: 0;
        }
    }
    .menu-label.neon-btn {
        display: block !important;
        margin: 10px 15px !important;
        padding: 10px !important;
        text-align: center !important;
        border-radius: 8px !important;
        background: #0f172a !important;
        color: #00f3ff !important;
        border: 1px solid #00f3ff !important;
        box-shadow: 0 0 5px #00f3ff, 0 0 10px #00f3ff inset !important;
        cursor: pointer !important;
        transition: all 0.3s ease !important;
        text-transform: uppercase !important;
        font-weight: bold !important;
        letter-spacing: 1px !important;
    }
    .menu-label.neon-btn:hover {
        background: #00f3ff !important;
        color: #000 !important;
        box-shadow: 0 0 15px #00f3ff, 0 0 20px #00f3ff inset !important;
    }
</style>
</head>

<body>
    <div class="wrapper">
        <div class="sidebar-wrapper" data-simplebar="true">
            <div class="sidebar-header">
                <a href="estatisticas.php" style="display: flex; align-items: center; text-decoration: none; color: inherit;">
                    <div>
                        <img src="./banners/logo.png" class="logo-icon" alt="logo icon">
                    </div>
                    <div>
                        <h4 class="logo-text">Player 99</h4>
                    </div>
                </a>
                <div class="toggle-icon ms-auto"><i class='bx bx-menu'></i>
                </div>
            </div>
            <ul class="metismenu" id="menu">
                <?php if (!empty($is_root_header)) { ?>
                    <li class="menu-label text-danger font-weight-bold" style="letter-spacing: 1px;"><i class='bx bx-shield-quarter'></i> ÁREA ROOT</li>
                    <li>
                        <a href="configuracoes_root.php" style="border-left: 3px solid #ff4e50;">
                            <div class="parent-icon"><i class='bx bx-slider-alt text-danger'></i></div>
                            <div class="menu-title text-danger font-weight-bold">Configurações Gerais</div>
                        </a>
                    </li>
                <?php } ?>
                <li class="menu-label neon-btn">SISTEMA</li>
                <?php if ($nivel_admin == 1) { ?>
                    <li>
                        <a href="estatisticas.php">
                            <div class="parent-icon"><i class='bx bx-play'></i></div>
                            <div class="menu-title">Estatisticas</div>
                        </a>
                    </li>
                    <li>
                        <a href="all_users.php">
                            <div class="parent-icon"><i class='bx bxs-user-account'></i></div>
                            <div class="menu-title">Clientes geral (<?php echo $all_mac_count; ?>)</div>
                        </a>
                    </li>
                    <li>
                        <a href="config_whatsapp.php">
                            <div class="parent-icon"><i class='bx bxl-whatsapp'></i></div>
                            <div class="menu-title">Configurar WhatsApp</div>
                        </a>
                    </li>
                    <li>
                        <a href="config_whatsapp_mensagens.php">
                            <div class="parent-icon"><i class='bx bx-message-dots'></i></div>
                            <div class="menu-title">Mensagens WhatsApp</div>
                        </a>
                    </li>
                    <li>
                        <a href="admin_logs.php">
                            <div class="parent-icon"><i class='bx bx-bug'></i></div>
                            <div class="menu-title">Logs de Erro</div>
                        </a>
                    </li>
                <?php } ?>
                <li>
                    <a href="login_cliente.php" target="_blank" rel="noopener noreferrer">
                        <div class="parent-icon"><i class='bx bx-user-circle'></i></div>
                        <div class="menu-title">Área do Cliente</div>
                    </a>
                </li>
                <li>
                    <a href="./api/mercado_pago_gold/consulta.php" target="_blank" rel="noopener noreferrer">
                        <div class="parent-icon"><i class='bx bx-dollar'></i></div>
                        <div class="menu-title">Link Renovação</div>
                    </a>
                </li>
                <?php if ($nivel_admin == 1) { ?>

                    <?php if ($username_check2 !== 'cemet@cemet.com.br') { ?>
                    <li>
                        <a href="mercado_pago_gold.php">
                            <div class="parent-icon"><i class='bx bx-diamond'></i></div>
                            <div class="menu-title">Mercado Pago Gold</div>
                        </a>
                    </li>
                    <?php } ?>
                    <li>
                        <a href="admin_faturamento.php">
                            <div class="parent-icon"><i class='bx bx-wallet'></i></div>
                            <div class="menu-title">Faturamento</div>
                        </a>
                    </li>

                    <li>
                        <a href="adm_radios.php">
                            <div class="parent-icon"><i class='bx bx-radio'></i></div>
                            <div class="menu-title">Gerenciar Rádios</div>
                        </a>
                    </li>
                    <li>
                        <a href="logo.php">
                            <div class="parent-icon"><i class='bx bx-image'></i></div>
                            <div class="menu-title">Logo do Sistema</div>
                        </a>
                    </li>
                    <?php if ($username_check2 !== 'cemet@cemet.com.br') { ?>
                    <li>
                        <a href="admin_afiliados.php">
                            <div class="parent-icon"><i class='bx bx-money'></i></div>
                            <div class="menu-title">Sistema de Afiliados</div>
                        </a>
                    </li>
                    <?php } ?>
                    <li>
                        <a href="admin_entretenimentos.php">
                            <div class="parent-icon"><i class='bx bx-news'></i></div>
                            <div class="menu-title">Categorias (RSS)</div>
                        </a>
                    </li>
                    <li>
                        <a href="admin_landing.php">
                            <div class="parent-icon"><i class='bx bx-window-alt'></i></div>
                            <div class="menu-title">Edit Pag Inicial</div>
                        </a>
                    </li>
                <?php } ?>
                <li>
                    <a href="qrcode.php">
                        <div class="parent-icon"><i class='bx bx-qr-scan'></i></div>
                        <div class="menu-title">Gerar QR Code</div>
                    </a>
                </li>
                <?php if ($nivel_admin == 1) { ?>
                    <li>
                        <a href="admplay_bloqueio.php">
                            <div class="parent-icon"><i class='bx bx-block'></i></div>
                            <div class="menu-title">Tela de Bloqueio</div>
                        </a>
                    </li>
                    <li>
                        <a href="admin_loja.php">
                            <div class="parent-icon"><i class='bx bx-store'></i></div>
                            <div class="menu-title">Minha Loja</div>
                        </a>
                    </li>
                <?php } ?>


                <li class="menu-label neon-btn">GERAL</li>
                <li>
                    <a href="usuario.php">
                        <div class="parent-icon"><i class='bx bx-user'></i></div>
                        <div class="menu-title">Editar Perfil</div>
                    </a>
                </li>
                <?php if ($nivel_header == 1) { ?>
                    <li>
                        <a href="usuarios.php">
                            <div class="parent-icon"><i class='bx bx-user'></i></div>
                            <div class="menu-title">Usuários</div>
                        </a>
                    </li>
                <?php } ?>
                
                <?php if ($nivel_header == 1) { ?>
                    <li class="menu-label">VENCIMENTO</li>
                    <li>
                        <a href="admins.php">
                            <div class="parent-icon"><i class="bx bxs-user-account"></i></div>
                            <div class="menu-title">Vencimento admin</div>
                        </a>
                    </li>
                    <li>
                        <a href="vencimento.php">
                            <div class="parent-icon"><i class='bx bx-hourglass'></i></div>
                            <div class="menu-title">Vencimento</div>
                        </a>
                    </li>
                <?php } ?>

                <li>
                    <a href="sair.php">
                        <div class="parent-icon"><i class='bx bx-exit'></i></div>
                        <div class="menu-title">Sair</div>
                    </a>
                </li>
            </ul>
        </div>
        <header>
            <div class="topbar d-flex align-items-center">
                <nav class="navbar navbar-expand">
                    <div class="mobile-toggle-menu"><i class='bx bx-menu'></i>
                    </div>
                    <div class="marquee">
                        <div class="marquee-content">
                            <span id="vencimento-text"></span>
                        </div>
                    </div>
                    <div class="top-menu ms-auto">
                        <ul class="navbar-nav align-items-center">
                            <li class="nav-item dropdown dropdown-large">
                                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false"> <i class='bx bx-category'></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="row row-cols-3 g-3 p-3">
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown dropdown-large">
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="header-notifications-list">
                                    </div>
                                    <a href="javascript:;">
                                        <div class="text-center msg-footer">View All Notifications</div>
                                    </a>
                                </div>
                            </li>
                            <li class="nav-item dropdown dropdown-large">
                                <div class="dropdown-menu dropdown-menu-end">
                                    <div class="header-message-list">
                                    </div>
                                    <a href="javascript:;">
                                        <div class="text-center msg-footer">View All Messages</div>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </header>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Esconder todos os itens do menu inicialmente
        $('.menu-label.neon-btn').nextUntil('.menu-label').hide();

        // Ação de clique para abrir/fechar
        $('.menu-label.neon-btn').click(function() {
            $(this).nextUntil('.menu-label').slideToggle(300);
        });

        var vencimentoText = document.getElementById('vencimento-text');
        <?php
        if ($nivel_admin == 1) {
            echo 'vencimentoText.innerHTML = "Olá Admin... Player 99";';
        } else {
            $nomeRevenda = $name_header;
            $macsTotais = $creditos;
            $macsUsados = $mac_used_by_revenda;
            $macsDisponiveis = $mac_available;

            if ($vencimento) {
                $dataAtual = new DateTime();
                $dataVencimento = new DateTime($vencimento);
                $diasRestantes = $dataAtual->diff($dataVencimento)->format('%r%a');
                
                $mensagemVencimento = "";
                if ($diasRestantes <= -1) {
                    $mensagemVencimento = "Seu painel está BLOQUEADO! Renove Agora!";
                } else {
                    $mensagemVencimento = "Seu acesso vence em: " . $diasRestantes . " dias!";
                }
                
                if ($diasRestantes > -1 && $diasRestantes <= 5) {
                    echo 'vencimentoText.classList.add("blink");';
                }
                echo 'vencimentoText.innerHTML = "Olá ' . $nomeRevenda . ', ' . $mensagemVencimento . ' - MACs Totais: ' . $macsTotais . ' -- MACs Disponíveis: ' . $macsDisponiveis . '";';
            } else {
                echo 'vencimentoText.innerHTML = "Olá ' . $nomeRevenda . ', Player 99 - MACs Totais: ' . $macsTotais . '             - MACs Disponíveis: ' . $macsDisponiveis . '";';
            }
        }
        ?>
    });
</script>
        <div class="page-wrapper">
            <div class="page-content">
            </div>
<?php
ob_end_flush();
?>
