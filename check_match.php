<?php
require 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    
    // Récupérer les informations du match
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([26]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($match) {
        echo "Détails du match ID 26 :\n";
        echo "Équipe à domicile: " . ($match['team_home'] ?? 'Non défini') . "\n";
        echo "Équipe à l'extérieur: " . ($match['team_away'] ?? 'Non défini') . "\n";
        echo "Score domicile: " . ($match['score_home'] ?? 'Non défini') . "\n";
        echo "Score extérieur: " . ($match['score_away'] ?? 'Non défini') . "\n";
        echo "Statut: " . ($match['status'] ?? 'Non défini') . "\n";
        echo "Saison: " . ($match['saison'] ?? 'Non défini') . "\n";
        echo "Compétition: " . ($match['competition'] ?? 'Non défini') . "\n";
        echo "ID de la poule: " . ($match['poule_id'] ?? 'Non défini') . "\n";
        
        // Vérifier les champs manquants
        $requiredFields = ['score_home', 'score_away', 'saison', 'competition', 'poule_id'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($match[$field]) && $match[$field] !== '0') {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            echo "\nChamps manquants : " . implode(', ', $missingFields) . "\n";
        } else {
            echo "\nTous les champs requis sont présents.\n";
        }
    } else {
        echo "Aucun match trouvé avec l'ID 26.\n";
    }
    
} catch (PDOException $e) {
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>
