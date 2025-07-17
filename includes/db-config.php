<?php
require_once __DIR__.'/../.env';

class DatabaseConfig {
    private static $pdo = null;
    
    public static function getConnection() {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        try {
            $dbUrl = getenv('DATABASE_URL');
            if (!$dbUrl) {
                throw new RuntimeException('DATABASE_URL environment variable not set');
            }

            $url = parse_url($dbUrl);
            if (!$url || !isset($url['host'], $url['user'], $url['path'])) {
                throw new RuntimeException('Invalid DATABASE_URL format');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $url['host'],
                $url['port'] ?? 3306,
                ltrim($url['path'], '/')
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_CA => __DIR__.'/cacert.pem'
            ];

            self::$pdo = new PDO(
                $dsn, 
                $url['user'], 
                $url['pass'] ?? '',
                $options
            );

            return self::$pdo;
            
        } catch (PDOException $e) {
            throw new RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }
}

// Example usage in your application:
// $pdo = DatabaseConfig::getConnection();
