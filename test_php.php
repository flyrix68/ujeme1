<?php
// Simple PHP test script
header('Content-Type: text/plain');
echo "PHP is working!\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Current Directory: " . __DIR__ . "\n";

try {
    // Test file writing
    $testFile = __DIR__ . '/test_write.txt';
    $testContent = "Test write at " . date('Y-m-d H:i:s') . "\n";
    
    if (file_put_contents($testFile, $testContent, FILE_APPEND) !== false) {
        echo "✓ Successfully wrote to test file: $testFile\n";
        echo "File content: " . file_get_contents($testFile);
    } else {
        echo "✗ Failed to write to test file. Check directory permissions.\n";
        echo "Current directory permissions: " . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
    }
    
    // Test database connection
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
