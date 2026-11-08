<?php
session_start();
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/includes/auth_helper.php';

// Verificação estrita de autenticação e permissão ROOT
if (!isset($_SESSION["U"]) || !isset($_SESSION["id"])) {
    header("Location: login.php");
    exit;
}

$currentUserId = intval($_SESSION["id"]);
if (!isRootUser($currentUserId)) {
    header("Location: estatisticas.php?r=acesso_negado");
    exit;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$defaultRedirectUri = $protocol . $host . $basePath . '/google_auth.php';

$msg = '';
$msgType = '';

// PROCESSAR SALVAMENTO DAS CONFIGURAÇÕES
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["salvar_configuracoes"])) {
    // 1. Google OAuth
    $googleEnabled = isset($_POST['google_login_enabled']) ? '1' : '0';
    $googleClientId = trim($_POST['google_client_id'] ?? '');
    $googleClientSecret = trim($_POST['google_client_secret'] ?? '');
    $googleRedirectUri = trim($_POST['google_redirect_uri'] ?? '');
    if (empty($googleRedirectUri)) {
        $googleRedirectUri = $defaultRedirectUri;
    }

    setSystemSetting('google_login_enabled', $googleEnabled);
    setSystemSetting('google_client_id', $googleClientId);
    setSystemSetting('google_client_secret', $googleClientSecret);
    setSystemSetting('google_redirect_uri', $googleRedirectUri);

    // 2. Evolution API (WhatsApp)
    $evoUrl = trim($_POST['evolution_url'] ?? '');
    $evoKey = trim($_POST['evolution_api_key'] ?? '');
    $evoInst = trim($_POST['evolution_instance'] ?? '');
    $evoHub = trim($_POST['evolution_hub_token'] ?? '');

    setSystemSetting('evolution_url', $evoUrl);
    setSystemSetting('evolution_api_key', $evoKey);
    setSystemSetting('evolution_instance', $evoInst);
    setSystemSetting('evolution_hub_token', $evoHub);

    // Sincroniza com evolution_config.json
    $evoJsonFile = __DIR__ . '/evolution_config.json';
    $evoData = [
        'evolution_url' => $evoUrl,
        'api_key' => $evoKey,
        'instancia' => $evoInst,
        'hub_token' => $evoHub
    ];
    @file_put_contents($evoJsonFile, json_encode($evoData, JSON_PRETTY_PRINT));

    // 3. Chaves de IA
    $openaiKey = trim($_POST['openai_api_key'] ?? '');
    $geminiKey = trim($_POST['gemini_api_key'] ?? '');
    $anthropicKey = trim($_POST['anthropic_api_key'] ?? '');
    $aiProvider = trim($_POST['ai_default_provider'] ?? 'gemini');

    setSystemSetting('openai_api_key', $openaiKey);
    setSystemSetting('gemini_api_key', $geminiKey);
    setSystemSetting('anthropic_api_key', $anthropicKey);
    setSystemSetting('ai_default_provider', $aiProvider);

    // 4. APIs e Gateways
    $tmdbKey = trim($_POST['tmdb_api_key'] ?? '');
    $weatherKey = trim($_POST['weather_api_key'] ?? '');
    $mpAccessToken = trim($_POST['mercadopago_access_token'] ?? '');
    $mpPublicKey = trim($_POST['mercadopago_public_key'] ?? '');

    setSystemSetting('tmdb_api_key', $tmdbKey);
    setSystemSetting('weather_api_key', $weatherKey);
    setSystemSetting('mercadopago_access_token', $mpAccessToken);
    setSystemSetting('mercadopago_public_key', $mpPublicKey);

    // Sincroniza TMDB se fornecido
    if (!empty($tmdbKey)) {
        try {
            $dbTmdb = new SQLite3(__DIR__ . "/api/.bet_tmdb.db");
            $dbTmdb->exec("CREATE TABLE IF NOT EXISTS api_key (id INTEGER PRIMARY KEY, key TEXT)");
            $stmtTmdb = $dbTmdb->prepare("UPDATE api_key SET key = :k WHERE id = 1");
            $stmtTmdb->bindValue(':k', $tmdbKey, SQLITE3_TEXT);
            $stmtTmdb->execute();
            $dbTmdb->close();
        } catch (Exception $e) {}
    }

    $msg = "Configurações do sistema atualizadas com sucesso!";
    $msgType = "success";
}

// Carrega valores atuais
$googleEnabled = getSystemSetting('google_login_enabled', '0') === '1';
$googleClientId = getSystemSetting('google_client_id', '');
$googleClientSecret = getSystemSetting('google_client_secret', '');
$googleRedirectUri = getSystemSetting('google_redirect_uri', $defaultRedirectUri);

