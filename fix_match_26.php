<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    // 1. Vérifier si le match existe et son état actuel
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([26]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Aucun match trouvé avec l'ID 26");
    }
    
    echo "État actuel du match ID 26 :\n";
    echo "- Équipe à domicile: " . $match['team_home'] . "\n";
    echo "- Équipe à l'extérieur: " . $match['team_away'] . "\n";
    echo "- Score domicile: " . ($match['score_home'] ?? 'Non défini') . "\n";
    echo "- Score extérieur: " . ($match['score_away'] ?? 'Non défini') . "\n";
    echo "- Statut: " . $match['status'] . "\n";
    echo "- Saison: " . ($match['saison'] ?? 'Non défini') . "\n";
    echo "- Compétition: " . ($match['competition'] ?? 'Non défini') . "\n";
    echo "- ID de la poule: " . ($match['poule_id'] ?? 'Non défini') . "\n\n";
    
    // 2. Vérifier si les scores sont définis, sinon les définir à 0
    $updates = [];
    $params = [];
    
    if (!isset($match['score_home']) || $match['score_home'] === null) {
        $updates[] = "score_home = 0";
        echo "Le score à domicile n'était pas défini. Défini à 0.\n";
    }
    
    if (!isset($match['score_away']) || $match['score_away'] === null) {
        $updates[] = "score_away = 0";
        echo "Le score à l'extérieur n'était pas défini. Défini à 0.\n";
    }
    
    // 3. Vérifier si le statut est 'ongoing', le changer à 'completed' si nécessaire
    if ($match['status'] === 'ongoing') {
        $updates[] = "status = 'completed'";
        echo "Le statut du match a été changé de 'ongoing' à 'completed'.\n";
    }
    
    // 4. Vérifier si la saison, la compétition et la poule sont définies
    if (empty($match['saison'])) {
        $updates[] = "saison = '2024-2025'";
        echo "La saison n'était pas définie. Définie à '2024-2025'.\n";
    }
    
    if (empty($match['competition'])) {
        $updates[] = "competition = 'tournoi'";
        echo "La compétition n'était pas définie. Définie à 'tournoi'.\n";
    }
    
    if (empty($match['poule_id'])) {
        $updates[] = "poule_id = 1";
        echo "L'ID de la poule n'était pas défini. Défini à 1.\n";
    }
    
    // 5. Mettre à jour le match si nécessaire
    if (!empty($updates)) {
        $sql = "UPDATE matches SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $params[] = 26; // ID du match
        
        $stmt->execute($params);
        
        // Valider la transaction
        $pdo->commit();
        
        echo "\nLe match a été mis à jour avec succès.\n";
        
        // Afficher le nouvel état du match
        $stmt = $pdo->query("SELECT * FROM matches WHERE id = 26");
        $updatedMatch = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\nNouvel état du match ID 26 :\n";
        echo "- Score domicile: " . $updatedMatch['score_home'] . "\n";
        echo "- Score extérieur: " . $updatedMatch['score_away'] . "\n";
        echo "- Statut: " . $updatedMatch['status'] . "\n";
        echo "- Saison: " . $updatedMatch['saison'] . "\n";
        echo "- Compétition: " . $updatedMatch['competition'] . "\n";
        echo "- ID de la poule: " . $updatedMatch['poule_id'] . "\n";
        
        echo "\nLe match peut maintenant être finalisé.\n";
    } else {
        echo "Aucune mise à jour nécessaire. Le match est prêt à être finalisé.\n";
    }
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "Erreur de base de données : " . $e->getMessage() . "\n";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "Détails de l'erreur : " . print_r($e->errorInfo, true) . "\n";
    }
} catch (Exception $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "Erreur : " . $e->getMessage() . "\n";
}
?>
