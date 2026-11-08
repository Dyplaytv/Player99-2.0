<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Auth;
use App\Core\Database;
use App\Models\User;
use App\Models\Client;

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
            } else {
                $this->redirect('/cliente_dashboard.php');
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
        } elseif ($request->input('cadastrado') === '1') {
            $errorMessage = "<div class='login-alert' style='background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); color: #6ee7b7;'><i class='fa fa-check-circle'></i> Conta criada com sucesso! Faça login abaixo.</div>";
        }

        $this->render('auth/login', [
            'errorMessage' => $errorMessage,
            'inputLogin' => htmlspecialchars($request->input('login', ''))
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
        }

        // 2. Tenta Área do Cliente (Motorista / Anunciante)
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

                $this->redirect('/cliente_dashboard.php');
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

        $vence = date('Y-m-d', strtotime('+30 DAYS'));
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

        $this->redirect('/login?cadastrado=1');
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/login?logout=1');
    }
}
