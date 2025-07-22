&lt;?php
class DatabaseConfig {
    private static $pdo = null;
    private static $last_connection_time = null;

    public static function getTeamLogo($teamName) {
        $logoDir = $_SERVER['DOCUMENT_ROOT'].'/assets/img/teams/';
        $logoFile = strtolower(str_replace(' ', '-', $teamName)) . '.png';
        $defaultLogo = $logoDir.'default.png';
        
        if (file_exists($logoDir.$logoFile)) {
            return '/assets/img/teams/'.$logoFile;
        }
        
        return file_exists($defaultLogo) 
            ? '/assets/img/teams/default.png'
            : '';
    }

    public static function getConnection($maxRetries = 3, $retryDelay = 1) {
        $attempt = 0;
        
        while ($attempt &lt; $maxRetries) {
            try {
                if (self::$pdo !== null &amp;&amp; time() - self::$last_connection_time &lt; 300) {
                    return self::$pdo;
                }
                self::$pdo = null;
                
                $dbUrl = getenv('DATABASE_URL');
                error_log("DB Connection - DATABASE_URL: " . ($dbUrl ? "set" : "not set"));
                
                if (!$dbUrl) {
                    if (getenv('RAILWAY_ENVIRONMENT') === 'production') {
                        throw new RuntimeException('DATABASE_URL is required in production');
                    }
                    $dbUrl = 'mysql://root:rootpassword@localhost:3306/ujem';
                }

                $dbParts = parse_url($dbUrl);
                if (!$dbParts) {
                    throw new RuntimeException('Invalid DATABASE_URL format');
                }

                $dbHost = $dbParts['host'] ?? 'localhost';
                $dbPort = $dbParts['port'] ?? 3306;
                $dbUser = $dbParts['user'] ?? 'root';
                $dbPass = $dbParts['pass'] ?? '';
                $dbName = isset($dbParts['path']) ? ltrim($dbParts['path'], '/') : 'ujem';

                $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";

                $options = [
                    PDO::ATTR_ERRMODE =&gt; PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE =&gt; PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT =&gt; 2, // Reduced timeout for Railway health checks
                    PDO::MYSQL_ATTR_INIT_COMMAND =&gt; "SET NAMES utf8mb4",
                    PDO::MYSQL_ATTR_SSL_CA =&gt; __DIR__.'/../includes/cacert.pem'
                ];

                self::$pdo = new PDO($dsn, $dbUser, $dbPass, $options);
                self::$pdo-&gt;query('SELECT 1')-&gt;fetchColumn();
                
                error_log("Database connected successfully to host: $dbHost");
                self::$last_connection_time = time();
                return self::$pdo;

            } catch (PDOException $e) {
                error_log("DB Connection Attempt ".($attempt+1)." failed: ".$e-&gt;getMessage());
                if (++$attempt &gt;= $maxRetries) {
                    return null;
                }
                sleep($retryDelay);
            }
        }
        return null;
    }
}
