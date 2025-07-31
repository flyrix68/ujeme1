<?php
// Enable error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/health-check.log');

// Set content type to JSON
header('Content-Type: application/json');

// Function to send JSON response and exit
function send_json_response($status, $message, $data = []) {
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    // Add debug info if not in production
    if (getenv('APP_ENV') !== 'production') {
        $response['debug'] = [
            'php_version' => phpversion(),
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Not set',
            'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'Not set',
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'Not set',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Not set',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'Not set'
        ];
    }
    
    http_response_code($status === 'success' ? 200 : 500);
    $options = 0;
    // Add pretty print if available (PHP 5.4+)
    if (defined('JSON_PRETTY_PRINT')) {
        $options |= JSON_PRETTY_PRINT;
    }
    // Add unescaped slashes if available (PHP 5.4+)
    if (defined('JSON_UNESCAPED_SLASHES')) {
        $options |= JSON_UNESCAPED_SLASHES;
    }
    // Add unescaped unicode if available (PHP 5.4+)
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $options |= JSON_UNESCAPED_UNICODE;
    }
    echo json_encode($response, $options);
    exit;
}

// Function to log detailed messages with timestamps
function health_log($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[HEALTH][$timestamp][$level] $message\n";
    
    // Log to error log
    error_log($logMessage);
    
    // Also log to a dedicated health check log file
    @file_put_contents(__DIR__ . '/../logs/health-check-detailed.log', $logMessage, FILE_APPEND);
    
    // If it's an error, also log to the main error log
    if ($level === 'error') {
        @file_put_contents(__DIR__ . '/../logs/error.log', $logMessage, FILE_APPEND);
    }
}

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    @mkdir(__DIR__ . '/../logs', 0777, true);
    @chmod(__DIR__ . '/../logs', 0777);
}

// Log the start of the health check
health_log("=== Starting Health Check ===");

// Log basic server information
health_log("PHP Version: " . phpversion());
health_log("Current Directory: " . __DIR__);
health_log("Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set'));
health_log("Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Not set'));

// Log environment variables (without sensitive data)
$envVars = [
    'DATABASE_URL' => isset($_ENV['DATABASE_URL']) ? 'Set (value hidden for security)' : 'Not set',
    'APP_ENV' => getenv('APP_ENV') ?: 'Not set',
    'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT') ?: 'Not set',
    'PATH' => getenv('PATH') ?: 'Not set',
    'HOSTNAME' => getenv('HOSTNAME') ?: 'Not set',
    'PWD' => getenv('PWD') ?: 'Not set'
];

health_log("Environment Variables: " . json_encode($envVars, JSON_PRETTY_PRINT));

// Function to check if a file exists and is readable
function check_file($path, $description) {
    $result = [
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'path' => $path
    ];
    
    if (!$result['exists']) {
        throw new Exception("$description not found at: $path");
    }
    
    if (!$result['readable']) {
        throw new Exception("$description is not readable at: $path");
    }
    
    health_log("$description found and readable at: $path");
    return $result;
}

try {
    // Test 1: Check if set_env.php exists and is readable
    $setEnvPath = __DIR__ . '/../set_env.php';
    $setEnvInfo = check_file($setEnvPath, 'set_env.php');
    
    // Include set_env.php
    health_log("Including set_env.php from: $setEnvPath");
    require $setEnvPath;
    health_log("Successfully included set_env.php");
    
    // Check if DATABASE_URL is set after including set_env.php
    $dbUrl = getenv('DATABASE_URL') ?: ($_ENV['DATABASE_URL'] ?? null);
    if (empty($dbUrl)) {
        throw new Exception("DATABASE_URL is not set in environment after including set_env.php");
    }
    
    // Sanitize the database URL for logging
    $sanitizedDbUrl = preg_replace('/(?<=:)[^:@]+(?=@)/', '*****', $dbUrl);
    health_log("DATABASE_URL is set in environment: $sanitizedDbUrl");
    
    // Test 2: Check if db-config.php exists and is readable
    $dbConfigPath = __DIR__ . '/../includes/db-config.php';
    $dbConfigInfo = check_file($dbConfigPath, 'db-config.php');
    
    // Include db-config.php
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
        $tableCount = count($tables);
        health_log("Found $tableCount tables in the database");
        
        // Success response
        send_json_response('success', 'All health checks passed', [
            'database' => [
                'status' => 'connected',
                'version' => $version,
                'table_count' => $tableCount
            ]
        ]);
        
    } catch (Exception $dbError) {
        $errorMsg = "Database connection error: " . $dbError->getMessage();
        health_log($errorMsg, 'error');
        health_log("Error in " . $dbError->getFile() . " on line " . $dbError->getLine(), 'error');
        
        // Get more detailed error information
        $errorInfo = [];
        if (isset($pdo) && $pdo instanceof PDO) {
            $errorInfo = $pdo->errorInfo();
        }
        
        throw new Exception("$errorMsg. PDO Error: " . json_encode($errorInfo));
    }

} catch (Exception $e) {
    $errorData = [
        'error' => $e->getMessage(),
        'location' => $e->getFile() . ':' . $e->getLine(),
        'trace' => explode("\n", $e->getTraceAsString())
    ];
    
    // Add environment information
    $errorData['environment'] = [
        'php_version' => phpversion(),
        'extensions' => get_loaded_extensions(),
        'include_path' => get_include_path(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'error_log' => ini_get('error_log')
    ];
    
    // Send error response
    send_json_response('error', 'Health check failed: ' . $e->getMessage(), $errorData);
}
?>
