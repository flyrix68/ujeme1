<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include set_env.php
require __DIR__ . '/set_env.php';

// Check if DATABASE_URL is set in different ways
$envVars = [
    'getenv' => getenv('DATABASE_URL'),
    '_ENV' => $_ENV['DATABASE_URL'] ?? 'Not set in _ENV',
    '_SERVER' => $_SERVER['DATABASE_URL'] ?? 'Not set in _SERVER'
];

echo "Environment Variable Check:\n";
echo "-------------------------\n";
foreach ($envVars as $type => $value) {
    echo "$type: " . (is_string($value) ? $value : json_encode($value)) . "\n";
}

// Dump all environment variables for debugging
echo "\nAll Environment Variables:\n";
echo "-------------------------\n";
foreach ($_ENV as $key => $value) {
    echo "$key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
}
?>
