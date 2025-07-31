<?php
/**
 * Database Connection Test Script
 * 
 * This script tests the database connection using direct PDO with detailed error reporting.
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/db_test_errors.log');

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

// Database configuration
$config = [
    'host' => 'yamanote.proxy.rlwy.net',
    'port' => '58372',
    'dbname' => 'railway',
    'user' => 'root',
    'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'charset' => 'utf8mb4',
    'ssl_ca' => __DIR__ . '/includes/cacert.pem'
];

// Function to test connection
function testConnection($config) {
    echo "Testing connection to: {$config['user']}@{$config['host']}:{$config['port']}/{$config['dbname']}\n";
    
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // Check if SSL certificate exists
    if (file_exists($config['ssl_ca'])) {
        echo "Using SSL certificate: {$config['ssl_ca']}\n";
        $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    } else {
        echo "No SSL certificate found, connecting without SSL\n";
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    try {
        $start = microtime(true);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        echo "✅ Successfully connected in {$time}ms\n";
        
        // Get server info
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "MySQL Version: $version\n";
        
        // Check SSL status
        $ssl = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch();
        echo "SSL: " . ($ssl['Value'] ? 'Enabled (' . $ssl['Value'] . ')' : 'Disabled') . "\n";
        
        // List databases
        $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        echo "Available databases: " . implode(', ', $databases) . "\n";
        
        // List tables
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables in {$config['dbname']}: " . count($tables) . "\n";
        if ($tables) {
            foreach ($tables as $table) {
                echo "- $table\n";
            }
        }
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n\n";
        
        // Detailed error information
        $errorInfo = $e->errorInfo ?? [];
        if (!empty($errorInfo)) {
            echo "SQLSTATE: {$errorInfo[0]}\n";
            echo "Driver Code: {$errorInfo[1]}\n";
            echo "Error Message: {$errorInfo[2]}\n\n";
        }
        
        // Common error codes and solutions
        $solutions = [
            '2002' => "Cannot connect to database server. Check if MySQL is running and the host/port are correct.",
            '1045' => "Access denied. Verify username and password.",
            '1044' => "Access denied for user to database. Check database permissions.",
            '1049' => "Database does not exist. Check database name.",
            '2006' => "MySQL server has gone away. The server might have crashed or been restarted.",
            '2013' => "Lost connection to MySQL server. Check network connectivity.",
            'default' => "Check your database configuration and server status."
        ];
        
        $errorCode = (string)$e->getCode();
        $solution = $solutions[$errorCode] ?? $solutions['default'];
        echo "💡 Suggestion: $solution\n";
        
        return false;
    }
}

// Run the test
echo "=== Database Connection Test ===\n\n";
$result = testConnection($config);

echo "\n=== Test Complete ===\n";
echo $result ? "✅ SUCCESS" : "❌ FAILED";
echo "\n\n";

// Additional diagnostic information
echo "\n=== System Information ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "OpenSSL: " . (extension_loaded('openssl') ? 'Enabled' : 'Disabled') . "\n";

// Check if we can connect to the host/port
$host = $config['host'];
$port = $config['port'];
$timeout = 5; // seconds

echo "\n=== Network Connectivity Test ===\n";
echo "Testing connection to $host:$port...\n";

$connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
if (is_resource($connection)) {
    echo "✅ Successfully connected to $host:$port\n";
    fclose($connection);
} else {
    echo "❌ Could not connect to $host:$port\n";
    echo "Error ($errno): $errstr\n";
    echo "\nTroubleshooting steps:\n";
    echo "1. Check if the database server is running\n";
    echo "2. Verify the hostname and port are correct\n";
    echo "3. Check your firewall settings\n";
    echo "4. Try pinging the server: ping $host\n";
}

// Check if we can resolve the hostname
$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "\nResolved $host to IP: $ip\n";
} else {
    echo "\n⚠️  Could not resolve hostname: $host\n";
}

// Check if SSL certificate exists
if (file_exists($config['ssl_ca'])) {
    $certInfo = openssl_x509_parse(file_get_contents($config['ssl_ca']));
    echo "\nSSL Certificate Info:\n";
    echo "- Subject: " . ($certInfo['name'] ?? 'N/A') . "\n";
    echo "- Valid From: " . date('Y-m-d H:i:s', $certInfo['validFrom_time_t'] ?? 0) . "\n";
    echo "- Valid To: " . date('Y-m-d H:i:s', $certInfo['validTo_time_t'] ?? 0) . "\n";
    if (isset($certInfo['validTo_time_t']) && $certInfo['validTo_time_t'] < time()) {
        echo "⚠️  WARNING: SSL certificate has expired!\n";
    }
} else {
    echo "\n⚠️  SSL certificate not found at: {$config['ssl_ca']}\n";
}

// Check if we can connect without SSL
if (isset($options[PDO::MYSQL_ATTR_SSL_CA])) {
    echo "\n=== Testing connection without SSL ===\n";
    unset($options[PDO::MYSQL_ATTR_SSL_CA]);
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    
    try {
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        echo "✅ Successfully connected without SSL\n";
        $ssl = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch();
        echo "SSL Status: " . ($ssl['Value'] ? 'Enabled (' . $ssl['Value'] . ')' : 'Disabled') . "\n";
    } catch (PDOException $e) {
        echo "❌ Connection without SSL failed: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
