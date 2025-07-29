<?php
header('Content-Type: text/plain');
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ENHANCED HEALTH CHECK ===\n";

// Test 1: Basic PHP execution
echo "[TEST 1] PHP is executing - SUCCESS\n";

// Test 2: Required extensions
$requiredExts = ['pdo', 'pdo_mysql', 'json'];
$missingExts = array_diff($requiredExts, get_loaded_extensions());
if (!empty($missingExts)) {
    die("[TEST 2] MISSING EXTENSIONS: " . implode(', ', $missingExts) . "\n");
}
echo "[TEST 2] All required extensions loaded - SUCCESS\n";

// Test 3: Verify db-config.php path
$dbConfigPath = __DIR__ . '/../includes/db-config.php';
echo "[TEST 3] Checking db-config.php at: $dbConfigPath\n";

if (!file_exists($dbConfigPath)) {
    die("[TEST 3] db-config.php NOT FOUND at specified path\n");
}

try {
    require_once $dbConfigPath;
    echo "[TEST 3] db-config.php loaded - SUCCESS\n";
} catch (Exception $e) {
    die("[TEST 3] db-config.php ERROR: " . $e->getMessage() . "\n");
}

// Test 4: Database connection
try {
    $pdo = DatabaseConfig::getConnection();
    echo "[TEST 4] Database connection established - SUCCESS\n";
} catch (Exception $e) {
    die("[TEST 4] Database connection FAILED: " . $e->getMessage() . "\n");
}

echo "\n=== ALL CHECKS PASSED ===\n";
http_response_code(200);
?>
