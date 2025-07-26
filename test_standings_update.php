&lt;?php
require 'includes/db-config.php';
require 'temp_update_classement.php';

$pdo = DatabaseConfig::getConnection();

// Check if matches are processed
$processed25 = $pdo->query("SELECT COUNT(*) FROM match_processed WHERE match_id=25")->fetchColumn();
$processed26 = $pdo->query("SELECT COUNT(*) FROM match_processed WHERE match_id=26")->fetchColumn();

echo "Match 25 processed: ".($processed25 ? "Yes" : "No")."\n";
echo "Match 26 processed: ".($processed26 ? "Yes" : "No")."\n";

// Manually test with match 25
if (!$processed25) {
    echo "\nTesting standings update with match 25...\n";
    updateClassementForMatch($pdo, [
        'saison' => '2024-2025',
        'competition' => 'tournoi',
        'poule_id' => 1,
        'team_home' => 'ABOUTOU 01 FC', 
        'team_away' => 'NGALWA FC',
        'score_home' => 2,
        'score_away' => 1
    ]);
    
    echo "Standings after update:\n";
    $standings = $pdo->query("SELECT * FROM classement")->fetchAll(PDO::FETCH_ASSOC);
    print_r($standings);
}
