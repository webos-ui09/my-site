<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("SELECT id, title, excerpt, content, created_at FROM news WHERE published=1 ORDER BY created_at DESC LIMIT 10");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

?>
