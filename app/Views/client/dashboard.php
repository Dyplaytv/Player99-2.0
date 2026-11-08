<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(!empty($customization['brand_name']) ? $customization['brand_name'] : 'Portal do Parceiro') ?> - Player99</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-brand: <?= !empty($customization['primary_color']) ? htmlspecialchars($customization['primary_color']) : '#34e2e4' ?>;
        }
        body {
            background-color: #0b0d14;
            color: #eef0f8;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: rgba(11, 13, 20, 0.85); }
        ::-webkit-scrollbar-thumb { background: rgba(52, 226, 228, 0.25); border-radius: 10px; }
        .glass-card {
            background: #141824;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.35);
        }
        .btn-cyan {
            background: linear-gradient(135deg, #0A38D1 0%, var(--primary-brand) 100%);
            color: #ffffff;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            transition: all 0.2s ease;
        }
        .btn-cyan:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            color: #fff;
        }
        .custom-brand-logo {
            max-height: 42px;
            max-width: 180px;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.5));
        }
        /* Dropdown com Busca em Tempo Real */
        .search-results-list {
            max-height: 250px;
            overflow-y: auto;
            position: absolute;
            width: 100%;
            z-index: 1050;
            background: #141824;
            border: 1px solid rgba(52, 226, 228, 0.3);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }
        .search-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: background 0.15s;
        }
        .search-item:hover {
            background: rgba(52, 226, 228, 0.15);
        }

        /* Melhoria de contraste e legibilidade das labels e textos secundários */
        .form-label, label {
            color: #d1d8f0 !important;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .text-muted {
            color: #b0b9d6 !important;
        }
        .text-white-50 {
            color: #cdd5ee !important;
        }
        .form-control, .form-select {
            color: #ffffff !important;
            background-color: #121520 !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
        }
        .form-control::placeholder {
            color: #7983a3 !important;
        }
    </style>
