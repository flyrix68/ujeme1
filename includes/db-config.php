<?php
// Enable maximum error reporting
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
class DatabaseConfig {
    private static $pdo = null;
    private static $last_connection_time = null;

    public static function getConnection($maxRetries = 3, $retryDelay = 1) {
        $attempt = 0;
        $lastError = null;
        $startTime = microtime(true);
        
        log_db_event("=== Database Connection Attempt Started ===");
        log_db_event("PHP Version: " . phpversion());
        log_db_event("PDO Available: " . (extension_loaded('pdo') ? 'Yes' : 'No'));
        log_db_event("PDO MySQL Available: " . (extension_loaded('pdo_mysql') ? 'Yes' : 'No'));
        
        while ($attempt < $maxRetries) {
            $attempt++;
            log_db_event("Attempt $attempt of $maxRetries");
            
            try {
                // Reuse connection if recent
                if (self::$pdo !== null && time() - self::$last_connection_time < 300) {
                    try {
                        log_db_event("Testing existing connection...");
                        self::$pdo->query('SELECT 1')->fetchColumn();
                        log_db_event("Existing connection is valid");
                        return self::$pdo;
                    } catch (PDOException $e) {
                        $errorMsg = "Existing connection failed - " . $e->getMessage();
                        log_db_event($errorMsg, 'ERROR');
                        self::$pdo = null;
                    }
                }
                self::$pdo = null;
                
                // Parse Railway DATABASE_URL environment variable
                log_db_event("Checking for DATABASE_URL in environment");
                
                // Check all possible places where the DATABASE_URL might be set
                $dbUrl = null;
                $sources = [
                    '_ENV' => $_ENV['DATABASE_URL'] ?? null,
                    'getenv' => getenv('DATABASE_URL'),
                    '_SERVER' => $_SERVER['DATABASE_URL'] ?? null,
                    'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT'),
                    'MYSQLHOST' => getenv('MYSQLHOST'),
                    'MYSQLUSER' => getenv('MYSQLUSER'),
                    'MYSQLPASSWORD' => getenv('MYSQLPASSWORD'),
                    'MYSQLDATABASE' => getenv('MYSQLDATABASE'),
                    'MYSQLPORT' => getenv('MYSQLPORT')
                ];
                
                // Log each source check
                foreach ($sources as $source => $value) {
                    $status = !empty($value) ? 'FOUND' : 'NOT FOUND';
                    $logValue = !empty($value) ? '(value exists)' : '(empty or not set)';
                    log_db_event("Checking $source: $status $logValue");
                    
                    if (!empty($value) && $source === 'DATABASE_URL') {
                        $dbUrl = $value;
                        log_db_event("Using DATABASE_URL from environment: " . substr($value, 0, 20) . '...');
                        break;
                    }
                }
                
                // If DATABASE_URL not found in environment, try reading directly from .env file
                if (empty($dbUrl)) {
                    log_db_event("DATABASE_URL not found in environment, checking .env file directly");
                    $envFile = __DIR__ . '/../.env';
                    
                    // Check if we have individual MySQL environment variables
                    if (!empty($sources['MYSQLHOST'])) {
                        log_db_event("Using individual MySQL environment variables");
                        $dbConfig = [
                            'host' => $sources['MYSQLHOST'],
                            'port' => $sources['MYSQLPORT'] ?? '3306',
                            'dbname' => $sources['MYSQLDATABASE'] ?? 'railway',
                            'user' => $sources['MYSQLUSER'] ?? 'root',
                            'pass' => $sources['MYSQLPASSWORD'] ?? ''
                        ];
                        $dbUrl = sprintf(
                            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                            $dbConfig['host'],
                            $dbConfig['port'],
                            $dbConfig['dbname']
                        );
                        
                        // Set connection options with error mode and timeout
                        $options = [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_EMULATE_PREPARES => false,
                            PDO::ATTR_TIMEOUT => 5,
                            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                        ];
                        
                        log_db_event("Attempting to connect to database with DSN: " . $dbUrl);
                        
                        try {
                            self::$pdo = new PDO(
                                $dbUrl,
                                $dbConfig['user'],
                                $dbConfig['pass'],
                                $options
                            );
                            
                            self::$last_connection_time = time();
                            log_db_event("Successfully connected to database");
                            return self::$pdo;
                            
                        } catch (PDOException $e) {
                            $errorMsg = sprintf(
                                "Database connection failed (attempt %d/%d): %s",
                                $attempt,
                                $maxRetries,
                                $e->getMessage()
                            );
                            log_db_event($errorMsg, 'ERROR');
                            $lastError = $e;
                            
                            if ($attempt < $maxRetries) {
                                $waitTime = $retryDelay * $attempt;
                                log_db_event("Retrying in {$waitTime} seconds...");
                                sleep($waitTime);
                            }
                            continue;
                        }
                    }
                    
                    if (file_exists($envFile)) {
                        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        foreach ($lines as $line) {
                            // Skip comments and empty lines
                            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                                continue;
                            }
                            
                            list($name, $value) = explode('=', $line, 2);
                            $name = trim($name);
                            $value = trim($value, "'\" \t\n\r\0\x0B");
                            
                            if ($name === 'DATABASE_URL') {
                                $dbUrl = $value;
                                error_log('[DB-CONFIG] Found DATABASE_URL in .env file');
                                break;
                            }
                        }
                    }
                    
                    if (empty($dbUrl)) {
                        error_log('[DB-CONFIG] ERROR: DATABASE_URL not found in environment or .env file');
                        error_log('[DB-CONFIG] Checked _ENV, getenv(), _SERVER, and .env file');
                        error_log('[DB-CONFIG] ==============================================');
                        throw new RuntimeException('DATABASE_URL not found in environment or .env file');
                    }
                }
                
                error_log('[DB-CONFIG] Using DATABASE_URL: ' . substr($dbUrl, 0, 30) . '...');
                
                $dbParts = parse_url($dbUrl);
                if ($dbParts === false) {
                    throw new RuntimeException('Failed to parse DATABASE_URL');
                }

                $dbHost = $dbParts['host'];
                $dbPort = $dbParts['port'] ?? 3306;
                $dbUser = $dbParts['user'] ?? 'root';
                $dbPass = $dbParts['pass'] ?? '';
                $dbName = ltrim($dbParts['path'], '/');

                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 30,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ];
                
                // Only add SSL options if the certificate file exists
                // For Railway, we'll try both with and without SSL
                $certPath = __DIR__ . '/cacert.pem';
                if (file_exists($certPath)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = realpath($certPath);
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                    error_log('Using SSL with certificate: ' . $certPath);
                } else {
                    error_log('SSL certificate not found at: ' . $certPath);
                    // For Railway, we can try without SSL if needed
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                    error_log('Proceeding without SSL certificate verification');
                }
                
                // Add connection timeout and other options
                $options[PDO::ATTR_TIMEOUT] = 10;
                $options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
                $options[PDO::ATTR_EMULATE_PREPARES] = false;
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4";

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
