<?php
error_reporting(E_ALL);
error_log("[SET_ENV] Starting environment variable loading");
$envPath = __DIR__ . '/.env';

// Function to read .env file manually
function loadEnvFile($filePath) {
    if (!file_exists($filePath)) {
        error_log("[SET_ENV] WARNING: .env file not found at $filePath");
        return false;
    }
    
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        error_log("[SET_ENV] ERROR: Failed to read .env file");
        return false;
    }
    
    $env = [];
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse the line
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($key !== '') {
                $env[$key] = $value;
            }
        }
    }
    
    return $env;
}

// Load environment variables from .env file
error_log("[SET_ENV] Loading .env file from: $envPath");
$env = loadEnvFile($envPath);

if ($env !== false) {
    // First, set all variables
    foreach ($env as $key => $value) {
        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
        error_log("[SET_ENV] Set: $key");
    }
    
    // Special handling for DATABASE_URL to ensure it's set
    if (isset($env['DATABASE_URL'])) {
        $_ENV['DATABASE_URL'] = $env['DATABASE_URL'];
        putenv("DATABASE_URL={$env['DATABASE_URL']}");
        error_log("[SET_ENV] Explicitly set DATABASE_URL: " . $env['DATABASE_URL']);
    } else {
        error_log("[SET_ENV] WARNING: DATABASE_URL not found in .env file");
    }
    
    // Log all environment variables for debugging
    error_log("[SET_ENV] All environment variables: " . print_r($_ENV, true));
}

// Load from system environment variables
$dbUrl = getenv('DATABASE_URL');
if ($dbUrl) {
    error_log("[SET_ENV] Loading DATABASE_URL from system env: " . substr($dbUrl, 0, 20) . "...");
    $_ENV['DATABASE_URL'] = $dbUrl;
    $_SERVER['DATABASE_URL'] = $dbUrl;
} else {
    error_log("[SET_ENV] WARNING: DATABASE_URL not set in system environment");
}

// Debug output
error_log("[SET_ENV] DATABASE_URL is ". (isset($_ENV['DATABASE_URL']) ? "set" : "NOT set"));
error_log("[SET_ENV] Completed environment variable loading");
?>
