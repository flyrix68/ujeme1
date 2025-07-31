<?php
/**
 * Database Configuration Wrapper
 * 
 * This file provides a wrapper around the DatabaseSSL class for backward compatibility.
 * All database operations are now handled by the DatabaseSSL class with SSL support.
 */

// Include the DatabaseSSL class
require_once __DIR__ . '/db-ssl.php';

// Enable error reporting
error_reporting(-1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Ensure logs directory exists
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
    @chmod($logDir, 0777);
}

// Set error log location
ini_set('error_log', $logDir . '/db_errors.log');

// Function to log database events
function log_db_event($message, $level = 'INFO') {
    $logFile = __DIR__ . '/../logs/db_connection.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Log to both error log and dedicated log file
    error_log($message);
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
}

if (!class_exists('DatabaseConfig')) {
    /**
     * DatabaseConfig Class
     * 
     * This class provides a backward-compatible interface for database operations
     * by wrapping the DatabaseSSL class methods.
     */
    class DatabaseConfig {
        private static $db = null;
        
        /**
         * Get a database connection instance
         * 
         * @param int $maxRetries Maximum number of connection retries (for backward compatibility)
         * @param int $retryDelay Delay between retries in seconds (for backward compatibility)
         * @return PDO A PDO database connection
         */
        public static function getConnection($maxRetries = 3, $retryDelay = 1) {
            try {
                // Get the DatabaseSSL instance (singleton)
                if (self::$db === null) {
                    self::$db = DatabaseSSL::getInstance();
                    
                    // Log the connection attempt
                    log_db_event("=== Database Connection Established via DatabaseSSL Wrapper ===");
                    log_db_event("PHP Version: " . phpversion());
                    log_db_event("PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No'));
                    log_db_event("PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No'));
                    
                    // Test the connection
                    $version = self::$db->getValue("SELECT VERSION()");
                    log_db_event("Database Version: $version");
                    log_db_event("Connection successful");
                }
                
                // Return the PDO connection from DatabaseSSL
                return self::$db->getConnection();
                
            } catch (Exception $e) {
                $errorMsg = "Failed to establish database connection: " . $e->getMessage();
                log_db_event($errorMsg, 'ERROR');
                throw new RuntimeException($errorMsg, 0, $e);
            }
        }
        
        /**
         * Get the path to a team's logo
         * 
         * @param string $teamName The name of the team
         * @return string The path to the team's logo or default logo if not found
         */
        public static function getTeamLogo($teamName) {
            // Clean up the team name for the filename
            $teamFilename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', trim($teamName)));
            
            // Directories to search for logos
            $logoDirs = [
                __DIR__ . '/../assets/images/teams/',
                __DIR__ . '/../public/images/teams/'
            ];
            
            // File extensions to try
            $extensions = ['png', 'jpg', 'jpeg', 'svg'];
            
            foreach ($logoDirs as $dir) {
                foreach ($extensions as $ext) {
                    $logoPath = $dir . $teamFilename . '.' . $ext;
                    if (file_exists($logoPath)) {
                        return str_replace('//', '/', str_replace('\\', '/', $logoPath));
                    }
                }
            }
            
            // Return default logo if no specific logo is found
            return '/assets/images/teams/default.png';
        }
        
    }
} // Fin de la vérification class_exists
