<?php
// Disable error display for production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Set content type to text/plain for better readability
header('Content-Type: text/plain');

echo "=== PHP Environment ===\n";
echo "PHP Version: " . phpversion() . "\n\n";

// Check for required extensions
$required_extensions = ['pdo', 'pdo_mysql', 'mysqli', 'json', 'curl'];
$missing_extensions = [];

echo "=== Required Extensions ===\n";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "[OK] $ext\n";
    } else {
        $missing_extensions[] = $ext;
        echo "[MISSING] $ext\n";
    }
}

if (!empty($missing_extensions)) {
    echo "\nERROR: Missing required extensions: " . implode(', ', $missing_extensions) . "\n";
}

// Check if we can connect to the database
echo "\n=== Database Connection Test ===\n";
try {
    // Try to include the database configuration
    $dbConfigPath = __DIR__ . '/../includes/db-config.php';
    if (file_exists($dbConfigPath)) {
        echo "Database config found at: $dbConfigPath\n";
        require_once $dbConfigPath;
        
        // Test database connection
        if (class_exists('DatabaseConfig')) {
            echo "DatabaseConfig class exists. Testing connection...\n";
            
            try {
                $pdo = DatabaseConfig::getConnection();
                echo "[SUCCESS] Connected to database!\n";
                echo "Database Server: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
                
                // Test a simple query
                $stmt = $pdo->query('SELECT 1 as test');
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "Test query result: " . print_r($result, true) . "\n";
                
            } catch (PDOException $e) {
                echo "[ERROR] Database connection failed: " . $e->getMessage() . "\n";
                echo "Error Code: " . $e->getCode() . "\n";
                if (isset($pdo)) {
                    echo "PDO Error Info: " . print_r($pdo->errorInfo(), true) . "\n";
                }
            }
        } else {
            echo "[ERROR] DatabaseConfig class not found after including $dbConfigPath\n";
        }
    } else {
        echo "[ERROR] Database config file not found at: $dbConfigPath\n";
    }
} catch (Exception $e) {
    echo "[EXCEPTION] " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}

// Check file permissions
echo "\n=== File Permissions ===\n";
$paths_to_check = [
    '/var/www/html',
    '/var/www/html/api',
    '/var/www/html/includes',
    '/var/log/apache2',
    '/var/lib/php/sessions'
];

foreach ($paths_to_check as $path) {
    if (file_exists($path)) {
        $perms = fileperms($path);
        $owner = posix_getpwuid(fileowner($path))['name'] ?? 'unknown';
        $group = posix_getgrgid(filegroup($path))['name'] ?? 'unknown';
        echo sprintf(
            "%s - Owner: %s, Group: %s, Perms: %o\n",
            $path,
            $owner,
            $group,
            $perms & 0777
        );
    } else {
        echo "$path does not exist\n";
    }
}

// Check Apache error log
echo "\n=== Apache Error Log ===\n";
$error_log = '/var/log/apache2/error.log';
if (file_exists($error_log)) {
    echo "Last 20 lines of $error_log:\n";
    $lines = file($error_log);
    $last_lines = array_slice($lines, -20);
    echo implode("", $last_lines);
} else {
    echo "Error log not found at $error_log\n";
}

// Check PHP error log
echo "\n=== PHP Error Log ===\n";
$php_error_log = ini_get('error_log');
if ($php_error_log && file_exists($php_error_log)) {
    echo "Last 20 lines of $php_error_log:\n";
    $lines = file($php_error_log);
    $last_lines = array_slice($lines, -20);
    echo implode("", $last_lines);
} else {
    echo "PHP error log not found at " . ($php_error_log ?: 'default location') . "\n";
}
?>
