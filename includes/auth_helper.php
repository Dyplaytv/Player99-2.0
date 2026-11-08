<?php
/**
 * auth_helper.php - Sistema de Autenticação Centralizada e Blindagem Root
 * Plataforma: Player99 / BaitaBric
 */

if (!defined('ROOT_EMAILS')) {
    define('ROOT_EMAILS', [
        'baitabric@gmail.com'
    ]);
}

if (!defined('ROOT_USERNAMES')) {
    define('ROOT_USERNAMES', [
        'baitabric',
        'diego'
    ]);
}

/**
 * Verifica se um determinado usuário (por array, email, username ou ID) é ROOT.
 *
 * @param mixed $user Informações do usuário (array associativo, ID numérico ou string de email/username)
 * @param SQLite3|null $db Conexão opcional com studiolivecode_users.db
 * @return bool
 */
function isRootUser($user, $db = null) {
    if (empty($user)) {
        return false;
    }

    // Se passou apenas string (email ou username)
    if (is_string($user)) {
        $clean = strtolower(trim($user));
        if (in_array($clean, array_map('strtolower', ROOT_EMAILS))) {
            return true;
        }
        if (in_array($clean, array_map('strtolower', ROOT_USERNAMES))) {
            return true;
        }
    }

    // Se passou array com dados do usuário
    if (is_array($user)) {
        $email = isset($user['email']) ? strtolower(trim($user['email'])) : '';
        $username = isset($user['username']) ? strtolower(trim($user['username'])) : '';

        if (!empty($email) && in_array($email, array_map('strtolower', ROOT_EMAILS))) {
            return true;
        }
        if (!empty($username) && in_array($username, array_map('strtolower', ROOT_USERNAMES))) {
            return true;
        }
    }

    // Se passou ID numérico, busca no banco se houver conexão
    if (is_numeric($user)) {
        $closeDb = false;
        if (!$db) {
            $dbPath = __DIR__ . '/../api/db/studiolivecode_users.db';
            if (file_exists($dbPath)) {
                try {
                    $db = new SQLite3($dbPath);
                    $closeDb = true;
                } catch (Exception $e) {
                    return false;
                }
            } else {
                return false;
            }
        }

        try {
            $stmt = $db->prepare("SELECT id, username, email FROM users WHERE id = :id");
            $stmt->bindValue(':id', (int)$user, SQLITE3_INTEGER);
            $res = $stmt->execute();
            $row = $res->fetchArray(SQLITE3_ASSOC);
            if ($closeDb) {
                $db->close();
            }
            if ($row) {
                return isRootUser($row);
            }
        } catch (Exception $e) {
            if ($closeDb && $db) {
                $db->close();
            }
        }
    }

    return false;
}

/**
 * Garante as colunas necessárias e o provisionamento do usuário root
 */
