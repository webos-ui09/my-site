<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();

$msg = flash_get('success');
$err = flash_get('error');
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Сменить пароль</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="container" style="max-width:600px;margin:40px auto;">
        <h1>Сменить пароль</h1>
        <?php if ($err): ?><div style="color:#c00;margin-bottom:10px"><?=htmlspecialchars($err)?></div><?php endif; ?>
        <?php if ($msg): ?><div style="color:green;margin-bottom:10px"><?=htmlspecialchars($msg)?></div><?php endif; ?>

        <form method="post" action="change_password_save.php">
            <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(get_csrf_token())?>">
            <div style="margin-bottom:10px">
                <label>Текущий пароль<br>
                    <input type="password" name="current_password" style="width:100%;padding:8px" />
                </label>
            </div>
            <div style="margin-bottom:10px">
                <label>Новый пароль<br>
                    <input type="password" name="new_password" style="width:100%;padding:8px" />
                </label>
            </div>
            <div style="margin-bottom:10px">
                <label>Повторите новый пароль<br>
                    <input type="password" name="new_password_confirm" style="width:100%;padding:8px" />
                </label>
            </div>
            <div>
                <button type="submit" style="padding:8px 14px">Сменить пароль</button>
                &nbsp; <a href="news.php">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>
