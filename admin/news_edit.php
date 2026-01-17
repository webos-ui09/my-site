<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$title = '';
$excerpt = '';
$content = '';
$published = 1;

if ($id) {
    $stmt = $conn->prepare('SELECT * FROM news WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $title = $row['title'];
        $excerpt = $row['excerpt'];
        $content = $row['content'];
        $published = $row['published'];
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= $id ? 'Редактировать' : 'Добавить' ?> новость</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="container" style="max-width:800px;margin:30px auto;">
        <h1><?= $id ? 'Редактировать' : 'Добавить' ?> новость</h1>
        <form method="post" action="news_save.php">
            <input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">
            <div style="margin-bottom:10px">
                <label>Заголовок<br>
                    <input type="text" name="title" value="<?= htmlspecialchars($title) ?>" style="width:100%;padding:8px">
                </label>
            </div>
            <div style="margin-bottom:10px">
                <label>Краткое описание (excerpt)<br>
                    <textarea name="excerpt" style="width:100%;padding:8px" rows="3"><?= htmlspecialchars($excerpt) ?></textarea>
                </label>
            </div>
            <div style="margin-bottom:10px">
                <label>Контент<br>
                    <textarea name="content" style="width:100%;padding:8px" rows="8"><?= htmlspecialchars($content) ?></textarea>
                </label>
            </div>
            <div style="margin-bottom:10px">
                <label><input type="checkbox" name="published" value="1" <?= $published ? 'checked' : '' ?>> Опубликовано</label>
            </div>
            <div>
                <button type="submit" style="padding:10px 16px">Сохранить</button>
                <a href="news.php" style="margin-left:10px">Отмена</a>
            </div>
        </form>
    </div>
</body>
</html>
