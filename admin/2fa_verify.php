<?php
require_once __DIR__ . '/inc_auth.php';

// require that we have a pending 2fa username
if (empty($_SESSION['pending_2fa_user'])) {
    header('Location: login.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $user = $_SESSION['pending_2fa_user'];
    try {
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        $stmt = $conn->prepare('SELECT twofa_secret FROM admin_users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !empty($row['twofa_secret']) && totp_verify($row['twofa_secret'], $code, 1)) {
            // success: complete login
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user;
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            unset($_SESSION['pending_2fa_user']);
            admin_audit('login_success', '2fa');
            header('Location: news.php');
            exit;
        } else {
            admin_audit('login_failed', '2fa');
            $error = 'Неверный код 2FA';
        }
    } catch (Throwable $e) {
        $error = 'Ошибка проверки 2FA';
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>2FA verification</title>
    <link rel="stylesheet" href="/style.css">
    <style>.box{max-width:400px;margin:80px auto;padding:20px;background:#fff;border-radius:6px}</style>
</head>
<body>
    <div class="container">
        <div class="box">
            <h2>Введите код 2FA</h2>
            <?php if ($error): ?><div style="color:#c00;margin-bottom:10px"><?=htmlspecialchars($error)?></div><?php endif; ?>
            <form method="post">
                <div style="margin-bottom:10px">
                    <label>Код из приложения<br>
                        <input type="text" name="code" style="width:100%;padding:8px" />
                    </label>
                </div>
                <div>
                    <button type="submit" style="padding:8px 14px">Подтвердить</button>
                </div>
            </form>
            <p style="margin-top:12px"><a href="login.php">Отменить</a></p>
        </div>
    </div>
</body>
</html>
