<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/health-check.log');

// Set content type to JSON
header('Content-Type: application/json');

// Function to log detailed messages with timestamps
function health_log($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[HEALTH][$timestamp] " . $message . "\n";
    
    // Log to error log
    error_log($logMessage);
    
    // Also log to a dedicated health check log file
    file_put_contents(__DIR__ . '/../logs/health-check-detailed.log', $logMessage, FILE_APPEND);
}

// Log the start of the health check
health_log("=== Starting Health Check ===");
health_log("PHP Version: " . phpversion());
health_log("Current Directory: " . __DIR__);
health_log("Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set'));
health_log("Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Not set'));

// Log environment variables (without sensitive data)
$envVars = [
    'DATABASE_URL' => isset($_ENV['DATABASE_URL']) ? 'Set (value hidden for security)' : 'Not set',
    'PATH' => $_ENV['PATH'] ?? 'Not set',
    'HOSTNAME' => $_ENV['HOSTNAME'] ?? 'Not set',
    'PWD' => $_ENV['PWD'] ?? 'Not set'
];

health_log("Environment Variables: " . json_encode($envVars, JSON_PRETTY_PRINT));

try {
    // Test 1: Check if set_env.php exists and is readable
    $setEnvPath = __DIR__ . '/../set_env.php';
    health_log("Checking if set_env.php exists at: $setEnvPath");
    
    if (!file_exists($setEnvPath)) {
        throw new Exception("set_env.php not found at: $setEnvPath");
    }
    
    if (!is_readable($setEnvPath)) {
        throw new Exception("set_env.php is not readable at: $setEnvPath");
    }
    
    health_log("Including set_env.php from: $setEnvPath");
    require $setEnvPath;
    health_log("Successfully included set_env.php");
    
    // Check if DATABASE_URL is set after including set_env.php
    if (empty($_ENV['DATABASE_URL']) && empty(getenv('DATABASE_URL'))) {
        throw new Exception("DATABASE_URL is not set in environment after including set_env.php");
    }
    
    health_log("DATABASE_URL is set in environment");
    
    // Test 2: Check if db-config.php exists and is readable
    $dbConfigPath = __DIR__ . '/../includes/db-config.php';
    health_log("Checking if db-config.php exists at: $dbConfigPath");
    
    if (!file_exists($dbConfigPath)) {
        throw new Exception("db-config.php not found at: $dbConfigPath");
    }
    
    if (!is_readable($dbConfigPath)) {
        throw new Exception("db-config.php is not readable at: $dbConfigPath");
    }
    
    health_log("Including db-config.php from: $dbConfigPath");
    require $dbConfigPath;
    health_log("Successfully included db-config.php");
    
    // Test 3: Check if DatabaseConfig class exists
    if (!class_exists('DatabaseConfig')) {
        throw new Exception("DatabaseConfig class not found after including db-config.php");
    }
    
    health_log("DatabaseConfig class found, attempting to get connection...");
    
    // Test 4: Test database connection
    try {
        $pdo = DatabaseConfig::getConnection();
        health_log("Successfully obtained database connection");
        
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        health_log("Database version: $version");
        
        // Test a simple query
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        health_log("Found " . count($tables) . " tables in the database");
        
    } catch (Exception $dbError) {
        health_log("Database connection error: " . $dbError->getMessage());
        health_log("Error in " . $dbError->getFile() . " on line " . $dbError->getLine());
        throw new Exception("Database connection failed: " . $dbError->getMessage());
    }

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
