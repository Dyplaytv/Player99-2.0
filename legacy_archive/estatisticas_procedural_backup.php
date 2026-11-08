<?php
// Conexão com banco de clientes
$db_clientes = new SQLite3('./api/db/studiolivecode_clientes.db');

// Handle renewal
if (isset($_GET['renew'])) {
    $id = intval($_GET['renew']);
    $maxRetries = 5;
    $retry = 0;
    $success = false;
    while ($retry < $maxRetries) {
        // Fetch current expire_date
        $stmt_fetch = $db_clientes->prepare("SELECT expire_date FROM ibo WHERE id = :id");
        $stmt_fetch->bindValue(':id', $id, SQLITE3_INTEGER);
        $result_fetch = $stmt_fetch->execute();
        $row = $result_fetch->fetchArray(SQLITE3_ASSOC);
        if (!$row) {
            break; // No client found
        }
        $expire_date = $row['expire_date'];
        $today = date('Y-m-d');
        $base_date = ($expire_date < $today) ? $today : $expire_date;

        // Update
        $stmt = $db_clientes->prepare("UPDATE ibo SET expire_date = date(:base_date, '+30 days') WHERE id = :id");
        $stmt->bindValue(':base_date', $base_date, SQLITE3_TEXT);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        if ($result) {
            $success = true;
            break;
        } else if ($db_clientes->lastErrorCode() == 5) { // SQLITE_BUSY
            usleep(100000); // 0.1 sec
            $retry++;
        } else {
            break;
        }
    }
    if ($success) {
        header("Location: " . basename(__FILE__));
        exit;
    } else {
        // Handle error without output if possible, or redirect with error param
        header("Location: " . basename(__FILE__) . "?error=renew_failed");
        exit;
    }
}

include ('./includes/header.php');

// --- Otimização: Funções para organizar o código ---
function getClientes(SQLite3 $db, string $condition): array
{
    $query = "SELECT * FROM ibo WHERE {$condition} ORDER BY expire_date ASC";
    $result = $db->query($query);
    $data = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $data[] = $row;
    }
    return $data;
}

// Total de clientes
$totalClientes = $db_clientes->querySingle("SELECT COUNT(*) FROM ibo");

// Datas para consulta
$hoje = date('Y-m-d');
$cincoDias = date('Y-m-d', strtotime('+5 days'));

// Clientes vencendo e vencidos
$clientesVencendoArr = getClientes($db_clientes, "expire_date BETWEEN '{$hoje}' AND '{$cincoDias}'");
$clientesVencidosArr = getClientes($db_clientes, "expire_date < '{$hoje}'");

$clientesVencendoCount = count($clientesVencendoArr);
$clientesVencidosCount = count($clientesVencidosArr);

// Combina clientes para a tabela de cobrança
$clientesCobrarArr = array_merge($clientesVencendoArr, $clientesVencidosArr);

// --- Otimização: Carrega os dados do WhatsApp uma única vez ---
$whatsData = [];
$whatsPath = __DIR__ . '/whats.json';
if (file_exists($whatsPath)) {
    $whatsData = json_decode(file_get_contents($whatsPath), true) ?? [];
    
    // --- Carrega os envios realizados ---
$enviosPath = __DIR__ . '/clientes_vencidos.json';
$enviosData = file_exists($enviosPath) ? json_decode(file_get_contents($enviosPath), true) : [];

}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap');

:root {
    --bg-color: #0d1117;
    --card-bg: #161b22;
    --border-color: #30363d;
    --text-color: #c9d1d9;
    --text-muted: #8b949e;
    --green-border: #22c55e;
    --yellow-border: #f59e0b;
    --red-border: #ef4444;
}

/* === BODY === */
body, .content-page, .content, .container-fluid {
    background-color: var(--bg-color);
    color: var(--text-color) !important;
    font-family: 'Poppins', sans-serif;
}

/* === LIMITA A LARGURA DO CONTAINER === */
.container-fluid {
    max-width: 800px;
    margin: 0 auto;
}

