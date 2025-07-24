<?php
require_once 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    echo "Successfully connected to Railway MySQL with SSL!\n";

    // Test SSL status
    $sslStatus = $pdo->query("SHOW STATUS LIKE 'Ssl_cipher'")->fetch(PDO::FETCH_ASSOC);
    echo "SSL Connection Info:\n";
    echo "- Cipher: " . ($sslStatus['Value'] ?: 'None') . "\n";
    
    // List tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
