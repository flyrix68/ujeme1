<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/health-check.log');

// Set content type to JSON
header('Content-Type: application/json');

function health_log($message) {
    error_log("[HEALTH] " . $message);
}

try {
    health_log("Starting health check");
    
    // Test 1: Basic PHP execution
    health_log("PHP execution verified");

    // Test 2: Required PHP extensions
    $requiredExts = ['pdo', 'pdo_mysql'];
    foreach ($requiredExts as $ext) {
        if (!extension_loaded($ext)) {
            throw new Exception("Missing required extension: $ext");
        }
    }
    health_log("Extensions verified");

    // Test 3: Database connection
    health_log("Including set_env.php");
    require __DIR__ . '/../set_env.php';
    
    health_log("Loading db-config.php");
    require __DIR__ . '/../includes/db-config.php';
    
    health_log("Testing database connection");
    $pdo = DatabaseConfig::getConnection();
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    health_log("Database version: $version");

    // Success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'All health checks passed',
        'database' => [
            'status' => 'connected',
            'version' => $version
        ],
        'timestamp' => date('c')
    ]);
    health_log("All checks passed");

} catch (Exception $e) {
    http_response_code(500);
    $errorMessage = $e->getMessage();
    health_log("ERROR: " . $errorMessage);
    
    echo json_encode([
        'status' => 'error',
        'message' => 'Health check failed',
        'error' => $errorMessage,
        'location' => $e->getFile() . ':' . $e->getLine(),
        'timestamp' => date('c')
    ]);
}
?>
