<?php
// Test script to check Apache and PHP configuration
echo "<h1>PHP Info</h1>";
phpinfo();

// Test database connection
echo "<h2>Database Connection Test</h2>";

try {
    // Database configuration from environment variables
    $dbHost = getenv('DB_HOST');
    $dbPort = getenv('DB_PORT');
    $dbName = getenv('DB_DATABASE');
    $dbUser = getenv('DB_USERNAME');
    $dbPass = getenv('DB_PASSWORD');
    $sslCa = getenv('DB_SSL_CA');
    
    echo "<p>Connecting to database: $dbUser@$dbHost:$dbPort/$dbName</p>";
    
    // Create connection with SSL options
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // Add SSL options if configured
    if ($sslCa && file_exists(__DIR__ . '/' . $sslCa)) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__ . '/' . $sslCa;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test a simple query
    $stmt = $pdo->query('SELECT VERSION() as version');
    $result = $stmt->fetch();
    echo "<p>MySQL Version: " . htmlspecialchars($result['version']) . "</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars(print_r($e->getTraceAsString(), true)) . "</pre>";
}

// Check required PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'openssl', 'mbstring', 'json'];
$missingExtensions = [];

echo "<h2>Required PHP Extensions</h2><ul>";
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<li style='color: green;'>✅ $ext</li>";
    } else {
        $missingExtensions[] = $ext;
        echo "<li style='color: red;'>❌ $ext (MISSING)</li>";
    }
}
echo "</ul>";

// Check file permissions
$writableDirs = [
    '/var/www/html/logs' => 'Logs directory',
    '/var/log/apache2' => 'Apache log directory',
    '/var/lib/php/sessions' => 'PHP sessions directory'
];

echo "<h2>File Permissions</h2><ul>";
foreach ($writableDirs as $path => $description) {
    if (is_writable($path)) {
        echo "<li style='color: green;'>✅ $description ($path) is writable</li>";
    } else {
        echo "<li style='color: red;'>❌ $description ($path) is NOT writable";
        if (file_exists($path)) {
            $perms = fileperms($path);
            echo " (Current permissions: " . substr(sprintf('%o', $perms), -4) . ")";
        } else {
            echo " (Directory does not exist)";
        }
        echo "</li>";
    }
}
echo "</ul>";

// Display environment variables (without sensitive data)
echo "<h2>Environment Variables</h2><pre>";
$envVars = getenv();
ksort($envVars);
foreach ($envVars as $key => $value) {
    // Mask sensitive data
    if (preg_match('/(PASS|PWD|SECRET|KEY|TOKEN|API[_-]?KEY)/i', $key)) {
        $value = '********';
    }
    echo htmlspecialchars("$key=$value") . "\n";
}
echo "</pre>";

// Display PHP error log location
echo "<h2>PHP Error Log</h2>";
$errorLog = ini_get('error_log');
if ($errorLog && file_exists($errorLog)) {
    echo "<p>Error log location: " . htmlspecialchars($errorLog) . "</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($errorLog) ?: 'Error log is empty') . "</pre>";
} else {
    echo "<p>Error log not found at: " . htmlspecialchars($errorLog) . "</p>";
}

// Display Apache error log if accessible
$apacheErrorLog = '/var/log/apache2/error.log';
echo "<h2>Apache Error Log</h2>";
if (file_exists($apacheErrorLog)) {
    echo "<pre>" . htmlspecialchars(file_get_contents($apacheErrorLog) ?: 'Apache error log is empty') . "</pre>";
} else {
    echo "<p>Apache error log not found at: " . htmlspecialchars($apacheErrorLog) . "</p>";
}
?>
