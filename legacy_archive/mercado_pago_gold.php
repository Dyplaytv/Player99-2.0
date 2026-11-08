<?php
// mercado_pago_gold.php
session_start();
if (!isset($_SESSION['id'])) {
    header('location: painel.php');
    exit;
}
$id = $_SESSION['id'];
$db1 = new SQLite3("./api/db/studiolivecode_users.db");
$query = $db1->prepare("SELECT admin, username FROM users WHERE id = :id");
$query->bindValue(':id', $id, SQLITE3_INTEGER);
$userData = $query->execute()->fetchArray(SQLITE3_ASSOC);
if (!$userData || $userData['admin'] != 1 || $userData['username'] == 'cemet@cemet.com.br') {
    header("Location: users.php");
    exit;
}

$config_file = './api/mercado_pago_gold/config.json';
$config = json_decode(file_get_contents($config_file), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_token'])) {
        $config['access_token'] = trim($_POST['access_token']);
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $msg = "Token atualizado com sucesso!";
    }

    if (isset($_POST['add_pacote'])) {
        $novo_pacote = [
            'id' => uniqid(),
            'nome' => trim($_POST['nome_pacote']),
            'valor' => floatval($_POST['valor_pacote']),
            'dias' => intval($_POST['dias_pacote'])
        ];
        $config['pacotes'][] = $novo_pacote;
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $msg = "Pacote adicionado com sucesso!";
    }

    if (isset($_POST['delete_pacote'])) {
        $id_delete = $_POST['id_pacote'];
        foreach ($config['pacotes'] as $k => $pacote) {
            if ($pacote['id'] === $id_delete) {
                unset($config['pacotes'][$k]);
            }
        }
        $config['pacotes'] = array_values($config['pacotes']);
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $msg = "Pacote excluído!";
    }

    if (isset($_POST['add_cupom'])) {
        if (!isset($config['cupons'])) {
            $config['cupons'] = [];
        }
        $novo_cupom = [
            'id' => uniqid(),
            'codigo' => strtoupper(trim($_POST['codigo_cupom'])),
            'desconto' => floatval($_POST['desconto_cupom'])
        ];
        $config['cupons'][] = $novo_cupom;
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $msg = "Cupom adicionado com sucesso!";
    }

    if (isset($_POST['delete_cupom'])) {
        $id_delete = $_POST['id_cupom'];
        foreach ($config['cupons'] as $k => $cupom) {
            if ($cupom['id'] === $id_delete) {
                unset($config['cupons'][$k]);
            }
        }
        $config['cupons'] = array_values($config['cupons']);
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $msg = "Cupom excluído!";
    }

    if (isset($_POST['update_plano_telas'])) {
        $config['plano_telas_ativas'] = [
            'valor' => floatval($_POST['valor_telas']),
            'dias' => intval($_POST['dias_telas'])
        ];
        file_put_contents($config_file, json_encode($config, JSON_PRETTY_PRINT));
        $msg = "Plano Telas Ativas atualizado!";
    }
}

// Para usar a interface do painel
include './includes/header.php';

$link_renovacao = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . dirname($_SERVER['REQUEST_URI']) . "/api/mercado_pago_gold/consulta.php";
?>

<div class="alert alert-info d-flex align-items-center justify-content-between" role="alert">
    <div>
        <strong>Link para Clientes:</strong> <span id="link_renovacao"><?php echo $link_renovacao; ?></span>
    </div>
    <button class="btn btn-sm btn-dark" onclick="copiarLink()"><i class="bx bx-copy"></i> Copiar Link</button>
</div>

<script>
function copiarLink() {
    var linkText = document.getElementById("link_renovacao").innerText;
    navigator.clipboard.writeText(linkText).then(function() {
        alert("Link copiado com sucesso!");
    }, function(err) {
        alert("Erro ao copiar o link: " + err);
    });
}
</script>

