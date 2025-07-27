<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\nStarting minimal test...\n";

// Test file writing
$testFile = __DIR__ . '/logs/test_write.log';
$testMessage = "Test write at " . date('Y-m-d H:i:s') . "\n";

// Try to write to a file
if (file_put_contents($testFile, $testMessage, FILE_APPEND) !== false) {
    echo "✓ Successfully wrote to $testFile\n\n";
} else {
    echo "✗ Failed to write to $testFile. Check directory permissions.\n";
    echo "Current directory: " . __DIR__ . "\n";
    echo "Trying to create logs directory...\n";
    
    if (!is_dir(__DIR__ . '/logs')) {
        if (mkdir(__DIR__ . '/logs', 0755, true)) {
            echo "✓ Created logs directory\n";
            
            // Try writing again
            if (file_put_contents($testFile, $testMessage, FILE_APPEND) !== false) {
                echo "✓ Successfully wrote to $testFile after creating directory\n";
            } else {
                echo "✗ Still cannot write to $testFile. Please check permissions.\n";
                echo "Current working directory: " . getcwd() . "\n";
                echo "Directory permissions: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
            }
        } else {
            echo "✗ Failed to create logs directory. Check parent directory permissions.\n";
        }
    }
}

// Test database connection
try {
    echo "\nTesting database connection...\n";
    
    if (!file_exists(__DIR__ . '/includes/db-config.php')) {
        throw new Exception("db-config.php not found at " . __DIR__ . '/includes/db-config.php');
    }
    
    require_once __DIR__ . '/includes/db-config.php';
    
    if (!class_exists('DatabaseConfig')) {
        throw new Exception("DatabaseConfig class not found in db-config.php");
    }
    
    $pdo = DatabaseConfig::getConnection();
    echo "✓ Database connection successful!\n";
    
    // Test a simple query
    $stmt = $pdo->query('SELECT 1 as test_value');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Test query result: " . print_r($result, true) . "\n";
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n";
    
    if (isset($pdo)) {
        echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
    }
}

echo "\nTest completed.\n";
?>

<h2>PHP Info</h2>
<?php
// Uncomment the line below to see PHP configuration details
// phpinfo();
?>
