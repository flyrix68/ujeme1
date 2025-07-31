<?php
// Direct test script for database connection with SSL

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'yamanote.proxy.rlwy.net';
$port = '58372';
$dbname = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
$caCertPath = __DIR__ . '/includes/cacert.pem';

// Function to test connection
function testConnection($dsn, $user, $pass, $options) {
    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get SSL status
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
        
        return [
            'success' => true,
            'version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'ssl_cipher' => $sslStatus['Value'] ?? 'Not in use',
            'ssl_version' => $sslVersion['Value'] ?? 'Not in use',
            'tables' => $pdo->query('SHOW TABLES LIMIT 3')->fetchAll(PDO::FETCH_COLUMN)
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $e->getCode()
        ];
    }
}

// Test 1: Basic connection without SSL
echo "\n=== Testing Basic Connection ===\n";
$dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
$result = testConnection($dsn, $user, $pass, [
    PDO::ATTR_TIMEOUT => 5
]);

if ($result['success']) {
    echo "✅ Connected successfully!\n";
    echo "- MySQL Version: {$result['version']}\n";
    echo "- SSL Cipher: {$result['ssl_cipher']}\n";
    echo "- SSL Version: {$result['ssl_version']}\n";
    echo "- Tables: " . implode(', ', $result['tables']) . "\n";
} else {
    echo "❌ Connection failed: {$result['error']} (Code: {$result['code']})\n";
}

// Test 2: Connection with SSL options
if (file_exists($caCertPath)) {
    echo "\n=== Testing Connection with SSL ===\n";
    echo "Using CA certificate: $caCertPath\n";
    
    // Test with different SSL modes
    $modes = [
        'PREFERRED' => [
            'dsn_extra' => ';sslmode=PREFERRED',
            'options' => [PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
        ],
        'REQUIRED' => [
            'dsn_extra' => ';sslmode=REQUIRED',
            'options' => [PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false]
        ],
        'VERIFY_CA' => [
            'dsn_extra' => ';sslmode=VERIFY_CA',
            'options' => [
                PDO::MYSQL_ATTR_SSL_CA => $caCertPath,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true
            ]
        ]
    ];
    
    foreach ($modes as $mode => $config) {
        echo "\n- Testing SSL Mode: $mode\n";
        $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4{$config['dsn_extra']}";
        $result = testConnection($dsn, $user, $pass, $config['options'] + [PDO::ATTR_TIMEOUT => 5]);
        
        if ($result['success']) {
            echo "  ✅ Success! SSL Cipher: {$result['ssl_cipher']}\n";
        } else {
            echo "  ❌ Failed: {$result['error']} (Code: {$result['code']})\n";
        }
    }
} else {
    echo "\n⚠️ CA certificate not found at: $caCertPath\n";
}
