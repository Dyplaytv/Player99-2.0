<?php
session_start();
if (!isset($_SESSION['cliente_id'])) {
    header("Location: login_cliente.php");
    exit;
}

$parceiro_id = $_SESSION['cliente_id'];

// Inicializa banco da Loja se não existir
require_once __DIR__ . '/init_loja_db.php';

$dbLoja = new SQLite3(__DIR__ . '/api/db/studiolivecode_loja.db');

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

    if (!empty($_POST['id'])) {
        // Edit
        $id_prod = (int)$_POST['id'];
        $query = "UPDATE produtos SET titulo = :titulo, descricao = :descricao, preco = :preco" . ($imagem ? ", imagem = :imagem" : "") . " WHERE id = :id AND parceiro_id = :parceiro_id";
        $stmt = $dbLoja->prepare($query);
        $stmt->bindValue(':id', $id_prod, SQLITE3_INTEGER);
    } else {
        // Insert
        $query = "INSERT INTO produtos (titulo, descricao, preco, imagem, parceiro_id) VALUES (:titulo, :descricao, :preco, :imagem, :parceiro_id)";
        $stmt = $dbLoja->prepare($query);
    }
    
    $stmt->bindValue(':titulo', $titulo, SQLITE3_TEXT);
    $stmt->bindValue(':descricao', $descricao, SQLITE3_TEXT);
    $stmt->bindValue(':preco', $preco, SQLITE3_FLOAT);
    $stmt->bindValue(':parceiro_id', $parceiro_id, SQLITE3_INTEGER);
    if ($imagem || empty($_POST['id'])) $stmt->bindValue(':imagem', $imagem, SQLITE3_TEXT);
    
    $stmt->execute();
    $msg = "<div class='alert alert-success'>Produto salvo com sucesso!</div>";
}

// Excluir Produto
if (isset($_GET['delete'])) {
    $id_del = (int)$_GET['delete'];
    $stmt = $dbLoja->prepare("DELETE FROM produtos WHERE id = :id AND parceiro_id = :parceiro_id");
    $stmt->bindValue(':id', $id_del, SQLITE3_INTEGER);
    $stmt->bindValue(':parceiro_id', $parceiro_id, SQLITE3_INTEGER);
    $stmt->execute();
    $msg = "<div class='alert alert-success'>Produto excluído com sucesso!</div>";
}

// Busca Produtos do Parceiro
$produtos = $dbLoja->query("SELECT * FROM produtos WHERE parceiro_id = $parceiro_id ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <title>Minha Loja - Dashboard do Afiliado/Parceiro</title>
    <style>
        body { background-color: #181828; color: #fff; }
        .card-custom { background-color: rgba(0,0,0,0.4); border-radius: 15px; border: 1px solid #0A38D1; margin-bottom: 20px; }
        .prod-img { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
        .btn-gold { background: linear-gradient(135deg, #FFD700, #B8860B); color: #000; font-weight: bold; border: none; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-store text-warning"></i> Meus Anúncios (Loja)</h2>
            <a href="afiliado_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Voltar ao Dashboard</a>
        </div>

        <?php echo $msg; ?>

        <div class="row">
            <!-- Formulário Novo Produto -->
            <div class="col-md-4">
                <div class="card card-custom p-4">
                    <h5 class="mb-3">Adicionar / Editar Produto</h5>
                    <form action="" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save">
                        <input type="hidden" name="id" id="prod_id" value="">
                        <div class="form-group">
                            <label>Título (Serviço Digital)</label>
                            <input type="text" name="titulo" id="prod_titulo" class="form-control bg-dark text-white" required>
                        </div>
                        <div class="form-group">
                            <label>Descrição Curta</label>
                            <textarea name="descricao" id="prod_desc" class="form-control bg-dark text-white" rows="2" required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Preço (R$)</label>
                            <input type="number" step="0.01" name="preco" id="prod_preco" class="form-control bg-dark text-white" placeholder="25.00" required>
                        </div>
                        <div class="form-group">
                            <label>Imagem (Capa)</label>
                            <input type="file" name="imagem" class="form-control-file text-white" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Salvar Produto</button>
                    </form>
                </div>
            </div>

            <!-- Lista de Produtos -->
            <div class="col-md-8">
                <div class="card card-custom p-4">
                    <h5 class="mb-3">Meus Produtos Cadastrados</h5>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover text-center align-middle">
                            <thead>
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
                                        <img src="uploads/loja/<?php echo $p['imagem'] ? $p['imagem'] : 'default.jpg'; ?>" class="prod-img">
                                    </td>
                                    <td class="align-middle text-left">
                                        <strong><?php echo $p['titulo']; ?></strong><br>
                                        <small class="text-muted"><?php echo substr($p['descricao'], 0, 50); ?>...</small>
                                    </td>
                                    <td class="align-middle text-success font-weight-bold">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></td>
                                    <td class="align-middle">
                                        <a href="loja.php?prod=<?php echo $p['id']; ?>" class="btn btn-sm btn-info" target="_blank" title="Visualizar na Loja"><i class="fas fa-eye"></i></a>
                                        <a href="?delete=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Deseja excluir este anúncio?')"><i class="fas fa-trash"></i></a>
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
</body>
</html>
