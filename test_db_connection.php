<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/db_test.log');

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

// Load environment variables
$env = loadEnv(__DIR__ . '/.env');
if ($env === false) {
    die("Failed to load .env file\n");
}

// Set environment variables
foreach ($env as $key => $value) {
    putenv("$key=$value");
    $_ENV[$key] = $value;
}

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
