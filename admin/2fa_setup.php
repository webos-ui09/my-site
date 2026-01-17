<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();

$user = $_SESSION['admin_username'] ?? null;
if (!$user) {
    header('Location: login.php');
    exit;
}

// generate a temporary secret (base32) for display
function base32_random($length = 16) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $s = '';
    for ($i = 0; $i < $length; $i++) $s .= $chars[random_int(0, strlen($chars)-1)];
    return $s;
}

// fetch current 2fa status
try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare('SELECT twofa_enabled, twofa_secret FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $enabled = ($row && !empty($row['twofa_enabled']));
    $secret = $row['twofa_secret'] ?? base32_random(16);
} catch (Throwable $e) {
    $enabled = false;
    $secret = base32_random(16);
}

$otpAuth = 'otpauth://totp/' . rawurlencode('SiteAdmin:' . $user) . '?secret=' . $secret . '&issuer=' . rawurlencode('SiteAdmin');
$qrUrl = 'https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl=' . rawurlencode($otpAuth);

?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Настройка 2FA</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="container" style="max-width:640px;margin:40px auto;">
        <h1>Настройка 2FA</h1>
        <?php if ($enabled): ?>
            <p>2FA уже включена для пользователя <?=htmlspecialchars($user)?></p>
            <form method="post" action="2fa_setup_save.php">
                <input type="hidden" name="action" value="disable">
                <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(get_csrf_token())?>">
                <button type="submit">Отключить 2FA</button>
            </form>
        <?php else: ?>
            <p>Отсканируйте QR-код в приложении (Google Authenticator, Authy и т.д.) и введите код ниже, чтобы включить 2FA.</p>
            <div style="display:flex;gap:20px;align-items:center">
                <div><img src="<?= $qrUrl ?>" alt="QR"></div>
                <div>
                    <div>Секрет: <strong><?= htmlspecialchars($secret) ?></strong></div>
                    <form method="post" action="2fa_setup_save.php">
                        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars(get_csrf_token())?>">
                        <input type="hidden" name="secret" value="<?=htmlspecialchars($secret)?>">
                        <div style="margin-top:10px">
                            <label>Код из приложения<br>
                                <input type="text" name="code" style="padding:8px;width:200px">
                            </label>
                        </div>
                        <div style="margin-top:10px">
                            <button type="submit">Включить 2FA</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <p style="margin-top:20px"><a href="news.php">← Назад</a></p>
    </div>
</body>
</html>
