<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if running via CLI
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    // Only set session settings if running via web
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); 
    ini_set('session.use_strict_mode', 1);
    session_set_cookie_params([
        'lifetime' => 3600,
        'path' => '/',
        'domain' => $_SERVER['HTTP_HOST'],
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();

    // Verify admin authentication for web requests
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        error_log("Unauthorized web access attempt from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        header('Location: ../index.php');
        exit();
    }
}

// Database connection with absolute path
$dbConfigPath = __DIR__ . '/../includes/db-config.php';
if (!file_exists($dbConfigPath)) {
    error_log("Database config not found at: $dbConfigPath");
    die("Database configuration error. Please contact administrator.");
}

require $dbConfigPath;

try {
    $pdo = DatabaseConfig::getConnection();
    // Test connection
    $stmt = $pdo->query('SELECT 1');
    if ($isCli) {
        echo "Database connection successful!\n";
    }
} catch (Exception $e) {
    error_log("Admin database connection failed: " . $e->getMessage());
    die(($isCli ? "CLI Error: " : "") . "Database connection error. Please try again later.");
}
?>
