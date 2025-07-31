<?php
// Test script for DatabaseSSL class with SSL connection

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/ssl_test_errors.log');

// Ensure logs directory exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
    @chmod($logDir, 0777);
}

// Include the DatabaseSSL class
require_once __DIR__ . '/includes/db-ssl.php';

// Function to log messages with timestamp to both console and file
function log_test($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Output to console
    echo $logMessage;
    
    // Also log to file
    $logFile = __DIR__ . '/logs/ssl_test.log';
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Also log to PHP error log if it's an error
    if ($level === 'ERROR') {
        error_log($message);
    }
}

try {
    log_test("=== Starting DatabaseSSL Test ===");
    
    // Get database instance
    log_test("Getting database instance...");
    $db = DatabaseSSL::getInstance();
    
    // Test connection
    log_test("Testing connection...");
    $result = $db->getValue("SELECT 1");
    
    if ($result == 1) {
        log_test("✅ Successfully connected to the database!");
        
        // Get database version
        $version = $db->getValue("SELECT VERSION()");
        log_test("Database Version: $version");
        
        // List all tables in the database
        log_test("\n=== Database Tables ===");
        $tables = $db->getRows("SHOW TABLES");
        if (!empty($tables)) {
            foreach ($tables as $table) {
                $tableName = array_values($table)[0];
                log_test("- $tableName");
            }
        } else {
            log_test("No tables found in the database.");
        }
    } else {
        log_test("❌ Connection test failed");
    }
    
} catch (Exception $e) {
    log_test("❌ Error: " . $e->getMessage());
    log_test("File: " . $e->getFile() . " (Line: " . $e->getLine() . ")");
    
    // Log the full trace for debugging
    $trace = $e->getTraceAsString();
    log_test("\nStack Trace:\n$trace");
}

log_test("=== Test Complete ===");
?>
