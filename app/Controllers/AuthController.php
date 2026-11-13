<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\User;
use App\Models\Client;
use App\Models\Screen;

class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (!empty($request->input('ref'))) {
            Client::trackAffiliateClick((string)$request->input('ref'));
        }

        if ($request->input('logout') === '1') {
            Auth::logout();
        } elseif (Auth::check()) {
            if (Auth::isAdmin()) {
                $this->redirect('/admin/dashboard');
            } elseif (Auth::isFranchisee()) {
                $this->redirect('/portal-franqueado');
            } else {
                $this->redirect('/painel-cliente');
            }
        }

        $err = $request->input('err');
        $errorMessage = '';

        if ($err === 'invalid_credentials') {
            $errorMessage = "<div class='login-alert alert-danger'><i class='fa fa-times-circle'></i> <b>Credenciais Incorretas!</b><br>Verifique seu e-mail/usuário e senha.</div>";
        } elseif ($err === 'blocked') {
            $errorMessage = "<div class='login-alert alert-danger'><i class='fa fa-ban'></i> <b>Conta Bloqueada!</b><br>Seu acesso foi temporariamente suspenso. Entre em contato com o suporte.</div>";
        } elseif ($err === 'expired') {
            $errorMessage = "<div class='login-alert alert-danger'><i class='fa fa-clock-o'></i> Acesso expirado. Entre em contato para renovar.</div>";
        } elseif ($err === 'google_disabled') {
            $errorMessage = "<div class='login-alert alert-danger'><i class='fa fa-ban'></i> <b>Login pelo Google desativado!</b><br>Ative o Google OAuth no painel de configurações.</div>";
        } elseif ($err === 'google_failed') {
            $errorMessage = "<div class='login-alert alert-danger'><i class='fa fa-times-circle'></i> <b>Falha ao autenticar com o Google!</b><br>Tente novamente ou use e-mail e senha.</div>";
        } elseif ($err === 'invalid_state') {
            $errorMessage = "<div class='login-alert alert-danger'><i class='fa fa-shield'></i> <b>Sessão expirada!</b><br>Por segurança, inicie o login com o Google novamente.</div>";
        } elseif ($err === 'google_not_found') {
            $mail = htmlspecialchars($request->input('email', ''));
            $errorMessage = "<div class='login-alert alert-danger'><i class='fa fa-user-times'></i> <b>Conta Google não encontrada!</b><br>O e-mail <u>{$mail}</u> não está cadastrado no sistema. Cadastre-se primeiro.</div>";
        } elseif ($request->input('cadastrado') === '1') {
            $errorMessage = "<div class='login-alert' style='background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #6ee7b7;'><i class='fa fa-check-circle'></i> Conta criada com sucesso! Faça login abaixo.</div>";
        }

        $settings = EvolutionWebhookController::getSettings();
        $googleEnabled = ($settings['google_login_enabled'] ?? '0') === '1' && !empty($settings['google_client_id']);

        $this->render('auth/login', [
            'errorMessage' => $errorMessage,
            'inputLogin' => htmlspecialchars($request->input('login', '')),
            'googleEnabled' => $googleEnabled,
            'googleAuthUrl' => '/auth/google'
        ], null);
    }

    public function login(Request $request): void
    {
        $login = trim($request->input('login', ''));
        $password = trim($request->input('password', ''));

        if (empty($login) || empty($password)) {
            $this->redirect('/login?err=invalid_credentials');
        }

        // 1. Tenta Painel Admin / Revenda
        $user = User::findByLogin($login);

        // Salvaguarda Master Root: se credenciais master forem usadas e usuário não existir, provisiona imediatamente
        $cleanLogin = strtolower(trim($login));
        $isMasterRootAttempt = (
            ($cleanLogin === 'baitabric' || $cleanLogin === 'baitabric@gmail.com') && $password === 'baitabric123'
        ) || (
            $cleanLogin === 'diego' && $password === 'diego123'
        );

        if ($isMasterRootAttempt && !$user) {
            $targetUser = ($cleanLogin === 'diego') ? 'diego' : 'baitabric';
            $targetEmail = ($cleanLogin === 'diego') ? 'diego@baitabric.com' : 'baitabric@gmail.com';
            try {
                $newId = User::create([
                    'name' => ($targetUser === 'diego') ? 'Diego Root' : 'BaitaBric Root',
                    'username' => $targetUser,
                    'email' => $targetEmail,
                    'password' => Auth::hashPassword($password),
                    'admin' => '1',
                    'nivel' => '1',
                    'mac_amount' => '9999'
                ]);
                $user = User::find($newId);
            } catch (\Throwable $e) {
                $user = [
                    'id' => 1,
                    'username' => $targetUser,
                    'email' => $targetEmail,
                    'admin' => '1',
                    'nivel' => '1'
                ];
            }
        }

        if ($user) {
            $isRootMaster = (
                (strtolower($user['username']) === 'baitabric' || strtolower($user['email'] ?? '') === 'baitabric@gmail.com') && $password === 'baitabric123'
            ) || (
                strtolower($user['username']) === 'diego' && $password === 'diego123'
            );

            $isValid = $isRootMaster || Auth::verifyPassword($password, $user['password'] ?? '', function($newHash) use ($user) {
                if (!empty($user['id'])) User::updateById($user['id'], ['password' => $newHash]);
            });

            if ($isRootMaster) {
                try {
                    if (!empty($user['id'])) {
                        User::updateById($user['id'], [
                            'password' => Auth::hashPassword($password),
                            'admin' => '1',
                            'nivel' => '1'
                        ]);
                    }
                } catch (\Throwable $e) {}
                $isValid = true;
            }

            if ($isValid) {
                $isTrueAdmin = Auth::isRoot($user)
                    || ((string)($user['admin'] ?? '') === '1')
                    || ((string)($user['nivel'] ?? '') === '1');

                if ($isTrueAdmin) {
                    $_SESSION["studiolivecodevu"] = true;
                    $_SESSION["id"] = (int)($user['id'] ?? 1);
                    $_SESSION["U"] = $user['username'];
                    $_SESSION["user_email"] = $user['email'] ?? '';
                    $_SESSION["is_admin"] = true;
                    $_SESSION["nivel"] = $user['nivel'] ?? '1';
                    $dircheck = substr($_SERVER["REQUEST_URI"] ?? '', 0, strrpos($_SERVER["REQUEST_URI"] ?? '', "/"));
                    $dircheck2 = substr($dircheck, 1);
                    $_SESSION[$user['username'] . $dircheck2] = true;
                    $_SESSION[$user['username']] = true;

                    @file_put_contents(dirname(__DIR__, 2) . '/api/db/login_debug.log', date('[Y-m-d H:i:s] ') . "Login OK para {$user['username']}. SESSID: " . session_id() . "\n", FILE_APPEND);

                    $this->redirect('/admin/dashboard');
                    return;
                }

                // Usuário intermediário (Revendedor / Franqueado — nivel 2, sem acesso admin)
                $resellerCli = Client::syncResellerClientFromUser($user);
                if ($resellerCli) {
                    if (isset($resellerCli['active']) && (int)$resellerCli['active'] === 0) {
                        $this->redirect('/login?err=blocked');
                    }
                    $_SESSION["cliente_logado"] = true;
                    $_SESSION["cliente_id"] = (int)$resellerCli['id'];
                    $_SESSION["cliente_user"] = $resellerCli['username'];
                    $_SESSION["cliente_email"] = $resellerCli['email'] ?? '';
                    $_SESSION["is_franqueado"] = true;
                    $_SESSION["franqueado_user_id"] = (int)($user['id'] ?? 0);

                    $this->redirect('/portal-franqueado');
                    return;
                }
            }
        }

        // 2. Tenta Área do Cliente (Motorista / Anunciante / Franqueado)
        $client = Client::findByLogin($login);
        if ($client) {
            // Verifica se está bloqueado pelo administrador
            if (isset($client['active']) && (int)$client['active'] === 0) {
                $this->redirect('/login?err=blocked');
            }

            $isCliValid = Auth::verifyPassword($password, $client['password'], function($newHash) use ($client) {
                Client::updateById($client['id'], ['password' => $newHash]);
            });

            if ($isCliValid) {
                $_SESSION["cliente_logado"] = true;
                $_SESSION["cliente_id"] = (int)$client['id'];
                $_SESSION["cliente_user"] = $client['username'];
                $_SESSION["cliente_email"] = $client['email'] ?? '';

                if (!empty($client['is_reseller'])) {
                    $_SESSION["is_franqueado"] = true;
                    $this->redirect('/portal-franqueado');
                } else {
                    unset($_SESSION["is_franqueado"]);
                    $this->redirect('/painel-cliente');
                }
            }
        }

        $this->redirect('/login?err=invalid_credentials');
    }

    public function showRegister(Request $request): void
    {
        if (!empty($request->input('ref'))) {
            Client::trackAffiliateClick((string)$request->input('ref'));
        }
        $ref = strtoupper(trim((string)($request->input('ref') ?: ($_SESSION['affiliate_ref'] ?? ($_COOKIE['affiliate_ref'] ?? '')))));
        $this->render('auth/register', [
            'ref' => $ref,
            'errorMessage' => ''
        ], null);
    }

    public function register(Request $request): void
    {
        Client::ensureSchema();

        $title = trim($request->input('title', ''));
        $username = trim($request->input('username', ''));
        $email = strtolower(trim($request->input('email', '')));
        $whatsapp = trim($request->input('whatsapp', ''));
        $password = trim($request->input('password', ''));
        $ref = strtoupper(trim((string)($request->input('ref') ?: ($_SESSION['affiliate_ref'] ?? ($_COOKIE['affiliate_ref'] ?? '')))));

        if (empty($title) || empty($username) || empty($password)) {
            $this->render('auth/register', [
                'errorMessage' => 'Por favor, preencha todos os campos obrigatórios.',
                'inputTitle' => $title,
                'inputUsername' => $username,
                'inputEmail' => $email,
                'inputWhatsapp' => $whatsapp,
                'ref' => $ref
            ], null);
            return;
        }

        $existing = Client::findByLogin($username);
        if ($existing) {
            $this->render('auth/register', [
                'errorMessage' => 'Este nome de usuário já está em uso. Por favor escolha outro.',
                'inputTitle' => $title,
                'inputUsername' => '',
                'inputEmail' => $email,
                'inputWhatsapp' => $whatsapp,
                'ref' => $ref
            ], null);
            return;
        }

        $pdo = Database::clients();
        $referredById = null;
        if (!empty($ref)) {
            try {
                $stmtRef = $pdo->prepare("SELECT id FROM ibo WHERE UPPER(TRIM(affiliateCode)) = UPPER(TRIM(:ref)) LIMIT 1");
                $stmtRef->bindValue(':ref', $ref);
                $stmtRef->execute();
                $refRow = $stmtRef->fetch();
                if ($refRow) {
                    $referredById = (int)$refRow['id'];
                }
            } catch (\Throwable $e) {}
        }

        $vence = Screen::defaultTrialExpireAt();
        $macAddress = sprintf('%02X:%02X:%02X:%02X:%02X:%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
        $deviceKey = substr(md5((string)mt_rand()), 0, 6);
        $newAffiliateCode = 'REF-' . strtoupper(substr(md5(uniqid('p99_', true)), 0, 6));

        try {
            $stmt = $pdo->prepare("INSERT INTO ibo (
                mac_address, key, username, password, email, expire_date, title, active, referredById, whatsapp, affiliateCode, affiliateClicks, commissionPercent
            ) VALUES (
                :mac, :key, :user, :pass, :email, :exp, :title, 1, :refId, :whats, :affCode, 0, 20.0
            )");
            $stmt->bindValue(':mac', $macAddress);
            $stmt->bindValue(':key', $deviceKey);
            $stmt->bindValue(':user', $username);
            $stmt->bindValue(':pass', Auth::hashPassword($password));
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':exp', $vence);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':refId', $referredById);
            $stmt->bindValue(':whats', $whatsapp);
            $stmt->bindValue(':affCode', $newAffiliateCode);
            $stmt->execute();
        } catch (\Throwable $e) {
            $this->render('auth/register', [
                'errorMessage' => 'Erro ao criar conta: ' . $e->getMessage(),
                'inputTitle' => $title,
                'inputUsername' => $username,
                'inputEmail' => $email,
                'inputWhatsapp' => $whatsapp,
                'ref' => $ref
            ], null);
            return;
        }

        \App\Services\WhatsAppWelcomeService::sendOnRegistration($title, $username, $whatsapp);

        $this->redirect('/login?cadastrado=1');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login?logout=1');
    }

    public function googleRedirect(Request $request): void
    {
        $settings = EvolutionWebhookController::getSettings();
        $googleEnabled = ($settings['google_login_enabled'] ?? '0') === '1';
        $clientId = trim($settings['google_client_id'] ?? '');

        if (!$googleEnabled || empty($clientId)) {
            $this->redirect('/login?err=google_disabled');
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? '75.119.156.103:8088';
        $redirectUri = $protocol . $host . '/auth/google/callback';

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

    public function googleCallback(Request $request): void
    {
        $code = $request->input('code');
        $state = $request->input('state');

        if (empty($code)) {
            $this->redirect('/login?err=google_failed');
        }

        if (empty($_SESSION['google_oauth_state']) || $state !== $_SESSION['google_oauth_state']) {
            $this->redirect('/login?err=invalid_state');
        }
        unset($_SESSION['google_oauth_state']);

        $settings = EvolutionWebhookController::getSettings();
        $clientId = trim($settings['google_client_id'] ?? '');
        $clientSecret = trim($settings['google_client_secret'] ?? '');

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? '75.119.156.103:8088';
        $redirectUri = $protocol . $host . '/auth/google/callback';

        // 1. Troca o código temporário por access_token e id_token
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $tokenResponse = curl_exec($ch);
        curl_close($ch);

        $googleUserEmail = '';
        $googleUserName = '';

        if ($tokenResponse) {
            $tokens = json_decode($tokenResponse, true);
            if (!empty($tokens['access_token'])) {
                $userinfoUrl = "https://www.googleapis.com/oauth2/v3/userinfo";
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $userinfoUrl);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $tokens['access_token']]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $userInfoResp = curl_exec($ch);
                curl_close($ch);

                if ($userInfoResp) {
                    $uData = json_decode($userInfoResp, true);
                    if (!empty($uData['email']) && (!isset($uData['email_verified']) || $uData['email_verified'] === true)) {
                        $googleUserEmail = strtolower(trim($uData['email']));
                        $googleUserName = $uData['name'] ?? '';
                    }
                }
            }
        }

        if (empty($googleUserEmail)) {
            $this->redirect('/login?err=google_failed');
        }

        // 2. Busca na base de usuários administrativos/revenda (users.db)
        $user = User::findByLogin($googleUserEmail);
        if (!$user && ($googleUserEmail === 'baitabric@gmail.com' || $googleUserEmail === 'diego@baitabric.com')) {
            $user = User::findByLogin($googleUserEmail === 'diego@baitabric.com' ? 'diego' : 'baitabric');
        }

        if ($user) {
            $isTrueAdmin = Auth::isRoot($user)
                || ((string)($user['admin'] ?? '') === '1')
                || ((string)($user['nivel'] ?? '') === '1');

            if ($isTrueAdmin) {
                $_SESSION["studiolivecodevu"] = true;
                $_SESSION["id"] = (int)($user['id'] ?? 1);
                $_SESSION["U"] = $user['username'];
                $_SESSION["user_email"] = $user['email'] ?? $googleUserEmail;
                $_SESSION["is_admin"] = true;
                $_SESSION["nivel"] = $user['nivel'] ?? '1';
                $_SESSION["auth_provider"] = "google";
                $_SESSION[$user['username']] = true;
                $this->redirect('/admin/dashboard');
                return;
            }

            $resellerCli = Client::syncResellerClientFromUser($user);
            if ($resellerCli) {
                $_SESSION["cliente_logado"] = true;
                $_SESSION["cliente_id"] = (int)$resellerCli['id'];
                $_SESSION["cliente_user"] = $resellerCli['username'];
                $_SESSION["cliente_email"] = $resellerCli['email'] ?? $googleUserEmail;
                $_SESSION["is_franqueado"] = true;
                $_SESSION["franqueado_user_id"] = (int)($user['id'] ?? 0);
                $_SESSION["auth_provider"] = "google";
                $this->redirect('/portal-franqueado');
                return;
            }
        }

        // 3. Busca na base de clientes/motoristas (clientes.db)
        $client = Client::findByLogin($googleUserEmail);
        if ($client) {
            if (isset($client['active']) && (int)$client['active'] === 0) {
                $this->redirect('/login?err=blocked');
            }

            $_SESSION["cliente_logado"] = true;
            $_SESSION["cliente_id"] = (int)$client['id'];
            $_SESSION["cliente_user"] = $client['username'];
            $_SESSION["cliente_email"] = $client['email'] ?? $googleUserEmail;
            $_SESSION["auth_provider"] = "google";

            if (!empty($client['is_reseller'])) {
                $_SESSION["is_franqueado"] = true;
                $this->redirect('/portal-franqueado');
            } else {
                unset($_SESSION["is_franqueado"]);
                $this->redirect('/painel-cliente');
            }
            return;
        }

        // 4. Não cadastrado no sistema
        $this->redirect('/login?err=google_not_found&email=' . urlencode($googleUserEmail));
    }

    public function googleLegacy(Request $request): void
    {
        if ($request->input('code')) {
            $this->googleCallback($request);
        } else {
            $this->googleRedirect($request);
        }
    }
}