</head>
<body>

    <!-- BARRA SUPERIOR -->
    <nav class="navbar navbar-dark border-bottom border-secondary border-opacity-25 px-4 py-3" style="background: #12151f;">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/painel-cliente">
                <img src="<?= htmlspecialchars(!empty($customization['logo_path']) ? $customization['logo_path'] : '/banners/logo.png') ?>?v=<?= time() ?>" alt="Logo" class="custom-brand-logo" onerror="this.onerror=null; this.src='/assets/images/logo.png';">
                <span class="ms-1"><?= htmlspecialchars(!empty($customization['brand_name']) ? $customization['brand_name'] : 'Player99') ?></span>
                <span class="badge bg-info bg-opacity-10 text-info ms-2">Portal da Franquia & Parceiro</span>
            </a>

            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small">
                    <i class="fa fa-user-circle me-1" style="color: var(--primary-brand);"></i> 
                    <?= htmlspecialchars($client['title'] ?? $client['username']) ?>
                </span>
                <?php if ($isAdmin): ?>
                    <a href="/admin/dashboard" class="btn btn-sm btn-outline-info">
                        <i class="fa fa-shield-alt me-1"></i> Painel Admin Root
                    </a>
                <?php endif; ?>
                <a href="/sair" class="btn btn-sm btn-outline-danger"><i class="fa fa-power-off me-1"></i> Sair</a>
            </div>
        </div>
    </nav>

    <!-- BARRA DE SELEÇÃO E BUSCA DE CLIENTE (VISÍVEL APENAS PARA ADMIN/ROOT/FRANQUEADOR) -->
    <?php if ($isAdmin): ?>
        <div class="py-3 px-4 border-bottom border-secondary border-opacity-25" style="background: rgba(18, 20, 30, 0.95);">
            <div class="container">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-5">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-2 py-1">
                                <i class="fa fa-crown me-1"></i> Modo Root / Franqueador
                            </span>
                            <span class="text-white-50 small">Gerenciando: <b><?= htmlspecialchars($client['title'] ?? $client['username']) ?></b> (#<?= $selectedClientId ?>)</span>
                        </div>
                    </div>

                    <!-- Buscador Instantâneo com Dropdown Filtrável -->
                    <div class="col-12 col-md-7 position-relative">
                        <div class="input-group">
                            <span class="input-group-text bg-dark text-info border-secondary">
                                <i class="fa fa-search"></i>
                            </span>
                            <input type="text" id="clientSearchInput" class="form-control bg-dark text-white border-secondary" placeholder="Digite o nome, usuário ou whatsapp do cliente/franqueado para filtrar..." autocomplete="off">
                            <button class="btn btn-outline-secondary" type="button" onclick="clearClientSearch()">Limpar</button>
                        </div>

                        <!-- Dropdown de Resultados Filtrados -->
                        <div id="searchResultsDropdown" class="search-results-list d-none">
                            <?php foreach ($allClients as $cli): ?>
                                <div class="search-item d-flex justify-content-between align-items-center" 
                                     data-id="<?= $cli['id'] ?>"
                                     data-title="<?= strtolower(htmlspecialchars($cli['title'] ?? '')) ?>"
                                     data-user="<?= strtolower(htmlspecialchars($cli['username'] ?? '')) ?>"
                                     data-whats="<?= htmlspecialchars($cli['whatsapp'] ?? '') ?>"
                                     onclick="selectClient(<?= $cli['id'] ?>)">
                                    <div>
                                        <div class="fw-bold text-white"><?= htmlspecialchars(!empty($cli['title']) ? $cli['title'] : $cli['username']) ?></div>
                                        <small class="text-muted">@<?= htmlspecialchars($cli['username']) ?> <?= !empty($cli['whatsapp']) ? '• ' . htmlspecialchars($cli['whatsapp']) : '' ?></small>
                                    </div>
                                    <span class="badge bg-info bg-opacity-15 text-info">#<?= $cli['id'] ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="container py-4">
        <?php if (!empty($msg)): ?>
            <div class="alert alert-success border-0 mb-4" style="background: rgba(16, 185, 129, 0.15); color: #6ee7b7;">
                <i class="fa fa-check-circle me-2"></i> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($err)): ?>
            <div class="alert alert-danger border-0 mb-4">
                <i class="fa fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($err) ?>
            </div>
        <?php endif; ?>

        <!-- ABAS DE NAVEGAÇÃO DO PAINEL -->
        <ul class="nav nav-pills mb-4 gap-2" id="clientTabs" role="tablist">
            <li class="nav-item">
                <button class="btn btn-outline-light active px-4 fw-bold" id="tab-telas-btn" data-bs-toggle="pill" data-bs-target="#tab-telas" type="button">
                    <i class="fa fa-tv me-2 text-info"></i> Telas & Mídias
                </button>
            </li>
            <li class="nav-item">
                <button class="btn btn-outline-light px-4 fw-bold" id="tab-custom-btn" data-bs-toggle="pill" data-bs-target="#tab-custom" type="button">
                    <i class="fa fa-palette me-2 text-warning"></i> Personalização da Franquia & Página
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- ABA 1: TELAS E MÍDIAS -->
            <div class="tab-pane fade show active" id="tab-telas">
                <div class="row g-4">
                    <!-- Coluna Telas -->
                    <div class="col-12 col-lg-5">
                        <div class="glass-card mb-4">
                            <h5 class="fw-bold mb-3"><i class="fa fa-tablet-screen-button text-info me-2"></i> Vincular Nova Tela / Carro</h5>
                            <form method="POST" action="/painel-cliente?client_id=<?= $selectedClientId ?>">
                                <input type="hidden" name="action" value="add_screen">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold">NOME DO CARRO OU PONTO</label>
                                    <input type="text" name="title" class="form-control" placeholder="Ex: Carro 01 - Placa ABC" required>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">CÓDIGO (OPCIONAL)</label>
                                        <input type="text" name="player_code" class="form-control text-info font-monospace" placeholder="Automático">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small fw-bold">ORIENTAÇÃO</label>
                                        <select name="rotation" class="form-select">
                                            <option value="0">Horizontal (0°)</option>
                                            <option value="90">Vertical (90°)</option>
                                        </select>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-cyan w-100"><i class="fa fa-plus me-1"></i> Criar Tela</button>
                            </form>
                        </div>

                        <div class="glass-card">
                            <h5 class="fw-bold mb-3"><i class="fa fa-tv text-success me-2"></i> Telas Ativas deste Cliente (<?= count($screens) ?>)</h5>
                            <?php if (empty($screens)): ?>
                                <p class="text-muted small mb-0">Nenhuma tela vinculada ainda. Crie uma acima!</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($screens as $s): ?>
                                        <div class="p-3 rounded-3 border border-secondary border-opacity-25 bg-dark d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($s['title'] ?: $s['nome_tela'] ?: 'Tela #' . $s['id']) ?></div>
                                                <small class="text-info font-monospace">Código: <?= htmlspecialchars($s['player_code']) ?></small>
                                            </div>
                                            <a href="/player/<?= htmlspecialchars($s['player_code']) ?>" target="_blank" class="btn btn-sm btn-cyan">
                                                <i class="fa fa-play me-1"></i> Abrir Player
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Coluna Mídias -->
                    <div class="col-12 col-lg-7">
                        <div class="glass-card mb-4">
                            <h5 class="fw-bold mb-3"><i class="fa fa-cloud-arrow-up text-info me-2"></i> Enviar Vídeo ou Banner Publicitário</h5>
                            <form method="POST" action="/painel-cliente?client_id=<?= $selectedClientId ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
                                <input type="hidden" name="action" value="upload_media">
                                <div class="col-12 col-md-5">
                                    <label class="form-label small fw-bold">TÍTULO DO ANÚNCIO</label>
                                    <input type="text" name="media_title" class="form-control" placeholder="Ex: Campanha Local">
                                </div>
                                <div class="col-12 col-md-5">
                                    <label class="form-label small fw-bold">ARQUIVO (MP4, JPG, PNG)</label>
                                    <input type="file" name="media_file" class="form-control" accept=".mp4,.webm,.jpg,.jpeg,.png,.webp" required>
                                </div>
                                <div class="col-12 col-md-2">
                                    <button type="submit" class="btn btn-cyan w-100" style="height: 38px;"><i class="fa fa-upload"></i></button>
                                </div>
                            </form>
                        </div>

                        <div class="glass-card">
                            <h5 class="fw-bold mb-3"><i class="fa fa-photo-film text-warning me-2"></i> Playlist em Reprodução (<?= count($videos) ?>)</h5>
                            <?php if (empty($videos)): ?>
                                <div class="text-center py-4 text-muted">
                                    <i class="fa fa-film fa-2x mb-2 opacity-50"></i>
                                    <p class="mb-0">Envie seu primeiro vídeo ou imagem acima para iniciar a transmissão.</p>
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($videos as $v): 
                                        $ext = strtolower(pathinfo($v['filename'], PATHINFO_EXTENSION));
                                        $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                        $url = '/uploads/clientes/' . $client['id'] . '/' . $v['filename'];
                                    ?>
                                        <div class="col-12 col-md-6">
                                            <div class="p-2 rounded-3 bg-dark border border-secondary border-opacity-25">
                                                <div style="height: 130px; background: #000; border-radius: 6px; overflow: hidden;" class="mb-2 d-flex align-items-center justify-content-center">
                                                    <?php if ($isImg): ?>
                                                        <img src="<?= htmlspecialchars($url) ?>" style="width:100%; height:100%; object-fit:cover;">
                                                    <?php else: ?>
                                                        <video src="<?= htmlspecialchars($url) ?>" style="width:100%; height:100%; object-fit:cover;" controls muted></video>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center px-1">
                                                    <span class="small fw-semibold text-truncate" style="max-width: 170px;"><?= htmlspecialchars($v['title']) ?></span>
                                                    <form method="POST" action="/painel-cliente?client_id=<?= $selectedClientId ?>">
                                                        <input type="hidden" name="action" value="delete_media">
                                                        <input type="hidden" name="video_id" value="<?= $v['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2"><i class="fa fa-trash"></i></button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ABA 2: PERSONALIZAÇÃO DA PÁGINA / FRANQUIA -->
            <div class="tab-pane fade" id="tab-custom">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                        <div>
                            <h5 class="fw-bold mb-1 text-white"><i class="fa fa-palette text-warning me-2"></i> Customização da Identidade Visual & Franquia</h5>
                            <p class="text-muted small mb-0">Personalize o logotipo, cores e informações da página deste cliente/franqueado.</p>
                        </div>
                        <span class="badge bg-secondary bg-opacity-25 text-white-50 px-3 py-2">
                            Cliente ID: #<?= $selectedClientId ?>
                        </span>
                    </div>

                    <form method="POST" action="/painel-cliente?client_id=<?= $selectedClientId ?>" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_customization">

                        <div class="row g-4">
                            <!-- Prévia e Upload da Logo Customizada -->
                            <div class="col-12 col-md-4 text-center">
                                <label class="form-label small fw-bold d-block mb-2">LOGOMARCA DA FRANQUIA / CLIENTE</label>
                                <div class="p-3 rounded-4 mb-3 d-inline-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.6); border: 2px dashed rgba(52, 226, 228, 0.3); min-width: 220px; min-height: 120px;">
                                    <img src="<?= htmlspecialchars(!empty($customization['logo_path']) ? $customization['logo_path'] : '/banners/logo.png') ?>?v=<?= time() ?>" alt="Logo Cliente" style="max-height: 90px; max-width: 100%; object-fit: contain;" onerror="this.onerror=null; this.src='/assets/images/logo.png';">
                                </div>
                                <input type="file" name="custom_logo" class="form-control" accept="image/*">
                                <small class="text-muted d-block mt-2" style="font-size: 11px;">PNG ou WEBP. Se não enviar, carrega a logo padrão da Player99.</small>
                            </div>

                            <!-- Dados da Franquia e Cores -->
                            <div class="col-12 col-md-8">
                                <div class="row g-3">
                                    <div class="col-12 col-sm-8">
                                        <label class="form-label text-muted small fw-bold">NOME DA EMPRESA / FRANQUIA</label>
                                        <input type="text" name="brand_name" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($customization['brand_name'] ?? '') ?>" placeholder="Ex: Player99 - Franquia Florianópolis" required>
                                    </div>

                                    <div class="col-12 col-sm-4">
                                        <label class="form-label text-muted small fw-bold">COR PRINCIPAL (DESTAQUE)</label>
                                        <div class="d-flex align-items-center gap-2">
                                            <input type="color" name="primary_color" class="form-control form-control-color bg-dark border-secondary" value="<?= htmlspecialchars($customization['primary_color'] ?? '#34e2e4') ?>" style="width: 50px; height: 38px;">
                                            <input type="text" class="form-control bg-dark text-white border-secondary font-monospace" value="<?= htmlspecialchars($customization['primary_color'] ?? '#34e2e4') ?>" readonly>
                                        </div>
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label class="form-label text-muted small fw-bold">WHATSAPP DE CONTATO / SUPORTE</label>
                                        <input type="text" name="support_whatsapp" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($customization['support_whatsapp'] ?? '') ?>" placeholder="Ex: (51) 99999-9999">
                                    </div>

                                    <div class="col-12 col-sm-6">
                                        <label class="form-label text-muted small fw-bold">E-MAIL DE ATENDIMENTO</label>
                                        <input type="email" name="support_email" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($customization['support_email'] ?? '') ?>" placeholder="contato@empresa.com">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label text-muted small fw-bold">COMUNICADO OU MENSAGEM DO PAINEL</label>
                                        <textarea name="custom_announcement" rows="3" class="form-control bg-dark text-white border-secondary" placeholder="Mensagem personalizada exibida aos motoristas e clientes deste franqueado..."><?= htmlspecialchars($customization['custom_announcement'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 text-end">
                            <button type="submit" class="btn btn-cyan px-5 fw-bold">
                                <i class="fa fa-save me-1"></i> Salvar Personalização da Franquia
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Live Search e Filtro em Tempo Real de Clientes (Modo Root/Franquia)
    const searchInput = document.getElementById('clientSearchInput');
    const dropdown = document.getElementById('searchResultsDropdown');

    if (searchInput && dropdown) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            const items = dropdown.querySelectorAll('.search-item');
            let hasVisible = false;

            if (query.length > 0) {
                dropdown.classList.remove('d-none');
                items.forEach(item => {
                    const title = item.getAttribute('data-title') || '';
                    const user = item.getAttribute('data-user') || '';
                    const whats = item.getAttribute('data-whats') || '';
                    const id = item.getAttribute('data-id') || '';

                    if (title.includes(query) || user.includes(query) || whats.includes(query) || id.includes(query)) {
                        item.classList.remove('d-none');
                        hasVisible = true;
                    } else {
                        item.classList.add('d-none');
                    }
                });
                if (!hasVisible) {
                    dropdown.classList.add('d-none');
                }
            } else {
                dropdown.classList.add('d-none');
            }
        });

        // Fecha dropdown ao clicar fora
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('d-none');
            }
        });
    }

    function selectClient(clientId) {
        // Redireciona e recarrega os dados completos e personalização daquele cliente instantaneamente
        window.location.href = '/painel-cliente?client_id=' + clientId;
    }

    function clearClientSearch() {
        if (searchInput) {
            searchInput.value = '';
            if (dropdown) dropdown.classList.add('d-none');
        }
    }
    </script>
</body>
</html>