/* === CARDS === */
.card {
    background: var(--card-bg);
    color: var(--text-color) !important;
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 15px;
    text-align: center;
    overflow: hidden;
    position: relative;
    box-shadow: 0 25px 40px rgba(0,0,0,0.5);
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.col-lg-4:nth-child(1) .card { animation-delay: 0.1s; }
.col-lg-4:nth-child(2) .card { animation-delay: 0.2s; }
.col-lg-4:nth-child(3) .card { animation-delay: 0.3s; }

.card-total { border-color: var(--green-border); }
.card-vencendo { border-color: var(--yellow-border); }
.card-vencidos { border-color: var(--red-border); }

.card-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-muted);
    margin-bottom: 10px;
}

.card-icon { font-size: 1rem; margin-bottom: 10px; opacity: 0.8; }
.card-total .card-icon { color: var(--green-border); }
.card-vencendo .card-icon { color: var(--yellow-border); }
.card-vencidos .card-icon { color: var(--red-border); }

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
}

/* === TABELA RESPONSIVA === */
.table-responsive-stack {
    width: 100%;
}
.table-responsive-stack thead { display: none; }
.table-responsive-stack td {
    display: block;
    width: 100%;
    text-align: right;
    padding: 10px;
    border-bottom: 1px solid var(--border-color);
    position: relative;
}
.table-responsive-stack td:last-child { border-bottom: 0; }
.table-responsive-stack td::before {
    content: attr(data-label);
    float: left;
    font-weight: 600;
    color: var(--text-muted);
}
.table-responsive-stack tr {
    display: block;
    background: var(--card-bg);
    border-radius: 8px;
    margin-bottom: 15px;
    border: 1px solid var(--border-color);
}

/* === BOTÕES === */
.btn-cobrar {
    background-color: #25d366;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: 6px;
    padding: 4px 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 0 5px #25d366, 0 0 10px #25d36633;
    position: relative;
    z-index: 1;
}
.btn-cobrar:hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px #25d366, 0 0 20px #25d36655;
}

.btn-renovar {
    background-color: #007bff;
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
    border-radius: 6px;
    padding: 4px 8px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 0 5px #007bff, 0 0 10px #007bff33;
    position: relative;
    z-index: 1;
    margin-left: 10px;
}
.btn-renovar:hover {
    transform: scale(1.05);
    box-shadow: 0 0 10px #007bff, 0 0 20px #007bff55;
}

