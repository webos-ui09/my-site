<?php
require_once __DIR__ . '/inc_auth.php';
// record logout
admin_audit('logout', null);
admin_logout();
header('Location: login.php');
exit;
?>
