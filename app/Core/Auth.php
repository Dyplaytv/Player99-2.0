<?php
namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['studiolivecodevu']) || !empty($_SESSION['cliente_logado']) || !empty($_SESSION['U']);
    }

    public static function isAdmin(): bool
    {
        if (!empty($_SESSION['is_admin']) || !empty($_SESSION['studiolivecodevu'])) {
            return true;
        }
        $username = $_SESSION['U'] ?? $_SESSION['cliente_user'] ?? '';
        $email = $_SESSION['user_email'] ?? $_SESSION['cliente_email'] ?? '';
        return self::isRoot(['username' => $username, 'email' => $email]);
    }

    public static function isClient(): bool
    {
        return !empty($_SESSION['cliente_logado']);
    }

    public static function user(): ?array
    {
        if (self::check()) {
            $user = [
                'id' => $_SESSION['id'] ?? $_SESSION['cliente_id'] ?? null,
                'username' => $_SESSION['U'] ?? $_SESSION['cliente_user'] ?? null,
                'email' => $_SESSION['user_email'] ?? $_SESSION['cliente_email'] ?? null,
                'is_admin' => !empty($_SESSION['is_admin']),
                'is_root' => false
            ];
            $user['is_root'] = self::isRoot($user);
            if ($user['is_root']) {
                $user['is_admin'] = true;
            }
            return $user;
        }
        return null;
    }

    public static function isRoot($user = null): bool
    {
        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $rootEmails = array_map('strtolower', $appConfig['root_emails'] ?? ['baitabric@gmail.com']);
        $rootUsers = array_map('strtolower', $appConfig['root_usernames'] ?? ['baitabric', 'diego']);

        if ($user === null) {
            $user = [
                'username' => $_SESSION['U'] ?? $_SESSION['cliente_user'] ?? '',
                'email' => $_SESSION['user_email'] ?? $_SESSION['cliente_email'] ?? ''
            ];
        }

        if (is_array($user)) {
            $email = strtolower(trim($user['email'] ?? ''));
            $username = strtolower(trim($user['username'] ?? ''));
            return in_array($email, $rootEmails) || in_array($username, $rootUsers);
        }

        if (is_string($user)) {
            $clean = strtolower(trim($user));
            return in_array($clean, $rootEmails) || in_array($clean, $rootUsers);
        }

        return false;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword(string $inputPassword, string $storedHash, ?callable $rehashCallback = null): bool
    {
        if (empty($storedHash)) {
            return false;
        }

        $matched = false;

        // Bcrypt / Argon2
        if (str_starts_with($storedHash, '$2y$') || str_starts_with($storedHash, '$2a$') || str_starts_with($storedHash, '$argon2')) {
            $matched = password_verify($inputPassword, $storedHash);
        }

        // MD5 legada
        if (!$matched && strlen($storedHash) === 32 && ctype_xdigit($storedHash)) {
            if (md5($inputPassword) === $storedHash) {
                $matched = true;
            }
        }

        // Plaintext legada
        if (!$matched && $inputPassword === $storedHash) {
            $matched = true;
        }

        if ($matched && is_callable($rehashCallback)) {
            call_user_func($rehashCallback, self::hashPassword($inputPassword));
        }

        return $matched;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $savedRef = $_SESSION['affiliate_ref'] ?? ($_COOKIE['affiliate_ref'] ?? '');

        // 1. Limpa todas as variáveis da sessão
        $_SESSION = [];

        // 2. Apaga o cookie de sessão do navegador em múltiplos escopos
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            $path = $params["path"] ?? '/';
            $domain = $params["domain"] ?? '';
            @setcookie(session_name(), '', time() - 86400, $path, $domain, false, true);
            @setcookie(session_name(), '', time() - 86400, '/');
            @setcookie(session_name(), '', time() - 86400, '');
        }

        // 3. Força regeneração de ID e destrói
        if (session_status() === PHP_SESSION_ACTIVE) {
            @session_regenerate_id(true);
            @session_destroy();
        }

        // 4. Cria e fecha sessão limpa garantindo persistência de estado vazio
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        if (!empty($savedRef)) {
            $_SESSION['affiliate_ref'] = $savedRef;
        }
        @session_write_close();
    }
}
