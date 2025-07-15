<?php
/**
 * Configuration de la base de données pour Railway et développement local
 * Version améliorée avec :
 * - Meilleure gestion des erreurs
 * - Connexion persistante
 * - SSL optionnel
 * - Journalisation améliorée
 */

class DatabaseConfig {
    private static $pdo = null;
    
    public static function getConnection() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            $config = self::parseConfig();
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            // Ajout du support SSL si nécessaire
            if (!empty($config['ssl'])) {
                $options[PDO::MYSQL_ATTR_SSL_CA] = $config['ssl_ca'] ?? null;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            self::$pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            
            // Test de connexion
            self::$pdo->query('SELECT 1');
            
            return self::$pdo;
            
        } catch (PDOException $e) {
            self::logError($e, $config ?? []);
            throw new RuntimeException('Database connection failed', 0, $e);
        }
    }
    
    private static function parseConfig() {
        $dbUrl = getenv('DATABASE_URL');
        
        if ($dbUrl) {
            $url = parse_url($dbUrl);
            return [
                'host' => $url['host'],
                'port' => $url['port'] ?? 3306,
                'dbname' => ltrim($url['path'] ?? '', '/'),
                'username' => $url['user'],
                'password' => $url['pass'],
                'ssl' => strpos($dbUrl, 'sslmode=require') !== false
            ];
        }
        
        // Fallback aux variables d'environnement standard
        return [
            'host' => getenv('MYSQLHOST') ?: 'localhost',
            'port' => getenv('MYSQLPORT') ?: 3306,
            'dbname' => getenv('MYSQLDATABASE') ?: 'app_db',
            'username' => getenv('MYSQLUSER') ?: 'root',
            'password' => getenv('MYSQLPASSWORD') ?: '',
            'ssl' => getenv('MYSQL_SSL') === 'true',
            'ssl_ca' => getenv('MYSQL_SSL_CA')
        ];
    }
    
    private static function logError(PDOException $e, array $config) {
        $logMessage = sprintf(
            "[%s] DB Connection Error: %s\nDSN: mysql:host=%s;port=%s;dbname=%s\nUsername: %s\nStack Trace: %s",
            date('Y-m-d H:i:s'),
            $e->getMessage(),
            $config['host'] ?? 'unknown',
            $config['port'] ?? 'unknown',
            $config['dbname'] ?? 'unknown',
            $config['username'] ?? 'unknown',
            $e->getTraceAsString()
        );
        
        error_log($logMessage);
        
        // En production, vous pourriez envoyer cette erreur à un service comme Sentry
        if (getenv('APP_ENV') === 'production') {
            // Intégration avec un service de monitoring
            // Sentry\captureException($e);
        }
    }
}

// Utilisation exemple :
try {
    $pdo = DatabaseConfig::getConnection();
    
    // Votre code utilisant $pdo...
    
} catch (RuntimeException $e) {
    header('HTTP/1.1 503 Service Temporarily Unavailable');
    include 'error-db.html'; // Une page d'erreur dédiée
    exit;
}
?>