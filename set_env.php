<?php
error_log("[SET_ENV] Starting environment variable loading");
$envPath = __DIR__ . '/.env';

// Load environment variables from .env file
if (file_exists($envPath)) {
    error_log("[SET_ENV] Loading .env file from: $envPath");
    $env = parse_ini_file($envPath);
    if ($env === false) {
        error_log("[SET_ENV] ERROR: Failed to parse .env file");
    } else {
        foreach ($env as $key => $value) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            error_log("[SET_ENV] Set: $key");
        }
    }
} else {
    error_log("[SET_ENV] WARNING: .env file not found at $envPath");
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
