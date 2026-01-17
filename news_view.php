<?php
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: index.html');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM news WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$news = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$news) {
    header('Location: index.html');
    exit;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($news['title']) ?></title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <div class="container" style="max-width:800px;margin:30px auto;">
        <h1><?= htmlspecialchars($news['title']) ?></h1>
        <div style="color:#888;margin-bottom:20px">Опубликовано: <?= htmlspecialchars($news['created_at']) ?></div>
        <div style="font-size:16px;line-height:1.6;">
            <?= nl2br(htmlspecialchars($news['content'])) ?>
        </div>

        <p style="margin-top:30px"><a href="index.html">← Вернуться на сайт</a></p>
    </div>
</body>
</html>
