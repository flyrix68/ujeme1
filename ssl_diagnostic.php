<?php
// Database SSL Diagnostic Script

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

// Function to test connection with specific options
function testConnection($config, $options, $description) {
    echo "\n=== $description ===\n";
    
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
        
        echo "✅ Connected in {$time}ms\n";
        
        // Get server variables
        $serverVars = [
            'version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'ssl_cipher' => $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC)['Value'],
            'ssl_version' => $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC)['Value'],
            'have_ssl' => $pdo->query('SHOW VARIABLES LIKE "have_ssl"')->fetch(PDO::FETCH_ASSOC)['Value'],
            'have_openssl' => $pdo->query('SHOW VARIABLES LIKE "have_openssl"')->fetch(PDO::FETCH_ASSOC)['Value']
        ];
        
        echo "- MySQL Version: {$serverVars['version']}\n";
        echo "- SSL Cipher: " . ($serverVars['ssl_cipher'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($serverVars['ssl_version'] ?: 'Not in use') . "\n";
        echo "- Have SSL: {$serverVars['have_ssl']}\n";
        echo "- Have OpenSSL: {$serverVars['have_openssl']}\n";
        
        // List some tables to verify connection is working
        $tables = $pdo->query('SHOW TABLES LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
        echo "- Sample tables: " . implode(', ', $tables) . "\n";
        
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        echo "Error code: " . $e->getCode() . "\n";
        return false;
    }
}

// Common PDO options
$commonOptions = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 5,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
];

// Test 1: No SSL
$options = $commonOptions;
testConnection($config, $options, "Test 1: No SSL");

// Test 2: SSL with verification disabled
$options = array_merge($commonOptions, [
    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
]);
testConnection($config, $options, "Test 2: SSL with verification disabled");

// Test 3: SSL with CA certificate
$caCertPath = __DIR__ . '/' . ltrim($config['ssl_ca'], '/');
if (file_exists($caCertPath)) {
    $options = array_merge($commonOptions, [
        PDO::MYSQL_ATTR_SSL_CA => $caCertPath,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
    ]);
    testConnection($config, $options, "Test 3: SSL with CA certificate");
} else {
    echo "\n⚠️ CA certificate not found at: $caCertPath\n";
}

// Test 4: Check if server supports SSL
$pdo = new PDO(
    "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}",
    $config['user'],
    $config['pass'],
    $commonOptions
);

// Get server SSL status
echo "\n=== Server SSL Capabilities ===\n";
$sslStatus = $pdo->query('SHOW STATUS WHERE Variable_name LIKE "Ssl_%"')->fetchAll(PDO::FETCH_KEY_PAIR);
$sslVars = $pdo->query('SHOW VARIABLES WHERE Variable_name LIKE "%ssl%"')->fetchAll(PDO::FETCH_KEY_PAIR);

echo "SSL Server Status:\n";
foreach ($sslStatus as $key => $value) {
    if ($value === '') continue;
    echo "- $key: $value\n";
}

echo "\nSSL Server Variables:\n";
foreach ($sslVars as $key => $value) {
    if ($value === '') continue;
    echo "- $key: $value\n";
}
