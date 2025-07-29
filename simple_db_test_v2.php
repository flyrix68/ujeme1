<?php
// Enable strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $dbHost = 'yamanote.proxy.rlwy.net';
    $dbPort = 58372;
    $dbUser = 'root';
    $dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    $dbName = 'railway';

    echo "Attempting connection to: $dbHost:$dbPort\n";
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ];
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
    echo "Connection successful!\n";
    echo "Database: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
    
} catch (PDOException $e) {
    echo "\nConnection failed:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Error Info: " . print_r($e->errorInfo, true) . "\n";
}
?>