function initSecurityMigrations() {
    $baseDir = __DIR__ . '/../api/db';
    if (!is_dir($baseDir)) {
        @mkdir($baseDir, 0755, true);
    }

    // 1. Tabela users em studiolivecode_users.db
    $usersDbPath = $baseDir . '/studiolivecode_users.db';
    try {
        $dbUsers = new SQLite3($usersDbPath);
        $dbUsers->busyTimeout(5000);

        // Cria tabela users caso não exista
        $dbUsers->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100),
            username VARCHAR(100) UNIQUE,
            password VARCHAR(255),
            logo VARCHAR(100),
            nivel VARCHAR(100) DEFAULT '1',
            admin VARCHAR(100) DEFAULT '1',
            store_type VARCHAR(100),
            mac_amount VARCHAR(100),
            email VARCHAR(150)
        )");

        // Garante coluna email
        $resCols = $dbUsers->query("PRAGMA table_info(users)");
        $userCols = [];
        while ($col = $resCols->fetchArray(SQLITE3_ASSOC)) {
            $userCols[] = $col['name'];
        }

        if (!in_array('email', $userCols)) {
            @$dbUsers->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150)");
        }

        // Verifica existência do usuário root baitabric@gmail.com
        $rootEmail = 'baitabric@gmail.com';
        $stmtCheck = $dbUsers->prepare("SELECT * FROM users WHERE LOWER(email) = :email OR LOWER(username) = 'baitabric'");
        $stmtCheck->bindValue(':email', strtolower($rootEmail), SQLITE3_TEXT);
        $resCheck = $stmtCheck->execute();
        $rootUser = $resCheck->fetchArray(SQLITE3_ASSOC);

        if (!$rootUser) {
            // Se já existir usuário 'admin', podemos vincular ou criar o root baitabric
            $stmtAdmin = $dbUsers->prepare("SELECT * FROM users WHERE username = 'admin' LIMIT 1");
            $resAdmin = $stmtAdmin->execute();
            $adminUser = $resAdmin->fetchArray(SQLITE3_ASSOC);

            if ($adminUser && empty($adminUser['email'])) {
                // Atualiza o admin existente para ter o e-mail baitabric@gmail.com
                $stmtUp = $dbUsers->prepare("UPDATE users SET email = :email, admin = '1', nivel = '1' WHERE id = :id");
                $stmtUp->bindValue(':email', $rootEmail, SQLITE3_TEXT);
                $stmtUp->bindValue(':id', $adminUser['id'], SQLITE3_INTEGER);
                $stmtUp->execute();
            } else {
                // Insere o usuário root
                $stmtIns = $dbUsers->prepare("INSERT INTO users (name, username, password, email, logo, nivel, admin) 
                    VALUES ('BaitaBric Root', 'baitabric', 'baitabric123', :email, './banners/logo.png', '1', '1')");
                $stmtIns->bindValue(':email', $rootEmail, SQLITE3_TEXT);
                $stmtIns->execute();
            }
        } else {
            // Garante que o root sempre mantenha admin=1 e nivel=1 (blindagem contra corrupção de privilégios)
            if ($rootUser['admin'] != '1' || $rootUser['nivel'] != '1' || empty($rootUser['email'])) {
                $stmtShield = $dbUsers->prepare("UPDATE users SET admin = '1', nivel = '1', email = :email WHERE id = :id");
                $stmtShield->bindValue(':email', $rootEmail, SQLITE3_TEXT);
                $stmtShield->bindValue(':id', $rootUser['id'], SQLITE3_INTEGER);
                $stmtShield->execute();
            }
        }

        // Verifica existência do usuário root diego
        $stmtDiego = $dbUsers->prepare("SELECT * FROM users WHERE LOWER(username) = 'diego' LIMIT 1");
        $resDiego = $stmtDiego->execute();
        $diegoUser = $resDiego->fetchArray(SQLITE3_ASSOC);

        if (!$diegoUser) {
            $stmtInsDiego = $dbUsers->prepare("INSERT INTO users (name, username, password, email, logo, nivel, admin) 
                VALUES ('Diego', 'diego', 'diego123', 'diego@baitabric.com', './banners/logo.png', '1', '1')");
            $stmtInsDiego->execute();
        } else {
            if ($diegoUser['admin'] != '1' || $diegoUser['nivel'] != '1') {
                $stmtUpDiego = $dbUsers->prepare("UPDATE users SET admin = '1', nivel = '1' WHERE id = :id");
                $stmtUpDiego->bindValue(':id', $diegoUser['id'], SQLITE3_INTEGER);
                $stmtUpDiego->execute();
            }
        }

        $dbUsers->close();
    } catch (Exception $e) {
        // Log silencioso ou registrar no error_handler
    }

    // 2. Tabela ibo em studiolivecode_clientes.db
    $clientesDbPath = $baseDir . '/studiolivecode_clientes.db';
    try {
        $dbClientes = new SQLite3($clientesDbPath);
        $dbClientes->busyTimeout(5000);

        $resColsC = $dbClientes->query("PRAGMA table_info(ibo)");
        $clienteCols = [];
        if ($resColsC) {
            while ($col = $resColsC->fetchArray(SQLITE3_ASSOC)) {
                $clienteCols[] = $col['name'];
            }
        }

        if (!empty($clienteCols) && !in_array('email', $clienteCols)) {
            @$dbClientes->exec("ALTER TABLE ibo ADD COLUMN email VARCHAR(150)");
        }

        $dbClientes->close();
    } catch (Exception $e) {
        // Continua normalmente
    }

    // 3. Tabela system_settings em studiolivecode_users.db
    try {
        $dbSettings = new SQLite3($usersDbPath);
        $dbSettings->busyTimeout(5000);
        $dbSettings->exec("CREATE TABLE IF NOT EXISTS system_settings (
            setting_key VARCHAR(100) PRIMARY KEY,
            setting_value TEXT,
            updated_at TEXT
        )");
        $dbSettings->close();
    } catch (Exception $e) {}
}

