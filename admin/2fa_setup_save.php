<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: 2fa_setup.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    flash_set('error', 'Неверный CSRF токен');
    header('Location: 2fa_setup.php');
    exit;
}

$action = $_POST['action'] ?? 'enable';
$user = $_SESSION['admin_username'] ?? null;
if (!$user) {
    flash_set('error', 'Пользователь не найден');
    header('Location: 2fa_setup.php');
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    if ($action === 'disable') {
        $upd = $conn->prepare('UPDATE admin_users SET twofa_enabled = 0, twofa_secret = NULL WHERE username = :u');
        $upd->execute([':u' => $user]);
        admin_audit('2fa_disabled', null);
        flash_set('success', '2FA отключена');
        header('Location: 2fa_setup.php');
        exit;
    }

    $secret = $_POST['secret'] ?? '';
    $code = trim($_POST['code'] ?? '');
    if (empty($secret) || empty($code)) {
        flash_set('error', 'Не указан секрет или код');
        header('Location: 2fa_setup.php');
        exit;
    }
    if (!totp_verify($secret, $code, 1)) {
        flash_set('error', 'Неверный код 2FA');
        header('Location: 2fa_setup.php');
        exit;
    }

    $ins = $conn->prepare('UPDATE admin_users SET twofa_enabled = 1, twofa_secret = :s WHERE username = :u');
    $ins->execute([':s' => $secret, ':u' => $user]);
    admin_audit('2fa_enabled', null);
    flash_set('success', '2FA успешно включена');
    header('Location: 2fa_setup.php');
    exit;

} catch (Throwable $e) {
    flash_set('error', 'Ошибка сервера');
    header('Location: 2fa_setup.php');
    exit;
}

?>
