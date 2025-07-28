<?php
if (!class_exists('DatabaseConfig')) {
class DatabaseConfig {
    private static $pdo = null;
    private static $last_connection_time = null;

    public static function getConnection($maxRetries = 3, $retryDelay = 1) {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $maxRetries) {
            try {
                // Reuse connection if recent
                if (self::$pdo !== null && time() - self::$last_connection_time < 300) {
                    // Test connection is still alive
                    try {
                        self::$pdo->query('SELECT 1')->fetchColumn();
                        return self::$pdo;
                    } catch (PDOException $e) {
                        error_log("Existing connection failed - creating new one");
                        self::$pdo = null;
                    }
                }
                self::$pdo = null;
                
                // Use Railway production credentials directly
                $dbHost = 'yamanote.proxy.rlwy.net';
                $dbPort = 58372;
                $dbUser = 'root';
                $dbPass = 'lHrCOmGSvbbiTSntPYLwjlWMuthCRxNu'; 
                $dbName = 'railway';

                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 30,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];
                
                // Only add SSL options if the certificate file exists
                $certPath = __DIR__ . '/cacert.pem';
                if (file_exists($certPath)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = realpath($certPath);
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                } else {
                    error_log('SSL certificate not found at: ' . $certPath);
                    // Try without SSL if certificate is not found
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }

                error_log("Attempting Railway connection to $dbHost:$dbPort");
                self::$pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                
                // Test connection
                if (self::$pdo->query('SELECT 1')->fetchColumn() != 1) {
                    throw new PDOException('Connection test failed');
                }

                error_log("Successfully connected to Railway database");
                self::$last_connection_time = time();
                return self::$pdo;

            } catch (PDOException $e) {
                $lastError = $e->getMessage();
                error_log("Connection attempt $attempt failed: " . $lastError);
                if (++$attempt >= $maxRetries) {
                    throw new RuntimeException("Failed to connect after $maxRetries attempts. Last error: " . $lastError);
                }
                $retrySeconds = $retryDelay * (1 + $attempt); // Exponential backoff
                error_log("Waiting $retrySeconds seconds before retry...");
                sleep($retrySeconds);
            }
        }
    }

    public static function getTeamLogo($teamName) {
        // Nettoyer le nom de l'équipe pour le nom de fichier
        $teamFilename = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', trim($teamName)));
        
        // Chemins à vérifier, dans l'ordre de priorité
        $possiblePaths = [
            '/uploads/logos/' . $teamFilename . '.png',
            '/uploads/logos/' . $teamFilename . '.jpg',
            '/uploads/logos/' . $teamFilename . '.jpeg',
            '/uploads/logos/' . $teamFilename . '.webp',
            '/assets/img/teams/' . $teamFilename . '.png',
            '/assets/img/teams/' . $teamFilename . '.jpg',
            '/assets/img/teams/' . $teamFilename . '.jpeg',
            '/assets/img/teams/' . $teamFilename . '.webp'
        ];
        
        $defaultPath = '/assets/img/teams/default.png';
        
        // Vérifier chaque chemin potentiel
        foreach ($possiblePaths as $logoPath) {
            $fullPath = __DIR__ . '/..' . $logoPath;
            if (file_exists($fullPath)) {
                return $logoPath;
            }
        }
        
        // Si aucun logo n'est trouvé, retourner le logo par défaut
        return $defaultPath;
    }
}
} // Fin de la vérification class_exists
