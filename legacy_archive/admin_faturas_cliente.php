<?php
session_start();
if (!isset($_SESSION['id'])) {
    header('location: painel.php');
    exit;
}

require_once './includes/db_financeiro.php';
require_once './includes/db_cliente.php';

$id_admin = $_SESSION['id'];
$db1 = new SQLite3("./api/db/studiolivecode_users.db");
$query = $db1->prepare("SELECT admin FROM users WHERE id = :id");
$query->bindValue(':id', $id_admin, SQLITE3_INTEGER);
$userData = $query->execute()->fetchArray(SQLITE3_ASSOC);

if (!$userData || $userData['admin'] != 1) {
    header("Location: users.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: all_users.php");
    exit;
}

$id_cliente = (int)$_GET['id'];

// Carrega dados do cliente
$stmtCliente = $dbFinanceiro->prepare("SELECT title, username, expire_date FROM ibo WHERE id = :id");
$stmtCliente->bindValue(':id', $id_cliente, SQLITE3_INTEGER);
$clienteInfo = $stmtCliente->execute()->fetchArray(SQLITE3_ASSOC);

if (!$clienteInfo) {
    header("Location: all_users.php");
    exit;
}

// Ação de Baixa Manual
if (isset($_POST['marcar_paga'])) {
    $id_fatura = (int)$_POST['id_fatura'];
    
    // Atualiza a fatura
    $dbFinanceiro->exec("UPDATE faturas SET status = 'PAGA' WHERE id = $id_fatura AND id_cliente = $id_cliente");
    
    // Lógica para estender o acesso (Ciclo até o dia 8)
    $hoje = new DateTime();
    $diaAtual = (int)$hoje->format('d');
    $mesAtual = (int)$hoje->format('m');
    $anoAtual = (int)$hoje->format('Y');
    
    if ($diaAtual >= 5) {
        $vencimentoAcesso = new DateTime("$anoAtual-$mesAtual-08");
        $vencimentoAcesso->modify('+1 month');
    } else {
        $vencimentoAcesso = new DateTime("$anoAtual-$mesAtual-08");
    }
    $new_expire_date = $vencimentoAcesso->format('Y-m-d');
    
    $dbFinanceiro->exec("UPDATE ibo SET expire_date = '$new_expire_date', active = 1 WHERE id = $id_cliente");
    
    header("Location: admin_faturas_cliente.php?id=$id_cliente&msg=paga");
    exit;
}

// Ação para apagar fatura caso tenha sido gerada errada
if (isset($_POST['apagar_fatura'])) {
    $id_fatura = (int)$_POST['id_fatura'];
    $dbFinanceiro->exec("DELETE FROM faturas WHERE id = $id_fatura AND id_cliente = $id_cliente");
    header("Location: admin_faturas_cliente.php?id=$id_cliente&msg=apagada");
    exit;
}

// Busca as faturas
$faturas = [];
$resFaturas = $dbFinanceiro->query("SELECT * FROM faturas WHERE id_cliente = $id_cliente ORDER BY id DESC");
while ($row = $resFaturas->fetchArray(SQLITE3_ASSOC)) {
    $faturas[] = $row;
}

include './includes/header.php';
?>

<div class="page-wrapper">
    <div class="page-content">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
            <div class="breadcrumb-title pe-3">Modo Espião: Faturas</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="all_users.php"><i class="bx bx-home-alt"></i> Usuários</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($clienteInfo['title']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'paga'): ?>
            <div class="alert alert-success border-0 bg-success alert-dismissible fade show py-2">
                <div class="d-flex align-items-center">
                    <div class="font-35 text-white"><i class='bx bxs-check-circle'></i></div>
                    <div class="ms-3">
                        <h6 class="mb-0 text-white">Sucesso</h6>
                        <div class="text-white">A fatura foi dada baixa e o acesso do cliente foi estendido para <?php echo date('d/m/Y', strtotime($clienteInfo['expire_date'])); ?>!</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['msg']) && $_GET['msg'] == 'apagada'): ?>
            <div class="alert alert-info border-0 bg-info alert-dismissible fade show py-2">
                <div class="d-flex align-items-center">
                    <div class="font-35 text-white"><i class='bx bxs-trash'></i></div>
                    <div class="ms-3">
                        <h6 class="mb-0 text-white">Apagada</h6>
                        <div class="text-white">A fatura foi excluída do sistema.</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card border-top border-0 border-4 border-primary">
            <div class="card-body p-5">
                <div class="card-title d-flex align-items-center">
                    <div><i class="bx bx-receipt me-1 font-22 text-primary"></i></div>
                    <h5 class="mb-0 text-primary">Gerenciar Faturas de: <?php echo htmlspecialchars($clienteInfo['title']); ?> (<?php echo htmlspecialchars($clienteInfo['username']); ?>)</h5>
                </div>
                <hr>

                <div class="table-responsive mt-4">
                    <table class="table table-hover table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Gerada em</th>
                                <th>Vencimento</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Ações (Modo Espião)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($faturas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">Nenhuma fatura encontrada para este cliente.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($faturas as $f): ?>
                                    <tr>
                                        <td><?php echo $f['id']; ?></td>
                                        <td data-sort="<?php echo $f['data_gerada']; ?>"><?php echo date('d/m/Y', strtotime($f['data_gerada'])); ?></td>
                                        <td data-sort="<?php echo $f['data_vencimento']; ?>"><?php echo date('d/m/Y', strtotime($f['data_vencimento'])); ?></td>
                                        <td>R$ <?php echo number_format($f['valor_total'], 2, ',', '.'); ?></td>
                                        <td>
                                            <?php if ($f['status'] == 'PAGA'): ?>
                                                <span class="badge bg-success">PAGA</span>
                                            <?php elseif ($f['status'] == 'VENCIDA'): ?>
                                                <span class="badge bg-danger">VENCIDA</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">PENDENTE</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <?php if ($f['status'] != 'PAGA'): ?>
                                                    <form method="POST" style="margin:0;">
                                                        <input type="hidden" name="id_fatura" value="<?php echo $f['id']; ?>">
                                                        <button type="submit" name="marcar_paga" class="btn btn-sm btn-success" title="Dar Baixa Manual" onclick="return confirm('Deseja dar baixa manual e ativar o acesso desse cliente?');">
                                                            <i class='bx bx-check-double'></i> Recebi em Mãos
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-outline-secondary" disabled>Já Paga</button>
                                                <?php endif; ?>
                                                
                                                <form method="POST" style="margin:0;">
                                                    <input type="hidden" name="id_fatura" value="<?php echo $f['id']; ?>">
                                                    <button type="submit" name="apagar_fatura" class="btn btn-sm btn-danger" title="Apagar Fatura" onclick="return confirm('Tem certeza que deseja apagar esta fatura do sistema?');">
                                                        <i class='bx bxs-trash'></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    <a href="all_users.php" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Voltar para Usuários</a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include './includes/footer.php'; ?>
