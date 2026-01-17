<?php
require_once __DIR__ . '/inc_auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = isset($_POST['user']) ? trim($_POST['user']) : '';
    $pass = isset($_POST['pass']) ? trim($_POST['pass']) : '';
    $res = admin_login($user, $pass);
    if ($res === '2fa') {
        // redirect to 2FA verification page
        header('Location: 2fa_verify.php');
        exit;
    } elseif ($res === true) {
        flash_set('success', 'Вход выполнен.');
        header('Location: news.php');
        exit;
    } else {
        // check attempts to show helpful message
        $attempts = isset($_SESSION['login_attempts']) ? array_filter($_SESSION['login_attempts'], function($ts){return $ts > time() - 300;}) : [];
        if (count($attempts) >= 5) {
            flash_set('error', 'Слишком много попыток входа. Попробуйте через несколько минут.');
        } else {
            flash_set('error', 'Неверный логин или пароль');
        }
        header('Location: login.php');
        exit;
    }
}
$error = flash_get('error');
$success = flash_get('success');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="/style.css">
    <style> .login-box{max-width:400px;margin:80px auto;padding:20px;background:#fff;border-radius:6px} </style>
</head>
<body>
    <div class="container" style="max-width:600px;margin:40px auto;">
        <div class="login-box">
            <h2>Вход в админку</h2>
            <?php if (!empty($error)): ?><div style="color:#c00;margin-bottom:10px"><?=htmlspecialchars($error)?></div><?php endif; ?>
            <?php if (!empty($success)): ?><div style="color:green;margin-bottom:10px"><?=htmlspecialchars($success)?></div><?php endif; ?>
            <form method="post">
                <div style="margin-bottom:10px">
                    <label>Логин<br>
                        <input type="text" name="user" style="width:100%;padding:8px" />
                    </label>
                </div>
                <div style="margin-bottom:10px">
                    <label>Пароль<br>
                        <input type="password" name="pass" style="width:100%;padding:8px" />
                    </label>
                </div>
                <div>
                    <button type="submit" style="padding:8px 14px">Войти</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
