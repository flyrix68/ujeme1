<?php
// Script to verify database connection and SSL status

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$config = [
    'host' => 'yamanote.proxy.rlwy.net',
    'port' => '58372',
    'dbname' => 'railway',
    'user' => 'root',
    'pass' => 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
    'ssl_ca' => 'includes/cacert.pem'
];

// Function to test connection with given options
function testConnection($config, $options, $testName) {
    echo "\n=== $testName ===\n";
    
    try {
        // Build DSN
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['dbname']
        );
        
        // Add SSL options to DSN if needed
        if (isset($options['ssl_mode'])) {
            $dsn .= ";sslmode=" . $options['ssl_mode'];
        }
        
        // Remove DSN-specific options from PDO options
        $pdoOptions = $options;
        unset($pdoOptions['ssl_mode']);
        
        // Set default PDO options
        $pdoOptions[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        $pdoOptions[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        $pdoOptions[PDO::ATTR_EMULATE_PREPARES] = false;
        $pdoOptions[PDO::ATTR_TIMEOUT] = 5;
        
        // Connect to database
        $start = microtime(true);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], $pdoOptions);
        $time = round((microtime(true) - $start) * 1000, 2);
        
        // Get server info
        $serverInfo = [
            'version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'ssl_cipher' => $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC)['Value'],
            'ssl_version' => $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC)['Value'],
            'have_ssl' => $pdo->query('SHOW VARIABLES LIKE "have_ssl"')->fetch(PDO::FETCH_ASSOC)['Value']
        ];
        
        echo "✅ Connected successfully in {$time}ms\n";
        echo "- MySQL Version: {$serverInfo['version']}\n";
        echo "- SSL Cipher: " . ($serverInfo['ssl_cipher'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($serverInfo['ssl_version'] ?: 'Not in use') . "\n";
        echo "- Have SSL: {$serverInfo['have_ssl']}\n";
        
        // List a table to verify the connection is working
        $tables = $pdo->query('SHOW TABLES LIMIT 1')->fetch(PDO::FETCH_COLUMN);
        echo "- First table: " . ($tables ?: 'No tables found') . "\n";
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        return false;
    }
}

// Test 1: Basic connection without SSL
testConnection($config, [], "Test 1: Basic connection without SSL");

// Test 2: Try with SSL mode in DSN
testConnection($config, ['ssl_mode' => 'PREFERRED'], "Test 2: SSL mode PREFERRED");

// Test 3: Try with SSL mode REQUIRED
testConnection($config, ['ssl_mode' => 'REQUIRED'], "Test 3: SSL mode REQUIRED");

// Test 4: Try with SSL options and CA certificate
$caCertPath = __DIR__ . '/' . ltrim($config['ssl_ca'], '/');
if (file_exists($caCertPath)) {
    testConnection($config, [
        'ssl_mode' => 'VERIFY_CA',
        PDO::MYSQL_ATTR_SSL_CA => $caCertPath,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
    ], "Test 4: With CA certificate verification");
} else {
    echo "\n⚠️ CA certificate not found at: $caCertPath\n";
}

// Test 5: Check server SSL capabilities
try {
    $pdo = new PDO(
        "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
        $config['user'],
        $config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "\n=== Server SSL Capabilities ===\n";
    
    // Check SSL related variables
    $sslVars = [
        'have_ssl', 'have_openssl', 'ssl_cert', 'ssl_key', 'ssl_ca',
        'tls_version', 'version_ssl_library', 'version_compile_os'
    ];
    
    $results = [];
    foreach ($sslVars as $var) {
        $stmt = $pdo->query("SHOW VARIABLES LIKE '$var'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $results[] = $result;
        }
    }
    
    echo "SSL-related server variables:\n";
    foreach ($results as $row) {
        echo "- {$row['Variable_name']} = {$row['Value']}\n";
    }
    
    // Check SSL status
    $sslStatus = $pdo->query('SHOW STATUS WHERE Variable_name LIKE "Ssl_%"')->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "\nSSL Status:\n";
    foreach ($sslStatus as $key => $value) {
        if (!empty($value)) {
            echo "- $key = $value\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Failed to check server SSL capabilities: " . $e->getMessage() . "\n";
}