$evolutionUrl = getSystemSetting('evolution_url', '');
$evolutionApiKey = getSystemSetting('evolution_api_key', '');
$evolutionInstance = getSystemSetting('evolution_instance', '');
$evolutionHubToken = getSystemSetting('evolution_hub_token', '');

// Fallback do evolution_config.json caso vazio no banco
if (empty($evolutionUrl) && file_exists(__DIR__ . '/evolution_config.json')) {
    $evoJson = json_decode(file_get_contents(__DIR__ . '/evolution_config.json'), true);
    $evolutionUrl = $evoJson['evolution_url'] ?? '';
    $evolutionApiKey = $evoJson['api_key'] ?? '';
    $evolutionInstance = $evoJson['instancia'] ?? '';
    $evolutionHubToken = $evoJson['hub_token'] ?? '';
}

$openaiApiKey = getSystemSetting('openai_api_key', '');
$geminiApiKey = getSystemSetting('gemini_api_key', '');
$anthropicApiKey = getSystemSetting('anthropic_api_key', '');
$aiDefaultProvider = getSystemSetting('ai_default_provider', 'gemini');

$tmdbApiKey = getSystemSetting('tmdb_api_key', '');
if (empty($tmdbApiKey) && file_exists(__DIR__ . "/api/.bet_tmdb.db")) {
    try {
        $dbT = new SQLite3(__DIR__ . "/api/.bet_tmdb.db");
        $rT = $dbT->querySingle("SELECT key FROM api_key WHERE id = 1");
        if ($rT) $tmdbApiKey = $rT;
        $dbT->close();
    } catch (Exception $e) {}
}

$weatherApiKey = getSystemSetting('weather_api_key', '');
$mpAccessToken = getSystemSetting('mercadopago_access_token', '');
$mpPublicKey = getSystemSetting('mercadopago_public_key', '');

include './includes/header.php';
?>

