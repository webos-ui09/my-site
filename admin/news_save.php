<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : null;
$content = isset($_POST['content']) ? trim($_POST['content']) : null;
$published = isset($_POST['published']) ? 1 : 0;
$csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

if (!verify_csrf_token($csrf)) {
    http_response_code(400);
    echo 'Invalid CSRF token';
    exit;
}

if ($id) {
    $stmt = $conn->prepare('UPDATE news SET title=:title, excerpt=:excerpt, content=:content, published=:published WHERE id=:id');
    $stmt->execute([':title'=>$title,':excerpt'=>$excerpt,':content'=>$content,':published'=>$published,':id'=>$id]);
    admin_audit('news_update', 'id=' . $id . ' title=' . $title);
} else {
    $stmt = $conn->prepare('INSERT INTO news (title, excerpt, content, published) VALUES (:title, :excerpt, :content, :published)');
    $stmt->execute([':title'=>$title,':excerpt'=>$excerpt,':content'=>$content,':published'=>$published]);
    $newId = $conn->lastInsertId();
    admin_audit('news_create', 'id=' . $newId . ' title=' . $title);
}

header('Location: news.php');
exit;

?>
