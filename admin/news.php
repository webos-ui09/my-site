<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Fetch all news
$stmt = $conn->prepare("SELECT id, title, excerpt, published, created_at FROM news ORDER BY created_at DESC");
$stmt->execute();
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Admin - Новости</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="container" style="max-width:1000px;margin:30px auto;">
        <h1>Новости — Админ</h1>
        <p>
            <a href="news_edit.php">Добавить новость</a>
            &nbsp;|&nbsp; <a href="change_password.php">Сменить пароль</a>
            &nbsp;|&nbsp; <a href="2fa_setup.php">2FA</a>
            &nbsp;|&nbsp; <a href="logout.php">Выйти</a>
        </p>

        <table style="width:100%;border-collapse:collapse">
            <thead>
                <tr>
                    <th style="text-align:left;padding:8px">ID</th>
                    <th style="text-align:left;padding:8px">Заголовок</th>
                    <th style="text-align:left;padding:8px">Дата</th>
                    <th style="text-align:left;padding:8px">Опубликовано</th>
                    <th style="text-align:left;padding:8px">Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($news as $n): ?>
                <tr style="border-top:1px solid #eee">
                    <td style="padding:8px"><?=htmlspecialchars($n['id'])?></td>
                    <td style="padding:8px"><?=htmlspecialchars($n['title'])?></td>
                    <td style="padding:8px"><?=htmlspecialchars($n['created_at'])?></td>
                    <td style="padding:8px"><?= $n['published'] ? 'Да' : 'Нет' ?></td>
                    <td style="padding:8px">
                        <a href="news_edit.php?id=<?= $n['id'] ?>">Редактировать</a> |
                        <form method="post" action="news_delete.php" style="display:inline;margin:0;padding:0">
                            <input type="hidden" name="id" value="<?= $n['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
                            <button type="submit" onclick="return confirm('Удалить новость?')" style="background:none;border:none;color:#06c;cursor:pointer;padding:0">Удалить</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
