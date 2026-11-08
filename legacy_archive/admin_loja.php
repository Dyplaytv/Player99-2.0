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
$query = $dbUsers->query("SELECT admin FROM users WHERE id = $id");
$userData = $query ? $query->fetchArray(SQLITE3_ASSOC) : null;
$isAllowed = !empty($_SESSION['is_admin']) || ($userData && $userData['admin'] == 1);
if (!$isAllowed) {
    die("Acesso Negado. Apenas administradores podem acessar esta página.");
}

if (file_exists(__DIR__ . '/init_loja_db.php')) {
    require_once __DIR__ . '/init_loja_db.php';
} elseif (file_exists(dirname(__DIR__) . '/init_loja_db.php')) {
    require_once dirname(__DIR__) . '/init_loja_db.php';
}
$dbLoja = new SQLite3(dirname(__DIR__) . '/api/db/studiolivecode_loja.db');

$msg = '';

// Adicionar / Editar Produto
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save') {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $preco = (float) str_replace(',', '.', $_POST['preco']);
    $imagem = '';

    // Upload de Imagem
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
        $ext = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $imgName = 'prod_' . uniqid() . '.' . $ext;
        $targetDir = __DIR__ . "/uploads/loja/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
        
        move_uploaded_file($_FILES['imagem']['tmp_name'], $targetDir . $imgName);
        $imagem = $imgName;
    }

    // parceiro_id = 0 indica Loja Oficial (Admin Geral)
    $parceiro_id = 0; 

    if (!empty($_POST['id'])) {
        // Edit
        $id_prod = (int)$_POST['id'];
        $query = "UPDATE produtos SET titulo = :titulo, descricao = :descricao, preco = :preco" . ($imagem ? ", imagem = :imagem" : "") . " WHERE id = :id";
        $stmt = $dbLoja->prepare($query);
        $stmt->bindValue(':id', $id_prod, SQLITE3_INTEGER);
    } else {
        // Insert
        $query = "INSERT INTO produtos (titulo, descricao, preco, imagem, parceiro_id) VALUES (:titulo, :descricao, :preco, :imagem, :parceiro_id)";
        $stmt = $dbLoja->prepare($query);
        $stmt->bindValue(':parceiro_id', $parceiro_id, SQLITE3_INTEGER);
    }
    
    $stmt->bindValue(':titulo', $titulo, SQLITE3_TEXT);
    $stmt->bindValue(':descricao', $descricao, SQLITE3_TEXT);
    $stmt->bindValue(':preco', $preco, SQLITE3_FLOAT);
    if ($imagem || empty($_POST['id'])) $stmt->bindValue(':imagem', $imagem, SQLITE3_TEXT);
    
    $stmt->execute();
    $msg = "<div class='alert alert-success'>Produto salvo com sucesso!</div>";
}

// Excluir Produto
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    $stmt = $dbLoja->prepare("DELETE FROM produtos WHERE id = :id");
    $stmt->bindValue(':id', $id_del, SQLITE3_INTEGER);
    $stmt->execute();
    $msg = "<div class='alert alert-success'>Produto excluído com sucesso!</div>";
}

// Busca Produtos do Admin
$produtos = $dbLoja->query("SELECT * FROM produtos ORDER BY id DESC");
?>
<?php include './includes/header.php'; ?>

<div class="row mb-4 align-items-center">
    <div class="col-md-8">
        <h2 class="mb-0"><i class="fas fa-store text-warning"></i> Gerenciar Loja Oficial</h2>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a href="loja.php" target="_blank" class="btn btn-warning text-dark font-weight-bold">
            <i class='bx bx-link-external'></i> Acessar Loja Pública
        </a>
    </div>
</div>
<div class="row">
    <div class="col-12">
        <?php echo $msg; ?>
    </div>
</div>

<div class="row">
    <!-- Formulário Novo Produto -->
    <div class="col-md-4">
        <div class="card radius-10 border-primary">
            <div class="card-body">
                <h5 class="mb-3">Adicionar / Editar Produto</h5>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" id="prod_id" value="">
                    <div class="mb-3">
                        <label>Título (Serviço Digital)</label>
                        <input type="text" name="titulo" id="prod_titulo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Descrição Curta</label>
                        <textarea name="descricao" id="prod_desc" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Preço (R$)</label>
                        <input type="number" step="0.01" name="preco" id="prod_preco" class="form-control" placeholder="25.00" required>
                    </div>
                    <div class="mb-3">
                        <label>Imagem (Capa)</label>
                        <input type="file" name="imagem" class="form-control" accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bx bx-save"></i> Salvar Produto</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Lista de Produtos -->
    <div class="col-md-8">
        <div class="card radius-10">
            <div class="card-body">
                <h5 class="mb-3">Produtos Cadastrados na Loja</h5>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th>Capa</th>
                                <th>Título</th>
                                <th>Preço</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($p = $produtos->fetchArray(SQLITE3_ASSOC)): ?>
                            <tr>
                                <td>
                                    <img src="uploads/loja/<?php echo $p['imagem'] ? $p['imagem'] : 'default.jpg'; ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px;">
                                </td>
                                <td class="text-start">
                                    <strong><?php echo htmlspecialchars($p['titulo']); ?></strong><br>
                                    <small class="text-muted"><?php echo substr(htmlspecialchars($p['descricao']), 0, 50); ?>...</small>
                                </td>
                                <td><strong class="text-success">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></strong></td>
                                <td>
                                    <a href="loja.php" class="btn btn-sm btn-info" target="_blank" title="Visualizar na Loja"><i class="bx bx-show"></i></a>
                                    <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir este anúncio?')"><i class="bx bx-trash"></i></a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include './includes/footer.php'; ?>
