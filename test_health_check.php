<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set the base URL for the application
$baseUrl = 'http://localhost:8080';

// Test the health check endpoint
echo "Testing health check endpoint...\n";
$healthCheckUrl = "$baseUrl/api/health.php";
$response = file_get_contents($healthCheckUrl);
$result = json_decode($response, true);

if ($result === null) {
    echo "Error: Invalid JSON response from health check endpoint\n";
    echo "Response: " . $response . "\n";
    exit(1);
}

echo "Health Check Result:\n";
echo "Status: " . ($result['status'] ?? 'unknown') . "\n";
echo "Message: " . ($result['message'] ?? 'No message') . "\n";

if (isset($result['data'])) {
    echo "\nDatabase Information:\n";
    echo "Status: " . ($result['data']['database']['status'] ?? 'unknown') . "\n";
    echo "Version: " . ($result['data']['database']['version'] ?? 'unknown') . "\n";
    echo "Table Count: " . ($result['data']['database']['table_count'] ?? 'unknown') . "\n";
}

if (isset($result['error'])) {
    echo "\nError Details:\n";
    echo "Message: " . ($result['error'] ?? 'No error message') . "\n";
    echo "Location: " . ($result['location'] ?? 'unknown') . "\n";
    
    if (isset($result['environment'])) {
        echo "\nEnvironment Information:\n";
        echo "PHP Version: " . ($result['environment']['php_version'] ?? 'unknown') . "\n";
        echo "Memory Limit: " . ($result['environment']['memory_limit'] ?? 'unknown') . "\n";
        
        if (isset($result['environment']['extensions']) && is_array($result['environment']['extensions'])) {
            echo "\nLoaded Extensions:\n";
            echo "- " . implode("\n- ", $result['environment']['extensions']) . "\n";
        }
    }
    
    if (isset($result['trace']) && is_array($result['trace'])) {
        echo "\nStack Trace:\n";
        foreach ($result['trace'] as $line) {
            echo "- $line\n";
        }
    }
    
    exit(1);
}

echo "\nHealth check completed successfully!\n";
?>
