<?php
// Simple PHP test file
header('Content-Type: text/plain');
echo "PHP is working!\n";

try {
    // Test 1: PHP version
    echo "PHP Version: " . phpversion() . "\n\n";
    
    // Test 2: Required extensions
    $required = ['pdo', 'pdo_mysql', 'json', 'session'];
    $loaded = get_loaded_extensions();
    $missing = [];
    
    echo "Checking required extensions:\n";
    foreach ($required as $ext) {
        $status = extension_loaded($ext) ? "[OK]" : "[MISSING]";
        echo "$status $ext\n";
        if (!in_array($ext, $loaded)) {
            $missing[] = $ext;
        }
    }
    
    // Test 3: File permissions
    echo "\nChecking file permissions:\n";
    $dirs = [
        __DIR__ => 'Current directory',
        __DIR__ . '/../includes' => 'Includes directory',
        __DIR__ . '/../uploads' => 'Uploads directory'
    ];
    
    foreach ($dirs as $path => $desc) {
        $writable = is_writable($path) ? 'writable' : 'NOT writable';
        $readable = is_readable($path) ? 'readable' : 'NOT readable';
        echo "$desc ($path): $readable, $writable\n";
    }
    
    // Test 4: Database config file
    $dbConfigPath = __DIR__ . '/../includes/db-config.php';
    echo "\nDatabase config: ";
    if (file_exists($dbConfigPath)) {
        echo "Found at $dbConfigPath\n";
    } else {
        echo "NOT FOUND at $dbConfigPath\n";
    }
    
    // Test 5: PHP error reporting
    echo "\nError reporting: " . ini_get('display_errors') . "\n";
    
    if (!empty($missing)) {
        throw new Exception("Missing required PHP extensions: " . implode(', ', $missing));
    }
    
} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
