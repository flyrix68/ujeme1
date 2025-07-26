&lt;?php
require 'includes/db-config.php';

$output = [];

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Get teams table structure
    $output[] = "Teams table structure:";
    $structure = $pdo->query('DESCRIBE teams')->fetchAll(PDO::FETCH_ASSOC);
    $output[] = print_r($structure, true);
    
    // Get team records with logo paths
    $output[] = "\nTeam records with logo paths:"; 
    $teams = $pdo->query('SELECT id, team_name, logo_path FROM teams')->fetchAll(PDO::FETCH_ASSOC);
    $output[] = print_r($teams, true);
    
    // List logo files
    $output[] = "\nLogo files in uploads/logos/:"; 
    $files = scandir(__DIR__.'/../uploads/logos/');
    $output[] = print_r(array_diff($files, ['.', '..']), true);
    
    file_put_contents('teams_query_output.txt', implode("\n", $output));
    
} catch(PDOException $e) {
    file_put_contents('teams_query_output.txt', "Database error: ".$e->getMessage());
}
