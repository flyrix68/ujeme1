&lt;?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/db-config.php';

try {
    echo "Testing Railway database connection with full diagnostics...\n";
    echo "Certificate path: " . __DIR__ . '/cacert.pem' . "\n";
    $pdo = DatabaseConfig::getConnection();
    echo "Successfully connected to:\n";
    echo "Host: " . $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT DATABASE()");
    echo "Connected database: " . $stmt->fetchColumn() . "\n";
    
    echo "Connection test succeeded.\n";
} catch (Exception $e) {
    echo "\n=== CONNECTION FAILURE DETAILS ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    if ($e instanceof PDOException) {
        echo "\nPDO Error Info:\n";
        print_r($e->errorInfo);
        
        echo "\nConnection Status:\n";
        if (isset($pdo) &amp;&amp; $pdo instanceof PDO) {
            echo "PDO attributes:\n";
            print_r($pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS));
        }
    }
    
    echo "\nPHP MySQL Extensions Loaded:\n";
    print_r(get_loaded_extensions());
    
    echo "\nSSL Certificate Status:\n";
    $certPath = __DIR__ . '/cacert.pem';
    echo file_exists($certPath) ? "Certificate found at $certPath" : "Certificate NOT found at $certPath";
}
?&gt;
