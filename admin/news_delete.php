<?php
require_once __DIR__ . '/inc_auth.php';
require_admin();
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

if (!verify_csrf_token($csrf)) {
    http_response_code(400);
    echo 'Invalid CSRF token';
    exit;
}

if ($id) {
    $stmt = $conn->prepare('DELETE FROM news WHERE id = :id');
    $stmt->execute([':id' => $id]);
    admin_audit('news_delete', 'id=' . $id);
}

header('Location: news.php');
exit;

?>
