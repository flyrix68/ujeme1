<?php
$dbHost = 'yamanote.proxy.rlwy.net';
$dbPort = 58372;
$dbUser = 'root';
$dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu'; 
$dbName = 'railway';

try {
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
        PDO::MYSQL_ATTR_SSL_CA => realpath('includes/cacert.pem'),
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ];
    
    $start = microtime(true);
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo 'Successfully connected to Railway MySQL in ' . round((microtime(true)-$start)*1000) . 'ms';
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
