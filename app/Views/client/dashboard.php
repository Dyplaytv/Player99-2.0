<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseParam = $isAdmin ? ('client_id=' . (int)$selectedClientId . '&') : '';
$affiliateLink = $protocol . $host . '/?ref=' . urlencode($client['affiliateCode'] ?? '');
$logoSrc = !empty($customization['logo_path']) ? $customization['logo_path'] : \App\Core\View::logoUrl();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Painel do Motorista - Player99</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <style>
        body { font-family: 'Inter', 'Roboto', sans-serif; background-color: #181b21; color: #333; margin: 0; }
        .wrapper { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: #0f172a; color: #fff; display: flex; flex-direction: column; border-right: 1px solid rgba(255,255,255,0.05); flex-shrink: 0; }
        .sidebar-brand { padding: 20px 20px 10px; font-size: 20px; font-weight: bold; display: flex; align-items: center; gap: 12px; color: #fff; }
        .sidebar-status {
            display: block !important;
            margin: 15px 15px 20px !important;
            padding: 12px 10px !important;
            text-align: center !important;
            border-radius: 8px !important;
            background: #0f172a !important;
            color: #00f3ff !important;
            border: 1px solid #00f3ff !important;
            box-shadow: 0 0 8px rgba(0, 243, 255, 0.5), 0 0 12px rgba(0, 243, 255, 0.25) inset !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            text-transform: uppercase !important;
            font-weight: 700 !important;
            font-size: 13px !important;
            letter-spacing: 0.8px !important;
            text-decoration: none !important;
        }
        .sidebar-status:hover {
            background: #00f3ff !important;
            color: #000 !important;
            box-shadow: 0 0 15px #00f3ff, 0 0 20px #00f3ff inset !important;
        }
        .sidebar-nav { flex: 1; padding: 5px 0; display: flex; flex-direction: column; }
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: #d1e0fc;
            text-decoration: none !important;
            transition: all 0.25s;
            font-size: 15px;
            border-left: 3px solid transparent;
        }
        .sidebar-link i { width: 32px; font-size: 19px; }
        .sidebar-link:hover { color: #fff; background-color: rgba(255,255,255,0.05); }
        .sidebar-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.07);
            border-left-color: #00f3ff;
            font-weight: 600;
        }
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; }
        .topbar {
            background: #0f172a;
            min-height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 30px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: #fff;
            flex-wrap: wrap;
            gap: 10px;
        }
        .content-area { padding: 30px; }
        .card { background-color: #ffffff; border: 1px solid #e0e0e0; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 25px; color: #333; }
        .card-header { background: #ffffff; border-bottom: 1px solid #eee; padding: 18px 25px; font-weight: 700; font-size: 17px; display: flex; justify-content: space-between; align-items: center; border-radius: 10px 10px 0 0 !important; color: #333; text-transform: uppercase; }
        .table { color: #333; margin-bottom: 0; }
        .table th { border-top: none; color: #555; font-weight: 700; font-size: 12px; text-transform: uppercase; border-bottom: 2px solid #eee; }
        .table td { border-top: 1px solid #eee; vertical-align: middle; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: #f8f9fa; }

        /* Drag and Drop Playlist Editor */
        .dnd-container { display: flex; gap: 20px; }
        .dnd-panel { flex: 1; background: #ffffff; border: 1px solid #eee; border-radius: 8px; padding: 15px; height: 580px; display: flex; flex-direction: column; }
        .dnd-panel .tab-content { flex: 1; display: flex; flex-direction: column; min-height: 0; }
        .dnd-panel .tab-pane { flex: 1; flex-direction: column; min-height: 0; }
        .dnd-panel .tab-pane.active { display: flex !important; }
        .dnd-header { font-weight: bold; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; color: #333; }
        .video-list { flex: 1; overflow-y: auto; padding-right: 5px; min-height: 50px; }
        .video-item { background: #f8f9fa; border: 1px solid #ddd; padding: 10px 15px; margin-bottom: 8px; border-radius: 5px; cursor: grab; display: flex; align-items: center; font-weight: 500; font-size: 14px; color: #333; }
        .video-item:active { cursor: grabbing; }
        .video-item i.drag-handle { color: #aaa; margin-right: 15px; cursor: grab; }
        .video-item .vid-title { flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .ghost-class { opacity: 0.4; background: #e9ecef; color: #333; }
        .empty-placeholder { text-align: center; padding: 40px 20px; color: #aaa; border: 2px dashed #ddd; border-radius: 8px; margin-top: 10px; }

        /* Root Search Bar */
        .root-bar { background: #111827; border-bottom: 1px solid rgba(0, 243, 255, 0.2); padding: 10px 30px; color: #fff; }
        .search-results-list { max-height: 240px; overflow-y: auto; position: absolute; width: 100%; z-index: 1050; background: #0f172a; border: 1px solid #00f3ff; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.8); }
        .search-item { padding: 8px 14px; cursor: pointer; border-bottom: 1px solid rgba(255,255,255,0.06); color: #fff; }
        .search-item:hover { background: rgba(0, 243, 255, 0.15); }

        .bottom-nav { display: none; }
        @media (max-width: 768px) {
            .sidebar { position: fixed; left: -260px; top: 0; bottom: 0; z-index: 1040; transition: left 0.3s ease; }
            .sidebar.show { left: 0; }
            .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1030; }
            .sidebar-overlay.show { display: block; }
            .topbar { padding: 10px 15px; }
            .content-area { padding: 15px 15px 90px; }
            .dnd-container { flex-direction: column; }
            .dnd-panel { height: 400px; }
            .card-header { flex-wrap: wrap; gap: 10px; padding: 15px; font-size: 16px; }
            .card-header .btn { width: 100%; }
            .table-telas thead { display: none; }
            .table-telas, .table-telas tbody, .table-telas tr, .table-telas td { display: block; width: 100%; }
            .table-telas tr { border: 1px solid #e3e6ea; border-radius: 12px; margin-bottom: 14px; padding: 12px; background: #fff !important; }
            .table-telas td { border: none !important; padding: 6px 0 !important; text-align: left !important; }
            .table-telas td[data-label]:before { content: attr(data-label); display: block; font-size: 11px; font-weight: 700; color: #888; text-transform: uppercase; margin-bottom: 3px; }
            .bottom-nav { display: flex; position: fixed; left: 0; right: 0; bottom: 0; z-index: 1020; background: #0b1424; border-top: 1px solid rgba(255,255,255,0.12); }
            .bottom-nav a { flex: 1; text-align: center; padding: 8px 2px 6px; color: #9fb3d9; text-decoration: none; font-size: 11px; }
            .bottom-nav a i { display: block; font-size: 20px; margin-bottom: 2px; }
            .bottom-nav a.active { color: #00f3ff; font-weight: 700; }
        }
    </style>
</head>
<body>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Navegação inferior Mobile na ordem solicitada -->
<nav class="bottom-nav">
    <a href="?<?= $baseParam ?>tab=arquivos" class="<?= $tab === 'arquivos' ? 'active' : '' ?>"><i class="fas fa-image"></i>Arquivos</a>
    <a href="?<?= $baseParam ?>tab=lista" class="<?= $tab === 'lista' ? 'active' : '' ?>"><i class="fas fa-list-ul"></i>Playlist</a>
    <a href="?<?= $baseParam ?>tab=telas" class="<?= $tab === 'telas' ? 'active' : '' ?>"><i class="fas fa-desktop"></i>Telas</a>
    <a href="?<?= $baseParam ?>tab=afiliado" class="<?= $tab === 'afiliado' ? 'active' : '' ?>"><i class="fas fa-hand-holding-usd"></i>Afiliado</a>
    <a href="?<?= $baseParam ?>tab=config" class="<?= $tab === 'config' ? 'active' : '' ?>"><i class="fas fa-cog"></i>Config</a>
</nav>

<div class="wrapper">
    <!-- BARRA LATERAL (Ordem Exata: Arquivos -> Playlist -> Telas -> Área do Afiliado -> Configurações -> Sair) -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <img src="<?= htmlspecialchars($logoSrc) ?>" style="max-height: 38px; object-fit: contain;" alt="Player99" onerror="this.onerror=null;this.src='/assets/images/logo.png';">
            <span><?= htmlspecialchars($customization['brand_name'] ?: 'Player99') ?></span>
        </div>

        <a href="?<?= $baseParam ?>tab=telas" class="sidebar-status">
            <i class="fas fa-check-circle mr-1"></i> TODAS FUNCIONANDO
        </a>

        <div class="sidebar-nav">
            <a href="?<?= $baseParam ?>tab=arquivos" class="sidebar-link <?= $tab === 'arquivos' ? 'active' : '' ?>">
                <i class="fas fa-image"></i> Arquivos
            </a>
            <a href="?<?= $baseParam ?>tab=lista" class="sidebar-link <?= $tab === 'lista' ? 'active' : '' ?>">
                <i class="fas fa-list-ul"></i> Playlist
            </a>
            <a href="?<?= $baseParam ?>tab=telas" class="sidebar-link <?= $tab === 'telas' ? 'active' : '' ?>">
                <i class="fas fa-desktop"></i> Telas (Player)
            </a>
            <a href="?<?= $baseParam ?>tab=afiliado" class="sidebar-link text-warning <?= $tab === 'afiliado' ? 'active' : '' ?>">
                <i class="fas fa-hand-holding-usd"></i> Área do Afiliado
            </a>
            <a href="?<?= $baseParam ?>tab=config" class="sidebar-link <?= $tab === 'config' ? 'active' : '' ?>">
                <i class="fas fa-cog"></i> Configurações
            </a>

            <a href="/sair" class="sidebar-link text-danger mt-3" style="border-top: 1px solid rgba(255,255,255,0.08);">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>

    <!-- CONTEÚDO PRINCIPAL -->
    <div class="main-content">
        <div class="topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-light d-md-none mr-3" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
                <h5 class="m-0 font-weight-bold text-white">Painel do Motorista</h5>
            </div>
            <div class="d-flex align-items-center" style="gap: 12px;">
                <span><i class="fas fa-user-circle text-info mr-1"></i> Olá, <b><?= htmlspecialchars($client['title'] ?: $client['username']) ?></b></span>
                <?php if ($isAdmin): ?>
                    <a href="/admin/dashboard" class="btn btn-sm btn-outline-info"><i class="fas fa-shield-alt mr-1"></i> Voltar ao Admin</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isAdmin && !empty($allClients)): ?>
        <div class="root-bar">
            <div class="row align-items-center">
                <div class="col-md-5 mb-2 mb-md-0">
                    <span class="badge badge-danger px-2 py-1 mr-2"><i class="fas fa-crown"></i> MODO ROOT</span>
                    <small class="text-light">Visualizando motorista: <b><?= htmlspecialchars($client['title'] ?: $client['username']) ?></b> (#<?= (int)$selectedClientId ?>)</small>
                </div>
                <div class="col-md-7 position-relative">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-dark text-info border-secondary"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="clientSearchInput" class="form-control bg-dark text-white border-secondary" placeholder="Trocar de motorista (digite nome, usuário ou WhatsApp)..." autocomplete="off">
                    </div>
                    <div id="searchResultsDropdown" class="search-results-list d-none">
                        <?php foreach ($allClients as $cli): ?>
                            <div class="search-item d-flex justify-content-between align-items-center"
                                 data-id="<?= (int)$cli['id'] ?>"
                                 data-title="<?= strtolower(htmlspecialchars($cli['title'] ?? '')) ?>"
                                 data-user="<?= strtolower(htmlspecialchars($cli['username'] ?? '')) ?>"
                                 data-whats="<?= htmlspecialchars($cli['whatsapp'] ?? '') ?>"
                                 onclick="window.location.href='/painel-cliente?client_id=<?= (int)$cli['id'] ?>&tab=<?= urlencode($tab) ?>'">
                                <div>
                                    <strong><?= htmlspecialchars($cli['title'] ?: $cli['username']) ?></strong>
                                    <small class="text-muted ml-2">@<?= htmlspecialchars($cli['username']) ?></small>
                                </div>
                                <span class="badge badge-info">#<?= (int)$cli['id'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="content-area">
            <?php if (!empty($msg)): ?>
                <div class="alert alert-success shadow-sm"><i class="fas fa-check-circle mr-1"></i> <?= $msg ?></div>
            <?php endif; ?>
            <?php if (!empty($err)): ?>
                <div class="alert alert-danger shadow-sm"><i class="fas fa-exclamation-triangle mr-1"></i> <?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- 1. MODO ARQUIVOS                                          -->
            <!-- ========================================================= -->
            <?php if ($tab === 'arquivos'): ?>
                <!-- Buscador Automático de Rádios -->
                <div class="card mb-4 shadow-sm" style="background-color: #1b1e24; border: 1px solid #2a2d35;">
                    <div class="card-header border-bottom-0 pb-0" style="background-color: #1b1e24; color: #fff;">
                        <h5 class="m-0 font-weight-bold"><i class="fas fa-search mr-2 text-info"></i> Buscador Automático de Rádios</h5>
                    </div>
                    <div class="card-body pt-2 text-white">
                        <label style="color: #bbb;">Buscar por Nome</label>
                        <div class="input-group">
                            <input type="text" id="radioSearchInput" class="form-control text-white border-secondary" style="background-color: #2a2d35;" placeholder="Ex: Jovem Pan, Gaúcha, Atlântida..." onkeypress="if(event.keyCode===13){searchRadio();}">
                            <div class="input-group-append">
                                <button class="btn btn-success" type="button" onclick="searchRadio()"><i class="fas fa-search mr-1"></i> Buscar</button>
                            </div>
                        </div>
                        <small class="text-muted mt-2 d-block">Digite o nome de uma rádio e clique em buscar para adicioná-la aos seus arquivos.</small>
                        <div id="radioSearchResults" class="mt-3"></div>
                    </div>
                </div>

                <!-- Lista de Arquivos do Motorista -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <i class="fas fa-folder-open text-primary mr-2"></i> Meus Arquivos
                            <span class="badge badge-<?= ($currentVideoCount >= $maxVideosAllowed) ? 'danger' : 'info' ?> ml-2" style="font-size: 13px;">
                                <?= (int)$currentVideoCount ?> / <?= (int)$maxVideosAllowed ?> vídeos
                            </span>
                            <span class="badge badge-secondary ml-1" style="font-size: 13px;">
                                Máx: <?= (int)$maxVideoSizeMb ?>MB por arquivo
                            </span>
                        </div>
                        <?php if ($currentVideoCount < $maxVideosAllowed): ?>
                            <button class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#uploadModal">
                                <i class="fas fa-upload mr-1"></i> ADICIONAR ARQUIVOS
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" onclick="alert('Você atingiu o limite de <?= (int)$maxVideosAllowed ?> vídeos. Apague vídeos antigos ou fale com o suporte.');">
                                <i class="fas fa-ban mr-1"></i> COTA CHEIA (<?= (int)$currentVideoCount ?>/<?= (int)$maxVideosAllowed ?>)
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="post" action="?<?= $baseParam ?>tab=arquivos" id="form-multi-select">
                            <div class="mb-3">
                                <button type="submit" name="apagar_multiplos" class="btn btn-sm btn-danger" onclick="return confirm('Tem certeza que deseja apagar os arquivos selecionados?');">
                                    <i class="fas fa-trash mr-1"></i> Apagar Selecionados
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th width="40"><input type="checkbox" id="check-all" onclick="document.querySelectorAll('.file-checkbox').forEach(cb => cb.checked = this.checked);"></th>
                                            <th>Nome do Arquivo</th>
                                            <th>Data de Envio</th>
                                            <th width="130">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($arquivosOnly)): ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhum arquivo enviado ainda. Clique em <b>ADICIONAR ARQUIVOS</b>!</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($arquivosOnly as $vid): ?>
                                                <tr>
                                                    <td><input type="checkbox" name="selected_files[]" value="<?= (int)$vid['id'] ?>" class="file-checkbox"></td>
                                                    <td class="font-weight-bold"><?= htmlspecialchars($vid['title'] ?: $vid['filename']) ?></td>
                                                    <td><?= !empty($vid['upload_date']) ? date('d/m/Y H:i', strtotime($vid['upload_date'])) : '-' ?></td>
                                                    <td>
                                                        <a href="/uploads/clientes/<?= (int)$selectedClientId ?>/<?= htmlspecialchars($vid['filename']) ?>" target="_blank" class="btn btn-sm btn-info" title="Visualizar">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="submit" name="apagar_video" value="1" class="btn btn-sm btn-danger" title="Apagar" onclick="document.getElementById('id_video_to_delete').value='<?= (int)$vid['id'] ?>'; return confirm('Apagar este arquivo?');">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <input type="hidden" name="id_video" id="id_video_to_delete" value="">
                        </form>
                    </div>
                </div>

                <!-- Modal Upload -->
                <div class="modal fade" id="uploadModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title text-dark font-weight-bold">Adicionar Arquivos</h5>
                                <button type="button" class="close text-dark" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form id="uploadForm" method="post" action="?<?= $baseParam ?>tab=arquivos" enctype="multipart/form-data">
                                    <div class="form-group mb-4 text-left" id="uploadInputGroup">
                                        <label class="font-weight-bold text-dark">Selecione os vídeos ou imagens</label>
                                        <input type="file" name="video_files[]" id="videoFileInput" class="form-control" accept="video/*,image/*" multiple required style="height:auto;padding:10px;">
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">Formatos: MP4, WEBM, JPG, PNG.</small>
                                            <small class="text-info font-weight-bold">Máx <?= (int)$maxVideoSizeMb ?>MB | Cota: <?= (int)$currentVideoCount ?>/<?= (int)$maxVideosAllowed ?></small>
                                        </div>
                                    </div>
                                    <div id="progressContainer" style="display:none; text-align:center;">
                                        <div class="progress mb-2" style="height: 24px;">
                                            <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-success font-weight-bold" style="width: 0%;">0%</div>
                                        </div>
                                        <p class="text-muted font-weight-bold mt-2" id="uploadStatusText">Enviando arquivos...</p>
                                    </div>
                                    <div class="d-flex justify-content-center" style="gap:10px;" id="uploadButtons">
                                        <button type="submit" name="upload_video" value="1" class="btn btn-primary btn-lg px-4">ENVIAR ARQUIVOS</button>
                                        <button type="button" class="btn btn-secondary btn-lg px-4" data-dismiss="modal">CANCELAR</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- 2. MODO PLAYLIST (LISTA DE REPRODUÇÃO)                    -->
            <!-- ========================================================= -->
            <?php if ($tab === 'lista'): ?>
                <?php if ($editListId > 0 && $playlistEdit): ?>
                    <div class="mb-3 d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
                        <h5 class="m-0 font-weight-bold">
                            <a href="?<?= $baseParam ?>tab=lista" class="text-secondary mr-2"><i class="fas fa-arrow-left"></i> Voltar</a>
                            | Editando Playlist: <span class="text-primary"><?= htmlspecialchars($playlistEdit['title']) ?></span>
                        </h5>
                        <button class="btn btn-success font-weight-bold" id="btnSaveOrder"><i class="fas fa-check mr-1"></i> Salvar Ordem</button>
                    </div>

                    <div class="dnd-container">
                        <!-- PAINEL ESQUERDO: Arquivos e Entretenimentos -->
                        <div class="dnd-panel">
                            <ul class="nav nav-tabs mb-2" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active py-1 px-3 font-weight-bold" data-toggle="tab" href="#pane-arquivos" role="tab">
                                        <i class="fas fa-file-video text-primary mr-1"></i> Arquivos
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link py-1 px-3 font-weight-bold" data-toggle="tab" href="#pane-ent" role="tab">
                                        <i class="fas fa-star text-warning mr-1"></i> Entretenimentos
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="pane-arquivos" role="tabpanel">
                                    <div class="dnd-header">
                                        <input type="text" id="searchVideos" class="form-control form-control-sm" placeholder="Procurar arquivo...">
                                    </div>
                                    <div class="video-list" id="sourceList">
                                        <?php foreach ($arquivosOnly as $vid): ?>
                                            <div class="video-item" data-id="<?= (int)$vid['id'] ?>">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <div class="vid-title"><?= htmlspecialchars($vid['title'] ?: $vid['filename']) ?></div>
                                                <i class="fas fa-plus-circle text-success ml-2" style="cursor:pointer;font-size:18px;" onclick="moveToTarget(this)" title="Adicionar à Playlist"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="pane-ent" role="tabpanel">
                                    <div class="dnd-header" style="gap: 8px;">
                                        <input type="text" id="searchEnt" class="form-control form-control-sm" placeholder="Procurar entretenimento ou RSS...">
                                        <button type="button" class="btn btn-sm btn-warning font-weight-bold text-nowrap" data-toggle="modal" data-target="#novoRssModal" title="Cadastrar novo Feed RSS">
                                            <i class="fas fa-rss mr-1"></i> + RSS
                                        </button>
                                    </div>
                                    <div class="video-list" id="sourceEnt">
                                        <?php foreach ($entOnly as $vid): ?>
                                            <div class="video-item" data-id="<?= (int)$vid['id'] ?>">
                                                <i class="fas fa-grip-vertical drag-handle"></i>
                                                <div class="vid-title"><?= htmlspecialchars($vid['title'] ?: $vid['filename']) ?></div>
                                                <?php if (strpos((string)$vid['filename'], '/widgets/') === 0): ?>
                                                    <a href="<?= htmlspecialchars($vid['filename']) ?>" target="_blank" class="text-info ml-2 mr-1 preview-btn" title="Visualizar Tela RSS"><i class="fas fa-eye"></i></a>
                                                <?php endif; ?>
                                                <i class="fas fa-plus-circle text-success ml-2" style="cursor:pointer;font-size:18px;" onclick="moveToTarget(this)" title="Adicionar à Playlist"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PAINEL DIREITO: Lista Final -->
                        <div class="dnd-panel" style="border: 2px solid #28a745;">
                            <div class="dnd-header bg-success text-white" style="margin:-15px -15px 15px -15px; padding: 15px; border-radius: 6px 6px 0 0;">
                                <span><i class="fas fa-list mr-1"></i> Lista Final que será exibida nas Telas</span>
                            </div>
                            <div class="video-list" id="targetList">
                                <?php if (empty($playlistVideosData)): ?>
                                    <div class="empty-placeholder" id="emptyMsg">
                                        <i class="fas fa-hand-point-left" style="font-size:30px; margin-bottom:10px;"></i><br>
                                        CLIQUE NO <b>+</b> OU ARRASTE OS ARQUIVOS PARA CÁ
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($playlistVideosData as $vid): ?>
                                        <div class="video-item" data-id="<?= (int)$vid['id'] ?>" data-duration="<?= (int)($vid['duration'] ?? 15) ?>">
                                            <i class="fas fa-grip-vertical drag-handle"></i>
                                            <div class="vid-title"><?= htmlspecialchars($vid['title'] ?: $vid['filename']) ?></div>
                                            <span class="badge badge-secondary ml-auto mr-2 duration-badge" style="cursor:pointer;" onclick="changeDuration(this)" title="Tempo de exibição (segundos)">
                                                <i class="fas fa-clock"></i> <span class="d-val"><?= (int)($vid['duration'] ?? 15) ?></span>s
                                            </span>
                                            <i class="fas fa-trash text-danger" style="cursor:pointer;" onclick="removeTarget(this)"></i>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Adicionar Novo Canal RSS -->
                    <div class="modal fade" id="novoRssModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-warning text-dark">
                                    <h5 class="modal-title font-weight-bold"><i class="fas fa-rss mr-2"></i> Adicionar Canal de Notícias RSS</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <form method="post" action="?<?= $baseParam ?>tab=lista&edit_list=<?= (int)$editListId ?>">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label class="font-weight-bold">Nome do Canal / Categoria</label>
                                            <input type="text" name="rss_nome" id="rss_nome_input" class="form-control" placeholder="Ex: RSS: UOL Esporte" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="font-weight-bold">URL do Feed RSS (.xml)</label>
                                            <input type="url" name="rss_url" id="rss_url_input" class="form-control" placeholder="https://..." required>
                                        </div>
                                        <div class="form-group mb-0">
                                            <small class="text-muted d-block mb-2 font-weight-bold">Sugestões rápidas (clique para preencher):</small>
                                            <div class="d-flex flex-wrap" style="gap: 6px;">
                                                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="document.getElementById('rss_nome_input').value='RSS: CNN Brasil';document.getElementById('rss_url_input').value='https://www.cnnbrasil.com.br/feed/';">CNN Brasil</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="document.getElementById('rss_nome_input').value='RSS: R7 Notícias';document.getElementById('rss_url_input').value='https://noticias.r7.com/feed.xml';">R7 Notícias</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="document.getElementById('rss_nome_input').value='RSS: Canaltech';document.getElementById('rss_url_input').value='https://rss.canaltech.com.br/rss/';">Canaltech</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="document.getElementById('rss_nome_input').value='RSS: InfoMoney';document.getElementById('rss_url_input').value='https://www.infomoney.com.br/feed/';">InfoMoney</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light border-0">
                                        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                                        <button type="submit" name="add_custom_rss" value="1" class="btn btn-warning font-weight-bold px-4">SALVAR RSS</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <div><i class="fas fa-list-ul text-primary mr-2"></i> Playlist / Listas de Reprodução</div>
                            <button class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#novaListaModal">
                                <i class="fas fa-plus mr-1"></i> CRIAR LISTA
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nome da Playlist</th>
                                            <th>Qtd. Mídias</th>
                                            <th>Status no Player</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($playlistsList)): ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">Nenhuma playlist criada. Clique em <b>CRIAR LISTA</b>!</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($playlistsList as $pl): ?>
                                                <tr>
                                                    <td class="font-weight-bold"><?= htmlspecialchars($pl['title']) ?></td>
                                                    <td><?= (int)$pl['qtd_videos'] ?> arquivos</td>
                                                    <td>
                                                        <?php if ((int)$pl['is_active'] === 1): ?>
                                                            <span class="badge badge-success px-3 py-2"><i class="fas fa-play mr-1"></i> ATIVA NA TELA</span>
                                                        <?php else: ?>
                                                            <form method="post" action="?<?= $baseParam ?>tab=lista" style="display:inline;">
                                                                <input type="hidden" name="id_playlist" value="<?= (int)$pl['id'] ?>">
                                                                <button type="submit" name="set_active" value="1" class="btn btn-sm btn-outline-success">Definir como Ativa</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="?<?= $baseParam ?>tab=lista&edit_list=<?= (int)$pl['id'] ?>" class="btn btn-sm btn-info">
                                                            <i class="fas fa-edit mr-1"></i> Editar Ordem
                                                        </a>
                                                        <form method="post" action="?<?= $baseParam ?>tab=lista" style="display:inline;" onsubmit="return confirm('Apagar esta playlist?');">
                                                            <input type="hidden" name="id_playlist" value="<?= (int)$pl['id'] ?>">
                                                            <button type="submit" name="apagar_lista" value="1" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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

                    <!-- Modal Nova Lista -->
                    <div class="modal fade" id="novaListaModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-light">
                                    <h5 class="modal-title font-weight-bold">Nova Lista de Reprodução</h5>
                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                </div>
                                <div class="modal-body">
                                    <form method="post" action="?<?= $baseParam ?>tab=lista">
                                        <input type="text" name="nome_lista" class="form-control form-control-lg mb-4 text-center" placeholder="Ex: Programação Principal" required>
                                        <div class="d-flex justify-content-center" style="gap:10px;">
                                            <button type="submit" name="criar_lista" value="1" class="btn btn-primary btn-lg px-4">CRIAR LISTA</button>
                                            <button type="button" class="btn btn-light btn-lg px-4" data-dismiss="modal">FECHAR</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- 3. MODO TELAS (PLAYER) - BASEADO NO MODELO ENVIADO        -->
            <!-- ========================================================= -->
            <?php if ($tab === 'telas'): ?>
                <div class="card">
                    <div class="card-header">
                        <div><i class="fas fa-desktop text-primary mr-2"></i> TELAS</div>
                        <button class="btn btn-primary font-weight-bold px-3" data-toggle="modal" data-target="#novaTelaModal">
                            <i class="fas fa-plus mr-1"></i> VINCULAR TELA
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-telas">
                                <thead>
                                    <tr>
                                        <th>NOME</th>
                                        <th>LISTA DE REPRODUÇÃO</th>
                                        <th>CÓDIGO DO PLAYER</th>
                                        <th>STATUS</th>
                                        <th>AÇÕES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($screens)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Nenhuma tela vinculada. Clique em <b>+ VINCULAR TELA</b>!</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($screens as $tela):
                                            $playerLink = $protocol . $host . '/player/' . urlencode($tela['player_code']);
                                            $isOnline = false;
                                            if (!empty($tela['ultimo_ping'])) {
                                                $lastPing = strtotime($tela['ultimo_ping']);
                                                if ($lastPing && (time() - $lastPing) <= 120) {
                                                    $isOnline = true;
                                                }
                                            }
                                            $currentRotation = (int)($tela['rotation'] ?? 0);
                                            $audioMode = $tela['audio_mode'] ?? 'video';
                                            $idRadio = (int)($tela['id_radio'] ?? 0);

                                            $btnClass = 'btn-info';
                                            $btnIcon = 'fa-volume-up';
                                            $btnTitle = 'Áudio do Vídeo';
                                            if ($audioMode === 'mute' || (!empty($tela['muted']) && $audioMode === 'video')) {
                                                $audioMode = 'mute';
                                                $btnClass = 'btn-secondary';
                                                $btnIcon = 'fa-volume-mute';
                                                $btnTitle = 'Mudo';
                                            } elseif ($audioMode === 'radio') {
                                                $btnClass = 'btn-warning';
                                                $btnIcon = 'fa-broadcast-tower';
                                                $btnTitle = 'Rádio Web';
                                            }
                                        ?>
                                            <tr>
                                                <td class="font-weight-bold align-middle tela-nome" data-label="NOME">
                                                    <?= htmlspecialchars($tela['nome_tela'] ?: $tela['title'] ?: ('Tela #' . $tela['id'])) ?>
                                                </td>
                                                <td class="align-middle" data-label="LISTA DE REPRODUÇÃO">
                                                    <select class="form-control form-control-sm select-playlist" data-idtela="<?= (int)$tela['id'] ?>">
                                                        <option value="0">-- Todas as mídias --</option>
                                                        <?php foreach ($playlistsList as $pl): ?>
                                                            <option value="<?= (int)$pl['id'] ?>" <?= ((int)($tela['id_playlist'] ?? 0) === (int)$pl['id']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($pl['title']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td class="align-middle" data-label="CÓDIGO DO PLAYER">
                                                    <div class="input-group input-group-sm">
                                                        <input type="text" class="form-control font-weight-bold text-center" style="background:#e9ecef;" value="<?= htmlspecialchars($tela['player_code']) ?>" readonly>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-outline-secondary" onclick="toggleRotation(<?= (int)$tela['id'] ?>, <?= $currentRotation === 90 ? 0 : 90 ?>)" title="<?= $currentRotation === 90 ? 'Voltar para Horizontal (0°)' : 'Girar para Vertical (90°)' ?>">
                                                                <i class="fas fa-sync-alt" style="<?= $currentRotation === 90 ? 'color:#00bcd4;' : '' ?>"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($playerLink) ?>'); alert('Link do Player copiado!');" title="Copiar Link do Player">
                                                                <i class="far fa-copy"></i>
                                                            </button>
                                                            <a href="<?= htmlspecialchars($playerLink) ?>" target="_blank" class="btn btn-outline-secondary" title="Abrir Player no Navegador">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-middle" data-label="STATUS">
                                                    <?php if ($isOnline): ?>
                                                        <i class="fas fa-circle text-success mr-1"></i> Online
                                                    <?php else: ?>
                                                        <i class="fas fa-circle text-danger mr-1"></i> Offline
                                                    <?php endif; ?>
                                                </td>
                                                <td class="align-middle tela-acoes" data-label="AÇÕES">
                                                    <button class="btn btn-sm <?= $btnClass ?>" onclick="openAudioModal(<?= (int)$tela['id'] ?>, '<?= htmlspecialchars($audioMode) ?>', <?= $idRadio ?>)" title="Áudio: <?= $btnTitle ?>">
                                                        <i class="fas <?= $btnIcon ?>"></i>
                                                        <?php if ($audioMode === 'radio'): ?>
                                                            <span class="font-weight-bold ml-1">Rádio</span>
                                                        <?php endif; ?>
                                                    </button>

                                                    <form method="post" action="?<?= $baseParam ?>tab=telas" style="display:inline-block;" onsubmit="return confirm('Enviar sinal para recarregar esta tela agora?');">
                                                        <input type="hidden" name="id_tela" value="<?= (int)$tela['id'] ?>">
                                                        <button type="submit" name="force_reload" value="1" class="btn btn-sm btn-primary" title="Forçar Atualização na Tela">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    </form>

                                                    <a href="<?= htmlspecialchars($playerLink) ?>" target="_blank" class="btn btn-sm btn-success font-weight-bold" title="Abrir / Instalar App Player">
                                                        <i class="fab fa-android mr-1"></i> APK
                                                    </a>

                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-light" type="button" data-toggle="dropdown">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right shadow border-0">
                                                            <form method="post" action="?<?= $baseParam ?>tab=telas" onsubmit="return confirm('Tem certeza que deseja remover esta tela?');">
                                                                <input type="hidden" name="id_tela" value="<?= (int)$tela['id'] ?>">
                                                                <button type="submit" name="apagar_tela" value="1" class="dropdown-item text-danger">
                                                                    <i class="fas fa-trash-alt mr-2"></i> Remover Tela
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Modal Configurar Áudio -->
                <div class="modal fade" id="audioModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title font-weight-bold">Configurar Áudio da Tela</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" id="audio_id_tela">
                                <div class="form-group mb-3 text-left">
                                    <label class="font-weight-bold">Modo de Áudio</label>
                                    <select class="form-control form-control-lg" id="audio_mode" onchange="toggleRadioSelect()">
                                        <option value="video">🎥 Tocar Áudio do Vídeo Original</option>
                                        <option value="mute">🔇 Silenciado (Mudo)</option>
                                        <option value="radio">📻 Rádio Web de Fundo</option>
                                    </select>
                                </div>
                                <div class="form-group mb-4 text-left" id="div_radio_select" style="display:none;">
                                    <label class="font-weight-bold">Escolha a Rádio</label>
                                    <select class="form-control form-control-lg" id="id_radio">
                                        <option value="0">-- Selecione uma rádio --</option>
                                        <optgroup label="⭐ Minhas Rádios Favoritas">
                                            <?php foreach ($radiosCliente as $r): ?>
                                                <option value="-<?= (int)$r['id'] ?>"><?= htmlspecialchars(str_replace('📻 ', '', $r['title'])) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                        <optgroup label="🌍 Rádios Globais">
                                            <?php foreach ($radiosGlobais as $r): ?>
                                                <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nome']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="d-flex justify-content-center mt-4" style="gap:10px;">
                                    <button type="button" class="btn btn-primary btn-lg px-4" onclick="saveAudioConfig()">SALVAR</button>
                                    <button type="button" class="btn btn-light btn-lg px-4" data-dismiss="modal">FECHAR</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Vincular Nova Tela -->
                <div class="modal fade" id="novaTelaModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title font-weight-bold">Vincular Nova Tela</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body">
                                <form method="post" action="?<?= $baseParam ?>tab=telas">
                                    <div class="form-group mb-3 text-left">
                                        <label class="font-weight-bold">Nome da Tela / Carro</label>
                                        <input type="text" name="nome_tela" class="form-control form-control-lg" placeholder="Ex: Tablet Banco Traseiro" required>
                                    </div>
                                    <div class="form-group mb-4 text-left">
                                        <label class="font-weight-bold">Código do Player (ou deixe em branco p/ gerar)</label>
                                        <input type="text" name="codigo_tela" class="form-control form-control-lg text-uppercase" placeholder="Ex: NRQ2FK" style="letter-spacing: 3px; font-weight: bold;">
                                    </div>
                                    <div class="d-flex justify-content-center" style="gap:10px;">
                                        <button type="submit" name="vincular_tela" value="1" class="btn btn-primary btn-lg px-4">VINCULAR TELA</button>
                                        <button type="button" class="btn btn-light btn-lg px-4" data-dismiss="modal">CANCELAR</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- 4. MODO ÁREA DO AFILIADO                                  -->
            <!-- ========================================================= -->
            <?php if ($tab === 'afiliado'): ?>
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card mb-0 border-0 shadow-sm" style="border-bottom: 4px solid #28a745 !important;">
                            <div class="card-body text-center py-4">
                                <small class="text-muted font-weight-bold text-uppercase">Saldo Disponível</small>
                                <h2 class="font-weight-bold text-success mt-2 mb-0">R$ <?= number_format($affiliateBalance, 2, ',', '.') ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card mb-0 border-0 shadow-sm" style="border-bottom: 4px solid #007bff !important;">
                            <div class="card-body text-center py-4">
                                <small class="text-muted font-weight-bold text-uppercase">Cliques no Link</small>
                                <h2 class="font-weight-bold text-primary mt-2 mb-0"><?= (int)($client['affiliateClicks'] ?? 0) ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card mb-0 border-0 shadow-sm" style="border-bottom: 4px solid #ffc107 !important;">
                            <div class="card-body text-center py-4">
                                <small class="text-muted font-weight-bold text-uppercase">Minha Comissão</small>
                                <h2 class="font-weight-bold text-warning mt-2 mb-0"><?= number_format((float)($client['commissionPercent'] ?? 20), 0) ?>%</h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div><i class="fas fa-hand-holding-usd text-warning mr-2"></i> Meu Link de Indicação (Afiliado)</div>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <p class="text-muted">Compartilhe seu link exclusivo ou QR Code com outros motoristas e parceiros. Você recebe <b><?= number_format((float)($client['commissionPercent'] ?? 20), 0) ?>% de comissão</b> recorrente!</p>
                                <div class="input-group input-group-lg mb-3">
                                    <input type="text" class="form-control font-weight-bold" id="affLinkInput" value="<?= htmlspecialchars($affiliateLink) ?>" readonly>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary font-weight-bold" type="button" onclick="navigator.clipboard.writeText(document.getElementById('affLinkInput').value); alert('Link de afiliado copiado!');">
                                            <i class="fas fa-copy mr-1"></i> Copiar Link
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div id="affQrCode" class="p-2 bg-white border rounded d-inline-block shadow-sm"></div>
                                <small class="d-block text-muted mt-1">QR Code de Indicação</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div><i class="fas fa-key text-info mr-2"></i> Dados de Recebimento (PIX)</div>
                            </div>
                            <div class="card-body">
                                <form method="post" action="?<?= $baseParam ?>tab=afiliado">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Minha Chave PIX</label>
                                        <input type="text" name="pixKey" class="form-control" value="<?= htmlspecialchars($client['pixKey'] ?? '') ?>" placeholder="CPF, CNPJ, E-mail, Telefone ou Chave Aleatória" required>
                                    </div>
                                    <button type="submit" name="update_pix" value="1" class="btn btn-primary font-weight-bold w-100">
                                        <i class="fas fa-save mr-1"></i> Salvar Chave PIX
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <div><i class="fas fa-money-bill-wave text-success mr-2"></i> Solicitar Saque</div>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted">O valor disponível será transferido diretamente para a sua chave PIX cadastrada.</p>
                                <form method="post" action="?<?= $baseParam ?>tab=afiliado">
                                    <button type="submit" name="withdraw" value="1" class="btn btn-success btn-lg font-weight-bold px-5" <?= $affiliateBalance <= 0 ? 'disabled' : '' ?>>
                                        <i class="fas fa-hand-holding-usd mr-1"></i> Sacar R$ <?= number_format($affiliateBalance, 2, ',', '.') ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ========================================================= -->
            <!-- 5. MODO CONFIGURAÇÕES (PERFIL, FINANCEIRO E FRANQUIA)     -->
            <!-- ========================================================= -->
            <?php if ($tab === 'config'): ?>
                <div class="card mb-4 shadow-sm">
                    <div class="card-header">
                        <div><i class="fas fa-user text-primary mr-2"></i> Perfil & Senha de Acesso</div>
                    </div>
                    <div class="card-body">
                        <form method="post" action="?<?= $baseParam ?>tab=config" class="row align-items-end">
                            <div class="col-md-6 form-group mb-md-0">
                                <label class="font-weight-bold">Alterar Senha de Acesso</label>
                                <input type="password" name="nova_senha" class="form-control" placeholder="Digite a nova senha..." required>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="atualizar_senha" value="1" class="btn btn-primary font-weight-bold">
                                    <i class="fas fa-save mr-1"></i> SALVAR NOVA SENHA
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-4" style="border-top: 4px solid #1abc9c !important;">
                    <div class="card-body text-center py-5">
                        <div class="mb-3">
                            <i class="fas fa-wallet fa-3x" style="color: #1abc9c;"></i>
                        </div>
                        <h3 class="font-weight-bold" style="color: #1abc9c;">Financeiro & Faturas</h3>
                        <p class="text-muted mb-4">Visualize faturas, realize pagamentos e mantenha seus dados de faturamento atualizados.</p>
                        <div class="d-flex justify-content-center flex-wrap" style="gap: 12px;">
                            <button class="btn btn-lg text-white font-weight-bold" style="background-color: #1abc9c;" data-toggle="modal" data-target="#faturasModal">
                                <i class="fas fa-file-invoice-dollar mr-2"></i> VISUALIZAR FATURAS
                            </button>
                            <button class="btn btn-lg btn-primary font-weight-bold" data-toggle="modal" data-target="#dadosFaturamentoModal">
                                <i class="fas fa-pen mr-2"></i> DADOS DE FATURAMENTO
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modal Faturas -->
                <div class="modal fade" id="faturasModal" tabindex="-1">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title font-weight-bold"><i class="fas fa-wallet mr-2"></i> FINANCEIRO / FATURAS</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <div class="modal-body bg-light">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm mb-2" style="border-bottom: 4px solid #1abc9c !important;">
                                            <div class="card-body">
                                                <small class="text-muted font-weight-bold text-uppercase">Valor Pago Este Mês</small>
                                                <h4 class="mt-2 font-weight-bold">R$ <?= number_format($totalAcumulado, 2, ',', '.') ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm mb-2" style="background-color: #0f172a; color: white;">
                                            <div class="card-body">
                                                <small class="text-white-50 font-weight-bold text-uppercase">Telas Vinculadas</small>
                                                <h4 class="mt-2 font-weight-bold text-white"><?= count($screens) ?> Telas</h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm mb-2" style="border-bottom: 4px solid #ffaa00 !important;">
                                            <div class="card-body">
                                                <small class="text-muted font-weight-bold text-uppercase">Previsto Próxima Fatura</small>
                                                <h4 class="mt-2 font-weight-bold">R$ <?= number_format($totalPrevisto, 2, ',', '.') ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-0">
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0 text-center">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Data Gerada</th>
                                                        <th>Vencimento</th>
                                                        <th>Valor Total</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($faturas)): ?>
                                                        <tr><td colspan="5" class="py-4 text-muted">Nenhuma fatura em aberto no momento.</td></tr>
                                                    <?php else: ?>
                                                        <?php foreach ($faturas as $fat): ?>
                                                            <tr>
                                                                <td>#<?= (int)$fat['id'] ?></td>
                                                                <td><?= !empty($fat['data_gerada']) ? date('d/m/Y', strtotime($fat['data_gerada'])) : '-' ?></td>
                                                                <td><?= !empty($fat['data_vencimento']) ? date('d/m/Y', strtotime($fat['data_vencimento'])) : '-' ?></td>
                                                                <td class="font-weight-bold">R$ <?= number_format((float)$fat['valor_total'], 2, ',', '.') ?></td>
                                                                <td>
                                                                    <?php if (($fat['status'] ?? '') === 'PAGA'): ?>
                                                                        <span class="badge badge-success px-3 py-2">PAGA</span>
                                                                    <?php elseif (($fat['status'] ?? '') === 'VENCIDA'): ?>
                                                                        <span class="badge badge-danger px-3 py-2">VENCIDA</span>
                                                                    <?php else: ?>
                                                                        <span class="badge badge-warning px-3 py-2">PENDENTE</span>
                                                                    <?php endif; ?>
                                                                </td>
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

                <!-- Modal Dados Faturamento -->
                <div class="modal fade" id="dadosFaturamentoModal" tabindex="-1">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-light">
                                <h5 class="modal-title font-weight-bold"><i class="fas fa-pen mr-2"></i> DADOS DE FATURAMENTO</h5>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                            </div>
                            <form method="post" action="?<?= $baseParam ?>tab=config">
                                <div class="modal-body p-4">
                                    <div class="row">
                                        <div class="col-md-12 form-group">
                                            <label class="font-weight-bold">Nome Completo / Razão Social</label>
                                            <input type="text" name="nome_razao" class="form-control" value="<?= htmlspecialchars($faturamento['nome_razao'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="font-weight-bold">CPF / CNPJ</label>
                                            <input type="text" name="cpf_cnpj" class="form-control" value="<?= htmlspecialchars($faturamento['cpf_cnpj'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6 form-group">
                                            <label class="font-weight-bold">WhatsApp</label>
                                            <input type="text" name="whats" class="form-control" value="<?= htmlspecialchars($faturamento['whats'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-12 form-group">
                                            <label class="font-weight-bold">Endereço Completo</label>
                                            <input type="text" name="endereco" class="form-control" value="<?= htmlspecialchars($faturamento['endereco'] ?? '') ?>" placeholder="Rua, Número, Bairro, Cidade - UF">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light border-0">
                                    <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                                    <button type="submit" name="atualizar_faturamento" value="1" class="btn btn-primary font-weight-bold px-4">SALVAR DADOS</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
<script>
// Menu Mobile
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const sidebar = document.querySelector('.sidebar');
if (mobileMenuBtn && sidebarOverlay && sidebar) {
    mobileMenuBtn.addEventListener('click', () => {
        sidebar.classList.add('show');
        sidebarOverlay.classList.add('show');
    });
    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });
}

// Busca de Motorista (Modo Root)
const clientSearchInput = document.getElementById('clientSearchInput');
const searchDropdown = document.getElementById('searchResultsDropdown');
if (clientSearchInput && searchDropdown) {
    clientSearchInput.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        const items = searchDropdown.querySelectorAll('.search-item');
        let visible = false;
        if (q.length > 0) {
            searchDropdown.classList.remove('d-none');
            items.forEach(it => {
                const match = (it.dataset.title || '').includes(q) || (it.dataset.user || '').includes(q) || (it.dataset.whats || '').includes(q) || (it.dataset.id || '').includes(q);
                it.classList.toggle('d-none', !match);
                if (match) visible = true;
            });
            if (!visible) searchDropdown.classList.add('d-none');
        } else {
            searchDropdown.classList.add('d-none');
        }
    });
}

// Ações da Aba Telas
document.querySelectorAll('.select-playlist').forEach(select => {
    select.addEventListener('change', function() {
        const idTela = this.getAttribute('data-idtela');
        const idPlaylist = this.value;
        $.post(window.location.pathname + '?<?= $baseParam ?>tab=telas', {
            ajax_action: 'vincular_playlist',
            id_tela: idTela,
            id_playlist: idPlaylist
        }, function(res) {
            if (res && res.success) {
                select.style.borderColor = '#28a745';
                setTimeout(() => select.style.borderColor = '', 2000);
            }
        }, 'json');
    });
});

function toggleRotation(idTela, newRotation) {
    $.post(window.location.pathname + '?<?= $baseParam ?>tab=telas', {
        ajax_action: 'toggle_rotation',
        id_tela: idTela,
        rotation: newRotation
    }, function(res) {
        if (res && res.success) {
            window.location.reload();
        }
    }, 'json');
}

function openAudioModal(idTela, mode, idRadio) {
    $('#audio_id_tela').val(idTela);
    $('#audio_mode').val(mode);
    $('#id_radio').val(idRadio);
    toggleRadioSelect();
    $('#audioModal').modal('show');
}

function toggleRadioSelect() {
    if ($('#audio_mode').val() === 'radio') {
        $('#div_radio_select').show();
    } else {
        $('#div_radio_select').hide();
    }
}

function saveAudioConfig() {
    $.post(window.location.pathname + '?<?= $baseParam ?>tab=telas', {
        ajax_action: 'set_audio',
        id_tela: $('#audio_id_tela').val(),
        audio_mode: $('#audio_mode').val(),
        id_radio: $('#id_radio').val()
    }, function(res) {
        if (res && res.success) {
            window.location.reload();
        }
    }, 'json');
}

// Buscador de Rádios (Aba Arquivos)
function searchRadio() {
    const input = document.getElementById('radioSearchInput');
    if (!input || !input.value.trim()) return;
    const resDiv = document.getElementById('radioSearchResults');
    resDiv.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin text-success" style="font-size:24px;"></i><br>Buscando...</div>';

    fetch(window.location.pathname + '?<?= $baseParam ?>action=search_radios&q=' + encodeURIComponent(input.value.trim()))
        .then(r => r.json())
        .then(data => {
            if (!data || data.length === 0) {
                resDiv.innerHTML = '<div class="text-warning text-center py-2">Nenhuma rádio encontrada.</div>';
                return;
            }
            let html = '<ul class="list-group list-group-flush border border-secondary rounded" style="max-height: 280px; overflow-y: auto;">';
            data.forEach(st => {
                if (st.url_resolved) {
                    const safeName = (st.name || '').replace(/"/g, '&quot;');
                    html += `<li class="list-group-item d-flex justify-content-between align-items-center" style="background-color:#23262d;border-color:#2a2d35;">
                        <div>
                            <strong class="text-white">${st.name}</strong><br>
                            <small class="text-muted">${st.country || 'Brasil'}</small>
                        </div>
                        <form method="post" action="?<?= $baseParam ?>tab=arquivos" class="m-0">
                            <input type="hidden" name="add_radio_name" value="${safeName}">
                            <input type="hidden" name="add_radio_url" value="${st.url_resolved}">
                            <button type="submit" name="add_radio_submit" value="1" class="btn btn-sm btn-success">Adicionar</button>
                        </form>
                    </li>`;
                }
            });
            html += '</ul>';
            resDiv.innerHTML = html;
        })
        .catch(() => {
            resDiv.innerHTML = '<div class="text-danger text-center py-2">Erro ao buscar rádios.</div>';
        });
}

// Upload com Progresso (Aba Arquivos)
const uploadForm = document.getElementById('uploadForm');
if (uploadForm) {
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const files = document.getElementById('videoFileInput').files;
        if (!files || files.length === 0) return;

        document.getElementById('uploadInputGroup').style.display = 'none';
        document.getElementById('uploadButtons').style.display = 'none';
        document.getElementById('progressContainer').style.display = 'block';

        const formData = new FormData(this);
        formData.append('upload_video', '1');
        formData.append('ajax_upload', '1');

        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.pathname + '?<?= $baseParam ?>tab=arquivos', true);
        xhr.upload.onprogress = function(ev) {
            if (ev.lengthComputable) {
                const pct = Math.round((ev.loaded / ev.total) * 100);
                const bar = document.getElementById('uploadProgressBar');
                bar.style.width = pct + '%';
                bar.innerText = pct + '%';
            }
        };
        xhr.onload = function() {
            window.location.href = '?<?= $baseParam ?>tab=arquivos';
        };
        xhr.onerror = function() {
            alert('Erro de conexão durante o envio.');
            window.location.reload();
        };
        xhr.send(formData);
    });
}

// Filtro de busca nas abas Arquivos e Entretenimentos
document.getElementById('searchVideos')?.addEventListener('keyup', function() {
    const f = this.value.toLowerCase();
    document.querySelectorAll('#sourceList .video-item').forEach(it => {
        const txt = (it.querySelector('.vid-title')?.innerText || '').toLowerCase();
        it.style.display = txt.includes(f) ? '' : 'none';
    });
});
document.getElementById('searchEnt')?.addEventListener('keyup', function() {
    const f = this.value.toLowerCase();
    document.querySelectorAll('#sourceEnt .video-item').forEach(it => {
        const txt = (it.querySelector('.vid-title')?.innerText || '').toLowerCase();
        it.style.display = txt.includes(f) ? '' : 'none';
    });
});

// Drag & Drop Editor (Aba Playlist)
if (document.getElementById('sourceList') && document.getElementById('targetList')) {
    new Sortable(document.getElementById('sourceList'), {
        group: { name: 'shared', pull: 'clone', put: false },
        sort: false,
        animation: 150,
        handle: '.drag-handle'
    });
    if (document.getElementById('sourceEnt')) {
        new Sortable(document.getElementById('sourceEnt'), {
            group: { name: 'shared', pull: 'clone', put: false },
            sort: false,
            animation: 150,
            handle: '.drag-handle'
        });
    }
    new Sortable(document.getElementById('targetList'), {
        group: 'shared',
        animation: 150,
        handle: '.drag-handle',
        ghostClass: 'ghost-class',
        onAdd: function(evt) {
            const item = evt.item;
            item.setAttribute('data-duration', '15');
            const icon = item.querySelector('.fa-plus-circle');
            if (icon) {
                const badge = document.createElement('span');
                badge.className = 'badge badge-secondary ml-auto mr-2 duration-badge';
                badge.style.cursor = 'pointer';
                badge.innerHTML = '<i class="fas fa-clock"></i> <span class="d-val">15</span>s';
                badge.onclick = function() { changeDuration(badge); };
                item.insertBefore(badge, icon);
                icon.className = 'fas fa-trash text-danger';
                icon.setAttribute('onclick', 'removeTarget(this)');
            }
            document.getElementById('emptyMsg')?.remove();
        }
    });

    document.getElementById('btnSaveOrder')?.addEventListener('click', function() {
        const items = document.querySelectorAll('#targetList .video-item');
        const dataToSave = [];
        items.forEach(it => {
            dataToSave.push({
                id: it.getAttribute('data-id'),
                duration: it.getAttribute('data-duration') || 15
            });
        });
        const btn = this;
        const oldHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
        $.post(window.location.pathname + '?<?= $baseParam ?>tab=lista', {
            ajax_action: 'save_order',
            id_playlist: <?= (int)$editListId ?>,
            video_data: JSON.stringify(dataToSave)
        }, function(res) {
            if (res && res.success) {
                btn.innerHTML = '<i class="fas fa-check-double"></i> Ordem Salva!';
                setTimeout(() => { btn.innerHTML = oldHtml; }, 2000);
            } else {
                btn.innerHTML = oldHtml;
                alert('Erro ao salvar a ordem da playlist.');
            }
        }, 'json');
    });
}

function moveToTarget(iconEl) {
    const item = iconEl.closest('.video-item').cloneNode(true);
    item.setAttribute('data-duration', '15');
    const icon = item.querySelector('.fa-plus-circle');
    const badge = document.createElement('span');
    badge.className = 'badge badge-secondary ml-auto mr-2 duration-badge';
    badge.style.cursor = 'pointer';
    badge.innerHTML = '<i class="fas fa-clock"></i> <span class="d-val">15</span>s';
    badge.onclick = function() { changeDuration(badge); };
    item.insertBefore(badge, icon);
    icon.className = 'fas fa-trash text-danger';
    icon.setAttribute('onclick', 'removeTarget(this)');
    document.getElementById('targetList').appendChild(item);
    document.getElementById('emptyMsg')?.remove();
}

function removeTarget(iconEl) {
    iconEl.closest('.video-item').remove();
}

function changeDuration(el) {
    const item = el.closest('.video-item');
    const cur = item.getAttribute('data-duration') || '15';
    const val = prompt('Tempo de exibição (em segundos):', cur);
    if (val !== null && !isNaN(val) && parseInt(val, 10) > 0) {
        item.setAttribute('data-duration', parseInt(val, 10));
        el.querySelector('.d-val').innerText = parseInt(val, 10);
    }
}

// QR Code Afiliado
if (document.getElementById('affQrCode') && typeof QRCodeStyling !== 'undefined') {
    const qr = new QRCodeStyling({
        width: 140,
        height: 140,
        data: <?= json_encode($affiliateLink) ?>,
        dotsOptions: { color: "#0f172a", type: "rounded" },
        backgroundOptions: { color: "#ffffff" }
    });
    qr.append(document.getElementById('affQrCode'));
}
</script>
</body>
</html>
