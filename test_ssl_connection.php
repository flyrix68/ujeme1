<?php
// Test script to verify SSL database connection

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables from .env file
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = array_map('trim', explode('=', $line, 2));
            putenv("$name=$value");
        }
    }
}

// Database configuration
$config = [
    'host' => getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net',
    'port' => getenv('DB_PORT') ?: '58372',
    'dbname' => getenv('DB_DATABASE') ?: 'railway',
    'user' => getenv('DB_USERNAME') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'ssl_ca' => getenv('DB_SSL_CA') ?: 'includes/cacert.pem',
    'ssl_verify' => filter_var(getenv('DB_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN) !== false
];

// Log function
function log_message($message) {
    echo "[DEBUG] " . $message . PHP_EOL;
}

// Test connection
log_message("=== Testing Database Connection with SSL ===");
log_message("Host: {$config['host']}:{$config['port']}");
log_message("Database: {$config['dbname']}");
log_message("User: {$config['user']}");
log_message("SSL CA: {$config['ssl_ca']}");
log_message("SSL Verify: " . ($config['ssl_verify'] ? 'true' : 'false'));

try {
    // Build DSN
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
    
    // Set PDO options
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    // Add SSL configuration
    $caCertPath = __DIR__ . '/' . ltrim($config['ssl_ca'], '/');
    log_message("Full CA cert path: " . $caCertPath);
    
    if ($config['ssl_verify'] && file_exists($caCertPath)) {
        log_message("Using SSL with CA certificate");
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caCertPath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    } else {
        if (!file_exists($caCertPath)) {
            log_message("WARNING: CA certificate not found at: " . $caCertPath, 'WARNING');
        }
        log_message("WARNING: Proceeding without SSL verification");
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    // Connect to database
    log_message("Connecting to database...");
    $start = microtime(true);
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    // Check SSL status
    $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
    $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
    
    log_message("✅ Connected successfully in {$time}ms");
    log_message("MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION));
    log_message("SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use'));
    log_message("SSL Version: " . ($sslVersion['Value'] ?: 'Not in use'));
    
    // List tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    log_message("\nTables in database (" . count($tables) . "):");
    foreach ($tables as $table) {
        log_message("- $table");
    }
    
} catch (PDOException $e) {
    log_message("❌ Connection failed: " . $e->getMessage());
    log_message("Error code: " . $e->getCode());
    
    // Common error codes and solutions
    $solutions = [
        '2002' => "Cannot connect to database server. Check if MySQL is running and the host/port are correct.",
        '1045' => "Access denied. Verify username and password.",
        '1044' => "Access denied for user to database. Check database permissions.",
        '1049' => "Database does not exist. Check database name.",
        '2006' => "MySQL server has gone away. The server might have crashed or been restarted.",
        '2013' => "Lost connection to MySQL server. Check network connectivity.",
        '2026' => "SSL connection error. Check SSL configuration and certificate.",
        'default' => "Check your database configuration and server status."
    ];
    
    $errorCode = (string)$e->getCode();
    $solution = $solutions[$errorCode] ?? $solutions['default'];
    log_message("💡 Suggestion: $solution");
}