<div class="card radius-10">
    <div class="card-body">
        <h4 class="card-title">Configuração Mercado Pago Gold</h4>
        <?php if(isset($msg)) echo "<div class='alert alert-success'>$msg</div>"; ?>

        <form method="POST" class="mb-4">
            <div class="form-group mb-3">
                <label><strong>Access Token (Mercado Pago)</strong></label>
                <input type="text" name="access_token" class="form-control" value="<?php echo htmlspecialchars($config['access_token'] ?? ''); ?>" required>
            </div>
            <button type="submit" name="update_token" class="btn btn-primary"><i class="bx bx-save"></i> Salvar Token</button>
        </form>

        <hr>
        <h5 class="card-title mt-4">Adicionar Novo Pacote</h5>
        <form method="POST" class="row g-3">
            <div class="col-md-4">
                <label>Nome do Pacote</label>
                <input type="text" name="nome_pacote" class="form-control" placeholder="Ex: 10 Créditos" required>
            </div>
            <div class="col-md-4">
                <label>Valor (R$)</label>
                <input type="number" step="0.01" name="valor_pacote" class="form-control" placeholder="Ex: 50.00" required>
            </div>
            <div class="col-md-4">
                <label>Quantidade de Dias</label>
                <input type="number" name="dias_pacote" class="form-control" placeholder="Ex: 30" required>
            </div>
            <div class="col-12 mt-3">
                <button type="submit" name="add_pacote" class="btn btn-success"><i class="bx bx-plus"></i> Adicionar Pacote</button>
            </div>
        </form>
    </div>
</div>

<div class="card radius-10 mt-4">
    <div class="card-body">
        <h5 class="card-title">Pacotes Cadastrados</h5>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Valor (R$)</th>
                        <th>Dias</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($config['pacotes'])): ?>
                        <tr><td colspan="4">Nenhum pacote cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($config['pacotes'] as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['nome']); ?></td>
                            <td>R$ <?php echo number_format($p['valor'], 2, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($p['dias'] ?? $p['creditos']); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_pacote" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="delete_pacote" class="btn btn-sm btn-danger"><i class="bx bx-trash"></i></button>
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

<div class="card radius-10 mt-4">
    <div class="card-body">
        <h5 class="card-title mt-2">Adicionar Novo Cupom</h5>
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label>Código do Cupom</label>
                <input type="text" name="codigo_cupom" class="form-control" placeholder="Ex: PROMO30" required>
            </div>
            <div class="col-md-6">
                <label>Desconto (%)</label>
                <input type="number" step="0.1" name="desconto_cupom" class="form-control" placeholder="Ex: 30" required>
            </div>
            <div class="col-12 mt-3 mb-4">
                <button type="submit" name="add_cupom" class="btn btn-warning"><i class="bx bx-plus"></i> Adicionar Cupom</button>
            </div>
        </form>

        <h5 class="card-title border-top pt-4">Cupons Cadastrados</h5>
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Código</th>
                        <th>Desconto (%)</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($config['cupons'])): ?>
                        <tr><td colspan="3">Nenhum cupom cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach($config['cupons'] as $c): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($c['codigo']); ?></strong></td>
                            <td><?php echo htmlspecialchars($c['desconto']); ?>%</td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id_cupom" value="<?php echo $c['id']; ?>">
                                    <button type="submit" name="delete_cupom" class="btn btn-sm btn-danger"><i class="bx bx-trash"></i></button>
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

<div class="card radius-10 mt-4">
    <div class="card-body">
        <h5 class="card-title mt-2">Configurar Plano Telas Ativas</h5>
        <p class="text-muted">Este plano define o valor padrão cobrado por cada tela ativa do cliente, bem como a duração do ciclo de faturamento.</p>
        <form method="POST" class="row g-3">
            <div class="col-md-6">
                <label>Valor por Tela (R$)</label>
                <input type="number" step="0.01" name="valor_telas" class="form-control" value="<?php echo htmlspecialchars($config['plano_telas_ativas']['valor'] ?? '100.00'); ?>" required>
            </div>
            <div class="col-md-6">
                <label>Período (Dias)</label>
                <input type="number" name="dias_telas" class="form-control" value="<?php echo htmlspecialchars($config['plano_telas_ativas']['dias'] ?? '30'); ?>" required>
            </div>
            <div class="col-12 mt-3 mb-4">
                <button type="submit" name="update_plano_telas" class="btn btn-info text-white"><i class="bx bx-save"></i> Salvar Plano Telas Ativas</button>
            </div>
        </form>
    </div>
</div>
<?php include './includes/footer.php'; ?>
