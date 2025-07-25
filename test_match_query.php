<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    $stmt = $pdo->prepare('SELECT * FROM matches WHERE id = ?');
    $stmt->execute([25]);
    
    if ($match = $stmt->fetch()) {
        echo "Match trouvé (ID 25):\n";
        print_r($match);
    } else {
        echo "Aucun match trouvé avec ID 25\n";
    }
} catch(Exception $e) {
    echo "ERREUR: " . $e->getMessage();
}
?>