/**
 * Obtém uma configuração do sistema (salva no banco studiolivecode_users.db)
 */
function getSystemSetting($key, $default = '') {
    $dbPath = __DIR__ . '/../api/db/studiolivecode_users.db';
    if (!file_exists($dbPath)) {
        return $default;
    }

    try {
        $db = new SQLite3($dbPath);
        $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :k LIMIT 1");
        $stmt->bindValue(':k', $key, SQLITE3_TEXT);
        $res = $stmt->execute();
        $row = $res->fetchArray(SQLITE3_ASSOC);
        $db->close();
        if ($row && $row['setting_value'] !== null) {
            return $row['setting_value'];
        }
    } catch (Exception $e) {}

    return $default;
}

/**
 * Grava ou atualiza uma configuração do sistema
 */
function setSystemSetting($key, $value) {
    $dbPath = __DIR__ . '/../api/db/studiolivecode_users.db';
    try {
        $db = new SQLite3($dbPath);
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES (:k, :v, :dt)
            ON CONFLICT(setting_key) DO UPDATE SET setting_value = :v, updated_at = :dt");
        $stmt->bindValue(':k', $key, SQLITE3_TEXT);
        $stmt->bindValue(':v', (string)$value, SQLITE3_TEXT);
        $stmt->bindValue(':dt', date('Y-m-d H:i:s'), SQLITE3_TEXT);
        $success = $stmt->execute();
        $db->close();
        return (bool)$success;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Retorna todas as configurações do sistema como array chave => valor
 */
function getAllSystemSettings() {
    $dbPath = __DIR__ . '/../api/db/studiolivecode_users.db';
    $settings = [];
    if (!file_exists($dbPath)) {
        return $settings;
    }

    try {
        $db = new SQLite3($dbPath);
        $res = $db->query("SELECT setting_key, setting_value FROM system_settings");
        while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        $db->close();
    } catch (Exception $e) {}

    return $settings;
}

/**
 * Validação segura de senha com suporte a migração transparente para password_hash
 *
 * @param string $inputPassword Senha fornecida pelo usuário
 * @param string $storedPassword Senha armazenada no banco
 * @param callable|null $rehashCallback Callback para atualizar o hash se a senha estiver em texto claro
 * @return bool
 */
function verifyAndRehashPassword($inputPassword, $storedPassword, $rehashCallback = null) {
    if (empty($inputPassword) || empty($storedPassword)) {
        return false;
    }

    // 1. Verifica se já é um hash padrão do PHP (ex: bcrypt ou argon2)
    $info = password_get_info($storedPassword);
    if ($info['algo'] !== 0) {
        if (password_verify($inputPassword, $storedPassword)) {
            if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT) && is_callable($rehashCallback)) {
                $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
                call_user_func($rehashCallback, $newHash);
            }
            return true;
        }
        return false;
    }

    // 2. Verificação legado (texto puro ou md5)
    $matched = false;
    if ($inputPassword === $storedPassword) {
        $matched = true;
    } elseif (md5($inputPassword) === $storedPassword) {
        $matched = true;
    }

    // Se combinou e temos callback, migra automaticamente para hash forte
    if ($matched && is_callable($rehashCallback)) {
        $newHash = password_hash($inputPassword, PASSWORD_DEFAULT);
        call_user_func($rehashCallback, $newHash);
    }

    return $matched;
}

// Executa auto-migração na primeira inclusão
initSecurityMigrations();
