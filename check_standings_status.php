&lt;?php
require 'includes/db-config.php';

$pdo = DatabaseConfig::getConnection();

// Check completed matches
$completed = $pdo->query("SELECT COUNT(*) FROM matches WHERE status='completed'")->fetchColumn();

// Check classement entries  
$classement = $pdo->query("SELECT COUNT(*) FROM classement")->fetchColumn();

// Check match_processed table
$processed_exists = $pdo->query("SHOW TABLES LIKE 'match_processed'")->rowCount() > 0;

echo "Completed matches: $completed\n";
echo "Classement entries: $classement\n"; 
echo "match_processed table exists: ".($processed_exists ? 'Yes' : 'No')."\n";
