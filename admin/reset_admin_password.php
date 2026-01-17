<?php
/**
 * Reset admin password helper.
 * Usage:
 * 1. Edit $username and $newPassword below.
 * 2. Run in browser: /admin/reset_admin_password.php
 * 3. Delete this file after use.
 */
require_once __DIR__ . '/../config/database.php';

$username = 'devtool';
$newPassword = 'cuwtot-xubzan-rysvi1';

try {
    $db = new Database();
    $conn = $db->getConnection();

    $hash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare('SELECT id FROM admin_users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    if ($row = $stmt->fetch()) {
        $upd = $conn->prepare('UPDATE admin_users SET password_hash = :p WHERE username = :u');
        $upd->execute([':p' => $hash, ':u' => $username]);
        echo "Password for user '$username' updated.\n";
    } else {
        echo "User '$username' not found. You can create it with create_admin.php.\n";
    }
    echo "Done. Remove this file for security.\n";
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
}
