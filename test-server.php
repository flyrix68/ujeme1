<?php
// Simple server test file
header('Content-Type: text/plain');

echo "=== Server Environment ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not set') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "\n";
echo "Current Directory: " . __DIR__ . "\n";

// List files in the current directory
echo "\n=== Directory Contents ===\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        echo "- $file\n";
    }
}

// Test database connection
try {
    require_once __DIR__ . '/includes/db-config.php';
    $pdo = DatabaseConfig::getConnection();
    echo "\n=== Database Connection ===\n";
    echo "Database: Connected successfully\n";
    
    // Test a simple query
    $stmt = $pdo->query('SELECT DATABASE() as db');
    $db = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current Database: " . ($db['db'] ?? 'Unknown') . "\n";
    
} catch (Exception $e) {
    echo "\n=== Database Error ===\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

// Show all environment variables
echo "\n=== Environment Variables ===\n";
foreach (getenv() as $key => $value) {
    // Skip sensitive data
    if (stripos($key, 'PASS') !== false || stripos($key, 'SECRET') !== false) {
        echo "$key: [REDACTED]\n";
    } else {
        echo "$key: $value\n";
    }
}
?>