/* === ESTILOS PARA TELAS GRANDES === */
@media (min-width: 768px) {
    .table-responsive-stack thead { display: table-header-group; }
    .table-responsive-stack tr { display: table-row; background: transparent; border: none; margin-bottom: 0; }
    .table-responsive-stack td, .table-responsive-stack th {
        display: table-cell;
        padding: 0.8rem;
        vertical-align: middle;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    .table-responsive-stack th {
        color: var(--text-muted);
        font-weight: 600;
    }
    .table-responsive-stack td::before { display: none; }
    .table-responsive-stack th:nth-child(1), .table-responsive-stack td:nth-child(1) { width: 30%; }
    .table-responsive-stack th:nth-child(2), .table-responsive-stack td:nth-child(2) { width: 20%; }
    .table-responsive-stack th:nth-child(3), .table-responsive-stack td:nth-child(3) { width: 25%; }
    .table-responsive-stack th:nth-child(4), .table-responsive-stack td:nth-child(4) {
        width: 25%;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: nowrap;
    }
    .btn-renovar { margin-left: 0; }
}

/* === ESTILOS PARA TELAS PEQUENAS === */
@media (max-width: 767px) {
    .table-responsive-stack td { text-align: center !important; }
    .table-responsive-stack td::before { width: 100%; text-align: center; float: none; display: block; margin-bottom: 5px; }
    .table-responsive-stack td[data-label="Ação"] {
        display: flex;
        justify-content: center;
        align-items: center;
        flex-wrap: nowrap;
        gap: 10px;
    }
    .btn-cobrar, .btn-renovar {
        width: auto;
        display: inline-flex;
        justify-content: center;
        margin: 0;
    }
    .btn-renovar { margin-left: 0; }
    .table-responsive-stack td[data-label="Ação"] span { margin-right: 10px; }
}
</style>


<div class="content-page">
    <div class="content">
        <div class="container-fluid" style="max-width: 1000px; margin: 0 auto;">

            <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Erro ao renovar: Banco de dados bloqueado ou outro erro.</div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4 col-md-12"><div class="card card-total"><div class="card-icon"><i class='bx bxs-user-detail'></i></div><h5 class="card-title">Total de Clientes</h5><div class="stat-number"><?= $totalClientes ?></div></div></div>
                <div class="col-lg-4 col-md-12"><div class="card card-vencendo"><div class="card-icon"><i class='bx bxs-time-five'></i></div><h5 class="card-title">Vencendo em 5 dias</h5><div class="stat-number"><?= $clientesVencendoCount ?></div></div></div>
                <div class="col-lg-4 col-md-12"><div class="card card-vencidos"><div class="card-icon"><i class='bx bxs-error-circle'></i></div><h5 class="card-title">Clientes Vencidos</h5><div class="stat-number"><?= $clientesVencidosCount ?></div></div></div>
            </div>


<div class="row mt-4 mb-4">
    <div class="col-12">
        <div class="card" style="padding: 20px; text-align: center;">

            <p style="margin-bottom: 22px; font-size: 0.95rem;">
                ✅ Sistema envia <strong style="color:red;">cobranças automaticamente</strong> todos os dias às 14h.<br>
                🚀 Para COBRAR AGORA:
            </p>
            <ol style="text-align: left; margin: 0 auto 20px auto; max-width: 600px; color: var(--text-muted);">
                <li>Clique em <strong style="color:blue;">Atualizar Lista</strong> para gerar a lista atualizada.</li>
                <li>Depois clique em <strong style="color:green;">Cobrar Agora</strong> para enviar as mensagens.</li>
            </ol>
            <div class="d-flex justify-content-center gap-3">
                <button id="btn-atualizar-vencidos" class="btn btn-primary" style="position: relative;">
                    <span class="btn-text">1️⃣ Atualizar Lista</span>
                    <span class="spinner-border spinner-border-sm" role="status" style="display:none; position: absolute; right:10px;"></span>
                </button>

                <button id="btn-enviar-cobrancas" class="btn btn-success" style="position: relative;">
                    <span class="btn-text">2️⃣ Cobrar Agora</span>
                    <span class="spinner-border spinner-border-sm" role="status" style="display:none; position: absolute; right:10px;"></span>
                </button>
            </div>
        </div>
    </div>
</div>




            <?php if (count($clientesCobrarArr) > 0): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card" style="animation-delay: 0.5s; text-align: left; padding: 20px;">
                        <h5 class="card-title" style="font-size: 1rem; margin-bottom: 20px;">Clientes Vencendo 05 dias:</h5>
                        
                        <table class="table-responsive-stack">
                            <thead>
                                <tr>
                                    <th scope="col">Cliente</th>
                                    <th scope="col">Vencimento</th>
                                    <th scope="col">MAC</th>
                                    <th scope="col">Cobrança</th>
                                    <th scope="col">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clientesCobrarArr as $cliente): ?>
<?php
    $mac = strtoupper($cliente['mac_address']); // chave do JSON é o MAC
    $whats = !empty($cliente['whatsapp']) ? $cliente['whatsapp'] : ($whatsData[$mac]['whatsapp'] ?? '');
    $urlWhats = '';
    if (!empty($whats)) {
        $message = "Olá " . $cliente['title'] . ",\nSua assinatura vence em " . date('d/m/Y', strtotime($cliente['expire_date'])) . ".\n\nPara não perder o acesso, renove agora no link abaixo:\n" . $cliente['link_payment'];
        $urlWhats = "https://wa.me/" . preg_replace('/\D/', '', $whats) . "?text=" . urlencode($message);
    }
