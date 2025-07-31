<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type to text/plain for better readability
header('Content-Type: text/plain; charset=utf-8');

// Function to output and log messages
function debug_message($message) {
    echo "[DEBUG] $message\n";
    error_log("[DEBUG] $message");
}

echo "=== Debug Information ===\n\n";

// 1. Basic PHP info
debug_message("PHP Version: " . phpversion());
debug_message("Current Directory: " . __DIR__);

echo "\n=== Environment Variables ===\n";

// 2. Check if .env file exists
$env_path = __DIR__ . '/.env';
debug_message("Checking .env file at: $env_path");

if (file_exists($env_path)) {
    debug_message(".env file exists");
    if (is_readable($env_path)) {
        debug_message(".env file is readable");
        
        // Read DATABASE_URL from .env file directly
        $env_content = file_get_contents($env_path);
        if (preg_match('/DATABASE_URL=["\']?([^\r\n\'"]+)["\']?/', $env_content, $matches)) {
            $db_url = $matches[1];
            debug_message("Found DATABASE_URL in .env file: " . 
                         preg_replace('/(:[^:]+)@/', ':*****@', $db_url));
        } else {
            debug_message("Could not find DATABASE_URL in .env file");
        }
    } else {
        debug_message("ERROR: .env file exists but is not readable");
    }
} else {
    debug_message("ERROR: .env file does not exist at $env_path");
}

echo "\n=== set_env.php ===\n";

// 3. Check set_env.php
$set_env_path = __DIR__ . '/set_env.php';
debug_message("Checking set_env.php at: $set_env_path");

if (file_exists($set_env_path)) {
    debug_message("set_env.php exists");
    if (is_readable($set_env_path)) {
        debug_message("set_env.php is readable");
        
        // Include set_env.php
        debug_message("Including set_env.php...");
        try {
            require $set_env_path;
            debug_message("Successfully included set_env.php");
            
            // Check if DATABASE_URL is set after including set_env.php
            if (isset($_ENV['DATABASE_URL'])) {
                $db_url = $_ENV['DATABASE_URL'];
                debug_message("Found DATABASE_URL in \$_ENV: " . 
                             preg_replace('/(:[^:]+)@/', ':*****@', $db_url));
            } elseif (getenv('DATABASE_URL')) {
                $db_url = getenv('DATABASE_URL');
                debug_message("Found DATABASE_URL in getenv(): " . 
                             preg_replace('/(:[^:]+)@/', ':*****@', $db_url));
            } else {
                debug_message("WARNING: DATABASE_URL not found in environment after including set_env.php");
            }
        } catch (Exception $e) {
            debug_message("ERROR including set_env.php: " . $e->getMessage());
        }
    } else {
        debug_message("ERROR: set_env.php exists but is not readable");
    }
} else {
    debug_message("ERROR: set_env.php does not exist at $set_env_path");
}

echo "\n=== Database Configuration ===\n";

// 4. Check db-config.php
$db_config_path = __DIR__ . '/includes/db-config.php';
debug_message("Checking db-config.php at: $db_config_path");

if (file_exists($db_config_path)) {
    debug_message("db-config.php exists");
    if (is_readable($db_config_path)) {
        debug_message("db-config.php is readable");
        
        // Include db-config.php
        debug_message("Including db-config.php...");
        try {
            require $db_config_path;
            debug_message("Successfully included db-config.php");
            
            // Check if DatabaseConfig class exists
            if (class_exists('DatabaseConfig')) {
                debug_message("DatabaseConfig class exists");
                
                // Test database connection
                debug_message("Testing database connection...");
                try {
                    $pdo = DatabaseSSL::getInstance()->getConnection();
                    debug_message("Successfully connected to the database");
                    
                    // Get database version
                    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                    debug_message("Database version: $version");
                    
                    // Get table count
                    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                    debug_message("Found " . count($tables) . " tables in the database");
                    
                } catch (Exception $e) {
                    debug_message("ERROR connecting to database: " . $e->getMessage());
                    debug_message("Error in " . $e->getFile() . " on line " . $e->getLine());
                }
            } else {
                debug_message("ERROR: DatabaseConfig class not found after including db-config.php");
            }
        } catch (Exception $e) {
            debug_message("ERROR including db-config.php: " . $e->getMessage());
        }
    } else {
        debug_message("ERROR: db-config.php exists but is not readable");
    }
} else {
    debug_message("ERROR: db-config.php does not exist at $db_config_path");
}

echo "\n=== PHP Info ===\n";

// 5. Display PHP info
$extensions = get_loaded_extensions();
sort($extensions);
debug_message("Loaded extensions: " . implode(', ', $extensions));

// 6. Check file permissions
function check_permissions($path) {
    $permissions = [];
    $permissions['exists'] = file_exists($path);
    $permissions['readable'] = is_readable($path);
    $permissions['writable'] = is_writable($path);
    $permissions['executable'] = is_executable($path);
    $permissions['owner'] = posix_getpwuid(fileowner($path))['name'] ?? 'unknown';
    $permissions['group'] = posix_getgrgid(filegroup($path))['name'] ?? 'unknown';
    $permissions['perms'] = substr(sprintf('%o', fileperms($path)), -4);
    return $permissions;
}

echo "\n=== File Permissions ===\n";

$files_to_check = [
    '.env' => __DIR__ . '/.env',
    'set_env.php' => __DIR__ . '/set_env.php',
    'db-config.php' => __DIR__ . '/includes/db-config.php',
    'logs' => __DIR__ . '/logs',
    'uploads' => __DIR__ . '/uploads'
];

foreach ($files_to_check as $name => $path) {
    debug_message("Checking permissions for $name ($path):");
    if (file_exists($path)) {
        $perms = check_permissions($path);
        foreach ($perms as $key => $value) {
            debug_message("  $key: " . ($value === true ? 'true' : ($value === false ? 'false' : $value)));
        }
    } else {
        debug_message("  File/directory does not exist");
    }
}

echo "\n=== End of Debug Information ===\n";
?>
