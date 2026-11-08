<?php
require_once dirname(__DIR__) . '/includes/db_financeiro.php';

// Proteção de Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id'])) {
    header('location: /login');
    exit;
}
$id = $_SESSION['id'];
$db1 = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_users.db");
$query = $db1->prepare("SELECT admin, username FROM users WHERE id = :id");
$query->bindValue(':id', $id, SQLITE3_INTEGER);
$userData = $query->execute()->fetchArray(SQLITE3_ASSOC);
$isAllowed = !empty($_SESSION['is_admin']) || ($userData && $userData['admin'] == 1);
if (!$isAllowed) {
    header("Location: /admin/dashboard");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_razao = $_POST['nome_razao'] ?? '';
    $cpf_cnpj = $_POST['cpf_cnpj'] ?? '';
    $endereco = $_POST['endereco'] ?? '';
    $whats = $_POST['whats'] ?? '';
    $chave_pix = $_POST['chave_pix'] ?? '';

    // Como é apenas 1 registro global para a empresa, vamos forçar id=1
    $stmt = $dbFinanceiro->prepare("INSERT OR REPLACE INTO faturamento_admin (id, nome_razao, cpf_cnpj, endereco, whats, chave_pix) VALUES (1, :nome, :cpf, :end, :wts, :pix)");
    $stmt->bindValue(':nome', $nome_razao, SQLITE3_TEXT);
    $stmt->bindValue(':cpf', $cpf_cnpj, SQLITE3_TEXT);
    $stmt->bindValue(':end', $endereco, SQLITE3_TEXT);
    $stmt->bindValue(':wts', $whats, SQLITE3_TEXT);
    $stmt->bindValue(':pix', $chave_pix, SQLITE3_TEXT);
    $stmt->execute();
    
    $msg = "<div class='alert alert-success'>Dados de faturamento salvos com sucesso!</div>";
}

$faturamentoAdmin = [
    'nome_razao' => '',
    'cpf_cnpj' => '',
    'endereco' => '',
    'whats' => '',
    'chave_pix' => ''
];
$resFat = $dbFinanceiro->query("SELECT * FROM faturamento_admin WHERE id = 1");
if ($rowFat = $resFat->fetchArray(SQLITE3_ASSOC)) {
    $faturamentoAdmin = $rowFat;
}

?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configurações de Faturamento - Admin</title>
    <?php include './includes/header.php'; ?>
</head>
<body class="bg-theme bg-theme1">
    <div class="wrapper">
        <div class="page-wrapper">
            <div class="page-content">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <div class="breadcrumb-title pe-3">Financeiro</div>
                    <div class="ps-3">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-0 p-0">
                                <li class="breadcrumb-item"><a href="all_users.php"><i class="bx bx-home-alt"></i></a></li>
                                <li class="breadcrumb-item active" aria-current="page">Faturamento da Empresa</li>
                            </ol>
                        </nav>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-9 mx-auto">
                        <h6 class="mb-0 text-uppercase">Dados de Faturamento e Recibos</h6>
                        <hr/>
                        <?php echo $msg; ?>
                        <div class="card border-top border-0 border-4 border-white">
                            <div class="card-body p-5">
                                <div class="card-title d-flex align-items-center">
                                    <div><i class="bx bx-wallet me-1 font-22 text-white"></i></div>
                                    <h5 class="mb-0 text-white">Configurar Minha Empresa</h5>
                                </div>
                                <hr>
                                <form method="post" class="row g-3">
                                    <p class="text-white-50">Estes dados aparecerão nos comprovantes de pagamento e faturas geradas para seus clientes.</p>
                                    
                                    <div class="col-md-12">
                                        <label class="form-label">Nome Fantasia ou Razão Social</label>
                                        <input type="text" name="nome_razao" class="form-control" value="<?php echo htmlspecialchars($faturamentoAdmin['nome_razao']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">CPF ou CNPJ</label>
                                        <input type="text" name="cpf_cnpj" class="form-control" value="<?php echo htmlspecialchars($faturamentoAdmin['cpf_cnpj']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">WhatsApp Corporativo</label>
                                        <input type="text" name="whats" class="form-control" value="<?php echo htmlspecialchars($faturamentoAdmin['whats']); ?>">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Endereço Completo</label>
                                        <input type="text" name="endereco" class="form-control" value="<?php echo htmlspecialchars($faturamentoAdmin['endereco']); ?>" placeholder="Rua, Número, Bairro, Cidade - UF">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label">Chave PIX Principal (Aparece nas instruções de pagamento)</label>
                                        <input type="text" name="chave_pix" class="form-control" value="<?php echo htmlspecialchars($faturamentoAdmin['chave_pix']); ?>">
                                    </div>

                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-light px-5"><i class="bx bx-save"></i> SALVAR CONFIGURAÇÕES</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include './includes/footer.php'; ?>
    </div>
</body>
</html>