?>

                                <tr>
                                    <td data-label="Cliente"><?= htmlspecialchars($cliente['title']) ?></td>
                                    <td data-label="Vencimento" data-sort="<?= $cliente['expire_date'] ?>"><?= date('d/m/Y', strtotime($cliente['expire_date'])) ?></td>
                                    <td data-label="MAC"><?= htmlspecialchars($cliente['mac_address']) ?></td>
<td data-label="Cobrança">
<?php
$mac = $cliente['mac_address'];

// --- Carrega os envios realizados do clientes_vencidos.json ---
$enviosPath = __DIR__ . '/clientes_vencidos.json';
$enviosData = file_exists($enviosPath) ? json_decode(file_get_contents($enviosPath), true) : [];

// Garantir que $enviosData seja sempre array
if (!is_array($enviosData)) {
    $enviosData = [];
}

$envio = $enviosData[$mac] ?? null;

if ($envio && isset($envio['enviado_em']) && !empty($envio['enviado_em'])) {
    $timestamp = strtotime($envio['enviado_em']);
    if ($timestamp !== false) {
        echo "✅ " . date('d/m/Y H:i', $timestamp);
    } else {
        echo "✅ —";
    }
} else {
    echo "—";
}
?>
</td>

<td data-label="Ação">
    <?php if (!empty($whats)): ?>
    <button type="button" class="btn btn-cobrar btn-cobrar-avulso" data-id="<?= $cliente['id'] ?>">
        <i class="bx bxl-whatsapp"></i> Cobrar
    </button>
    <?php else: ?>
    <span style="color:var(--text-muted);">Sem Whats</span>
    <?php endif; ?>
    <a href="?renew=<?= $cliente['id'] ?>" class="btn btn-renovar">
        Renovar
    </a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
<?php endif; ?>
</div>
</div>
</div>
<script>
document.getElementById('btn-atualizar-vencidos').addEventListener('click', function() {
    const btn = this;
    const spinner = btn.querySelector('.spinner-border');
    spinner.style.display = 'inline-block';
    btn.querySelector('.btn-text').textContent = 'Atualizando...';
    
    fetch('gerar_vencidos.php')
    .then(response => response.text())
    .then(() => {
        setTimeout(() => {
            spinner.style.display = 'none';
            btn.querySelector('.btn-text').textContent = 'Lista de vencidos Atualizada com sucesso!';
        }, 5000); // 3 segundos de “animação”
    });
});

document.getElementById('btn-enviar-cobrancas').addEventListener('click', function() {
    const btn = this;
    const spinner = btn.querySelector('.spinner-border');
    spinner.style.display = 'inline-block';
    btn.querySelector('.btn-text').textContent = 'Enviando...';
    
    fetch('enviar_cobrancas.php')
    .then(response => response.text())
    .then(() => {
        setTimeout(() => {
            spinner.style.display = 'none';
            btn.querySelector('.btn-text').textContent = 'Cobranças enviadas com sucesso!';
        }, 5000); // 3 segundos de “animação”
    });
});

$(document).ready(function() {
    $('.btn-cobrar-avulso').on('click', function() {
        var btn = $(this);
        var id = btn.data('id');
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="bx bx-loader bx-spin"></i>');
        
        $.post('api/whatsapp_avulso.php', {id: id}, function(res) {
            btn.prop('disabled', false).html(originalText);
            const Toast = Swal.mixin({toast: true, position: 'bottom', showConfirmButton: false, timer: 3000});
            if(res.success) {
                Toast.fire({icon: 'success', title: 'Cobrança enviada com sucesso!'});
            } else {
                Toast.fire({icon: 'error', title: 'Erro: ' + res.error});
            }
        }, 'json').fail(function() {
            btn.prop('disabled', false).html(originalText);
            alert('Erro de comunicação com o servidor.');
        });
    });
});
</script>

<?php include ('./includes/footer.php'); ?>
