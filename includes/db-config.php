&lt;?php
class DatabaseConfig {
    private static $pdo = null;
    private static $last_connection_time = null;

    public static function getConnection($maxRetries = 3, $retryDelay = 1) {
        $attempt = 0;
        
        while ($attempt &lt; $maxRetries) {
            try {
                // Reuse connection if recent
                if (self::$pdo !== null &amp;&amp; time() - self::$last_connection_time &lt; 300) {
                    return self::$pdo;
                }
                self::$pdo = null;
                
                // First try DATABASE_URL
                $dbUrl = getenv('DATABASE_URL');
                if ($dbUrl && strpos($dbUrl, 'railway') !== false) {
                    $dbParts = parse_url($dbUrl);
                    if (!$dbParts) {
                        throw new RuntimeException('Invalid database URL format');
                    }
                    $dbHost = $dbParts['host'] ?? 'localhost';
                    $dbPort = $dbParts['port'] ?? 3306;
                    $dbUser = $dbParts['user'] ?? 'root';
                    $dbPass = $dbParts['pass'] ?? '';
                    $dbName = isset($dbParts['path']) ? ltrim($dbParts['path'], '/') : 'railway';
                    $sslEnabled = true;
                }
                // Fallback to Railway MYSQL* variables
                elseif (getenv('MYSQLHOST')) {
                    $dbHost = getenv('MYSQLHOST');
                    $dbPort = getenv('MYSQLPORT') ?: 3306;
                    $dbUser = getenv('MYSQLUSER') ?: 'root';
                    $dbPass = getenv('MYSQLPASSWORD');
                    $dbName = getenv('MYSQLDATABASE') ?: 'railway';
                    $sslEnabled = true;
                }
                // Local development fallback
                else {
                    $dbUrl = 'mysql://root:rootpassword@localhost:3306/ujem';
                    $dbParts = parse_url($dbUrl);
                    if (!$dbParts) {
                        throw new RuntimeException('Invalid database URL format');
                    }
                    $dbHost = $dbParts['host'] ?? 'localhost';
                    $dbPort = $dbParts['port'] ?? 3306;
                    $dbUser = $dbParts['user'] ?? 'root';
                    $dbPass = $dbParts['pass'] ?? '';
                    $dbName = isset($dbParts['path']) ? ltrim($dbParts['path'], '/') : 'ujem';
                    $sslEnabled = false;
                }

                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT => 15, // Increased timeout for Railway
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
                ];

                // Try SSL if enabled and certificate exists
                if ($sslEnabled && file_exists(__DIR__.'/../includes/cacert.pem')) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = __DIR__.'/../includes/cacert.pem';
                    error_log("Attempting SSL connection with certificate");
                } else {
                    error_log("SSL not configured, using standard connection");
                }

                error_log("Connecting to $dbHost as $dbUser");
                self::$pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                self::$pdo-&gt;query('SELECT 1')-&gt;fetchColumn(); // Test connection
                
                error_log("Connected to database $dbName");
                self::$last_connection_time = time();
                return self::$pdo;

            } catch (PDOException $e) {
                error_log("DB Connection Attempt ".($attempt+1)." failed: ".$e-&gt;getMessage());
                if (++$attempt >= $maxRetries) {
                    throw new RuntimeException("Connection failed after $maxRetries attempts: ".$e->getMessage());
                }
                sleep($retryDelay);
            }
        }
    }
}
