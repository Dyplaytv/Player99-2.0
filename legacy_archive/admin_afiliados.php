<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    header("location: /login");
    exit;
}

$id = $_SESSION['id'];
$dbUsers = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_users.db");
$query = $dbUsers->query("SELECT admin, username FROM users WHERE id = $id");
$userData = $query ? $query->fetchArray(SQLITE3_ASSOC) : null;
if (!$userData || $userData['admin'] != 1 || $userData['username'] == 'cemet@cemet.com.br') {
    die("Acesso Negado. Apenas administradores podem acessar esta página.");
}

$db = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_clientes.db");

// Garantir que a tabela existe (fallback caso db_financeiro.php não tenha rodado)
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

$msg = '';

// Atualizar porcentagem de comissão
if (isset($_POST['update_percent'])) {
    $userId = intval($_POST['user_id']);
    $newPercent = floatval($_POST['percent']);
    
    $stmt = $db->prepare("UPDATE ibo SET commissionPercent = :percent WHERE id = :id");
    $stmt->bindValue(':percent', $newPercent, SQLITE3_FLOAT);
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    
    $msg = "Porcentagem atualizada com sucesso!";
}

// Registrar pagamento de saque
if (isset($_POST['pay_withdraw'])) {
    $userId = intval($_POST['user_id']);
    
    // Atualiza transações pendentes para pagas
    $stmt = $db->prepare("UPDATE affiliate_transactions SET type = 'PAYOUT', description = 'Saque Pago' WHERE userId = :id AND type = 'WITHDRAW_PENDING'");
    $stmt->bindValue(':id', $userId, SQLITE3_INTEGER);
    $stmt->execute();
    
    $msg = "Pagamento registrado com sucesso!";
}

// Busca pedidos de saque
$saques = [];
$resSaque = $db->query("
    SELECT t.userId, sum(t.amount) as total, i.title, i.pixKey 
    FROM affiliate_transactions t
    JOIN ibo i ON t.userId = i.id
    WHERE t.type = 'WITHDRAW_PENDING'
    GROUP BY t.userId
");
while ($row = $resSaque->fetchArray(SQLITE3_ASSOC)) {
    $saques[] = $row;
}

// Busca lista de afiliados para edição de comissão
$afiliados = [];
$resAfil = $db->query("SELECT id, title, username, affiliateClicks, commissionPercent FROM ibo ORDER BY id DESC LIMIT 100");
while ($row = $resAfil->fetchArray(SQLITE3_ASSOC)) {
    $afiliados[] = $row;
}
?>
<?php include './includes/header.php'; ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="mb-0">Painel de Afiliados (Administrador)</h2>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="login.php" target="_blank" class="btn btn-primary">
            <i class='bx bx-link-external'></i> Acessar Painel do Afiliado
        </a>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo $msg; ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="card radius-10 mb-4 border-warning">
    <div class="card-body">
        <h4 class="card-title text-warning">Pedidos de Saque Pendentes</h4>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome Afiliado</th>
                        <th>Chave PIX</th>
                        <th>Valor Pendente (R$)</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($saques)): ?>
                        <tr><td colspan="5">Nenhum saque pendente.</td></tr>
                    <?php else: ?>
                        <?php foreach ($saques as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['title']); ?></td>
                                <td><strong class="text-info"><?php echo htmlspecialchars($s['pixKey']); ?></strong></td>
                                <td><strong>R$ <?php echo number_format($s['total'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="user_id" value="<?php echo $s['userId']; ?>">
                                        <button type="submit" name="pay_withdraw" class="btn btn-success btn-sm" onclick="return confirm('Confirmar que você já realizou o PIX para o afiliado?')">
                                            <i class="bx bx-check"></i> Registrar Pagamento
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card radius-10">
    <div class="card-body">
        <h4 class="card-title">Gerenciar Porcentagem de Afiliados</h4>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Login</th>
                        <th>Cliques</th>
                        <th>Comissão (%)</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($afiliados as $a): ?>
                        <tr>
                            <td><?php echo $a['id']; ?></td>
                            <td><?php echo htmlspecialchars($a['title']); ?></td>
                            <td><?php echo htmlspecialchars($a['username']); ?></td>
                            <td><?php echo $a['affiliateClicks']; ?></td>
                            <td>
                                <form method="post" class="d-flex align-items-center">
                                    <input type="hidden" name="user_id" value="<?php echo $a['id']; ?>">
                                    <input type="number" step="0.1" class="form-control form-control-sm me-2" style="width: 80px;" name="percent" value="<?php echo $a['commissionPercent']; ?>">
                            </td>
                            <td>
                                    <button type="submit" name="update_percent" class="btn btn-primary btn-sm">Salvar %</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include './includes/footer.php'; ?>
