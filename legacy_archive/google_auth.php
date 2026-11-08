<?php
/**
 * google_auth.php - Controlador de Autenticação OAuth 2.0 / Google Sign-In
 * Plataforma: Player99 / BaitaBric
 */
session_start();
date_default_timezone_set('America/Sao_Paulo');
require_once __DIR__ . '/error_handler.php';
require_once __DIR__ . '/includes/auth_helper.php';

$googleEnabled = getSystemSetting('google_login_enabled', '0') === '1';
$clientId = trim(getSystemSetting('google_client_id', ''));
$clientSecret = trim(getSystemSetting('google_client_secret', ''));

// Calcula redirect URI padrão se não estiver explicitamente configurada
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$basePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$defaultRedirect = $protocol . $host . $basePath . '/google_auth.php';
$redirectUri = trim(getSystemSetting('google_redirect_uri', ''));
if (empty($redirectUri)) {
    $redirectUri = $defaultRedirect;
}

// 1. INICIAR FLUXO DE LOGIN (Redirecionar para o Google)
if (isset($_GET['action']) && $_GET['action'] === 'login') {
    if (!$googleEnabled || empty($clientId)) {
        header("Location: login.php?err=google_disabled");
        exit;
    }

    $state = bin2hex(random_bytes(16));
    $_SESSION['google_oauth_state'] = $state;

    $params = [
        'client_id' => $clientId,
        'redirect_uri' => $redirectUri,
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'access_type' => 'online',
        'state' => $state,
        'prompt' => 'select_account'
    ];

    $authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query($params);
    header("Location: " . $authUrl);
    exit;
}

// 2. RECEBER RETORNO VIA GIS (One-tap / Credential POST)
$idTokenGis = $_POST['credential'] ?? '';
$googleUserEmail = '';
$googleUserName = '';

if (!empty($idTokenGis)) {
    // Validação do Token GIS via API pública do Google
    $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idTokenGis);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $verifyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $tokenInfo = json_decode($response, true);
        if (isset($tokenInfo['email']) && ($tokenInfo['email_verified'] === 'true' || $tokenInfo['email_verified'] === true)) {
            $googleUserEmail = strtolower(trim($tokenInfo['email']));
            $googleUserName = $tokenInfo['name'] ?? '';
        }
    }
}

// 3. RECEBER RETORNO VIA STANDARD OAUTH CODE (GET ?code=...&state=...)
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $state = $_GET['state'] ?? '';

    if (empty($_SESSION['google_oauth_state']) || $state !== $_SESSION['google_oauth_state']) {
        header("Location: login.php?err=invalid_state");
        exit;
    }
    unset($_SESSION['google_oauth_state']);

    // Troca o código por access_token e id_token
    $tokenUrl = "https://oauth2.googleapis.com/token";
    $postData = [
        'code' => $code,
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectUri,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $tokenResponse = curl_exec($ch);
    curl_close($ch);

    if ($tokenResponse) {
        $tokens = json_decode($tokenResponse, true);
        if (!empty($tokens['access_token'])) {
            // Busca dados do usuário
            $userinfoUrl = "https://www.googleapis.com/oauth2/v3/userinfo";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $userinfoUrl);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $tokens['access_token']]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $userInfoResp = curl_exec($ch);
            curl_close($ch);

            if ($userInfoResp) {
                $uData = json_decode($userInfoResp, true);
                if (!empty($uData['email'])) {
                    $googleUserEmail = strtolower(trim($uData['email']));
                    $googleUserName = $uData['name'] ?? '';
                }
            }
        }
    }
}

// Se não conseguiu obter o e-mail autenticado pelo Google
if (empty($googleUserEmail)) {
    header("Location: login.php?err=google_failed");
    exit;
}

// =========================================================================
// 4. ROTEAMENTO DINÂMICO BASEADO NO E-MAIL VERIFICADO DO GOOGLE
// =========================================================================

