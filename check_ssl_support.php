<?php
// Script to check MySQL server SSL support and capabilities

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'yamanote.proxy.rlwy.net';
$port = '58372';
$dbname = 'railway';
$user = 'root';
$pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';

// Function to get server variables
function getServerVariables($pdo, $variables) {
    $result = [];
    foreach ($variables as $var) {
        $stmt = $pdo->query("SHOW VARIABLES LIKE '$var'");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $result[$row['Variable_name']] = $row['Value'];
        }
    }
    return $result;
}

try {
    // Connect without SSL first
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Get server version
    $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    
    // Check SSL status
    $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC)['Value'];
    $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC)['Value'];
    
    // Get SSL-related variables
    $sslVars = getServerVariables($pdo, [
        'have_ssl', 'have_openssl', 'ssl_cert', 'ssl_key', 'ssl_ca',
        'tls_version', 'version_ssl_library', 'version_compile_os'
    ]);
    
    // Get all SSL-related status variables
    $sslStatusVars = [];
    $stmt = $pdo->query('SHOW STATUS WHERE Variable_name LIKE "Ssl_%"');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $sslStatusVars[$row['Variable_name']] = $row['Value'];
    }
    
    // Output results
    echo "=== MySQL Server Information ===\n";
    echo "Server Version: $version\n";
    echo "Current SSL Cipher: " . ($sslStatus ?: 'Not in use') . "\n";
    echo "Current SSL Version: " . ($sslVersion ?: 'Not in use') . "\n\n";
    
    echo "=== SSL Support ===\n";
    foreach ($sslVars as $name => $value) {
        echo "$name: $value\n";
    }
    
    echo "\n=== SSL Status ===\n";
    foreach ($sslStatusVars as $name => $value) {
        if (!empty($value)) {
            echo "$name: $value\n";
        }
    }
    
    // Check if SSL is enabled on the server
    $sslEnabled = !empty($sslVars['have_ssl']) && $sslVars['have_ssl'] === 'YES';
    $openSSLEnabled = !empty($sslVars['have_openssl']) && $sslVars['have_openssl'] === 'YES';
    
    echo "\n=== SSL Support Summary ===\n";
    echo "SSL Supported: " . ($sslEnabled ? '✅ Yes' : '❌ No') . "\n";
    echo "OpenSSL Supported: " . ($openSSLEnabled ? '✅ Yes' : '❌ No') . "\n";
    
    if (!$sslEnabled) {
        echo "\n⚠️  SSL is not enabled on the MySQL server.\n";
        echo "To enable SSL, you need to configure the MySQL server with SSL support.\n";
        echo "This typically requires:\n";
        echo "1. SSL certificates for the MySQL server\n";
        echo "2. Enabling SSL in the MySQL server configuration (my.cnf)\n";
        echo "3. Restarting the MySQL server\n";
    } else if (empty($sslStatus)) {
        echo "\nℹ️  SSL is supported by the server but not currently in use.\n";
        echo "To require SSL for connections, you can:\n";
        echo "1. Create a user that requires SSL: CREATE USER 'username'@'%' IDENTIFIED BY 'password' REQUIRE SSL;\n";
        echo "2. Or modify an existing user: ALTER USER 'username'@'%' REQUIRE SSL;\n";
    } else {
        echo "\n✅ SSL is properly configured and in use.\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
}
