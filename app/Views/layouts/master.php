<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $pageTitle ?? 'Player99 - Gestão de Mídia DOOH' ?></title>

    <link rel="manifest" href="/manifest_cliente.json">
    <meta name="theme-color" content="#0d0f17">
    <link rel="icon" type="image/png" href="<?= \App\Core\View::logoUrl() ?>">

    <!-- CSS Frameworks & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-main: #0a0b10;
            --bg-card: rgba(18, 20, 30, 0.85);
            --border-card: rgba(255, 255, 255, 0.12);
            --primary-cyan: #34e2e4;
            --primary-blue: #0A38D1;
            --text-main: #ffffff;
            --text-muted: #b8c1dd;
            --sidebar-width: 260px;
        }

        * {
            box-sizing: border-box;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) rgba(10, 11, 16, 0.5);
        }

        /* Melhorar visibilidade geral de textos secundários / muted */
        .text-muted {
            color: #b0b9d6 !important;
        }

        /* Scrollbars Modernas & Elegantes (WebKit / Chrome / Edge) */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(10, 11, 16, 0.6);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(52, 226, 228, 0.6);
            box-shadow: 0 0 8px rgba(52, 226, 228, 0.4);
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Moderna */
        .sidebar {
            width: var(--sidebar-width);
            background: rgba(12, 14, 22, 0.95);
            border-right: 1px solid var(--border-card);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            transition: all 0.3s ease;
        }

        .sidebar-brand {
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--border-card);
            text-decoration: none;
            color: #fff;
        }
        .sidebar-brand img {
            max-height: 38px;
            filter: drop-shadow(0 2px 8px rgba(52, 226, 228, 0.4));
        }
        .sidebar-brand span {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #fff, #34e2e4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-nav {
            padding: 20px 12px;
            list-style: none;
            margin: 0;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.1) transparent;
        }
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 10px;
        }
        .sidebar-nav::-webkit-scrollbar-thumb:hover {
            background: var(--primary-cyan);
            box-shadow: 0 0 6px rgba(52, 226, 228, 0.5);
        }

        .nav-category {
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #8b96b5;
            padding: 14px 14px 6px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 16px;
            color: #cdd5ee;
            text-decoration: none;
            border-radius: 12px;
            font-size: 0.92rem;
            font-weight: 600;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }
        .sidebar-link:hover, .sidebar-link.active {
            color: #fff;
            background: rgba(52, 226, 228, 0.1);
            border-left: 3px solid var(--primary-cyan);
        }
        .sidebar-link.active i, .sidebar-link:hover i {
            color: var(--primary-cyan);
        }
        .sidebar-link i {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
            color: #616a82;
            transition: color 0.2s;
        }

        .sidebar-link.root-link {
            border: 1px dashed rgba(239, 68, 68, 0.4);
            background: rgba(239, 68, 68, 0.05);
            color: #fca5a5;
        }
        .sidebar-link.root-link:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fff;
            border-color: #ef4444;
        }
        .sidebar-link.root-link i { color: #ef4444; }

        /* Main Content Wrapper */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        /* Top Bar */
        .topbar {
            height: 70px;
            background: rgba(12, 14, 22, 0.8);
            backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--border-card);
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 90;
        }

        .topbar-user {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .user-badge {
            background: rgba(52, 226, 228, 0.1);
            border: 1px solid rgba(52, 226, 228, 0.3);
            color: var(--primary-cyan);
            font-size: 0.75rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        /* Content Area */
        .page-content {
            padding: 30px;
            flex: 1;
        }

        /* Cards Estilizados */
        .glass-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            margin-bottom: 25px;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .stat-icon.cyan { background: rgba(52, 226, 228, 0.15); color: var(--primary-cyan); }
        .stat-icon.blue { background: rgba(10, 56, 209, 0.2); color: #5a82f7; }
        .stat-icon.green { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .stat-icon.orange { background: rgba(245, 158, 11, 0.15); color: #fbbf24; }

        .stat-val {
            font-size: 1.8rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 3px;
        }
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            font-weight: 600;
        }

        /* Botões */
        .btn-cyan {
            background: linear-gradient(135deg, #0A38D1 0%, #34e2e4 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            padding: 10px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(52, 226, 228, 0.3);
            transition: all 0.2s;
        }
        .btn-cyan:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 226, 228, 0.45);
            color: #fff;
        }

        /* Tabelas */
        .table-custom {
            width: 100%;
            color: #fff;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .table-custom th {
            color: var(--text-muted);
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-card);
        }
        .table-custom td {
            background: rgba(24, 27, 40, 0.6);
            padding: 14px 16px;
            vertical-align: middle;
            border-top: 1px solid var(--border-card);
            border-bottom: 1px solid var(--border-card);
        }
        .table-custom tr td:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
            border-left: 1px solid var(--border-card);
        }
        .table-custom tr td:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
            border-right: 1px solid var(--border-card);
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="/admin/dashboard" class="sidebar-brand">
            <img src="<?= \App\Core\View::logoUrl() ?>" alt="Logo" onerror="this.onerror=null; this.src='/assets/images/logo.png';">
            <span>Player99</span>
        </a>

        <ul class="sidebar-nav">
            <?php if (!empty($currentUser['is_root'])): ?>
                <div class="nav-category" style="color: #ef4444;"><i class="fa fa-shield-alt"></i> Área Root</div>
                <li>
                    <a href="/admin/configuracoes" class="sidebar-link root-link">
                        <i class="fa fa-sliders-h"></i>
                        <span>Configurações Gerais</span>
                    </a>
                </li>
                <li>
                    <a href="/painel-cliente" class="sidebar-link" style="color: #34e2e4; border: 1px dashed rgba(52, 226, 228, 0.4); background: rgba(52, 226, 228, 0.05); margin-bottom: 6px;">
                        <i class="fa fa-car text-cyan"></i>
                        <span>Portal do Motorista</span>
                    </a>
                </li>
            <?php endif; ?>

            <div class="nav-category">Principal</div>
            <li>
                <a href="/admin/dashboard" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/dashboard' ? 'active' : '' ?>">
                    <i class="fa fa-chart-pie"></i>
                    <span>Estatísticas</span>
                </a>
            </li>
            <li>
                <a href="/admin/telas" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/telas' ? 'active' : '' ?>">
                    <i class="fa fa-tablet-alt"></i>
                    <span>Telas & Carros</span>
                </a>
            </li>
            <li>
                <a href="/admin/usuarios" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/usuarios' ? 'active' : '' ?>">
                    <i class="fa fa-users"></i>
                    <span>Usuários & Revendas</span>
                </a>
            </li>

            <div class="nav-category">DOOH & Mídia</div>
            <li>
                <a href="/admin/radios" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/radios' ? 'active' : '' ?>">
                    <i class="fa fa-broadcast-tower"></i>
                    <span>Web Rádios</span>
                </a>
            </li>
            <li>
                <a href="/admin/bloqueio" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/bloqueio' ? 'active' : '' ?>">
                    <i class="fa fa-ban"></i>
                    <span>Tela de Bloqueio</span>
                </a>
            </li>
            <li>
                <a href="/admin/landing-editor" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/landing-editor' ? 'active' : '' ?>">
                    <i class="fa fa-image"></i>
                    <span>Editar Home</span>
                </a>
            </li>

            <div class="nav-category">Negócios</div>
            <li>
                <a href="/admin/faturamento" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/faturamento' ? 'active' : '' ?>">
                    <i class="fa fa-wallet"></i>
                    <span>Faturamento</span>
                </a>
            </li>
            <li>
                <a href="/admin/afiliados" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/afiliados' ? 'active' : '' ?>">
                    <i class="fa fa-handshake"></i>
                    <span>Afiliados</span>
                </a>
            </li>
            <li>
                <a href="/admin/loja" class="sidebar-link <?= ($currentRoute ?? '') === '/admin/loja' ? 'active' : '' ?>">
                    <i class="fa fa-store"></i>
                    <span>Minha Loja</span>
                </a>
            </li>

            <div class="nav-category">Conta</div>
            <li>
                <a href="/sair" class="sidebar-link text-danger">
                    <i class="fa fa-sign-out-alt"></i>
                    <span>Sair do Sistema</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <header class="topbar">
            <div>
                <h5 class="mb-0 fw-bold"><?= $pageHeading ?? 'Painel de Controle' ?></h5>
            </div>
            <div class="topbar-user">
                <?php if (!empty($currentUser['is_root'])): ?>
                    <span class="user-badge" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.4); color: #fca5a5;">
                        <i class="fa fa-crown me-1"></i> Root Superadmin
                    </span>
                <?php else: ?>
                    <span class="user-badge">Administrador</span>
                <?php endif; ?>
                <div class="fw-semibold text-white">
                    <?= htmlspecialchars($currentUser['username'] ?? 'Usuário') ?>
                </div>
                <a href="/sair" class="btn btn-sm btn-outline-danger ms-2" title="Sair">
                    <i class="fa fa-power-off"></i>
                </a>
            </div>
        </header>

        <main class="page-content">
            <?= $content ?? '' ?>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
