<?php
// Set maximum error reporting
error_reporting(-1);
ini_set('display_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/logs/db_test.log');

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0777, true);
}

// Set a custom error handler to log all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $message = sprintf(
        '[%s] PHP %s: %s in %s on line %d',
        date('Y-m-d H:i:s'),
        $errno,
        $errstr,
        $errfile,
        $errline
    );
    error_log($message);
    file_put_contents(__DIR__ . '/logs/db_test.log', $message . PHP_EOL, FILE_APPEND);
    return true; // Don't execute PHP internal error handler
});

// Set exception handler
set_exception_handler(function($e) {
    $message = sprintf(
        '[%s] Uncaught Exception: %s in %s on line %d\nStack trace:\n%s',
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    error_log($message);
    file_put_contents(__DIR__ . '/logs/db_test.log', $message . PHP_EOL, FILE_APPEND);
    
    // Send a proper error response
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Uncaught Exception',
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], JSON_PRETTY_PRINT);
    }
    exit(1);
});

// Function to read .env file manually
function loadEnv($filePath) {
    if (!file_exists($filePath)) {
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return false;
    }
    
    $env = [];
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse the line
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($key !== '') {
                $env[$key] = $value;
            }
        }
    }
    
    return $env;
}

// Function to output a section header for better readability
function outputSection($title) {
    echo "\n\n" . str_repeat('=', 80) . "\n";
    echo strtoupper($title) . "\n";
    echo str_repeat('=', 80) . "\n\n";
}

// Output system information
outputSection('System Information');
echo "PHP Version: " . phpversion() . "\n";
echo "OS: " . php_uname() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'CLI') . "\n";

// Check for required extensions
outputSection('Required Extensions');
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'openssl'];
$missingExtensions = [];
foreach ($requiredExtensions as $ext) {
    $loaded = extension_loaded($ext) ? '✓' : '✗';
    echo "[$loaded] $ext\n";
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    die("\nERROR: The following required PHP extensions are missing: " . implode(', ', $missingExtensions) . "\n");
}

// Load environment variables
outputSection('Environment Variables');
$env = loadEnv(__DIR__ . '/.env');
if ($env === false) {
    die("ERROR: Failed to load .env file\n");
}

// Set environment variables
foreach ($env as $key => $value) {
    // Skip sensitive data in output
    $displayValue = (stripos($key, 'PASSWORD') !== false || stripos($key, 'SECRET') !== false) 
        ? str_repeat('*', min(8, strlen($value)))
        : $value;
    
    echo "$key=$displayValue\n";
    
    putenv("$key=$value");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

// Check for required environment variables
outputSection('Required Environment Variables');
$requiredVars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'];
$missingVars = [];
foreach ($requiredVars as $var) {
    $value = getenv($var);
    $status = $value !== false ? '✓' : '✗';
    echo "[$status] $var=" . ($value !== false ? (str_contains($var, 'PASSWORD') ? str_repeat('*', 8) : $value) : 'NOT SET') . "\n";
    
    if ($value === false) {
        $missingVars[] = $var;
    }
}

if (!empty($missingVars)) {
    die("\nERROR: The following required environment variables are missing: " . implode(', ', $missingVars) . "\n");
}

// Test database connection
outputSection('Database Connection Test');

try {
    // Get database configuration
    $dbHost = getenv('DB_HOST');
    $dbName = getenv('DB_NAME');
    $dbUser = getenv('DB_USER');
    $dbPass = getenv('DB_PASSWORD');
    $dbPort = getenv('DB_PORT') ?: '3306';
    
    echo "Attempting to connect to database...\n";
    echo "Host: $dbHost\n";
    echo "Database: $dbName\n";
    echo "User: $dbUser\n";
    echo "Port: $dbPort\n\n";
    
    // Test basic connection
    echo "Testing basic connection...\n";
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ];
    
    // Test with SSL if cacert.pem exists
    $caCertPath = __DIR__ . '/includes/cacert.pem';
    if (file_exists($caCertPath)) {
        echo "Found CA certificate: $caCertPath\n";
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caCertPath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
    } else {
        echo "CA certificate not found at $caCertPath, connecting without SSL verification\n";
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    // Attempt connection
    $startTime = microtime(true);
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "✅ Successfully connected to database in {$connectionTime}ms\n";
    
    // Get database version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "Database Version: $version\n";
    
    // Test a simple query
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "\nFound " . count($tables) . " tables in the database\n";
    
    // Test SSL status
    $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
    echo "\nSSL Status:\n";
    echo "Cipher in use: " . ($sslStatus['Value'] ?: 'Not using SSL') . "\n";
    
    // Show connection variables
    $vars = $pdo->query('SHOW VARIABLES LIKE "%version%";')->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "\nDatabase Server Version: " . ($vars['version'] ?? 'N/A') . "\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    
    // Provide troubleshooting tips based on error code
    switch ($e->getCode()) {
        case 2002:
            echo "\nTROUBLESHOOTING: Cannot connect to the database server.\n";
            echo "- Check if the database server is running\n";
            echo "- Verify the hostname ($dbHost) and port ($dbPort) are correct\n";
            echo "- Check your firewall settings\n";
            break;
            
        case 1045:
            echo "\nTROUBLESHOOTING: Access denied for user.\n";
            echo "- Verify the username and password are correct\n";
            echo "- Check if the user has proper permissions\n";
            echo "- Make sure the user is allowed to connect from this host\n";
            break;
            
        case 1049:
            echo "\nTROUBLESHOOTING: Database does not exist.\n";
            echo "- Verify the database name ($dbName) is correct\n";
            echo "- Check if the database exists on the server\n";
            echo "- Make sure the user has access to this database\n";
            break;
            
        default:
            echo "\nTROUBLESHOOTING: General database error.\n";
            echo "- Check your database server logs for more details\n";
            echo "- Verify all connection parameters are correct\n";
            echo "- Try connecting with a database management tool to verify credentials\n";
    }
    
    exit(1);
}

echo "\n✅ All tests completed successfully!\n";

// Get database URL
$dbUrl = getenv('DATABASE_URL');
if ($dbUrl === false) {
    die("DATABASE_URL not found in environment\n");
}

echo "Testing database connection with URL: $dbUrl\n";

// Parse database URL
$dbParts = parse_url($dbUrl);
if ($dbParts === false) {
    die("Failed to parse DATABASE_URL\n");
}

$dbHost = $dbParts['host'] ?? 'localhost';
$dbPort = $dbParts['port'] ?? 3306;
$dbUser = $dbParts['user'] ?? 'root';
$dbPass = $dbParts['pass'] ?? '';
$dbName = ltrim($dbParts['path'] ?? '', '/');

// Test connection without SSL first
try {
    echo "\nTrying connection without SSL...\n";
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
    ];
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "✓ Successfully connected to database without SSL\n";
    
    // Test query
    $stmt = $pdo->query('SELECT VERSION() as version');
    $version = $stmt->fetchColumn();
    echo "Database version: $version\n";
    
} catch (PDOException $e) {
    echo "✗ Connection without SSL failed: " . $e->getMessage() . "\n";
    
    // Try with SSL
    try {
        echo "\nTrying connection with SSL...\n";
        $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/cacert.pem';
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        echo "✓ Successfully connected to database with SSL\n";
        
        // Test query
        $stmt = $pdo->query('SELECT VERSION() as version');
        $version = $stmt->fetchColumn();
        echo "Database version: $version\n";
        
    } catch (PDOException $e) {
        die("✗ Connection with SSL also failed: " . $e->getMessage() . "\n");
    }
}

echo "\nConnection test completed successfully!\n";
?>
