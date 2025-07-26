&lt;?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Get teams table structure
    echo "Teams table structure:\n";
    $structure = $pdo->query('DESCRIBE teams')->fetchAll(PDO::FETCH_ASSOC);
    print_r($structure);
    
    // Get team records with logo paths
    echo "\n\nTeam records with logo paths:\n"; 
    $teams = $pdo->query('SELECT id, team_name, logo_path FROM teams')->fetchAll(PDO::FETCH_ASSOC);
    print_r($teams);
    
    // List logo files in uploads/logos
    echo "\nLogo files in uploads/logos/:\n";
    $files = scandir(__DIR__.'/../uploads/logos/');
    print_r(array_diff($files, ['.', '..']));
    
} catch(PDOException $e) {
    die("Database error: ".$e->getMessage());
}
