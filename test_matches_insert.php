<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

// Fonction pour afficher les messages formatés
function print_message($message, $type = 'info') {
    $colors = [
        'success' => 'green',
        'error' => 'red',
        'info' => 'blue',
        'warning' => 'orange'
    ];
    $color = $colors[$type] ?? 'black';
    echo "<div style='color: $color; margin: 10px 0; padding: 10px; border: 1px solid $color; border-radius: 4px;'>";
    echo htmlspecialchars($message);
    echo "</div>\n";
}

// Fonction pour ajouter un match de test
function add_test_match($pdo, $team_home, $team_away, $competition, $phase, $match_date, $match_time, $score_home, $score_away, $status) {
    try {
        $sql = "INSERT INTO matches (
            competition, phase, match_date, match_time, 
            team_home, team_away, venue, score_home, score_away, status, poule_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $venue = 'Stade de test';
        $poule_id = 1; // ID de poule par défaut
        
        $stmt = $pdo->prepare($sql);
        $params = [
            $competition,
            $phase,
            $match_date,
            $match_time,
            $team_home,
            $team_away,
            $venue,
            $score_home,
            $score_away,
            $status,
            $poule_id
        ];
        
        $result = $stmt->execute($params);
        
        if ($result) {
            $match_id = $pdo->lastInsertId();
            print_message("Match ajouté avec succès ! ID: $match_id, Statut: $status", 'success');
            return $match_id;
        } else {
            print_message("Erreur lors de l'ajout du match", 'error');
            return false;
        }
    } catch (PDOException $e) {
        print_message("Erreur PDO: " . $e->getMessage(), 'error');
        return false;
    }
}

// Obtenir la connexion à la base de données
try {
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Test d'ajout de matchs avec différents statuts</h2>";
    
    // Données de test pour les équipes (remplacer par des équipes existantes dans votre base de données)
    $teams = [
        ['Équipe A', 'Équipe B'],
        ['Équipe C', 'Équipe D'],
        ['Équipe E', 'Équipe F']
    ];
    
    // 1. Match à venir (pending)
    $future_date = date('Y-m-d', strtotime('+2 days'));
    $future_time = '20:00:00';
    print_message("Ajout d'un match à venir (statut: pending)", 'info');
    $pending_match_id = add_test_match(
        $pdo, 
        $teams[0][0], $teams[0][1], 
        'Ligue des Champions', 'Phase de groupes',
        $future_date, $future_time,
        null, null, // scores null
        'pending' // statut forcé à pending
    );
    
    // 2. Match en cours (ongoing)
    $current_date = date('Y-m-d');
    $current_time = date('H:i:s');
    print_message("Ajout d'un match en cours (statut: ongoing)", 'info');
    $ongoing_match_id = add_test_match(
        $pdo, 
        $teams[1][0], $teams[1][1], 
        'Ligue 1', 'Journée 10',
        $current_date, $current_time,
        1, 1, // scores 1-1
        'ongoing' // statut forcé à ongoing
    );
    
    // 3. Match terminé (completed)
    $past_date = date('Y-m-d', strtotime('-1 day'));
    $past_time = '18:30:00';
    print_message("Ajout d'un match terminé (statut: completed)", 'info');
    $completed_match_id = add_test_match(
        $pdo, 
        $teams[2][0], $teams[2][1], 
        'Coupe de France', '16ème de finale',
        $past_date, $past_time,
        2, 0, // scores 2-0
        'completed' // statut forcé à completed
    );
    
    // Afficher les matchs ajoutés
    echo "<h3>Récapitulatif des matchs ajoutés :</h3>";
    echo "<ul>";
    echo "<li>Match à venir (pending) - ID: " . ($pending_match_id ?: 'échec') . " - $future_date $future_time</li>";
    echo "<li>Match en cours (ongoing) - ID: " . ($ongoing_match_id ?: 'échec') . " - $current_date $current_time</li>";
    echo "<li>Match terminé (completed) - ID: " . ($completed_match_id ?: 'échec') . " - $past_date $past_time</li>";
    echo "</ul>";
    
    // Lien vers le tableau de bord
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='admin/dashboard.php' class='btn btn-primary'>Vérifier dans le tableau de bord</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    print_message("Erreur de connexion à la base de données: " . $e->getMessage(), 'error');
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
