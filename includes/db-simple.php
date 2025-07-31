<?php
/**
 * Simple Database Configuration
 * This is a basic database connection handler for your application.
 */

class Database {
    private static $instance = null;
    private $pdo;
    
    // Database configuration
    private $host = 'yamanote.proxy.rlwy.net';
    private $port = '58372';
    private $dbname = 'railway';
    private $user = 'root';
    private $pass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu';
    
    private function __construct() {
        try {
            // Build DSN
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
            
            // Basic PDO options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5
            ];
            
            // Try to establish connection
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
            
            // Test the connection
            $this->pdo->query('SELECT 1');
            
        } catch (PDOException $e) {
            // Log error and show user-friendly message
            $this->logError($e);
            throw new Exception('Database connection failed. Please try again later.');
        }
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
        return $this->pdo;
    }
    
    // Simple query execution
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->logError($e);
            throw new Exception('Database query failed.');
        }
    }
    
    // Get single row
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
    
    // Get all rows
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    // Insert data
    public function insert($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($fields) VALUES ($placeholders)";
        $this->query($sql, $data);
        
        return $this->pdo->lastInsertId();
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
    
    // Error logging
    private function logError(PDOException $e) {
        // Log to file
        $logMessage = date('[Y-m-d H:i:s] ') . $e->getMessage() . "\n";
        $logMessage .= "Code: " . $e->getCode() . "\n";
        $logMessage .= "File: " . $e->getFile() . " (Line: " . $e->getLine() . ")\n\n";
        
        $logFile = __DIR__ . '/../logs/db_errors.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}

// Example usage:
/*
try {
    $db = Database::getInstance();
    
    // Get single row
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
