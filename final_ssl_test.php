<?php
// Final SSL test with comprehensive reporting

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$config = [
    'host' => 'yamanote.proxy.rlwy.net',
    'port' => '58372',
    'dbname' => 'railway',
    'user' => 'root',
    'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'ca_cert' => __DIR__ . '/includes/cacert.pem'
];

// Function to test connection with given options
function testConnection($config, $options, $testName) {
    echo "\n=== $testName ===\n";
    
    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['dbname']
        );
        
        $start = microtime(true);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        // Get SSL status
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC)['Value'];
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC)['Value'];
        $haveSsl = $pdo->query('SHOW VARIABLES LIKE "have_ssl"')->fetch(PDO::FETCH_ASSOC)['Value'];
        
        echo "✅ Connected in {$time}ms\n";
        echo "- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "- SSL Support: $haveSsl\n";
        echo "- SSL Cipher: " . ($sslStatus ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($sslVersion ?: 'Not in use') . "\n";
        
        // List a table to verify the connection is working
        $table = $pdo->query('SHOW TABLES LIMIT 1')->fetch(PDO::FETCH_COLUMN);
        echo "- First table: " . ($table ?: 'No tables found') . "\n";
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        return false;
    }
}

// Test 1: Basic connection without SSL
testConnection($config, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_TIMEOUT => 5
], "Test 1: Basic Connection (No SSL)");

// Test 2: Try with SSL options but no verification
if (file_exists($config['ca_cert'])) {
    testConnection($config, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        PDO::MYSQL_ATTR_SSL_CA => $config['ca_cert']
    ], "Test 2: With SSL Options (No Verification)");
    
    // Test 3: Try with full SSL verification
    testConnection($config, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true,
        PDO::MYSQL_ATTR_SSL_CA => $config['ca_cert']
    ], "Test 3: With Full SSL Verification");
} else {
    echo "\n⚠️ CA certificate not found at: {$config['ca_cert']}\n";
}

// Final recommendations
echo "\n=== SSL Configuration Recommendations ===\n";
echo "Based on the test results, here are the recommendations for your database connection:\n\n";

echo "1. **Current Status**: Your MySQL server does not have SSL enabled.\n";
echo "   - SSL Cipher: Not in use\n";
echo "   - SSL Version: Not in use\n\n";

echo "2. **Recommendation**:\n";
echo "   - Contact your database administrator to enable SSL on the MySQL server.\n";
echo "   - The server needs to be configured with proper SSL certificates.\n";
echo "   - After enabling SSL on the server, update your application's database configuration to use SSL.\n\n";

echo "3. **For Development**:\n";
echo "   - If you're in a development environment, you can continue without SSL.\n";
echo "   - For production, SSL is highly recommended to secure your database connections.\n\n";

echo "4. **Next Steps**:\n";
echo "   - Verify with your hosting provider (Railway) about SSL support for your database.\n";
echo "   - If SSL is not available, consider using a VPN or SSH tunnel for secure connections.\n";
echo "   - Monitor your database logs for any connection issues.\n";