<style>
.root-badge-header {
    background: linear-gradient(135deg, #e52d27 0%, #b31217 100%);
    color: #fff;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(229, 45, 39, 0.4);
}
.nav-pills-custom .nav-link {
    color: #a0aec0;
    font-weight: 600;
    padding: 12px 20px;
    border-radius: 10px;
    margin-right: 10px;
    transition: all 0.3s;
    background: rgba(255, 255, 255, 0.04);
}
.nav-pills-custom .nav-link.active {
    background: linear-gradient(135deg, #0A38D1, #00d2ff);
    color: #fff;
    box-shadow: 0 4px 15px rgba(10, 56, 209, 0.4);
}
.input-with-eye {
    position: relative;
    display: flex;
    align-items: center;
}
.input-with-eye input {
    padding-right: 42px;
}
.input-with-eye .toggle-eye-btn {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #718096;
    cursor: pointer;
    font-size: 1.15rem;
    padding: 0;
}
.input-with-eye .toggle-eye-btn:hover {
    color: #00d2ff;
}
.info-box-custom {
    background: rgba(10, 56, 209, 0.08);
    border-left: 4px solid #0A38D1;
    padding: 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    color: #cbd5e1;
}
</style>

<div class="page-wrapper">
    <div class="page-content">
        <!-- Breadcrumb & Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h4 class="font-weight-bold mb-1">Configurações Gerais do Sistema</h4>
                <p class="text-muted mb-0">Gestão central de APIs, Credenciais Google, WhatsApp e Inteligência Artificial</p>
            </div>
            <div>
                <span class="root-badge-header">
                    <i class="bx bx-shield-quarter"></i> ACESSO EXCLUSIVO ROOT
                </span>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
                <i class="bx bx-check-circle me-1"></i> <b><?= htmlspecialchars($msg) ?></b>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="configuracoes_root.php" autocomplete="off">
            <input type="hidden" name="salvar_configuracoes" value="1">

            <!-- Navegação por Abas -->
            <ul class="nav nav-pills nav-pills-custom mb-4" id="configTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="google-tab" data-bs-toggle="pill" data-bs-target="#tab-google" type="button" role="tab">
                        <i class='bx bxl-google me-1'></i> Google Sign-In (OAuth)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="evolution-tab" data-bs-toggle="pill" data-bs-target="#tab-evolution" type="button" role="tab">
                        <i class='bx bxl-whatsapp me-1'></i> Evolution API (WhatsApp)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ai-tab" data-bs-toggle="pill" data-bs-target="#tab-ai" type="button" role="tab">
                        <i class='bx bx-brain me-1'></i> Chaves de IA
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="apis-tab" data-bs-toggle="pill" data-bs-target="#tab-apis" type="button" role="tab">
                        <i class='bx bx-network-chart me-1'></i> Outras APIs & Pagamentos
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="configTabsContent">
                <!-- ABA 1: GOOGLE OAUTH -->
                <div class="tab-pane fade show active" id="tab-google" role="tabpanel">
                    <div class="card radius-10">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h5 class="card-title text-primary mb-0"><i class='bx bxl-google'></i> Autenticação com a Conta Google</h5>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="google_login_enabled" name="google_login_enabled" value="1" <?= $googleEnabled ? 'checked' : '' ?> style="cursor: pointer; transform: scale(1.3);">
                                    <label class="form-check-label ms-2 font-weight-bold" for="google_login_enabled">Habilitar Botão Google no Login</label>
                                </div>
                            </div>
                            <hr>

                            <div class="info-box-custom mb-4">
                                <strong><i class='bx bx-info-circle'></i> Como obter as credenciais do Google:</strong>
                                <ol class="mb-0 mt-2 ps-3">
                                    <li>Acesse o console de desenvolvedores: <a href="https://console.cloud.google.com/apis/credentials" target="_blank" class="text-info font-weight-bold">Google Cloud Console</a>.</li>
                                    <li>Crie um projeto e configure a <b>Tela de permissão OAuth</b> (OAuth Consent Screen).</li>
                                    <li>Em <b>Credenciais</b>, clique em <b>Criar Credenciais &gt; ID do cliente OAuth</b> (Aplicativo da Web).</li>
                                    <li>Em <b>URIs de redirecionamento autorizados</b>, adicione a URL abaixo:</li>
                                </ol>
                                <div class="mt-2 input-group">
                                    <input type="text" class="form-control form-control-sm text-info font-monospace" value="<?= htmlspecialchars($googleRedirectUri) ?>" id="copyRedirectUri" readonly>
                                    <button class="btn btn-sm btn-outline-info" type="button" onclick="navigator.clipboard.writeText(document.getElementById('copyRedirectUri').value); alert('URL copiada!');"><i class='bx bx-copy'></i> Copiar</button>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label font-weight-bold" for="google_client_id">Google Client ID</label>
                                    <input type="text" class="form-control" name="google_client_id" id="google_client_id" placeholder="Ex: 123456789-xxxxxxxx.apps.googleusercontent.com" value="<?= htmlspecialchars($googleClientId) ?>">
                                    <small class="text-muted">Chave pública do cliente gerada no Google Cloud.</small>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label font-weight-bold" for="google_client_secret">Google Client Secret</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="google_client_secret" id="google_client_secret" placeholder="Ex: GOCSPX-xxxxxxxxxxxxxxxx" value="<?= htmlspecialchars($googleClientSecret) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('google_client_secret', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                    <small class="text-muted">Chave secreta do cliente OAuth do Google.</small>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label font-weight-bold" for="google_redirect_uri">URI de Redirecionamento Personalizada</label>
                                    <input type="text" class="form-control" name="google_redirect_uri" id="google_redirect_uri" value="<?= htmlspecialchars($googleRedirectUri) ?>">
                                    <small class="text-muted">Deixe em branco para usar o endereço padrão detectado automaticamente.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 2: EVOLUTION API (WHATSAPP) -->
                <div class="tab-pane fade" id="tab-evolution" role="tabpanel">
                    <div class="card radius-10">
                        <div class="card-body">
                            <h5 class="card-title text-success"><i class='bx bxl-whatsapp'></i> Evolution API (Gateway WhatsApp)</h5>
                            <hr>
                            <p class="text-muted">Configurações para envio de avisos de vencimento, cobranças, notificações automáticas e comunicação com clientes.</p>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="evolution_url">URL da Evolution API</label>
                                    <input type="text" class="form-control" name="evolution_url" id="evolution_url" placeholder="http://75.119.156.103:8080" value="<?= htmlspecialchars($evolutionUrl) ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="evolution_api_key">Global API Key</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="evolution_api_key" id="evolution_api_key" placeholder="Chave Global" value="<?= htmlspecialchars($evolutionApiKey) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('evolution_api_key', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="evolution_instance">Nome da Instância</label>
                                    <input type="text" class="form-control" name="evolution_instance" id="evolution_instance" placeholder="player99_xxxx" value="<?= htmlspecialchars($evolutionInstance) ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="evolution_hub_token">Token HUB (opcional)</label>
                                    <input type="text" class="form-control" name="evolution_hub_token" id="evolution_hub_token" placeholder="HUB_xxxxxxxx" value="<?= htmlspecialchars($evolutionHubToken) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 3: INTELIGÊNCIA ARTIFICIAL -->
                <div class="tab-pane fade" id="tab-ai" role="tabpanel">
                    <div class="card radius-10">
                        <div class="card-body">
                            <h5 class="card-title text-warning"><i class='bx bx-brain'></i> Provedores de Inteligência Artificial</h5>
                            <hr>
                            <p class="text-muted">Configure chaves de IA para geração automática de conteúdos de mídia indoor, resumos de notícias, títulos e automações.</p>

                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label font-weight-bold">Provedor Padrão de IA</label>
                                    <select name="ai_default_provider" class="form-select">
                                        <option value="gemini" <?= $aiDefaultProvider === 'gemini' ? 'selected' : '' ?>>Google Gemini (Recomendado)</option>
                                        <option value="openai" <?= $aiDefaultProvider === 'openai' ? 'selected' : '' ?>>OpenAI ChatGPT (GPT-4o / GPT-4o-mini)</option>
                                        <option value="anthropic" <?= $aiDefaultProvider === 'anthropic' ? 'selected' : '' ?>>Anthropic Claude (Claude 3.5 Sonnet)</option>
                                    </select>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label font-weight-bold" for="gemini_api_key">Google Gemini API Key</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="gemini_api_key" id="gemini_api_key" placeholder="AIzaSy..." value="<?= htmlspecialchars($geminiApiKey) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('gemini_api_key', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                    <small class="text-muted">Obtida em <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-info">Google AI Studio</a>.</small>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label font-weight-bold" for="openai_api_key">OpenAI API Key</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="openai_api_key" id="openai_api_key" placeholder="sk-..." value="<?= htmlspecialchars($openaiApiKey) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('openai_api_key', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                    <small class="text-muted">Obtida em <a href="https://platform.openai.com/api-keys" target="_blank" class="text-info">OpenAI Platform</a>.</small>
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label class="form-label font-weight-bold" for="anthropic_api_key">Anthropic Claude API Key</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="anthropic_api_key" id="anthropic_api_key" placeholder="sk-ant-..." value="<?= htmlspecialchars($anthropicApiKey) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('anthropic_api_key', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 4: OUTRAS APIS & GATEWAYS -->
                <div class="tab-pane fade" id="tab-apis" role="tabpanel">
                    <div class="card radius-10">
                        <div class="card-body">
                            <h5 class="card-title text-info"><i class='bx bx-network-chart'></i> APIs de Mídia, Clima e Pagamentos</h5>
                            <hr>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="tmdb_api_key">TMDB API Key (Filmes e Sinopses)</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="tmdb_api_key" id="tmdb_api_key" placeholder="Chave TMDB v3" value="<?= htmlspecialchars($tmdbApiKey) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('tmdb_api_key', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                    <small class="text-muted">Utilizada nos widgets de Entretenimento e Lançamentos de Cinema.</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="weather_api_key">OpenWeather API Key (Previsão do Tempo)</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="weather_api_key" id="weather_api_key" placeholder="Chave OpenWeatherMap" value="<?= htmlspecialchars($weatherApiKey) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('weather_api_key', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                    <small class="text-muted">Utilizada no Widget de Clima nas TVs dos clientes.</small>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="mercadopago_access_token">Mercado Pago Access Token</label>
                                    <div class="input-with-eye">
                                        <input type="password" class="form-control" name="mercadopago_access_token" id="mercadopago_access_token" placeholder="APP_USR-..." value="<?= htmlspecialchars($mpAccessToken) ?>">
                                        <button type="button" class="toggle-eye-btn" onclick="togglePassVisibility('mercadopago_access_token', this)"><i class='bx bx-show'></i></button>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label class="form-label font-weight-bold" for="mercadopago_public_key">Mercado Pago Public Key</label>
                                    <input type="text" class="form-control" name="mercadopago_public_key" id="mercadopago_public_key" placeholder="TEST-... ou APP_USR-..." value="<?= htmlspecialchars($mpPublicKey) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botão Salvar Fixo / Destacado -->
            <div class="mt-4 mb-5 text-end">
                <button type="submit" class="btn btn-primary btn-lg px-5 radius-10 shadow">
                    <i class='bx bx-save me-1'></i> Salvar Todas as Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bx-show');
            icon.classList.add('bx-hide');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bx-hide');
            icon.classList.add('bx-show');
        }
    }
}
</script>

<?php include './includes/footer.php'; ?>
