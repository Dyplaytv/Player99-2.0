<?php
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ScreenController;
use App\Controllers\UserController;
use App\Controllers\SettingsController;
use App\Controllers\RadioController;
use App\Controllers\LockScreenController;
use App\Controllers\LandingEditorController;
use App\Controllers\BillingController;
use App\Controllers\AffiliateController;
use App\Controllers\StoreController;
use App\Controllers\ClientDashboardController;
use App\Controllers\EvolutionWebhookController;

/** @var Router $router */

// -------------------------------------------------------------
// 1. ROTAS PRINCIPAIS (URLs Amigáveis MVC)
// -------------------------------------------------------------

// Landing Page
$router->get('/', [HomeController::class, 'index']);
$router->get('/index', [HomeController::class, 'index']);
$router->get('/index.php', [HomeController::class, 'index']);

// Páginas públicas gerenciadas pelo painel (Editar Home)
$router->get('/parceiros', [HomeController::class, 'parceiros']);
$router->get('/parceiros.php', [HomeController::class, 'parceiros']);
$router->get('/afiliados', [HomeController::class, 'afiliados']);
$router->get('/afiliados.php', [HomeController::class, 'afiliados']);

// Loja Pública (Vitrine de Serviços & Vouchers)
$router->get('/loja', [StoreController::class, 'publicStore']);
$router->get('/loja.php', [StoreController::class, 'publicStore']);

