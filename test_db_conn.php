<?php
// Simple database connection test

try {
    // Database configuration
    $host = 'yamanote.proxy.rlwy.net';
    $db   = 'railway';
    $user = 'root';
    $pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    $port = '58372';
    
    // DSN - Data Source Name
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    
    // PDO options
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Try to connect
    echo "Connecting to database...\n";
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // If we get here, connection was successful
    echo "✅ Successfully connected to the database!\n";
    
    // Get server version
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "- MySQL Version: $version\n";
    
    // Check SSL status
    $ssl = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch();
    echo "- SSL Status: " . ($ssl['Value'] ? 'Enabled' : 'Disabled') . "\n";
    
    // List databases
    $databases = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
    echo "- Available databases: " . implode(', ', $databases) . "\n";
    
} catch (PDOException $e) {
    // Connection failed
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "Error code: " . $e->getCode() . "\n";
    
    // Check if it's an SSL issue
    if (strpos($e->getMessage(), 'SSL') !== false) {
        echo "\n⚠️  SSL-related error detected. Try these steps:\n";
        echo "1. Check if the SSL certificate exists at: " . __DIR__ . "/includes/cacert.pem\n";
        echo "2. Try adding these options to your PDO connection:\n";
        echo "   PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false\n";
    }
    
    // Common error codes
    switch ($e->getCode()) {
        case 2002:
            echo "\n🔧 Can't connect to MySQL server. Check if the server is running and the host/port are correct.\n";
            break;
        case 1045:
            echo "\n🔧 Access denied. Check your username and password.\n";
            break;
        case 1044:
            echo "\n🔧 Access denied for user to database. Check database permissions.\n";
            break;
        case 1049:
            echo "\n🔧 Database does not exist. Check the database name.\n";
            break;
    }
}
