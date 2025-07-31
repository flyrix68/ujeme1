<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up logging
$logFile = __DIR__ . '/direct_db_test.log';
file_put_contents($logFile, "=== Direct Database Connection Test ===\n");

function log_message($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    echo $logMessage;
}

log_message("Starting direct database connection test");

// 1. Load environment variables from .env file
log_message("1. Loading .env file");
$envFile = __DIR__ . '/.env';
if (!file_exists($envFile)) {
    die("Error: .env file not found at $envFile");
}

// Parse .env file
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$envVars = [];
foreach ($lines as $line) {
    // Skip comments
    if (strpos(trim($line), '#') === 0) {
        continue;
    }
    
    // Parse name=value pairs
    if (strpos($line, '=') !== false) {
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, "'\" ");
        $envVars[$name] = $value;
    }
}

// 2. Extract database connection details
$dbUrl = $envVars['DATABASE_URL'] ?? '';
log_message("2. DATABASE_URL from .env: " . (!empty($dbUrl) ? 'FOUND' : 'NOT FOUND'));

if (empty($dbUrl)) {
    die("Error: DATABASE_URL not found in .env file");
}

// Parse the database URL
// Format: mysql://username:password@hostname:port/database
$dbParts = parse_url($dbUrl);

if (!$dbParts) {
    die("Error: Invalid DATABASE_URL format in .env file");
}

$dbConfig = [
    'driver'   => $dbParts['scheme'] ?? 'mysql',
    'host'     => $dbParts['host'] ?? 'localhost',
    'port'     => $dbParts['port'] ?? '3306',
    'database' => ltrim($dbParts['path'] ?? '', '/'),
    'username' => $dbParts['user'] ?? '',
    'password' => $dbParts['pass'] ?? '',
];

log_message("3. Database connection details:");
log_message("   - Driver: " . $dbConfig['driver']);
log_message("   - Host: " . $dbConfig['host']);
log_message("   - Port: " . $dbConfig['port']);
log_message("   - Database: " . $dbConfig['database']);
log_message("   - Username: " . $dbConfig['username']);
log_message("   - Password: " . (!empty($dbConfig['password']) ? '***' : 'empty'));

// 3. Test database connection
try {
    log_message("4. Attempting to connect to database...");
    
    $dsn = sprintf(
        "%s:host=%s;port=%d;dbname=%s;charset=utf8mb4",
        $dbConfig['driver'],
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Add SSL options if certificate exists
    $certFile = __DIR__ . '/cacert.pem';
    if (file_exists($certFile)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = $certFile;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        log_message("   - Using SSL certificate: $certFile");
    } else {
        log_message("   - No SSL certificate found, connecting without SSL");
    }
    
    // Connect to the database
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $options);
    
    // Test the connection
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    log_message("   ✅ Successfully connected to database!");
    log_message("   - Database version: $version");
    
    // Test a simple query
    $tableCheck = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    log_message("   - Found " . count($tableCheck) . " tables in the database");
    
} catch (PDOException $e) {
    log_message("   ❌ Database connection failed: " . $e->getMessage());
    log_message("   Error in " . $e->getFile() . " on line " . $e->getLine());
}

log_message("Test completed\n");
?>
