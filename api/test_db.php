<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain');

try {
    // Include the database configuration
    require_once __DIR__ . '/../includes/db-config.php';
    
    echo "[1/3] Including db-config.php... OK\n";
    
    // Get database connection
    $pdo = DatabaseSSL::getInstance()->getConnection();
    echo "[2/3] Database connection established... OK\n";
    
    // Test a simple query
    $stmt = $pdo->query('SELECT 1');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result && isset($result['1'])) {
        echo "[3/3] Database query test... OK\n\n";
        echo "SUCCESS: Database connection is working properly!\n";
        echo "PHP Version: " . phpversion() . "\n";
        echo "MySQL Server: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    } else {
        throw new Exception("Database query failed");
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n\n";
    
    // Additional debug information
    echo "Debug Information:\n";
    echo "PHP Version: " . phpversion() . "\n";
    echo "PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No') . "\n";
    echo "PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No') . "\n";
    
    if (isset($pdo)) {
        try {
            echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
        } catch (Exception $e) {
            echo "Could not get PDO error info: " . $e->getMessage() . "\n";
        }
    }
}
