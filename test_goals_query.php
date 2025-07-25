<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Test goals query
    $goalsStmt = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
    $goalsStmt->execute([25]);
    
    if ($goals = $goalsStmt->fetchAll()) {
        echo "Buts trouvés pour match ID 25:\n";
        print_r($goals);
    } else {
        echo "Aucun but trouvé pour match ID 25\n";
    }

    // Test cards query
    $cardsStmt = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
    $cardsStmt->execute([25]);
    
    if ($cards = $cardsStmt->fetchAll()) {
        echo "\nCartons trouvés pour match ID 25:\n";
        print_r($cards);
    } else {
        echo "\nAucun carton trouvé pour match ID 25\n";
    }
} catch(Exception $e) {
    echo "ERREUR: " . $e->getMessage();
}
?>
