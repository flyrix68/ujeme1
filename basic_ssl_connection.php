<?php
// Basic SSL connection test

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$config = [
    'host' => 'yamanote.proxy.rlwy.net',
    'port' => '58372',
    'dbname' => 'railway',
    'user' => 'root',
    'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'ssl_ca' => __DIR__ . '/includes/cacert.pem'
];

// Function to test SSL connection
function testSslConnection($config) {
    echo "=== Testing SSL Connection ===\n";
    
    try {
        // Build DSN with SSL mode
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4;sslmode=REQUIRED',
            $config['host'],
            $config['port'],
            $config['dbname']
        );
        
        // SSL options
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // Disable certificate verification for now
        ];
        
        // Add CA certificate if it exists
        if (file_exists($config['ssl_ca'])) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'];
            echo "Using CA certificate: {$config['ssl_ca']}\n";
        } else {
            echo "⚠️ CA certificate not found, proceeding without it\n";
        }
        
        // Connect to database
        $start = microtime(true);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        // Check connection status
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Connected in {$time}ms\n";
        echo "- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "- SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($sslVersion['Value'] ?: 'Not in use') . "\n";
        
        // Test query
        $result = $pdo->query('SELECT 1 as test')->fetch(PDO::FETCH_ASSOC);
        echo "- Test query result: " . ($result ? 'Success' : 'Failed') . "\n";
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        
        // Common error codes and solutions
        $solutions = [
            '2002' => "Cannot connect to database server. Check if MySQL is running and the host/port are correct.",
            '1045' => "Access denied. Verify username and password.",
            '2026' => "SSL connection error. The server might not support SSL or the SSL configuration is incorrect.",
            'default' => "Check your database configuration and server status."
        ];
        
        $errorCode = (string)$e->getCode();
        $solution = $solutions[$errorCode] ?? $solutions['default'];
        echo "💡 Suggestion: $solution\n";
        
        return false;
    }
}

// Run the test
testSslConnection($config);
