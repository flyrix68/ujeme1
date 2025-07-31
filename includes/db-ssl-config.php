<?php
// Database configuration with SSL support
class DatabaseConfig {
    private static $pdo = null;
    private static $lastConnectionTime = null;
    
    public static function getConnection() {
        // Reuse existing connection if available and recent
        if (self::$pdo !== null && (time() - self::$lastConnectionTime) < 300) {
            try {
                // Test the connection
                self::$pdo->query('SELECT 1');
                return self::$pdo;
            } catch (PDOException $e) {
                // Connection failed, will create a new one
                self::$pdo = null;
            }
        }
        
        // Load database configuration
        $config = [
            'host' => getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net',
            'port' => getenv('DB_PORT') ?: '58372',
            'dbname' => getenv('DB_DATABASE') ?: 'railway',
            'user' => getenv('DB_USERNAME') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
            'ssl_ca' => getenv('DB_SSL_CA') ?: 'includes/cacert.pem',
            'ssl_verify' => filter_var(getenv('DB_SSL_VERIFY'), FILTER_VALIDATE_BOOLEAN) !== false
        ];
        
        // Build DSN
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['dbname']
        );
        
        // Set PDO options
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_TIMEOUT => 5
        ];
        
        // Configure SSL with strict enforcement
        $caCertPath = __DIR__ . '/' . ltrim($config['ssl_ca'], '/');
        
        // Check if the file exists directly or in the parent directory
        if (!file_exists($caCertPath)) {
            // Try alternative path (one level up)
            $altPath = dirname(__DIR__) . '/' . ltrim($config['ssl_ca'], '/');
            if (file_exists($altPath)) {
                $caCertPath = $altPath;
            } else {
                throw new RuntimeException("SSL CA certificate not found at: " . $caCertPath . " or " . $altPath);
            }
        }
        
        // Force SSL with verification
        $options[PDO::MYSQL_ATTR_SSL_CA] = $caCertPath;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = true;
        $options[PDO::MYSQL_ATTR_SSL_CIPHER] = 'DEFAULT@SECLEVEL=1';
        $dsn .= ';sslmode=VERIFY_IDENTITY';
        
        // Log SSL configuration
        error_log("Enforcing SSL with CA certificate: " . $caCertPath);
        
        // Add MySQL SSL mode setting
        $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci, SESSION sql_mode='NO_ENGINE_SUBSTITUTION', SESSION sql_require_primary_key=1";
        
        try {
            // Create new PDO connection
            self::$pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
            self::$lastConnectionTime = time();
            
            // Verify SSL is being used
            $sslStatus = self::$pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
            if (empty($sslStatus['Value'])) {
                error_log("WARNING: Connection is not using SSL!");
            } else {
                error_log("SSL connection established: " . $sslStatus['Value']);
            }
            
            return self::$pdo;
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
}

// Test the connection when this file is run directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    try {
        $pdo = DatabaseSSL::getInstance()->getConnection();
        echo "✅ Successfully connected to the database with SSL\n";
        
        // Show SSL status
        $sslStatus = $pdo->query('SHOW STATUS LIKE "Ssl_cipher"')->fetch(PDO::FETCH_ASSOC);
        $sslVersion = $pdo->query('SHOW STATUS LIKE "Ssl_version"')->fetch(PDO::FETCH_ASSOC);
        
        echo "- MySQL Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
        echo "- SSL Cipher: " . ($sslStatus['Value'] ?: 'Not in use') . "\n";
        echo "- SSL Version: " . ($sslVersion['Value'] ?: 'Not in use') . "\n";
        
        // List tables
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "\nTables in database (" . count($tables) . "):\n";
        foreach (array_slice($tables, 0, 5) as $table) {
            echo "- $table\n";
        }
        if (count($tables) > 5) {
            echo "... and " . (count($tables) - 5) . " more\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
