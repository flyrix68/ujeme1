&lt;<?php
// Minimal health check
header('Content-Type: text/plain');

try {
    // Test 1: Basic PHP execution
    echo "[OK] PHP is executing\n";
    
    // Test 2: Check for required PHP extensions
    $requiredExts = ['pdo', 'pdo_mysql', 'json'];
    $missingExts = [];
    foreach ($requiredExts as $ext) {
        if (!extension_loaded($ext)) {
            $missingExts[] = $ext;
        }
    }
    
    if (!empty($missingExts)) {
        throw new Exception("Missing PHP extensions: " . implode(', ', $missingExts));
    }
    echo "[OK] Required PHP extensions are loaded\n";
    
    // Test 3: Check if we can include db-config
    if (!@include __DIR__ . '/../includes/db-config.php') {
        throw new Exception("Failed to include db-config.php");
    }
    echo "[OK] db-config.php loaded successfully\n";
    
    // If we got here, everything is OK
    http_response_code(200);
    echo "\n[SUCCESS] Health check passed\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo "\n[ERROR] " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
