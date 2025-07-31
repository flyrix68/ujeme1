<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up a dedicated log file for this test
$logFile = __DIR__ . '/test_health.log';
file_put_contents($logFile, "=== Starting Test Health Check ===\n");

function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

log_message("Starting test_health.php");

// Log environment variables before including set_env.php
log_message("Environment variables before set_env.php:");
log_message("getenv('DATABASE_URL'): " . (getenv('DATABASE_URL') ? 'SET' : 'NOT SET'));
log_message("_ENV['DATABASE_URL']: " . (isset($_ENV['DATABASE_URL']) ? 'SET' : 'NOT SET'));
log_message("_SERVER['DATABASE_URL']: " . (isset($_SERVER['DATABASE_URL']) ? 'SET' : 'NOT SET'));

// Include set_env.php
log_message("Including set_env.php");
require __DIR__ . '/set_env.php';

// Log environment variables after including set_env.php
log_message("\nEnvironment variables after set_env.php:");
log_message("getenv('DATABASE_URL'): " . (getenv('DATABASE_URL') ? 'SET' : 'NOT SET'));
log_message("_ENV['DATABASE_URL']: " . (isset($_ENV['DATABASE_URL']) ? 'SET' : 'NOT SET'));
log_message("_SERVER['DATABASE_URL']: " . (isset($_SERVER['DATABASE_URL']) ? 'SET' : 'NOT SET'));

// Try to include db-config.php
log_message("\nIncluding db-config.php");
require __DIR__ . '/includes/db-config.php';

try {
    log_message("Attempting to get database connection");
    $pdo = DatabaseConfig::getConnection();
    log_message("✅ Successfully connected to database!");
    
    // Test a simple query
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    log_message("Database version: $version");
    
} catch (Exception $e) {
    log_message("❌ Database connection failed: " . $e->getMessage());
    log_message("Error in " . $e->getFile() . " on line " . $e->getLine());
}

log_message("Test completed\n");
?>
