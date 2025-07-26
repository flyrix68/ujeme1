&lt;?php
require 'includes/db-config.php';
require 'includes/team_function.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Start transaction
    $pdo->beginTransaction();
    
    // Get unprocessed completed matches
    $matches = $pdo->query("
        SELECT m.* 
        FROM matches m
        LEFT JOIN match_processed mp ON m.id = mp.match_id
        WHERE m.status = 'completed' 
        AND mp.match_id IS NULL
        ORDER BY m.match_date, m.match_time
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($matches)) {
        echo "No unprocessed matches found\n";
        exit(0);
    }

    echo "Processing " . count($matches) . " matches...\n";
    
    foreach ($matches as $match) {
        echo "Processing match {$match['id']}: {$match['team_home']} vs {$match['team_away']}\n";
        
        updateClassementForMatch($pdo, [
            'saison' => $match['saison'] ?? '2024-2025',
            'competition' => $match['competition'] ?? 'tournoi',
            'poule_id' => $match['poule_id'] ?? 1,
            'team_home' => $match['team_home'],
            'team_away' => $match['team_away'], 
            'score_home' => $match['score_home'],
            'score_away' => $match['score_away']
        ]);
        
        // Mark match as processed
        $pdo->prepare("INSERT INTO match_processed (match_id) VALUES (?)")
            ->execute([$match['id']]);
    }
    
    $pdo->commit();
    echo "Standings updated successfully\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error updating standings: " . $e->getMessage() . "\n";
}
