<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: change_password.php');
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    flash_set('error', 'Неверный CSRF токен');
    header('Location: change_password.php');
    exit;
}

$current = $_POST['current_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$confirm = $_POST['new_password_confirm'] ?? '';

if ($new !== $confirm || strlen($new) < 6) {
    flash_set('error', 'Новый пароль должен быть не менее 6 символов и совпадать в обоих полях');
    header('Location: change_password.php');
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();

    // use currently logged-in admin username
    $username = $_SESSION['admin_username'] ?? null;
    if (!$username) {
        flash_set('error', 'Пользователь не найден');
        header('Location: change_password.php');
        exit;
    }
    $stmt = $conn->prepare('SELECT id, password_hash FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $verified = false;
    if ($row && !empty($row['password_hash'])) {
        if (password_verify($current, $row['password_hash'])) {
            $verified = true;
        }
    }

    if (!$verified) {
        flash_set('error', 'Текущий пароль неверен');
        header('Location: change_password.php');
        exit;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    if ($row && isset($row['id'])) {
        $upd = $conn->prepare('UPDATE admin_users SET password_hash = :h WHERE id = :id');
        $upd->execute([':h' => $newHash, ':id' => $row['id']]);
    } else {
        // insert or update by username
        $ins = $conn->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:u, :h) ON DUPLICATE KEY UPDATE password_hash = :h2');
        $ins->execute([':u' => $username, ':h' => $newHash, ':h2' => $newHash]);
    }

    admin_audit('password_change', null);

    flash_set('success', 'Пароль успешно изменён');
    header('Location: change_password.php');
    exit;

} catch (Throwable $e) {
    flash_set('error', 'Ошибка при изменении пароля');
    header('Location: change_password.php');
    exit;
}

?>
