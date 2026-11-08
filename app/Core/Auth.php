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

    public static function verifyPassword(string $inputPassword, string $storedPassword, ?callable $rehashCallback = null): bool
    {
        if (empty($inputPassword) || empty($storedPassword)) {
            return false;
        }

        $info = password_get_info($storedPassword);
        if ($info['algo'] !== 0) {
            if (password_verify($inputPassword, $storedPassword)) {
                if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT) && is_callable($rehashCallback)) {
                    call_user_func($rehashCallback, self::hashPassword($inputPassword));
                }
                return true;
            }
            return false;
        }

        $matched = ($inputPassword === $storedPassword || md5($inputPassword) === $storedPassword);
        if ($matched && is_callable($rehashCallback)) {
            call_user_func($rehashCallback, self::hashPassword($inputPassword));
        }

        return $matched;
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
