<?php
// Simple admin auth + CSRF helper
// Configure secure session cookie params before starting session
if (php_sapi_name() !== 'cli') {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $cookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    if (function_exists('session_set_cookie_params')) {
        // PHP 7.3+ supports array argument
        session_set_cookie_params($cookieParams);
    }
    if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
        session_start();
    }
} else {
    if (session_status() === PHP_SESSION_NONE) session_start();
}

// Note: credentials are stored in DB `admin_users` table. No fallback to constants.

function is_admin_logged_in() {
    return !empty($_SESSION['admin_logged_in']);
}

function require_admin() {
    // enforce HTTPS for admin pages when possible
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    if (!$isSecure && !in_array(php_sapi_name(), ['cli', 'cli-server'])) {
        // try redirect to https if host is not localhost
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if ($host && stripos($host, 'localhost') === false && stripos($host, '127.0.0.1') === false) {
            $uri = 'https://' . $host . $_SERVER['REQUEST_URI'];
            header('Location: ' . $uri);
            exit;
        }
    }
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Try to authenticate using `admin_users` table if available,
 * otherwise fallback to constants defined above.
 */
function admin_login($user, $pass) {
    // simple rate limit: max 5 attempts per 5 minutes per session
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    // clean old attempts
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function ($ts) { return $ts > time() - 300; });
    if (count($_SESSION['login_attempts']) >= 5) {
        return false; // too many attempts
    }

    // Try DB-based auth first
    try {
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();

        $stmt = $conn->prepare("SELECT password_hash FROM admin_users WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['password_hash'])) {
            if (password_verify($pass, $row['password_hash'])) {
                // if user has 2FA enabled, require TOTP verification
                $twofaStmt = $conn->prepare("SELECT twofa_enabled, twofa_secret FROM admin_users WHERE username = :u LIMIT 1");
                $twofaStmt->execute([':u' => $user]);
                $twofaRow = $twofaStmt->fetch(PDO::FETCH_ASSOC);
                // successful password verification
                $_SESSION['login_attempts'] = [];
                session_regenerate_id(true);
                if ($twofaRow && !empty($twofaRow['twofa_enabled'])) {
                    // store pending username and require 2FA
                    $_SESSION['pending_2fa_user'] = $user;
                    $_SESSION['pending_2fa_time'] = time();
                    return '2fa';
                }
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                admin_audit('login_success', 'password');
                return true;
            }
            // record failed attempt
            $_SESSION['login_attempts'][] = time();
            admin_audit('login_failed', 'password');
            return false;
        }
    } catch (Throwable $e) {
        // DB not available or table missing — fallback below
    }

    // no fallback to constants — require DB-based user
    $_SESSION['login_attempts'][] = time();
    admin_audit('login_failed', 'no_user');
    return false;
}

/**
 * Record an admin action to admin_audit table (if available)
 */
function admin_audit($action, $details = null) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        $username = $_SESSION['admin_logged_in'] ? (isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : null) : (isset($_SESSION['pending_2fa_user']) ? $_SESSION['pending_2fa_user'] : null);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $conn->prepare('INSERT INTO admin_audit (username, action, details, ip) VALUES (:u, :a, :d, :ip)');
        $stmt->execute([':u' => $username, ':a' => $action, ':d' => $details, ':ip' => $ip]);
    } catch (Throwable $e) {
        // ignore audit errors
    }
}

// --- TOTP helpers (RFC6238 simple implementation) ---
function base32_decode($b32) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper($b32);
    $l = strlen($b32);
    $n = 0;
    $j = 0;
    $binary = '';
    for ($i = 0; $i < $l; $i++) {
        $n = $n << 5;
        $n = $n + strpos($alphabet, $b32[$i]);
        $j += 5;
        if ($j >= 8) {
            $j -= 8;
            $binary .= chr(($n & (0xFF << $j)) >> $j);
        }
    }
    return $binary;
}

function totp_now($secret, $digits = 6, $timeStep = 30, $t0 = 0) {
    $time = floor((time() - $t0) / $timeStep);
    return totp_at($secret, $time, $digits);
}

function totp_at($secret, $time, $digits = 6) {
    $secretkey = base32_decode($secret);
    $time = pack('N*', 0) . pack('N*', $time);
    $hash = hash_hmac('sha1', $time, $secretkey, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncatedHash = substr($hash, $offset, 4);
    $value = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;
    $modulo = pow(10, $digits);
    return str_pad($value % $modulo, $digits, '0', STR_PAD_LEFT);
}

function totp_verify($secret, $code, $discrepancy = 1, $digits = 6) {
    $time = floor(time() / 30);
    for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
        if (hash_equals(totp_at($secret, $time + $i, $digits), $code)) return true;
    }
    return false;
}

function admin_logout() {
    // destroy session and cookie
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Flash message helpers
function flash_set($key, $msg) {
    if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
    $_SESSION['flash'][$key] = $msg;
}

function flash_get($key) {
    if (isset($_SESSION['flash'][$key])) {
        $m = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $m;
    }
    return null;
}

?>
