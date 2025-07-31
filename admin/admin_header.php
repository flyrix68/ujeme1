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

// Database connection with extended timeout and retries
$maxAttempts = 3;
$attempt = 0;

while ($attempt < $maxAttempts) {
    try {
        $pdo = DatabaseSSL::getInstance()->getConnection();
        
        // Set longer timeout for admin operations
        $pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Test connection with a simple query
        $stmt = $pdo->query('SELECT 1');
        if ($isCli) {
            echo "Database connection successful!\n";
        }
        break; // Exit loop on success
        
    } catch (Exception $e) {
        $attempt++;
        error_log("Admin database connection attempt $attempt failed: " . $e->getMessage());
        
        if ($attempt >= $maxAttempts) {
            error_log("Admin database connection failed after $maxAttempts attempts");
            die(($isCli ? "CLI Error: " : "") . "Database connection error. Please contact administrator.");
        }
        
        // Wait before retrying
        sleep(1);
    }
}
?>