// Autenticação (Login e Logout)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/login.php', [AuthController::class, 'showLogin']);
$router->post('/login.php', [AuthController::class, 'login']);
$router->get('/login_cliente.php', [AuthController::class, 'showLogin']);
$router->post('/login_cliente.php', [AuthController::class, 'login']);
$router->get('/sair', [AuthController::class, 'logout']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/sair.php', [AuthController::class, 'logout']);
$router->get('/logout.php', [AuthController::class, 'logout']);
$router->get('/admin/sair', [AuthController::class, 'logout']);
$router->get('/admin/logout', [AuthController::class, 'logout']);

// Cadastro de Parceiros / Motoristas
$router->get('/cadastro', [AuthController::class, 'showRegister']);
$router->post('/cadastro', [AuthController::class, 'register']);
$router->get('/cadastro.php', [AuthController::class, 'showRegister']);
$router->post('/cadastro.php', [AuthController::class, 'register']);

// Portal do Cliente / Motorista
$router->get('/painel-cliente', [ClientDashboardController::class, 'index']);
$router->post('/painel-cliente', [ClientDashboardController::class, 'index']);
$router->get('/cliente_dashboard.php', [ClientDashboardController::class, 'index']);
$router->post('/cliente_dashboard.php', [ClientDashboardController::class, 'index']);

// Painel Administrativo / Dashboard
$router->get('/admin/dashboard', [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/estatisticas.php', [DashboardController::class, 'index']);

// Telas & Carros (DOOH Engine - CRUD Completo + Mídias + Player + App PWA tv.php)
$router->get('/admin/telas', [ScreenController::class, 'list']);
$router->get('/admin/telas/criar', [ScreenController::class, 'create']);
$router->post('/admin/telas/criar', [ScreenController::class, 'create']);
$router->get('/admin/telas/{id}/midias', [ScreenController::class, 'media']);
$router->post('/admin/telas/{id}/midias', [ScreenController::class, 'media']);
$router->get('/admin/telas/{id}/deletar', [ScreenController::class, 'delete']);
$router->get('/all_users.php', [ScreenController::class, 'list']);
$router->get('/users.php', [ScreenController::class, 'list']);
$router->get('/users_create.php', [ScreenController::class, 'create']);
$router->post('/users_create.php', [ScreenController::class, 'create']);
$router->get('/player/{codigo}', [ScreenController::class, 'player']);
$router->get('/player', [ScreenController::class, 'player']);
$router->get('/player.php', [ScreenController::class, 'player']);
$router->get('/tv', [ScreenController::class, 'tv']);
$router->get('/tv.php', [ScreenController::class, 'tv']);
$router->get('/api_pairing.php', [ScreenController::class, 'apiPairing']);
$router->get('/api/pairing', [ScreenController::class, 'apiPairing']);
$router->get('/manifest.json', [ScreenController::class, 'manifest']);
$router->get('/manifest.webmanifest', [ScreenController::class, 'manifest']);
$router->get('/pwa-manifest.php', [ScreenController::class, 'manifest']);
$router->get('/sw.js', [ScreenController::class, 'serviceWorker']);
$router->get('/api/ping', [ScreenController::class, 'ping']);
$router->get('/widgets/{slug}', [ScreenController::class, 'widget']);

// Gestão de Usuários & Clientes (com Modo Espião e Painel Completo)
$router->get('/admin/usuarios', [UserController::class, 'list']);
$router->post('/admin/usuarios', [UserController::class, 'list']);
$router->get('/admin/usuarios/{id}/espiao', [UserController::class, 'spy']);
$router->get('/admin_spy.php', [UserController::class, 'spy']);
$router->get('/admin/usuarios/criar', [UserController::class, 'create']);
$router->post('/admin/usuarios/criar', [UserController::class, 'create']);
$router->get('/admin/usuarios/{id}/deletar', [UserController::class, 'delete']);
$router->get('/all_users.php', [UserController::class, 'list']);

// Configurações Gerais Root & Evolution API / Agente I.A.
$router->get('/admin/configuracoes', [SettingsController::class, 'index']);
$router->post('/admin/configuracoes', [SettingsController::class, 'index']);
$router->get('/admin/configuracoes/test-api', [SettingsController::class, 'testApi']);
$router->post('/admin/configuracoes/test-api', [SettingsController::class, 'testApi']);
$router->get('/admin/configuracoes/evolution-status', [SettingsController::class, 'evolutionStatus']);
$router->get('/admin/configuracoes/evolution-connect', [SettingsController::class, 'evolutionConnect']);
$router->get('/admin/configuracoes/evolution-logout', [SettingsController::class, 'evolutionLogout']);
$router->get('/configuracoes_root.php', [SettingsController::class, 'index']);
$router->post('/configuracoes_root.php', [SettingsController::class, 'index']);

// Webhook Receptor Evolution API (WhatsApp Leads & Atendimento I.A.)
$router->post('/api/evolution/webhook', [EvolutionWebhookController::class, 'handle']);
$router->get('/api/evolution/webhook', [EvolutionWebhookController::class, 'handle']);

// Web Rádios (Nativo MVC)
$router->get('/admin/radios', [RadioController::class, 'index']);
$router->post('/admin/radios', [RadioController::class, 'index']);
$router->get('/adm_radios.php', [RadioController::class, 'index']);
$router->post('/adm_radios.php', [RadioController::class, 'index']);

// Tela de Bloqueio
$router->get('/admin/bloqueio', [LockScreenController::class, 'index']);
$router->post('/admin/bloqueio', [LockScreenController::class, 'index']);
$router->get('/admplay_bloqueio.php', [LockScreenController::class, 'index']);
$router->post('/admplay_bloqueio.php', [LockScreenController::class, 'index']);

// Editor da Landing Page & Logo Oficial
$router->get('/admin/landing-editor', [LandingEditorController::class, 'index']);
$router->post('/admin/landing-editor', [LandingEditorController::class, 'index']);
$router->get('/admin_landing.php', [LandingEditorController::class, 'index']);
$router->post('/admin_landing.php', [LandingEditorController::class, 'index']);
$router->get('/admplay_logo.php', [LandingEditorController::class, 'index']);
$router->post('/admplay_logo.php', [LandingEditorController::class, 'index']);
$router->get('/logo.php', [LandingEditorController::class, 'index']);
$router->get('/logo', [LandingEditorController::class, 'index']);

// Faturamento & Fiscal
$router->get('/admin/faturamento', [BillingController::class, 'index']);
$router->post('/admin/faturamento', [BillingController::class, 'index']);
$router->get('/admin_faturamento.php', [BillingController::class, 'index']);
$router->post('/admin_faturamento.php', [BillingController::class, 'index']);

// Afiliados
$router->get('/admin/afiliados', [AffiliateController::class, 'index']);
$router->post('/admin/afiliados', [AffiliateController::class, 'index']);
$router->get('/admin_afiliados.php', [AffiliateController::class, 'index']);
$router->post('/admin_afiliados.php', [AffiliateController::class, 'index']);

// Minha Loja
$router->get('/admin/loja', [StoreController::class, 'index']);
$router->post('/admin/loja', [StoreController::class, 'index']);
$router->get('/admin_loja.php', [StoreController::class, 'index']);
$router->post('/admin_loja.php', [StoreController::class, 'index']);
