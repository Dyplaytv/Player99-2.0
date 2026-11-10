<?php
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseParam = $isAdmin && $selectedFranchiseeId > 0 ? ('client_id=' . (int)$selectedFranchiseeId . '&') : '';
$affCode = $client['affiliateCode'] ?? '';
$affiliateLink = $protocol . $host . '/?ref=' . urlencode($affCode);
$registerLink = $protocol . $host . '/cadastro?ref=' . urlencode($affCode);
$today = date('Y-m-d');
$logoSrc = \App\Core\View::logoUrl();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($logoSrc) ?>">
    <title>Portal do Franqueado - Player99</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qr-code-styling@1.5.0/lib/qr-code-styling.js"></script>
    <style>
        :root {
            --bg-dark: #08090e;
            --sidebar-bg: #0c0e14;
            --card-bg: rgba(18, 21, 32, 0.88);
            --border-glass: rgba(255, 255, 255, 0.08);
            --primary-cyan: #34e2e4;
            --franchise-purple: #c084fc;
            --text-main: #e2e8f0;
            --text-muted: #8b92b2;
        }

        * { box-sizing: border-box; }
        body {
            background: radial-gradient(circle at 20% 0%, #151929 0%, var(--bg-dark) 100%);
            color: var(--text-main);
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
            margin: 0;
            overflow-x: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-glass);
            display: flex;
            flex-direction: column;
            z-index: 1040;
            padding-top: 20px;
            transition: left 0.3s ease;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 22px 18px;
            border-bottom: 1px solid var(--border-glass);
            margin-bottom: 14px;
            text-decoration: none;
        }
        .sidebar-brand img { max-height: 36px; width: auto; }
        .sidebar-brand span {
            font-size: 1.15rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, var(--franchise-purple) 55%, var(--primary-cyan) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .franchise-pill {
            margin: 0 16px 14px;
            padding: 10px 14px;
            border-radius: 12px;
            background: rgba(192, 132, 252, 0.1);
            border: 1px solid rgba(192, 132, 252, 0.3);
            color: #e9d5ff;
            font-size: 0.78rem;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .sidebar-nav {
            list-style: none;
            padding: 0 12px;
            margin: 0;
            flex: 1;
            overflow-y: auto;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 14px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-bottom: 4px;
        }
        .sidebar-link i { width: 20px; text-align: center; font-size: 1rem; }
        .sidebar-link:hover { color: #fff; background: rgba(255, 255, 255, 0.05); }
        .sidebar-link.active {
            color: #fff;
            background: rgba(192, 132, 252, 0.14);
            border: 1px solid rgba(192, 132, 252, 0.35);
            box-shadow: 0 4px 15px rgba(192, 132, 252, 0.12);
        }
        .sidebar-link.active i { color: var(--franchise-purple); }

        .nav-divider {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #525970;
            font-weight: 700;
            padding: 14px 14px 6px;
        }

        .main-wrapper {
            margin-left: 260px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            min-height: 68px;
            padding: 12px 28px;
            background: rgba(12, 14, 20, 0.8);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border-glass);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .root-bar {
            background: #111827;
            border-bottom: 1px solid rgba(192, 132, 252, 0.28);
            padding: 10px 28px;
        }

        .page-content {
            padding: 26px 28px 60px;
            flex: 1;
        }

        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: 16px;
            padding: 22px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 16px;
            height: 100%;
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            flex-shrink: 0;
        }
        .stat-icon.green { background: rgba(16, 185, 129, 0.14); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.25); }
        .stat-icon.purple { background: rgba(192, 132, 252, 0.14); color: #c084fc; border: 1px solid rgba(192, 132, 252, 0.25); }
        .stat-icon.cyan { background: rgba(52, 226, 228, 0.12); color: #34e2e4; border: 1px solid rgba(52, 226, 228, 0.25); }
        .stat-icon.amber { background: rgba(245, 158, 11, 0.14); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.25); }

        .stat-val { font-size: 1.6rem; font-weight: 800; color: #fff; line-height: 1.2; }
        .stat-label { font-size: 0.78rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.4px; }

        .table-custom {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }
        .table-custom th {
            font-size: 0.74rem;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
            padding: 8px 14px;
            border: none;
        }
        .table-custom td {
            background: rgba(255, 255, 255, 0.025);
            padding: 13px 14px;
            vertical-align: middle;
            border-top: 1px solid var(--border-glass);
            border-bottom: 1px solid var(--border-glass);
            color: #e2e8f0;
        }
        .table-custom td:first-child { border-left: 1px solid var(--border-glass); border-radius: 12px 0 0 12px; }
        .table-custom td:last-child { border-right: 1px solid var(--border-glass); border-radius: 0 12px 12px 0; }

        .btn-franchise {
            background: linear-gradient(135deg, #7c3aed 0%, #c084fc 100%);
            border: none;
            color: #fff;
            font-weight: 700;
            border-radius: 10px;
        }
        .btn-franchise:hover { filter: brightness(1.08); color: #fff; }

        .bottom-nav { display: none; }
        .sidebar-overlay { display: none; }

        @media (max-width: 991.98px) {
            .sidebar { left: -260px; }
            .sidebar.show { left: 0; }
            .sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.6);
                z-index: 1030;
            }
            .main-wrapper { margin-left: 0; }
            .page-content { padding: 18px 14px 86px; }
            .topbar { padding: 10px 14px; }
            .root-bar { padding: 10px 14px; }
            .bottom-nav {
                display: flex;
                position: fixed;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 1020;
                background: #0c0e14;
                border-top: 1px solid var(--border-glass);
            }
            .bottom-nav a {
                flex: 1;
                text-align: center;
                padding: 8px 4px;
                color: var(--text-muted);
                text-decoration: none;
                font-size: 0.7rem;
                font-weight: 600;
            }
            .bottom-nav a i { display: block; font-size: 1.15rem; margin-bottom: 2px; }
            .bottom-nav a.active { color: var(--franchise-purple); }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<nav class="bottom-nav">
    <a href="?<?= $baseParam ?>tab=resumo" class="<?= $tab === 'resumo' ? 'active' : '' ?>"><i class="fa fa-chart-line"></i>Resumo</a>
    <a href="?<?= $baseParam ?>tab=motoristas" class="<?= $tab === 'motoristas' ? 'active' : '' ?>"><i class="fa fa-car"></i>Motoristas</a>
    <a href="?<?= $baseParam ?>tab=financeiro" class="<?= $tab === 'financeiro' ? 'active' : '' ?>"><i class="fa fa-wallet"></i>Comissões</a>
    <a href="?<?= $baseParam ?>tab=config" class="<?= $tab === 'config' ? 'active' : '' ?>"><i class="fa fa-cog"></i>Perfil</a>
</nav>

<aside class="sidebar" id="franchiseSidebar">
    <a href="/portal-franqueado?<?= rtrim($baseParam, '&') ?>" class="sidebar-brand">
        <img src="<?= htmlspecialchars($logoSrc) ?>" alt="Player99" onerror="this.onerror=null;this.src='/assets/images/logo.png';">
        <span>Player99</span>
    </a>

    <div class="franchise-pill">
        <i class="fa fa-store me-1"></i> Portal do Franqueado
    </div>

    <ul class="sidebar-nav">
        <li>
            <a href="?<?= $baseParam ?>tab=resumo" class="sidebar-link <?= $tab === 'resumo' ? 'active' : '' ?>">
                <i class="fa fa-chart-line"></i>
                <span>Visão Geral</span>
            </a>
        </li>
        <li>
            <a href="?<?= $baseParam ?>tab=motoristas" class="sidebar-link <?= $tab === 'motoristas' ? 'active' : '' ?>">
                <i class="fa fa-car-side"></i>
                <span>Meus Motoristas &amp; Telas</span>
            </a>
        </li>
        <li>
            <a href="?<?= $baseParam ?>tab=financeiro" class="sidebar-link <?= $tab === 'financeiro' ? 'active' : '' ?>">
                <i class="fa fa-hand-holding-dollar"></i>
                <span>Comissões &amp; Saques</span>
            </a>
        </li>
        <li>
            <a href="?<?= $baseParam ?>tab=config" class="sidebar-link <?= $tab === 'config' ? 'active' : '' ?>">
                <i class="fa fa-sliders"></i>
                <span>Perfil &amp; Senha</span>
            </a>
        </li>

        <div class="nav-divider">Atalhos</div>
        <li>
            <a href="/painel-cliente<?= $isAdmin && $selectedFranchiseeId > 0 ? ('?client_id=' . (int)$selectedFranchiseeId) : '' ?>" class="sidebar-link" style="color: #34e2e4;">
                <i class="fa fa-desktop"></i>
                <span>Portal do Motorista</span>
            </a>
        </li>
        <?php if ($isAdmin): ?>
            <li>
                <a href="/admin/dashboard" class="sidebar-link" style="color: #fca5a5;">
                    <i class="fa fa-shield-halved"></i>
                    <span>Painel Admin</span>
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a href="/sair" class="sidebar-link text-danger mt-2">
                <i class="fa fa-sign-out-alt"></i>
                <span>Sair do Sistema</span>
            </a>
        </li>
    </ul>
</aside>

<div class="main-wrapper">
    <header class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button type="button" class="btn btn-sm btn-outline-light d-lg-none" id="mobileMenuBtn">
                <i class="fa fa-bars"></i>
            </button>
            <div>
                <h5 class="mb-0 fw-bold text-white">Portal do Franqueado</h5>
                <small class="text-muted">Gestão de motoristas, telas e comissões da sua rede</small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($client): ?>
                <span class="badge px-3 py-2 rounded-pill" style="background: rgba(192,132,252,0.16); color: #e9d5ff; border: 1px solid rgba(192,132,252,0.35);">
                    <i class="fa fa-store me-1"></i> <?= htmlspecialchars($client['title'] ?: $client['username']) ?>
                </span>
            <?php endif; ?>
            <?php if ($isAdmin): ?>
                <a href="/admin/dashboard" class="btn btn-sm btn-outline-info">
                    <i class="fa fa-arrow-left me-1"></i> Voltar ao Admin
                </a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($isAdmin): ?>
        <div class="root-bar">
            <div class="row g-2 align-items-center">
                <div class="col-md-5 d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-danger"><i class="fa fa-crown me-1"></i> MODO ADMIN</span>
                    <small class="text-light">
                        <?php if ($client): ?>
                            Visualizando: <b><?= htmlspecialchars($client['title'] ?: $client['username']) ?></b> (#<?= (int)$client['id'] ?>)
                            <?php if (empty($client['is_reseller'])): ?>
                                <form method="post" action="?<?= $baseParam ?>tab=<?= urlencode($tab) ?>" class="d-inline ms-2">
                                    <button type="submit" name="promote_reseller" value="1" class="btn btn-xs btn-warning py-0 px-2 fw-bold" style="font-size:11px;">
                                        Ativar como Franqueado
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            Nenhum franqueado selecionado.
                        <?php endif; ?>
                    </small>
                </div>
                <div class="col-md-7">
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm bg-dark text-white border-secondary" onchange="if(this.value) window.location.href='/portal-franqueado?client_id=' + this.value + '&tab=<?= urlencode($tab) ?>'">
                            <option value="">-- Trocar de Franqueado / Cliente (<?= (int)$resellersCount ?> franqueado(s)) --</option>
                            <?php foreach ($allFranchisees as $f): ?>
                                <option value="<?= (int)$f['id'] ?>" <?= ((int)$f['id'] === (int)$selectedFranchiseeId) ? 'selected' : '' ?>>
                                    <?= !empty($f['is_reseller']) ? '★ [FRANQUEADO] ' : '[Motorista] ' ?>
                                    <?= htmlspecialchars(($f['title'] ?: $f['username']) . ' (@' . $f['username'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <a href="/admin/usuarios" class="btn btn-sm btn-outline-light text-nowrap">
                            <i class="fa fa-users me-1"></i> Gerenciar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <main class="page-content">
        <?php if (!$client): ?>
            <div class="glass-card text-center py-5">
                <i class="fa fa-store fa-3x mb-3" style="color: var(--franchise-purple);"></i>
                <h4 class="fw-bold text-white">Nenhum Franqueado Encontrado</h4>
                <p class="text-muted mb-4">Cadastre um usuário com nível Revendedor/Franqueado ou ative um cliente na aba Usuários &amp; Revendas.</p>
                <a href="/admin/usuarios" class="btn btn-franchise px-4 py-2">Ir para Usuários &amp; Revendas</a>
            </div>
        <?php else: ?>

            <?php if (!empty($msg)): ?>
                <div class="alert alert-success border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="background: rgba(16,185,129,0.16); color: #6ee7b7; border: 1px solid rgba(16,185,129,0.35) !important;">
                    <i class="fa fa-check-circle fa-lg"></i>
                    <div><?= $msg ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($err)): ?>
                <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-4" style="background: rgba(239,68,68,0.16); color: #fca5a5; border: 1px solid rgba(239,68,68,0.35) !important;">
                    <i class="fa fa-exclamation-triangle fa-lg"></i>
                    <div><?= htmlspecialchars($err) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($createdDriver)):
                $credText = "Seu acesso Player99 foi criado!\nLink: {$protocol}{$host}/login\nUsuário: {$createdDriver['usuario']}\nSenha: {$createdDriver['senha']}";
                $cleanDrvWhats = preg_replace('/\D/', '', (string)($createdDriver['whatsapp'] ?? ''));
            ?>
                <div class="glass-card mb-4" style="border-color: rgba(52,226,228,0.45); background: rgba(16,185,129,0.08);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h6 class="fw-bold text-info mb-1"><i class="fa fa-key me-1"></i> Dados de Acesso do Novo Motorista</h6>
                            <div class="small text-white">
                                Nome: <b><?= htmlspecialchars($createdDriver['nome']) ?></b> ·
                                Usuário: <code class="text-info"><?= htmlspecialchars($createdDriver['usuario']) ?></code> ·
                                Senha: <code class="text-warning"><?= htmlspecialchars($createdDriver['senha']) ?></code>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="copyText(<?= htmlspecialchars(json_encode($credText), ENT_QUOTES) ?>, 'Credenciais copiadas!')">
                                <i class="fa fa-copy me-1"></i> Copiar Acesso
                            </button>
                            <?php if ($cleanDrvWhats !== ''): ?>
                                <a href="https://api.whatsapp.com/send?phone=<?= htmlspecialchars($cleanDrvWhats) ?>&text=<?= urlencode($credText) ?>" target="_blank" class="btn btn-sm btn-success">
                                    <i class="fab fa-whatsapp me-1"></i> Enviar no WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($tab === 'resumo'): ?>
                <!-- KPIs Principais -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="glass-card stat-card">
                            <div class="stat-icon green"><i class="fa fa-wallet"></i></div>
                            <div>
                                <div class="stat-val text-success">R$ <?= number_format($affiliateBalance, 2, ',', '.') ?></div>
                                <div class="stat-label">Saldo Disponível</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="glass-card stat-card">
                            <div class="stat-icon purple"><i class="fa fa-car"></i></div>
                            <div>
                                <div class="stat-val"><?= (int)$totalDrivers ?></div>
                                <div class="stat-label">Motoristas (<?= (int)$activeDrivers ?> ativo(s))</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="glass-card stat-card">
                            <div class="stat-icon cyan"><i class="fa fa-tablet-screen-button"></i></div>
                            <div>
                                <div class="stat-val"><?= (int)$totalScreens ?></div>
                                <div class="stat-label">Telas (<?= (int)$onlineScreens ?> online)</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="glass-card stat-card">
                            <div class="stat-icon amber"><i class="fa fa-percent"></i></div>
                            <div>
                                <div class="stat-val text-warning"><?= number_format($commissionPercent, 0) ?>%</div>
                                <div class="stat-label">Comissão · <?= (int)($client['affiliateClicks'] ?? 0) ?> cliques</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <!-- Link de Indicação + QR Code -->
                    <div class="col-lg-7">
                        <div class="glass-card h-100">
                            <h5 class="fw-bold text-white mb-2"><i class="fa fa-share-nodes me-2" style="color:var(--franchise-purple);"></i> Meu Link de Franqueado (Indicação)</h5>
                            <p class="text-muted small mb-3">
                                Todos os motoristas que se cadastrarem pelo seu link ficam vinculados abaixo da sua franquia. Você recebe <b><?= number_format($commissionPercent, 0) ?>%</b> de comissão a cada recarga confirmada.
                            </p>

                            <div class="row g-3 align-items-center">
                                <div class="col-md-8">
                                    <label class="form-label small text-muted fw-bold text-uppercase">Link da Página Inicial</label>
                                    <div class="input-group input-group-sm mb-3">
                                        <input type="text" class="form-control bg-dark text-white border-secondary" id="frqLinkHome" value="<?= htmlspecialchars($affiliateLink) ?>" readonly>
                                        <button class="btn btn-outline-info" type="button" onclick="copyText(document.getElementById('frqLinkHome').value, 'Link copiado!')">
                                            <i class="fa fa-copy me-1"></i> Copiar
                                        </button>
                                    </div>

                                    <label class="form-label small text-muted fw-bold text-uppercase">Link Direto para Cadastro</label>
                                    <div class="input-group input-group-sm mb-3">
                                        <input type="text" class="form-control bg-dark text-white border-secondary" id="frqLinkCad" value="<?= htmlspecialchars($registerLink) ?>" readonly>
                                        <button class="btn btn-outline-info" type="button" onclick="copyText(document.getElementById('frqLinkCad').value, 'Link de cadastro copiado!')">
                                            <i class="fa fa-copy me-1"></i> Copiar
                                        </button>
                                    </div>

                                    <a href="https://api.whatsapp.com/send?text=<?= urlencode('Cadastre-se na Player99 e ganhe renda extra no carro com telas digitais: ' . $registerLink) ?>" target="_blank" class="btn btn-sm btn-success fw-bold">
                                        <i class="fab fa-whatsapp me-1"></i> Compartilhar no WhatsApp
                                    </a>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div id="frqQrCode" class="p-2 bg-white rounded d-inline-block shadow"></div>
                                    <div class="small text-muted mt-1">QR Code da Franquia</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Criar Motorista Rápido -->
                    <div class="col-lg-5">
                        <div class="glass-card h-100">
                            <h5 class="fw-bold text-white mb-3"><i class="fa fa-user-plus text-success me-2"></i> Cadastrar Novo Motorista</h5>
                            <form method="post" action="?<?= $baseParam ?>tab=resumo">
                                <input type="hidden" name="return_tab" value="resumo">
                                <div class="row g-2">
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-1">Nome do Motorista</label>
                                        <input type="text" name="motorista_nome" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Ex: Carlos Eduardo" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small text-muted mb-1">Usuário (Login)</label>
                                        <input type="text" name="motorista_usuario" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="carlosedu" autocomplete="off" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small text-muted mb-1">Senha Inicial</label>
                                        <input type="text" name="motorista_senha" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="mín. 4 chars" minlength="4" autocomplete="off" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small text-muted mb-1">WhatsApp</label>
                                        <input type="text" name="motorista_whatsapp" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="5511999999999">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <button type="submit" name="criar_motorista" value="1" class="btn btn-franchise w-100 py-2">
                                            <i class="fa fa-plus me-1"></i> Criar Motorista na Rede
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Resumo Motoristas + Últimas Comissões -->
                <div class="row g-4">
                    <div class="col-lg-7">
                        <div class="glass-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-white mb-0"><i class="fa fa-car-side text-info me-2"></i> Motoristas Recentes</h6>
                                <a href="?<?= $baseParam ?>tab=motoristas" class="btn btn-sm btn-outline-info">Ver Todos (<?= (int)$totalDrivers ?>)</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead>
                                        <tr><th>Motorista</th><th class="text-center">Telas</th><th>Status</th><th>Validade</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($drivers)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhum motorista cadastrado ainda.</td></tr>
                                        <?php else: foreach (array_slice($drivers, 0, 6) as $d):
                                            $dActive = ((int)($d['active'] ?? 1) === 1) && (empty($d['expire_date']) || substr((string)$d['expire_date'], 0, 10) >= $today);
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-white"><?= htmlspecialchars($d['title'] ?: $d['username']) ?></div>
                                                    <small class="text-muted font-monospace">@<?= htmlspecialchars($d['username']) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-info py-0 px-2" onclick="openDriverScreens(<?= (int)$d['id'] ?>, <?= htmlspecialchars(json_encode($d['title'] ?: $d['username']), ENT_QUOTES) ?>)">
                                                        <?= (int)$d['telas_count'] ?> tela(s)
                                                    </button>
                                                </td>
                                                <td>
                                                    <?php if ((int)($d['active'] ?? 1) === 0): ?>
                                                        <span class="badge bg-secondary">Bloqueado</span>
                                                    <?php elseif ($dActive): ?>
                                                        <span class="badge bg-success bg-opacity-25 text-success">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger bg-opacity-25 text-danger">Vencido</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small font-monospace"><?= !empty($d['expire_date']) ? date('d/m/Y', strtotime($d['expire_date'])) : '-' ?></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="glass-card h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-white mb-0"><i class="fa fa-coins text-success me-2"></i> Últimas Movimentações</h6>
                                <a href="?<?= $baseParam ?>tab=financeiro" class="btn btn-sm btn-outline-success">Financeiro</a>
                            </div>
                            <div class="table-responsive">
                                <table class="table-custom">
                                    <thead><tr><th>Descrição</th><th class="text-end">Valor</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($affiliateTransactions)): ?>
                                            <tr><td colspan="2" class="text-center text-muted py-4">Nenhuma comissão registrada ainda.</td></tr>
                                        <?php else: foreach (array_slice($affiliateTransactions, 0, 6) as $tx): ?>
                                            <tr>
                                                <td>
                                                    <div class="small text-white"><?= htmlspecialchars($tx['description'] ?? '') ?></div>
                                                    <small class="text-muted"><?= !empty($tx['createdAt']) ? date('d/m/Y H:i', strtotime($tx['createdAt'])) : '-' ?></small>
                                                </td>
                                                <td class="text-end fw-bold <?= ($tx['type'] ?? '') === 'COMMISSION' ? 'text-success' : 'text-warning' ?>">
                                                    <?= ($tx['type'] ?? '') === 'COMMISSION' ? '+' : '-' ?> R$ <?= number_format((float)($tx['amount'] ?? 0), 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab === 'motoristas'): ?>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <div class="glass-card">
                            <h5 class="fw-bold text-white mb-3"><i class="fa fa-user-plus text-success me-2"></i> Novo Motorista</h5>
                            <form method="post" action="?<?= $baseParam ?>tab=motoristas">
                                <input type="hidden" name="return_tab" value="motoristas">
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Nome Completo *</label>
                                    <input type="text" name="motorista_nome" class="form-control bg-dark text-white border-secondary" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Usuário (login) *</label>
                                    <input type="text" name="motorista_usuario" class="form-control bg-dark text-white border-secondary" autocomplete="off" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Senha *</label>
                                    <input type="text" name="motorista_senha" class="form-control bg-dark text-white border-secondary" minlength="4" autocomplete="off" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">WhatsApp</label>
                                    <input type="text" name="motorista_whatsapp" class="form-control bg-dark text-white border-secondary" placeholder="5511999999999">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">E-mail (opcional)</label>
                                    <input type="email" name="motorista_email" class="form-control bg-dark text-white border-secondary" placeholder="motorista@email.com">
                                </div>
                                <button type="submit" name="criar_motorista" value="1" class="btn btn-franchise w-100 py-2">
                                    <i class="fa fa-plus me-1"></i> Criar Motorista
                                </button>
                                <small class="text-muted d-block mt-2">
                                    O motorista entra vinculado à sua rede e começa aguardando recarga para liberar as telas.
                                </small>
                            </form>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="glass-card">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div>
                                    <h5 class="fw-bold text-white mb-0"><i class="fa fa-users text-info me-2"></i> Motoristas da Minha Franquia (<?= (int)$totalDrivers ?>)</h5>
                                    <small class="text-muted">Total de telas na rede: <b class="text-info"><?= (int)$totalScreens ?></b> (<?= (int)$onlineScreens ?> online)</small>
                                </div>
                                <input type="text" id="filterDriversInput" class="form-control form-control-sm bg-dark text-white border-secondary" placeholder="Buscar motorista..." style="max-width: 230px;">
                            </div>

                            <div class="table-responsive">
                                <table class="table-custom" id="tblFranchiseDrivers">
                                    <thead>
                                        <tr>
                                            <th>Motorista</th>
                                            <th>WhatsApp</th>
                                            <th class="text-center">Nº de Telas</th>
                                            <th>Status</th>
                                            <th>Validade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($drivers)): ?>
                                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum motorista cadastrado ainda. Crie ao lado ou compartilhe seu link de indicação.</td></tr>
                                        <?php else: foreach ($drivers as $d):
                                            $dActive = ((int)($d['active'] ?? 1) === 1) && (empty($d['expire_date']) || substr((string)$d['expire_date'], 0, 10) >= $today);
                                            $cleanW = preg_replace('/\D/', '', (string)($d['whatsapp'] ?? ''));
                                            $searchKey = strtolower(($d['title'] ?? '') . ' ' . ($d['username'] ?? '') . ' ' . $cleanW);
                                        ?>
                                            <tr class="drv-row" data-search="<?= htmlspecialchars($searchKey) ?>">
                                                <td>
                                                    <div class="fw-bold text-white"><?= htmlspecialchars($d['title'] ?: $d['username']) ?></div>
                                                    <small class="text-muted font-monospace">@<?= htmlspecialchars($d['username']) ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($cleanW !== ''): ?>
                                                        <a href="https://api.whatsapp.com/send?phone=<?= htmlspecialchars($cleanW) ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2">
                                                            <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($d['whatsapp']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted small">---</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-outline-info py-1 px-2" onclick="openDriverScreens(<?= (int)$d['id'] ?>, <?= htmlspecialchars(json_encode($d['title'] ?: $d['username']), ENT_QUOTES) ?>)" title="Ver detalhes das telas">
                                                        <i class="fa fa-tablet-alt me-1"></i> <?= (int)$d['telas_count'] ?>
                                                        <?php if ((int)$d['telas_online'] > 0): ?>
                                                            <span class="badge bg-success ms-1"><?= (int)$d['telas_online'] ?> on</span>
                                                        <?php endif; ?>
                                                    </button>
                                                </td>
                                                <td>
                                                    <?php if ((int)($d['active'] ?? 1) === 0): ?>
                                                        <span class="badge bg-secondary">Bloqueado</span>
                                                    <?php elseif ($dActive): ?>
                                                        <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50">Ativo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50">Vencido</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="font-monospace small">
                                                    <?= !empty($d['expire_date']) ? date('d/m/Y', strtotime($d['expire_date'])) : 'Sem data' ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fa fa-lock me-1"></i> A data de validade é gerenciada exclusivamente pela administração ou renovada automaticamente via recarga.
                            </small>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab === 'financeiro'): ?>
                <!-- Cards Financeiros -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="glass-card stat-card">
                            <div class="stat-icon green"><i class="fa fa-sack-dollar"></i></div>
                            <div>
                                <div class="stat-val text-success">R$ <?= number_format($affiliateBalance, 2, ',', '.') ?></div>
                                <div class="stat-label">Saldo Disponível p/ Saque</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="glass-card stat-card">
                            <div class="stat-icon cyan"><i class="fa fa-chart-simple"></i></div>
                            <div>
                                <div class="stat-val">R$ <?= number_format($totalEarned, 2, ',', '.') ?></div>
                                <div class="stat-label">Total Acumulado em Comissões</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="glass-card stat-card">
                            <div class="stat-icon amber"><i class="fa fa-clock-rotate-left"></i></div>
                            <div>
                                <div class="stat-val text-warning">R$ <?= number_format($pendingWithdraw, 2, ',', '.') ?></div>
                                <div class="stat-label">Saques Pendentes (Pago: R$ <?= number_format($paidWithdraw, 2, ',', '.') ?>)</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PIX + Solicitar Saque -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <div class="glass-card h-100">
                            <h5 class="fw-bold text-white mb-3"><i class="fa fa-key text-info me-2"></i> Chave PIX para Recebimento</h5>
                            <form method="post" action="?<?= $baseParam ?>tab=financeiro">
                                <input type="hidden" name="return_tab" value="financeiro">
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Sua Chave PIX</label>
                                    <input type="text" name="pixKey" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($client['pixKey'] ?? '') ?>" placeholder="CPF, CNPJ, E-mail, Celular ou Chave Aleatória" required>
                                </div>
                                <button type="submit" name="update_pix" value="1" class="btn btn-info fw-bold text-dark w-100">
                                    <i class="fa fa-save me-1"></i> Salvar Chave PIX
                                </button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass-card h-100 d-flex flex-column justify-content-between text-center">
                            <div>
                                <h5 class="fw-bold text-white mb-2"><i class="fa fa-money-bill-wave text-success me-2"></i> Solicitar Saque</h5>
                                <p class="text-muted small">
                                    Você ganha <b><?= number_format($commissionPercent, 0) ?>%</b> sobre cada plano recarregado pelos seus motoristas.
                                </p>
                            </div>
                            <form method="post" action="?<?= $baseParam ?>tab=financeiro">
                                <input type="hidden" name="return_tab" value="financeiro">
                                <button type="submit" name="withdraw" value="1" class="btn btn-success btn-lg fw-bold px-5" <?= $affiliateBalance <= 0 ? 'disabled' : '' ?>>
                                    <i class="fa fa-hand-holding-dollar me-1"></i> Sacar R$ <?= number_format($affiliateBalance, 2, ',', '.') ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Recargas dos Motoristas + Extrato -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="glass-card h-100">
                            <h6 class="fw-bold text-white mb-3"><i class="fa fa-bolt text-warning me-2"></i> Recargas dos Meus Motoristas</h6>
                            <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                                <table class="table-custom">
                                    <thead>
                                        <tr><th>Motorista / Plano</th><th>Valor</th><th>Sua Comissão</th><th>Status</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recharges)): ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma recarga realizada pelos seus motoristas ainda.</td></tr>
                                        <?php else: foreach ($recharges as $rc): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold text-white small"><?= htmlspecialchars($rc['nome_cliente'] ?: ('Cliente #' . $rc['id_cliente'])) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($rc['nome_plano']) ?> (<?= (int)$rc['dias'] ?>d)</small>
                                                </td>
                                                <td class="small">R$ <?= number_format((float)$rc['valor_final'], 2, ',', '.') ?></td>
                                                <td class="fw-bold text-success small">R$ <?= number_format((float)$rc['comissao_estimada'], 2, ',', '.') ?></td>
                                                <td>
                                                    <?php if (($rc['status'] ?? '') === 'PAGO'): ?>
                                                        <span class="badge bg-success">PAGO</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning text-dark">PENDENTE</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="glass-card h-100">
                            <h6 class="fw-bold text-white mb-3"><i class="fa fa-history text-info me-2"></i> Extrato de Comissões &amp; Saques</h6>
                            <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                                <table class="table-custom">
                                    <thead><tr><th>Data</th><th>Descrição</th><th class="text-end">Valor</th></tr></thead>
                                    <tbody>
                                        <?php if (empty($affiliateTransactions)): ?>
                                            <tr><td colspan="3" class="text-center text-muted py-4">Nenhuma movimentação registrada ainda.</td></tr>
                                        <?php else: foreach ($affiliateTransactions as $tx): ?>
                                            <tr>
                                                <td class="small text-muted"><?= !empty($tx['createdAt']) ? date('d/m/Y H:i', strtotime($tx['createdAt'])) : '-' ?></td>
                                                <td class="small">
                                                    <?= htmlspecialchars($tx['description'] ?? '') ?>
                                                    <?php if (($tx['type'] ?? '') === 'WITHDRAW_PENDING'): ?>
                                                        <span class="badge bg-warning text-dark ms-1">Pendente</span>
                                                    <?php elseif (($tx['type'] ?? '') === 'PAYOUT'): ?>
                                                        <span class="badge bg-info text-dark ms-1">Pago</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end fw-bold <?= ($tx['type'] ?? '') === 'COMMISSION' ? 'text-success' : 'text-danger' ?>">
                                                    <?= ($tx['type'] ?? '') === 'COMMISSION' ? '+' : '-' ?> R$ <?= number_format((float)($tx['amount'] ?? 0), 2, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($tab === 'config'): ?>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="glass-card">
                            <h5 class="fw-bold text-white mb-3"><i class="fa fa-id-card me-2" style="color:var(--franchise-purple);"></i> Dados do Franqueado</h5>
                            <ul class="list-unstyled mb-0">
                                <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                                    <span class="text-muted">Nome</span>
                                    <strong class="text-white"><?= htmlspecialchars($client['title'] ?: $client['username']) ?></strong>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                                    <span class="text-muted">Usuário</span>
                                    <code class="text-info">@<?= htmlspecialchars($client['username']) ?></code>
                                </li>
                                <li class="d-flex justify-content-between py-2 border-bottom border-secondary border-opacity-25">
                                    <span class="text-muted">Código de Indicação</span>
                                    <code class="text-warning"><?= htmlspecialchars($affCode) ?></code>
                                </li>
                                <li class="d-flex justify-content-between py-2">
                                    <span class="text-muted">Taxa de Comissão</span>
                                    <span class="badge bg-success"><?= number_format($commissionPercent, 0) ?>%</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="glass-card">
                            <h5 class="fw-bold text-white mb-3"><i class="fa fa-lock text-info me-2"></i> Alterar Senha de Acesso</h5>
                            <form method="post" action="?<?= $baseParam ?>tab=config">
                                <div class="mb-3">
                                    <label class="form-label small text-muted fw-bold">Nova Senha</label>
                                    <input type="password" name="nova_senha" class="form-control bg-dark text-white border-secondary" minlength="4" placeholder="Mínimo 4 caracteres" required>
                                </div>
                                <button type="submit" name="atualizar_senha" value="1" class="btn btn-franchise px-4">
                                    <i class="fa fa-save me-1"></i> Salvar Nova Senha
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </main>
</div>

<!-- Modal: Telas do Motorista (Somente Leitura) -->
<div class="modal fade" id="driverScreensModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content bg-dark text-white border border-secondary">
            <div class="modal-header border-secondary">
                <h6 class="modal-title fw-bold">
                    <i class="fa fa-tablet-screen-button text-info me-2"></i> Telas de <span id="drvScreensTitle"></span>
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="drvScreensBody"></div>
            <div class="modal-footer border-secondary">
                <small class="text-muted me-auto"><i class="fa fa-info-circle me-1"></i> Visualização somente leitura.</small>
                <button type="button" class="btn btn-sm btn-outline-light" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const mobileBtn = document.getElementById('mobileMenuBtn');
const sidebarEl = document.getElementById('franchiseSidebar');
const overlayEl = document.getElementById('sidebarOverlay');
if (mobileBtn && sidebarEl && overlayEl) {
    mobileBtn.addEventListener('click', () => {
        sidebarEl.classList.add('show');
        overlayEl.classList.add('show');
    });
    overlayEl.addEventListener('click', () => {
        sidebarEl.classList.remove('show');
        overlayEl.classList.remove('show');
    });
}

function copyText(text, toastMsg) {
    navigator.clipboard.writeText(text).then(() => {
        let el = document.getElementById('frqToast');
        if (!el) {
            el = document.createElement('div');
            el.id = 'frqToast';
            el.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;background:#7c3aed;color:#fff;padding:10px 18px;border-radius:10px;font-weight:700;font-size:13px;box-shadow:0 8px 24px rgba(0,0,0,0.4);';
            document.body.appendChild(el);
        }
        el.textContent = toastMsg || 'Copiado!';
        el.style.display = 'block';
        clearTimeout(window._frqT);
        window._frqT = setTimeout(() => { el.style.display = 'none'; }, 2400);
    });
}

const filterInput = document.getElementById('filterDriversInput');
if (filterInput) {
    filterInput.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('#tblFranchiseDrivers tbody tr.drv-row').forEach(r => {
            const key = r.getAttribute('data-search') || '';
            r.style.display = key.includes(q) ? '' : 'none';
        });
    });
}

