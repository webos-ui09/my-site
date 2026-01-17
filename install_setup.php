<?php
/**
 * install_setup.php
 * Creates `news` table (if missing) and `admin_users` table and inserts initial admin
 * Uses credentials from `admin/inc_auth.php` if present.
 */
require_once __DIR__ . '/config/database.php';

$db = new Database();
$conn = $db->getConnection();
// Include admin defaults before any output to avoid session_start() after headers sent warnings
if (file_exists(__DIR__ . '/admin/inc_auth.php')) {
    require_once __DIR__ . '/admin/inc_auth.php';
}

try {
    $sqlNews = "CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        excerpt VARCHAR(512) DEFAULT NULL,
        content TEXT,
        published TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sqlNews);
    echo "OK: news table created or already exists.\n";

    $sqlAdmin = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        twofa_enabled TINYINT(1) DEFAULT 0,
        twofa_secret VARCHAR(128) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sqlAdmin);
    echo "OK: admin_users table created or already exists.\n";

    // Audit table
    $sqlAudit = "CREATE TABLE IF NOT EXISTS admin_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) DEFAULT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT DEFAULT NULL,
        ip VARCHAR(45) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
    $conn->exec($sqlAudit);
    echo "OK: admin_audit table created or already exists.\n";

    // Seed initial admin from admin/inc_auth.php constants if not present
    $adminUser = null;
    $adminPass = null;
    if (file_exists(__DIR__ . '/admin/inc_auth.php')) {
        // include to get ADMIN_USER / ADMIN_PASS defaults if present
        require_once __DIR__ . '/admin/inc_auth.php';
        if (defined('ADMIN_USER') && defined('ADMIN_PASS')) {
            $adminUser = ADMIN_USER;
            $adminPass = ADMIN_PASS;
        }
    }

    // Fallback to sensible default seed if none provided
    if (!$adminUser) {
        $adminUser = 'admin';
        $adminPass = 'admin';
    }

    if ($adminUser) {
        $stmt = $conn->prepare('SELECT id FROM admin_users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $adminUser]);
        $exists = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$exists) {
            $hash = password_hash($adminPass, PASSWORD_DEFAULT);
            $ins = $conn->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:u, :h)');
            $ins->execute([':u' => $adminUser, ':h' => $hash]);
            echo "OK: seeded admin user '{$adminUser}'.\n";
        } else {
            echo "Info: admin user '{$adminUser}' already exists.\n";
        }
    } else {
        echo "Info: admin defaults not found; no admin user seeded.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

?>
