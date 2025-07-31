<?php
require_once __DIR__ . '/includes/db-ssl.php';

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 1. Récupérer toutes les équipes avec leur logo
    $teams = $pdo->query("SELECT team_name, logo_path FROM teams WHERE logo_path IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($teams)) {
        echo "Aucune équipe avec logo trouvée dans la table 'teams'.\n";
        exit;
    }
    
    echo "Mise à jour des logos pour " . count($teams) . " équipes...\n\n";
    
    $updatedCount = 0;
    $notFoundCount = 0;
    
    foreach ($teams as $team) {
        $teamName = $team['team_name'];
        $logoPath = $team['logo_path'];
        
        // Vérifier si l'équipe existe dans la table classement
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM classement WHERE nom_equipe = ?");
        $checkStmt->execute([$teamName]);
        $exists = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
        
        if ($exists) {
            // Mettre à jour le logo
            $updateStmt = $pdo->prepare("UPDATE classement SET logo = ? WHERE nom_equipe = ?");
            $updateStmt->execute([$logoPath, $teamName]);
            
            if ($updateStmt->rowCount() > 0) {
                echo "✓ Logo mis à jour pour: $teamName ($logoPath)\n";
                $updatedCount++;
            } else {
                echo "- Aucun changement pour: $teamName (logo déjà à jour)\n";
            }
        } else {
            echo "- Équipe non trouvée dans le classement: $teamName\n";
            $notFoundCount++;
        }
    }
    
    echo "\nRésumé:\n";
    echo "- Logos mis à jour: $updatedCount\n";
    echo "- Équipes non trouvées dans le classement: $notFoundCount\n";
    
} catch (PDOException $e) {
    echo "Erreur de base de données: " . $e->getMessage() . "\n";
}
?>
