<?php
// Database configuration with SSL support

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');
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

class DatabaseSSL {
    private static $instance = null;
    private $pdo = null;
    private $lastConnectionTime = null;
    
    // Database configuration with environment variables
    private $config = [
        'host' => '',
        'port' => '',
        'dbname' => '',
        'user' => '',
        'pass' => '',
        'ssl_ca' => '',
        'ssl_verify' => false
    ];
    
    // Load configuration from environment variables
    private function loadConfig() {
        $this->config = [
            'host' => getenv('DB_HOST') ?: 'yamanote.proxy.rlwy.net',
            'port' => getenv('DB_PORT') ?: '58372',
            'dbname' => getenv('DB_DATABASE') ?: 'railway',
            'user' => getenv('DB_USERNAME') ?: 'root',
            'pass' => getenv('DB_PASSWORD') ?: 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu',
            'ssl_ca' => getenv('DB_SSL_CA') ?: __DIR__ . '/cacert.pem',
            'ssl_verify' => getenv('DB_SSL_VERIFY') === 'true' || getenv('DB_SSL_VERIFY') === '1'
        ];
        
        // Log configuration (without sensitive data)
        $logConfig = $this->config;
        $logConfig['pass'] = '***'; // Mask password in logs
        log_db_event('Database configuration loaded: ' . json_encode($logConfig));
    }
    
    // Private constructor to prevent creating multiple instances
    private function __construct() {
        $this->loadConfig();
        $this->connect();
    }
    
    // Get database instance (Singleton pattern)
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Get PDO connection
    public function getConnection() {
        // Reconnect if connection is lost or too old (5 minutes)
        if ($this->pdo === null || (time() - $this->lastConnectionTime > 300)) {
            $this->connect();
        }
        return $this->pdo;
    }
    
    // Establish database connection
    private function connect($maxRetries = 3, $retryDelay = 1) {
        $attempt = 0;
        $connected = false;
        
        log_db_event("=== Database Connection Attempt Started ===");
        
        while ($attempt < $maxRetries && !$connected) {
            $attempt++;
            log_db_event("Attempt $attempt of $maxRetries");
            
            try {
                // Build DSN
                $dsn = sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['dbname']
                );
                
                // Base PDO options
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                // Add SSL configuration if CA certificate exists
                if (!empty($this->config['ssl_ca']) && file_exists($this->config['ssl_ca'])) {
                    log_db_event("Using SSL with CA certificate: " . $this->config['ssl_ca']);
                    
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $this->config['ssl_ca'];
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $this->config['ssl_verify'];
                    
                    if ($this->config['ssl_verify']) {
                        log_db_event("Server certificate verification is enabled");
                    } else {
                        log_db_event("Server certificate verification is disabled");
                    }
                } else {
                    log_db_event("No valid SSL CA certificate provided, proceeding without SSL");
                }
                
                // Create PDO instance
                $this->pdo = new PDO(
                    $dsn,
                    $this->config['user'],
                    $this->config['pass'],
                    $options
                );
                
                // Test the connection
                $this->pdo->query('SELECT 1');
                $this->lastConnectionTime = time();
                $connected = true;
                
                log_db_event("✅ Successfully connected to the database");
                
            } catch (PDOException $e) {
                $errorMsg = sprintf(
                    "Connection attempt %d failed: %s (Code: %s)",
                    $attempt,
                    $e->getMessage(),
                    $e->getCode()
                );
                
                log_db_event($errorMsg, 'ERROR');
                
                // Wait before retrying
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                } else {
                    throw new Exception("Failed to connect to database after $maxRetries attempts: " . $e->getMessage());
                }
            }
        }
    }
    
    // Execute a query with parameters
    public function query($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            log_db_event("Query failed: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    // Get a single row
    public function getRow($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    // Get all rows
    public function getRows($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    // Get a single value
    public function getValue($sql, $params = []) {
        return $this->query($sql, $params)->fetchColumn();
    }
    
    // Insert data and return last insert ID
    public function insert($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $this->query($sql, $data);
        
        return $this->getConnection()->lastInsertId();
    }
    
    // Update data
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach (array_keys($data) as $field) {
            $set[] = "$field = :$field";
        }
        $set = implode(', ', $set);
        
        $sql = "UPDATE $table SET $set WHERE $where";
        return $this->query($sql, array_merge($data, $whereParams))->rowCount();
    }
    
    // Delete data
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params)->rowCount();
    }
}

// Example usage:
/*
try {
    // Get database instance
    $db = DatabaseSSL::getInstance();
    
    // Get a single row
    $user = $db->getRow("SELECT * FROM users WHERE id = :id", ['id' => 1]);
    
    // Get multiple rows
    $posts = $db->getRows("SELECT * FROM posts WHERE status = :status", ['status' => 'published']);
    
    // Insert data
    $newId = $db->insert('users', [
        'username' => 'test',
        'email' => 'test@example.com',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    
    // Update data
    $affected = $db->update('users', 
        ['email' => 'new@example.com'], 
        'id = :id', 
        ['id' => $newId]
    );
    
    // Delete data
    $deleted = $db->delete('users', 'id = :id', ['id' => $newId]);
    
} catch (Exception $e) {
    // Handle error
    echo "Error: " . $e->getMessage();
}
*/
?>
