<?php
/**
 * Database Connection Test Script
 * 
 * This script tests the database connection using the DatabaseConfig class
 * and verifies that all required environment variables are properly set.
 * 
 * Usage: 
 *   - Command line: php test_connection.php
 *   - Web browser: http://your-domain.com/test_connection.php
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Define log directory and files
$logDir = __DIR__ . DIRECTORY_SEPARATOR . 'logs';
$errorLog = $logDir . DIRECTORY_SEPARATOR . 'test_connection_errors.log';
$outputLog = $logDir . DIRECTORY_SEPARATOR . 'test_connection_output.log';

// Set error log path
ini_set('error_log', $errorLog);

// Ensure logs directory exists
if (!is_dir($logDir)) {
    if (!mkdir($logDir, 0755, true)) {
        die("FATAL: Could not create log directory: $logDir");
    }
}

// Clear previous log files if they exist
if (file_exists($errorLog)) unlink($errorLog);
if (file_exists($outputLog)) unlink($outputLog);

// Check if running from command line or web
$isCli = (php_sapi_name() === 'cli');

// Function to log messages with timestamp and output them
function log_message($message, $level = 'INFO') {
    global $errorLog, $isCli;
    
    $timestamp = date('Y-m-d H:i:s');
    $logLine = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log to error log file
    file_put_contents($errorLog, $logLine, FILE_APPEND);
    
    // Also output to console if running in CLI, or buffer for web output
    if ($isCli) {
        fwrite(STDERR, $logLine);
    }
    
    return $logLine;
}

// Function to display a section header
function display_section($title) {
    $line = str_repeat('=', 80);
    $section = PHP_EOL . $line . PHP_EOL . strtoupper($title) . PHP_EOL . $line . PHP_EOL;
    
    // Log the section header
    log_message("SECTION: $title");
    
    return $section;
}

// Function to safely get environment variable with default
function get_env_var($name, $default = null) {
    $value = getenv($name);
    if ($value === false || $value === '') {
        $value = $default;
    }
    return $value;
}

// Function to mask sensitive data in output
function mask_sensitive_data($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            if (is_string($key) && preg_match('/(pass|pwd|secret|key|token|api[_-]?key)/i', $key)) {
                $data[$key] = str_repeat('*', 8);
            } elseif (is_array($value) || is_object($value)) {
                $data[$key] = mask_sensitive_data((array)$value);
            }
        }
    }
    return $data;
}

// Start output buffering
ob_start();

// Function to get the output buffer and clear it
function get_clean_output() {
    $output = ob_get_clean();
    ob_start();
    return $output;
}

// Function to save output to log file
function save_output_to_log($output, $file) {
    if (!empty($output)) {
        file_put_contents($file, $output, FILE_APPEND);
    }
}

// Function to check if a file exists and is readable
function check_file($path, $description) {
    if (!file_exists($path)) {
        throw new Exception("$description not found at: $path");
    }
    if (!is_readable($path)) {
        throw new Exception("$description is not readable: $path");
    }
    return true;
}

try {
    // Display header
    echo display_section('Database Connection Test');
    echo "Test started at: " . date('Y-m-d H:i:s') . PHP_EOL;
    
    // Log script start
    log_message("Starting database connection test");
    
    // Check if required files exist and are readable
    $requiredFiles = [
        __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db-config.php' => 'Database Config File',
        __DIR__ . DIRECTORY_SEPARATOR . '.env' => 'Environment File'
    ];
    
    foreach ($requiredFiles as $file => $description) {
        try {
            check_file($file, $description);
            echo "✅ $description found at: $file" . PHP_EOL;
            log_message("$description found and is readable: $file");
        } catch (Exception $e) {
            log_message($e->getMessage(), 'ERROR');
            echo "❌ " . $e->getMessage() . PHP_EOL;
            throw new Exception("Required files are missing or inaccessible. Please check the paths above.");
        }
    }
    
    // Load environment variables
    $envFile = __DIR__ . DIRECTORY_SEPARATOR . '.env';
    log_message("Loading environment variables from: $envFile");
    
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new Exception("Failed to read .env file");
        }
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parse name=value pairs
            if (strpos($line, '=') !== false) {
                list($name, $value) = array_map('trim', explode('=', $line, 2));
                
                // Remove quotes if present
                $value = trim($value, '"\'');
                
                // Set in all places PHP might look for env vars
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                
                log_message("Loaded env var: $name = " . 
                    (preg_match('/(pass|pwd|secret|key|token|api[_-]?key)/i', $name) ? '********' : $value));
            }
        }
    } else {
        log_message("Warning: .env file not found, using system environment variables", 'WARNING');
    }
    
    // Define required environment variables
    $requiredVars = [
        'DB_HOST' => 'Database Host',
        'DB_USERNAME' => 'Database Username',
        'DB_PASSWORD' => 'Database Password',
        'DB_DATABASE' => 'Database Name'
    ];
    
    // Check required environment variables
    $missingVars = [];
    $envVars = [];
    
    echo display_section('Environment Variables');
    
    foreach ($requiredVars as $var => $description) {
        $value = get_env_var($var);
        $envVars[$var] = $value;
        
        if (empty($value)) {
            $missingVars[$var] = $description;
            echo sprintf("❌ %-20s: %s (MISSING)\n", $var, $description);
        } else {
            $displayValue = $value;
            if (in_array($var, ['DB_PASSWORD', 'DB_PASS', 'PASSWORD'])) {
                $displayValue = str_repeat('*', 8); // Mask passwords
            }
            echo sprintf("✅ %-20s: %s\n", $var, $displayValue);
        }
    }
    
    // Also show optional but useful variables
    $optionalVars = [
        'DB_PORT' => 'Database Port',
        'DB_SSL_CA' => 'SSL Certificate Path',
        'DB_SSL_VERIFY' => 'SSL Verification',
        'APP_ENV' => 'Application Environment',
        'APP_DEBUG' => 'Debug Mode'
    ];
    
    foreach ($optionalVars as $var => $description) {
        $value = get_env_var($var, 'Not set');
        $envVars[$var] = $value;
        
        $displayValue = $value;
        if (in_array($var, ['DB_PASSWORD', 'DB_PASS', 'PASSWORD'])) {
            $displayValue = str_repeat('*', 8);
        } elseif (is_bool($value) || $value === 'true' || $value === 'false') {
            $displayValue = $value ? 'true' : 'false';
        }
        
        echo sprintf("ℹ️  %-20s: %s\n", $var, $displayValue);
    }
    
    if (!empty($missingVars)) {
        $missingList = [];
        foreach ($missingVars as $var => $desc) {
            $missingList[] = "$var ($desc)";
        }
        throw new Exception("Missing required environment variables: " . implode(', ', $missingList));
    }
    
    // Log environment summary (without sensitive data)
    $logVars = mask_sensitive_data($envVars);
    log_message("Environment variables loaded: " . json_encode($logVars, JSON_PRETTY_PRINT));
    
    // Include database configuration
    $dbConfigFile = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'db-config.php';
    log_message("Including database configuration from: $dbConfigFile");
    
    try {
        check_file($dbConfigFile, 'Database configuration file');
        require_once $dbConfigFile;
        
        if (!class_exists('DatabaseConfig')) {
            throw new Exception("DatabaseConfig class not found in $dbConfigFile");
        }
        
        // Test database connection
        echo display_section('Database Connection Test');
        
        // Get database configuration for display
        $dbHost = get_env_var('DB_HOST');
        $dbPort = get_env_var('DB_PORT', '3306');
        $dbName = get_env_var('DB_NAME');
        $dbUser = get_env_var('DB_USER');
        $dbPass = get_env_var('DB_PASSWORD');
        
        echo "Attempting to connect to database...\n";
        echo "- Host: $dbHost\n";
        echo "- Port: $dbPort\n";
        echo "- Database: $dbName\n";
        echo "- Username: $dbUser\n";
        echo "- Password: " . str_repeat('*', 8) . "\n\n";
        
        // Test connection with error handling
        $startTime = microtime(true);
        $maxRetries = 3;
        $retryDelay = 1; // seconds
        $connectionSuccess = false;
        $lastError = null;
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                log_message("Connection attempt $attempt of $maxRetries");
                
                $pdo = DatabaseSSL::getInstance()->getConnection($maxRetries, $retryDelay);
                $connectionTime = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($pdo) {
                    $connectionSuccess = true;
                    echo "✅ Successfully connected to the database in {$connectionTime}ms\n";
                    log_message("Database connection successful");
                    
                    // Get database information
                    try {
                        // Get database version
                        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
                        echo "\n📊 Database Information:\n";
                        echo "- Version: $version\n";
                        
                        // Get database name
                        $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
                        echo "- Current Database: $dbName\n";
                        
                        // Get character set and collation
                        $charset = $pdo->query('SHOW VARIABLES LIKE "character_set_database"')->fetch(PDO::FETCH_ASSOC);
                        $collation = $pdo->query('SHOW VARIABLES LIKE "collation_database"')->fetch(PDO::FETCH_ASSOC);
                        
                        if ($charset) echo "- Character Set: " . $charset['Value'] . "\n";
                        if ($collation) echo "- Collation: " . $collation['Value'] . "\n";
                        
                        // Check SSL status
                        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
                        $sslEnabled = !empty($sslStatus['Value']);
                        
                        echo "\n🔒 SSL/TLS Status: " . ($sslEnabled ? 'Enabled' : 'Disabled') . "\n";
                        if ($sslEnabled) {
                            $sslInfo = [];
                            $sslVars = $pdo->query('SHOW STATUS LIKE "Ssl_%"')->fetchAll(PDO::FETCH_KEY_PAIR);
                            
                            if (isset($sslVars['Ssl_cipher'])) echo "- Cipher: " . $sslVars['Ssl_cipher'] . "\n";
                            if (isset($sslVars['Ssl_version'])) echo "- Version: " . $sslVars['Ssl_version'] . "\n";
                            
                            // Log SSL details for debugging
                            log_message("SSL Connection Details: " . json_encode($sslVars, JSON_PRETTY_PRINT));
                        }
                        
                        // Get list of tables
                        echo "\n📂 Database Tables:\n";
                        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
                        $tableCount = count($tables);
                        
                        echo "- Found $tableCount tables in the database\n";
                        
                        if ($tableCount > 0) {
                            echo "\nFirst 5 tables (alphabetical order):\n";
                            sort($tables);
                            foreach (array_slice($tables, 0, 5) as $table) {
                                // Get row count for each table (with error handling)
                                try {
                                    $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                                    echo sprintf("- %-30s (Rows: %s)\n", $table, number_format($count));
                                } catch (Exception $e) {
                                    echo "- $table (Unable to count rows: " . $e->getMessage() . ")\n";
                                }
                            }
                            
                            if ($tableCount > 5) {
                                echo "... and " . ($tableCount - 5) . " more tables\n";
                            }
                        }
                        
                        // Test a simple query
                        echo "\n🧪 Running test query...\n";
                        try {
                            $testQuery = $pdo->query('SELECT 1+1 AS result, NOW() AS server_time, @@version AS mysql_version');
                            $testResult = $testQuery->fetch(PDO::FETCH_ASSOC);
                            
                            if ($testResult) {
                                echo "✅ Query executed successfully!\n";
                                echo "- 1 + 1 = " . ($testResult['result'] ?? '?') . "\n";
                                echo "- Server Time: " . ($testResult['server_time'] ?? 'N/A') . "\n";
                                echo "- MySQL Version: " . ($testResult['mysql_version'] ?? 'N/A') . "\n";
                            } else {
                                echo "⚠️  Query executed but no results returned\n";
                            }
                        } catch (Exception $e) {
                            echo "❌ Test query failed: " . $e->getMessage() . "\n";
                            log_message("Test query failed: " . $e->getMessage(), 'ERROR');
                        }
                        
                    } catch (Exception $e) {
                        echo "⚠️  Could not retrieve database information: " . $e->getMessage() . "\n";
                        log_message("Error getting database info: " . $e->getMessage(), 'WARNING');
                    }
                    
                    break; // Exit retry loop on success
                }
            } catch (PDOException $e) {
                $lastError = $e;
                $errorCode = $e->getCode();
                $errorMessage = $e->getMessage();
                
                log_message(sprintf(
                    "Connection attempt %d/%d failed: [%d] %s",
                    $attempt,
                    $maxRetries,
                    $errorCode,
                    $errorMessage
                ), 'WARNING');
                
                if ($attempt < $maxRetries) {
                    $waitTime = $retryDelay * $attempt;
                    echo "⚠️  Connection attempt $attempt failed. Retrying in {$waitTime}s...\n";
                    sleep($waitTime);
                }
            }
        }
        
        if (!$connectionSuccess && $lastError) {
            throw $lastError; // Re-throw the last error if all attempts failed
        }
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "\n";
        echo "Error Code: " . $e->getCode() . "\n\n";
        
        // Provide troubleshooting tips based on error code
        switch ($e->getCode()) {
            case 2002:
                echo "TROUBLESHOOTING: Cannot connect to the database server.\n";
                echo "- Check if the database server is running\n";
                echo "- Verify the hostname and port are correct\n";
                echo "- Check your firewall settings\n";
                break;
                
            case 1045:
                echo "TROUBLESHOOTING: Access denied for user.\n";
                echo "- Verify the username and password are correct\n";
                echo "- Check if the user has proper permissions\n";
                echo "- Make sure the user is allowed to connect from this host\n";
                break;
                
            case 1049:
                echo "TROUBLESHOOTING: Database does not exist.\n";
                echo "- Verify the database name is correct\n";
                echo "- Check if the database exists on the server\n";
                echo "- Make sure the user has access to this database\n";
                break;
                
            default:
                echo "TROUBLESHOOTING: General database error.\n";
                echo "- Check your database server logs for more details\n";
                echo "- Verify all connection parameters are correct\n";
                echo "- Try connecting with a database management tool to verify credentials\n";
        }
        
        throw $e;
    }
    
    // Test completed successfully
    echo display_section('Test Completed');
    echo "✅ All tests completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    log_message("Test failed: " . $e->getMessage(), 'ERROR');
    
    // Display the last few lines of the error log
    $errorLog = __DIR__ . '/logs/test_connection_errors.log';
    if (file_exists($errorLog)) {
        echo "\nLast 5 errors from the log:\n";
        $lines = file($errorLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastLines = array_slice($lines, -5);
        echo implode("\n", $lastLines) . "\n";
    }
    
    exit(1);
} finally {
    // Save the output to a log file
    $output = ob_get_clean();
    file_put_contents(__DIR__ . '/logs/test_connection_output.log', $output);
    
    // Output the results
    echo $output;
    
    // If running in a browser, add some basic HTML formatting
    if (php_sapi_name() !== 'cli') {
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
}

// Add a link to return to the previous page if this was accessed via browser
if (php_sapi_name() !== 'cli') {
    echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
}
