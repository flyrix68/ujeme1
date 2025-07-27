<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

// Obtenir la connexion à la base de données
try {
    $pdo = DatabaseConfig::getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Données de test pour un nouveau match
    $test_match = [
        'team_home' => 'Equipe A',  // Remplacer par des noms d'équipes existants
        'team_away' => 'Equipe B',  // Remplacer par des noms d'équipes existants
        'competition' => 'Test Competition',
        'phase' => 'Phase de groupes',
        'match_date' => date('Y-m-d'),
        'match_time' => date('H:i:s'),
        'venue' => 'Stade de test',
        'score_home' => null,
        'score_away' => null,
        'poule_id' => 1  // Remplacer par un ID de poule existant
    ];
    
    // Afficher les données de test
    echo "<h2>Données de test pour le nouveau match :</h2>";
    echo "<pre>" . print_r($test_match, true) . "</pre>";
    
    // Vérifier si les équipes existent
    $stmt = $pdo->prepare("SELECT team_name FROM teams WHERE team_name IN (?, ?)");
    $stmt->execute([$test_match['team_home'], $test_match['team_away']]);
    $existing_teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Équipes existantes :</h2>";
    echo "<pre>" . print_r($existing_teams, true) . "</pre>";
    
    if (count($existing_teams) !== 2) {
        $missing_teams = array_diff([$test_match['team_home'], $test_match['team_away']], $existing_teams);
        throw new Exception("Les équipes suivantes n'existent pas : " . implode(', ', $missing_teams));
    }
    
    // Déterminer le statut
    $match_datetime = strtotime($test_match['match_date'] . ' ' . $test_match['match_time']);
    $current_datetime = time();
    
    if ($test_match['score_home'] !== null && $test_match['score_away'] !== null) {
        $status = 'completed';
    } elseif ($match_datetime > $current_datetime) {
        $status = 'pending';
    } else {
        $status = 'ongoing';
    }
    
    echo "<h2>Statut déterminé : $status</h2>";
    echo "<p>Date/Heure du match : " . date('Y-m-d H:i:s', $match_datetime) . "</p>";
    echo "<p>Date/Heure actuelle : " . date('Y-m-d H:i:s', $current_datetime) . "</p>";
    
    // Insérer le match de test
    $sql = "INSERT INTO matches (
        competition, phase, match_date, match_time, 
        team_home, team_away, venue, score_home, score_away, status, poule_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $params = [
        $test_match['competition'],
        $test_match['phase'],
        $test_match['match_date'],
        $test_match['match_time'],
        $test_match['team_home'],
        $test_match['team_away'],
        $test_match['venue'],
        $test_match['score_home'],
        $test_match['score_away'],
        $status,
        $test_match['poule_id']
    ];
    
    echo "<h2>Requête SQL :</h2>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    echo "<h2>Paramètres :</h2>";
    echo "<pre>" . print_r($params, true) . "</pre>";
    
    // Exécuter la requête
    $result = $stmt->execute($params);
    
    if ($result) {
        $match_id = $pdo->lastInsertId();
        echo "<div class='alert alert-success'>Match ajouté avec succès ! ID du match : $match_id</div>";
        
        // Afficher le match ajouté
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$match_id]);
        $added_match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h2>Match ajouté :</h2>";
        echo "<pre>" . print_r($added_match, true) . "</pre>";
    } else {
        echo "<div class='alert alert-danger'>Erreur lors de l'ajout du match.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Erreur PDO : " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Erreur : " . $e->getMessage() . "</div>";
}

// Lien vers le tableau de bord
echo "<div class='mt-4'><a href='admin/dashboard.php' class='btn btn-primary'>Voir le tableau de bord</a></div>";
?>
