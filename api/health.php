<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/health-check.log');
header('Content-Type: text/plain');

function health_log($message) {
    error_log("[HEALTH] " . $message);
    echo "[LOG] $message\n";
}

try {
    health_log("Starting health check");
    
    // Test 1: Basic PHP execution
    echo "[OK] PHP is executing\n";
    health_log("PHP execution verified");

    // Test 2: Required PHP extensions
    $requiredExts = ['pdo', 'pdo_mysql'];
    foreach ($requiredExts as $ext) {
        if (!extension_loaded($ext)) {
            throw new Exception("Missing required extension: $ext");
        }
    }
    echo "[OK] Required extensions loaded\n";
    health_log("Extensions verified");

    // Test 3: Database connection
    health_log("Loading db-config.php");
    require __DIR__ . '/../includes/db-config.php';
    
    health_log("Testing database connection");
    $pdo = DatabaseConfig::getConnection();
    if ($pdo->query('SELECT 1')->fetchColumn() != 1) {
        throw new Exception("Database connection test failed");
    }
    echo "[OK] Database connection successful\n";
    health_log("Database connection verified");

    // Success
    http_response_code(200);
    echo "\n[SUCCESS] All health checks passed\n";
    health_log("All checks passed");

} catch (Exception $e) {
    http_response_code(500);
    health_log("ERROR: " . $e->getMessage());
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo "Location: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