const DRIVER_SCREENS_MAP = <?= json_encode($driverScreens ?? [], JSON_UNESCAPED_UNICODE) ?>;
const DRIVER_TODAY = '<?= $today ?>';

function escHtml(v) {
    return String(v || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openDriverScreens(driverId, driverName) {
    const titleEl = document.getElementById('drvScreensTitle');
    const bodyEl = document.getElementById('drvScreensBody');
    if (titleEl) titleEl.textContent = driverName || ('#' + driverId);
    const list = DRIVER_SCREENS_MAP[driverId] || [];

    if (!list.length) {
        bodyEl.innerHTML = '<div class="text-center text-muted py-4">Este motorista ainda não vinculou nenhuma tela.</div>';
    } else {
        let html = '<div class="table-responsive"><table class="table table-dark table-striped align-middle mb-0"><thead><tr><th>Código</th><th>Identificação</th><th>Status</th><th>Validade</th></tr></thead><tbody>';
        list.forEach(s => {
            const online = parseInt(s.is_online || 0, 10) === 1;
            const exp = (s.expire_date || '').slice(0, 10);
            const expLabel = exp ? (exp < DRIVER_TODAY ? '<span class="badge bg-danger">Vencida (' + exp.split('-').reverse().join('/') + ')</span>' : '<span class="badge bg-success">' + exp.split('-').reverse().join('/') + '</span>') : '<span class="text-muted small">Segue conta</span>';
            html += '<tr>'
                + '<td><code class="text-info">' + escHtml(s.player_code) + '</code></td>'
                + '<td>' + escHtml(s.nome_tela || s.title || ('Tela #' + s.id)) + '</td>'
                + '<td>' + (online ? '<span class="badge bg-success">Online</span>' : '<span class="badge bg-secondary">Offline</span>') + '</td>'
                + '<td>' + expLabel + '</td>'
                + '</tr>';
        });
        html += '</tbody></table></div>';
        bodyEl.innerHTML = html;
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('driverScreensModal')).show();
}

if (document.getElementById('frqQrCode') && typeof QRCodeStyling !== 'undefined') {
    const qr = new QRCodeStyling({
        width: 132,
        height: 132,
        data: <?= json_encode($registerLink) ?>,
        dotsOptions: { color: "#0f172a", type: "rounded" },
        backgroundOptions: { color: "#ffffff" }
    });
    qr.append(document.getElementById('frqQrCode'));
}
</script>
</body>
</html>