// A) VERIFICA SE É USUÁRIO ADMINISTRATIVO / REVENDA OU ROOT (users)
$usersDbPath = dirname(__DIR__) . "/api/db/studiolivecode_users.db";
if (file_exists($usersDbPath)) {
    try {
        $dbUsers = new SQLite3($usersDbPath);
        $stmtU = $dbUsers->prepare("SELECT * FROM users WHERE LOWER(email) = :email OR (LOWER(username) = 'baitabric' AND :email = 'baitabric@gmail.com') LIMIT 1");
        $stmtU->bindValue(':email', $googleUserEmail, SQLITE3_TEXT);
        $resU = $stmtU->execute();
        $userRow = $resU->fetchArray(SQLITE3_ASSOC);
        $dbUsers->close();

        if ($userRow) {
            $userId = (int)$userRow['id'];
            $isRoot = isRootUser($userRow);

            // Verifica vencimento administrativo se não for root
            if (!$isRoot) {
                $vencPath = dirname(__DIR__) . "/api/db/studiolivecode_vencimento.db";
                if (file_exists($vencPath)) {
                    $dbV = new SQLite3($vencPath);
                    $stmtV = $dbV->prepare("SELECT vencimento FROM vencimento WHERE id = :id");
                    $stmtV->bindValue(':id', $userId, SQLITE3_INTEGER);
                    $resV = $stmtV->execute();
                    $rowV = $resV->fetchArray(SQLITE3_ASSOC);
                    $dbV->close();

                    if ($rowV && !empty($rowV['vencimento']) && strtotime(date('Y-m-d')) > strtotime($rowV['vencimento'])) {
                        header("Location: login.php?err=admin_vencido&v=" . urlencode($rowV['vencimento']));
                        exit;
                    }
                }
            }

            // Sessão Administrativa Válida
            $dircheck = substr($_SERVER["REQUEST_URI"], 0, strrpos($_SERVER["REQUEST_URI"], "/"));
            $dircheck2 = substr($dircheck, 1);
            
            $_SESSION["studiolivecodevu"] = true;
            $_SESSION["id"] = $userId;
            $_SESSION["U"] = $userRow['username'];
            $_SESSION[$dircheck2] = $dircheck2;
            $_SESSION[$userRow['username'] . $dircheck2] = $userRow['username'] . $dircheck2;
            $_SESSION["user_email"] = $userRow['email'] ?? $googleUserEmail;
            $_SESSION["is_admin"] = ($userRow['admin'] == '1' || $userRow['nivel'] == '1' || $isRoot);
            $_SESSION["nivel"] = $userRow['nivel'];
            $_SESSION["auth_provider"] = "google";

            if ($_SESSION["is_admin"]) {
                header("Location: estatisticas.php");
            } else {
                header("Location: users.php");
            }
            exit;
        }
    } catch (Exception $e) {}
}

// B) VERIFICA SE É CLIENTE FINAL (ibo)
$clientesDbPath = dirname(__DIR__) . "/api/db/studiolivecode_clientes.db";
if (file_exists($clientesDbPath)) {
    try {
        $dbClientes = new SQLite3($clientesDbPath);
        $stmtC = $dbClientes->prepare("SELECT id, username, password, email, expire_date, active FROM ibo WHERE LOWER(email) = :email LIMIT 1");
        $stmtC->bindValue(':email', $googleUserEmail, SQLITE3_TEXT);
        $resC = $stmtC->execute();
        $clienteRow = $resC->fetchArray(SQLITE3_ASSOC);
        $dbClientes->close();

        if ($clienteRow) {
            $clienteId = (int)$clienteRow['id'];
            if ($clienteRow['active'] != 1) {
                header("Location: login.php?err=cliente_inativo");
                exit;
            }

            $exp = $clienteRow['expire_date'];
            if (strpos($exp, '/') !== false) {
                $parts = explode('/', $exp);
                if (count($parts) == 3 && strlen($parts[0]) == 2 && strlen($parts[2]) == 4) {
                    $exp = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
                } elseif (count($parts) == 3 && strlen($parts[0]) == 4) {
                    $exp = $parts[0] . '-' . $parts[1] . '-' . $parts[2];
                }
            }

            if (!empty($exp) && strtotime(date('Y-m-d')) > strtotime($exp)) {
                header("Location: login.php?err=cliente_vencido&exp=" . urlencode($exp));
                exit;
            }

            // Sessão Cliente Válida
            $_SESSION["cliente_logado"] = true;
            $_SESSION["cliente_id"] = $clienteId;
            $_SESSION["cliente_user"] = $clienteRow["username"];
            $_SESSION["cliente_email"] = $clienteRow["email"] ?? $googleUserEmail;
            $_SESSION["auth_provider"] = "google";

            header("Location: cliente_dashboard.php");
            exit;
        }
    } catch (Exception $e) {}
}

// C) NÃO ENCONTRADO EM NENHUM BANCO
header("Location: login.php?err=google_not_found&email=" . urlencode($googleUserEmail));
exit;
