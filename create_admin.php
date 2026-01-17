<?php
/**
 * Simple admin-creation script.
 * Usage:
 * 1. Edit $username and $password below.
 * 2. Run in browser: http://localhost/site/create_admin.php
 * 3. Remove this file after use for security.
 */
require_once __DIR__ . '/config/database.php';

$username = 'devtool';
$password = 'cuwtot-xubzan-rysvi1';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // ensure table exists
    $conn->exec("CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        twofa_enabled TINYINT(1) DEFAULT 0,
        twofa_secret VARCHAR(64) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare('SELECT id FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    if ($stmt->fetch()) {
        echo "User '$username' already exists.\n";
    } else {
        $ins = $conn->prepare('INSERT INTO admin_users (username,password_hash) VALUES (:u,:p)');
        $ins->execute([':u' => $username, ':p' => $hash]);
        echo "Created admin user: $username\n";
    }

    echo "Done. Please delete create_admin.php for security.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
