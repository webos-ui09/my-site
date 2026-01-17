<?php
/**
 * Debug helper: list admin users in DB.
 * Usage: open in browser while on local machine, then delete the file.
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');
try{
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->query('SELECT id, username, password_hash, twofa_enabled, created_at FROM admin_users');
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(empty($rows)){
        echo "No admin users found.\n";
    } else {
        foreach($rows as $r){
            echo "ID: " . ($r['id'] ?? '') . "\n";
            echo "Username: " . ($r['username'] ?? '') . "\n";
            echo "Password hash: " . ($r['password_hash'] ?? '') . "\n";
            echo "2FA: " . ($r['twofa_enabled'] ? 'yes' : 'no') . "\n";
            echo "Created: " . ($r['created_at'] ?? '') . "\n";
            echo "-------------------------\n";
        }
    }
}catch(Throwable $e){
    echo "Error connecting to DB: " . $e->getMessage() . "\n";
}

echo "\nAfter checking, DELETE this file (admin/list_admins.php) to avoid leaking credentials.\n";
