<?php
// Simple database connection test script

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

// Get database configuration
$host = getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net';
$db   = getenv('DB_NAME') ?: 'railway';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
$port = getenv('DB_PORT') ?: '58372';

// Display configuration (masking password)
echo "Testing database connection with:\n";
echo "- Host: $host\n";
echo "- Port: $port\n";
echo "- Database: $db\n";
echo "- User: $user\n";
echo "- Password: " . str_repeat('*', 8) . "\n\n";

// Test connection
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // Try with SSL if certificate exists
    $caCertPath = __DIR__ . '/includes/cacert.pem';
    if (file_exists($caCertPath)) {
        echo "Using SSL certificate: $caCertPath\n";
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caCertPath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    } else {
        echo "No SSL certificate found, connecting without SSL\n";
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    echo "\nConnecting to database...\n";
    $start = microtime(true);
    $pdo = new PDO($dsn, $user, $pass, $options);
    $time = round((microtime(true) - $start) * 1000, 2);
    
    echo "✅ Connected successfully in {$time}ms\n";
    
    // Get server info
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "- MySQL Version: $version\n";
    
    // Check SSL status
    $ssl = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch();
    echo "- SSL: " . ($ssl['Value'] ? 'Enabled (' . $ssl['Value'] . ')' : 'Disabled') . "\n";
    
    // List tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "- Tables found: " . count($tables) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n\n";
    
    // Basic troubleshooting
    echo "Troubleshooting tips:\n";
    echo "1. Verify the database server is running and accessible\n";
    echo "2. Check your database credentials in the .env file\n";
    echo "3. Ensure the database user has proper permissions\n";
    echo "4. Check if the database exists\n";
    echo "5. Verify network connectivity to the database server\n";
    
    if (strpos($e->getMessage(), 'SSL') !== false) {
        echo "\nSSL-related error detected. Try these additional steps:\n";
        echo "1. Check if the SSL certificate exists at: $caCertPath\n";
        echo "2. Try disabling SSL by adding `DB_SSL_VERIFY=false` to your .env file\n";
    }
    
    exit(1);
}
