<?php
header('Content-Type: text/plain');
echo "=== PHP ENVIRONMENT TEST ===\n\n";

// Test 1: Basic execution
echo "[TEST] PHP is executing\n";
echo "PHP version: " . phpversion() . "\n";

// Test 2: Show loaded extensions
echo "\n[EXTENSIONS LOADED]\n";
print_r(get_loaded_extensions());

// Test 3: Check specific required extensions
$requiredExts = ['pdo', 'pdo_mysql', 'json'];
echo "\n[REQUIRED EXTENSIONS]\n";
foreach ($requiredExts as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? 'Loaded' : 'MISSING') . "\n";
}

// Test 4: Show PHP configuration info
echo "\n[PHP CONFIGURATION]\n";
ob_start();
phpinfo();
$phpinfo = ob_get_clean();
echo $phpinfo;
?>
