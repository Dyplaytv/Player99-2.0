<?php
// admin_logs.php
require_once 'includes/header.php';

if (!isset($nivel_admin) || $nivel_admin != 1) {
    echo "<script>window.location.href='estatisticas.php';</script>";
    exit;
}

$dbPath = __DIR__ . "/api/db/error_logs.db";

// Limpar logs
if (isset($_GET['action']) && $_GET['action'] == 'clear') {
    if (file_exists($dbPath)) {
        $dbLog = new SQLite3($dbPath);
        $dbLog->exec("DELETE FROM system_logs");
        $dbLog->exec("VACUUM");
        $dbLog->close();
    }
    echo "<script>window.location.href='admin_logs.php';</script>";
    exit;
}

// Buscar logs
$logs = [];
if (file_exists($dbPath)) {
    try {
        $dbLog = new SQLite3($dbPath);
        $res = $dbLog->query("SELECT * FROM system_logs ORDER BY id DESC LIMIT 500");
        if ($res) {
            while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                $logs[] = $row;
            }
        }
        $dbLog->close();
    } catch (Exception $e) {}
}

?>

<div class="content-page">
    <div class="content">
        <div class="container-fluid">
            
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box">
                        <div class="page-title-right">
                            <a href="?action=clear" class="btn btn-danger btn-sm" onclick="return confirm('Tem certeza que deseja apagar todos os logs?');">
                                <i class="fas fa-trash-alt"></i> Limpar Logs
                            </a>
                        </div>
                        <h4 class="page-title">Logs de Erro do Sistema</h4>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="header-title mb-4">Últimos 500 Erros Registrados</h4>
                            
                            <div class="table-responsive">
                                <table class="table table-hover table-centered mb-0" id="logsTable">
                                    <thead>
                                        <tr>
                                            <th>Data/Hora</th>
                                            <th>Tipo</th>
                                            <th>Mensagem</th>
                                            <th>Arquivo</th>
                                            <th>Linha</th>
                                            <th>URL</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Nenhum erro registrado até o momento. Sistema rodando perfeitamente!</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($logs as $log): ?>
                                            <tr>
                                                <td class="text-nowrap"><?php echo date('d/m/Y H:i:s', strtotime($log['date_time'])); ?></td>
                                                <td>
                                                    <?php if($log['error_type'] == 'FATAL_ERROR' || $log['error_type'] == 'EXCEPTION'): ?>
                                                        <span class="badge bg-danger text-white"><?php echo $log['error_type']; ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark"><?php echo $log['error_type']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="max-width: 300px; white-space: normal; word-wrap: break-word;">
                                                        <strong><?php echo htmlspecialchars($log['message']); ?></strong>
                                                    </div>
                                                </td>
                                                <td><small><?php echo htmlspecialchars(basename($log['file'])); ?></small></td>
                                                <td><?php echo $log['line']; ?></td>
                                                <td><small><?php echo htmlspecialchars($log['url']); ?></small></td>
                                            </tr>
                                            <?php endforeach; ?>
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
</div>

<?php require_once 'includes/footer.php'; ?>
