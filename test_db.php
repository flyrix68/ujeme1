<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to JSON
header('Content-Type: application/json');

// Function to log messages
function log_message($message) {
    $log_file = __DIR__ . '/logs/test_db.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Log start of test
log_message("=== Starting Database Test ===");

// Log environment variables (without sensitive data)
$env_vars = [
    'DATABASE_URL' => isset($_ENV['DATABASE_URL']) ? 'Set (value hidden for security)' : 'Not set',
    'PATH' => $_ENV['PATH'] ?? 'Not set',
    'HOSTNAME' => $_ENV['HOSTNAME'] ?? 'Not set',
    'PWD' => $_ENV['PWD'] ?? 'Not set'
];

log_message("Environment Variables: " . json_encode($env_vars, JSON_PRETTY_PRINT));

// Test 1: Check if set_env.php exists and is readable
$set_env_path = __DIR__ . '/set_env.php';
log_message("Checking if set_env.php exists at: $set_env_path");

if (!file_exists($set_env_path)) {
    $error = "Error: set_env.php not found at: $set_env_path";
    log_message($error);
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => $error]));
}

if (!is_readable($set_env_path)) {
    $error = "Error: set_env.php is not readable at: $set_env_path";
    log_message($error);
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => $error]));
}

// Include set_env.php
log_message("Including set_env.php");
require $set_env_path;
log_message("Successfully included set_env.php");

// Check if DATABASE_URL is set after including set_env.php
if (empty($_ENV['DATABASE_URL']) && empty(getenv('DATABASE_URL'))) {
    $error = "Error: DATABASE_URL is not set in environment after including set_env.php";
    log_message($error);
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => $error]));
}

log_message("DATABASE_URL is set in environment");

// Test 2: Check if db-config.php exists and is readable
$db_config_path = __DIR__ . '/includes/db-config.php';
log_message("Checking if db-config.php exists at: $db_config_path");

if (!file_exists($db_config_path)) {
    $error = "Error: db-config.php not found at: $db_config_path";
    log_message($error);
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => $error]));
}

if (!is_readable($db_config_path)) {
    $error = "Error: db-config.php is not readable at: $db_config_path";
    log_message($error);
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => $error]));
}

// Include db-config.php
log_message("Including db-config.php");
require $db_config_path;
log_message("Successfully included db-config.php");

// Test 3: Check if DatabaseConfig class exists
if (!class_exists('DatabaseConfig')) {
    $error = "Error: DatabaseConfig class not found after including db-config.php";
    log_message($error);
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => $error]));
}

log_message("DatabaseConfig class found, attempting to get connection...");

// Test 4: Test database connection
try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    log_message("Successfully obtained database connection");
    
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    log_message("Database version: $version");
    
    // Test a simple query
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    log_message("Found " . count($tables) . " tables in the database");
    
    // Success response
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'All tests passed',
        'database' => [
            'status' => 'connected',
            'version' => $version,
            'table_count' => count($tables)
        ],
        'timestamp' => date('c')
    ]);
    
} catch (Exception $e) {
    $error = "Database connection error: " . $e->getMessage();
    log_message($error);
    log_message("Error in " . $e->getFile() . " on line " . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Database test failed',
        'error' => $e->getMessage(),
        'location' => $e->getFile() . ':' . $e->getLine(),
        'timestamp' => date('c')
    ]);
}

log_message("=== End of Database Test ===\n");
?>
