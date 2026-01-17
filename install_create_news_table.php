<?php
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    $sql = "CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        excerpt VARCHAR(512) DEFAULT NULL,
        content TEXT,
        published TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

    $conn->exec($sql);
    echo "OK: news table created or already exists.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}

?>
