&lt;?php
require 'includes/db-config.php';
session_start();
$_SESSION['user_id'] = 9999;
$_SESSION['user_prenom'] = 'Admin';
$_SESSION['user_nom'] = 'Test';
$_SESSION['user_email'] = 'admin@ujem.com';
$_SESSION['role'] = 'admin';
header('Location: admin/dashboard.php');
