<?php
// Minimal test file to check basic PHP functionality
header('Content-Type: text/plain');

echo "=== MINIMAL PHP TEST ===\n";
echo "PHP Version: " . phpversion() . "\n";

// Test 1: File system access
try {
    $testFile = __DIR__ . '/test_write.txt';
    file_put_contents($testFile, 'test');
    if (file_exists($testFile)) {
        echo "[OK] Can write to filesystem\n";
        unlink($testFile);
    } else {
        echo "[ERROR] Cannot write to filesystem\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Filesystem test failed: " . $e->getMessage() . "\n";
}

// Test 2: Basic PHP functions
try {
    $testArray = [1, 2, 3];
    if (count($testArray) === 3) {
        echo "[OK] Basic PHP functions work\n";
    } else {
        echo "[ERROR] Basic PHP functions not working\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Basic PHP functions test failed: " . $e->getMessage() . "\n";
}

// Test 3: PDO extension
try {
    if (extension_loaded('pdo')) {
        echo "[OK] PDO extension is loaded\n";    
        if (extension_loaded('pdo_mysql')) {
            echo "[OK] PDO MySQL driver is loaded\n";
        } else {
            echo "[WARNING] PDO MySQL driver is NOT loaded\n";
        }
    } else {
        echo "[ERROR] PDO extension is NOT loaded\n";
    }
} catch (Exception $e) {
    echo "[ERROR] PDO test failed: " . $e->getMessage() . "\n";
}

// Test 4: Error reporting
try {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    echo "[OK] Error reporting is enabled\n";
} catch (Exception $e) {
    echo "[ERROR] Error reporting test failed: " . $e->getMessage() . "\n";
}

// Test 5: Include path
$includePath = get_include_path();
echo "Include path: " . $includePath . "\n";

// List files in current directory
echo "\n=== Directory Listing ===\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "- $file\n";
    }
}

// Show any errors that might have occurred
echo "\n=== Errors ===\n";
$errors = error_get_last();
if ($errors) {
    print_r($errors);
} else {
    echo "No errors detected\n";
}
?>
