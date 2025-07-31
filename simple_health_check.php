<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up a dedicated log file for this test
$logFile = __DIR__ . '/simple_health_check.log';
file_put_contents($logFile, "=== Starting Simple Health Check ===\n");

function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

log_message("Starting simple_health_check.php");

// 1. Test basic PHP execution
log_message("1. PHP is executing");

// 2. Test including set_env.php
log_message("2. Including set_env.php");
require __DIR__ . '/set_env.php';

// 3. Check if DATABASE_URL is set in various ways
log_message("\n3. Checking DATABASE_URL in different contexts:");
log_message("   getenv('DATABASE_URL'): " . (getenv('DATABASE_URL') ? 'SET' : 'NOT SET'));
log_message("   _ENV['DATABASE_URL']: " . (isset($_ENV['DATABASE_URL']) ? 'SET' : 'NOT SET'));
log_message("   _SERVER['DATABASE_URL']: " . (isset($_SERVER['DATABASE_URL']) ? 'SET' : 'NOT SET'));

// 4. Try to include db-config.php
log_message("\n4. Including db-config.php");
require __DIR__ . '/includes/db-config.php';

// 5. Test database connection
try {
    log_message("5. Attempting to get database connection");
    $pdo = DatabaseSSL::getInstance()->getConnection();
    log_message("   ✅ Successfully connected to database!");
    
    // Test a simple query
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    log_message("   Database version: $version");
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Health check passed',
        'database' => 'connected',
        'version' => $version
    ]);
    
} catch (Exception $e) {
    log_message("   ❌ Database connection failed: " . $e->getMessage());
    log_message("   Error in " . $e->getFile() . " on line " . $e->getLine());
    
    // Return error response
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Health check failed',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

log_message("\nTest completed\n");
?>
