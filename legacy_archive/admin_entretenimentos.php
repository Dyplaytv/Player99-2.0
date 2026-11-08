<?php
require_once './includes/header.php';

if ($nivel_admin != 1) {
    header("Location: dashboard.php");
    exit;
}

$dbRadios = new SQLite3(dirname(__DIR__) . "/api/db/studiolivecode_radios.db");
$msg = '';

$bgDir = __DIR__ . '/uploads/bg_entretenimentos/';
if (!file_exists($bgDir)) {
    mkdir($bgDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_ent'])) {
        $nome = trim($_POST['nome']);
        $rss_url = trim($_POST['rss_url']);
        $icon = trim($_POST['icon']) ?: 'fas fa-newspaper';
        
        $bg_image = '';
        if (isset($_FILES['bg_image']) && $_FILES['bg_image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $filename = uniqid('bg_') . '.' . $ext;
                if (move_uploaded_file($_FILES['bg_image']['tmp_name'], $bgDir . $filename)) {
                    $bg_image = $filename;
                }
            }
        }

        if (!empty($nome) && !empty($rss_url)) {
            $stmt = $dbRadios->prepare("INSERT INTO entretenimentos (nome, url, icon, is_visible, rss_url, bg_image) VALUES (:nome, :url, :icon, 1, :rss_url, :bg_image)");
            $stmt->bindValue(':nome', $nome, SQLITE3_TEXT);
            $stmt->bindValue(':icon', $icon, SQLITE3_TEXT);
            $stmt->bindValue(':rss_url', $rss_url, SQLITE3_TEXT);
            $stmt->bindValue(':bg_image', $bg_image, SQLITE3_TEXT);
            
            // Temporary URL, we will update it with the inserted ID right after
            $stmt->bindValue(':url', '/widgets/temp', SQLITE3_TEXT);
            
            if ($stmt->execute()) {
                $lastId = $dbRadios->lastInsertRowID();
                $finalUrl = '/widgets/rss_' . $lastId . '.php';
                $dbRadios->exec("UPDATE entretenimentos SET url = '$finalUrl' WHERE id = $lastId");
                header("Location: admin_entretenimentos.php?msg=add_success");
                exit;
            } else {
                header("Location: admin_entretenimentos.php?msg=add_error");
                exit;
            }
        }
    } elseif (isset($_POST['delete_ent'])) {
        $id = (int)$_POST['id_ent'];
        $dbRadios->exec("DELETE FROM entretenimentos WHERE id = $id");
        header("Location: admin_entretenimentos.php?msg=delete_success");
        exit;
    } elseif (isset($_POST['toggle_visible'])) {
        $id = (int)$_POST['id_ent'];
        $current = (int)$_POST['current_visible'];
        $newVal = $current == 1 ? 0 : 1;
        $dbRadios->exec("UPDATE entretenimentos SET is_visible = $newVal WHERE id = $id");
        header("Location: admin_entretenimentos.php?msg=toggle_success");
        exit;
    }
}

// Handle GET messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'add_success') $msg = '<div class="alert alert-success">Categoria adicionada com sucesso!</div>';
    if ($_GET['msg'] === 'add_error') $msg = '<div class="alert alert-danger">Erro ao adicionar categoria.</div>';
    if ($_GET['msg'] === 'delete_success') $msg = '<div class="alert alert-success">Categoria removida!</div>';
    if ($_GET['msg'] === 'toggle_success') $msg = '<div class="alert alert-success">Status atualizado!</div>';
}

$entretenimentos = [];
$res = $dbRadios->query("SELECT * FROM entretenimentos ORDER BY id DESC");
if ($res) {
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        $entretenimentos[] = $row;
    }
}
?>

<div class="card radius-10">
    <div class="card-body">
        <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-4">
            <div class="breadcrumb-title pe-3">Sistema</div>
            <div class="ps-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0 p-0">
                        <li class="breadcrumb-item"><a href="dashboard.php"><i class="bx bx-home-alt"></i></a></li>
                        <li class="breadcrumb-item active" aria-current="page">Gerenciar Entretenimento (RSS)</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <?php if(!empty($msg)) echo $msg; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0 text-white">Nova Categoria Dinâmica</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Nome da Categoria</label>
                                <input type="text" name="nome" class="form-control" placeholder="Ex: Esportes, Fofocas..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Link do RSS (.xml)</label>
                                <input type="url" name="rss_url" class="form-control" placeholder="https://..." required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ícone FontAwesome (Opcional)</label>
                                <input type="text" name="icon" class="form-control" placeholder="Ex: fas fa-futbol" value="fas fa-newspaper">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Imagem de Fundo (JPG/PNG)</label>
                                <input type="file" name="bg_image" class="form-control" accept="image/jpeg, image/png">
                                <small class="text-muted">Resolução recomendada: 1920x1080. O robô vai escrever a notícia em cima dessa imagem!</small>
                            </div>
                            <button type="submit" name="add_ent" class="btn btn-primary w-100">Adicionar Categoria</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0 text-white"><i class="bx bx-list-ul"></i> Categorias Existentes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Ícone</th>
                                        <th>Nome</th>
                                        <th>Origem (RSS)</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($entretenimentos as $ent): ?>
                                    <tr>
                                        <td class="text-center" style="font-size: 24px;"><i class="<?php echo htmlspecialchars($ent['icon']); ?>"></i></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($ent['nome']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($ent['url']); ?></small>
                                        </td>
                                        <td>
                                            <?php if(!empty($ent['rss_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($ent['rss_url']); ?>" target="_blank" class="badge bg-info text-white">Abrir RSS</a>
                                                <?php if(!empty($ent['bg_image'])): ?>
                                                    <span class="badge bg-secondary text-white">Tem Fundo</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-dark text-white">Padrão / Fixo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($ent['is_visible'] == 1): ?>
                                                <span class="badge bg-success">Ativo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Oculto</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" style="display:inline;">
                                                <input type="hidden" name="id_ent" value="<?php echo $ent['id']; ?>">
                                                <input type="hidden" name="current_visible" value="<?php echo $ent['is_visible']; ?>">
                                                <button type="submit" name="toggle_visible" class="btn btn-sm btn-<?php echo $ent['is_visible'] == 1 ? 'warning' : 'success'; ?>" title="<?php echo $ent['is_visible'] == 1 ? 'Ocultar' : 'Mostrar'; ?>">
                                                    <i class="bx <?php echo $ent['is_visible'] == 1 ? 'bx-hide' : 'bx-show'; ?>"></i>
                                                </button>
                                            </form>
                                            
                                            <?php if(!empty($ent['rss_url'])): ?>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('Deseja realmente apagar esta categoria dinâmica? As playlists dos clientes serão afetadas.');">
                                                <input type="hidden" name="id_ent" value="<?php echo $ent['id']; ?>">
                                                <button type="submit" name="delete_ent" class="btn btn-sm btn-danger" title="Apagar">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if(empty($entretenimentos)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Nenhuma categoria encontrada.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once './includes/footer.php'; ?>
