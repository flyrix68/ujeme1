<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the set_env.php to load environment variables
require __DIR__ . '/set_env.php';

// Test the health endpoint
$healthUrl = 'http://localhost:8080/api/health.php';
$ch = curl_init($healthUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Health Check Response (Status: $httpCode):\n";
echo $response;

// Check if the response contains database connection info
if (strpos($response, 'Database connection successful') !== false) {
    echo "\n✅ Database connection is working!";
} else {
    echo "\n❌ There might be an issue with the database connection.";
}
?>
