<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

echo "<h1>Liste des matchs</h1>";

try {
    // Obtenir la connexion à la base de données
    $pdo = DatabaseConfig::getConnection();
    
    // Activer les exceptions PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer tous les matchs
    $stmt = $pdo->query("
        SELECT 
            m.id, 
            m.team_home, 
            m.team_away, 
            m.score_home, 
            m.score_away, 
            m.status,
            m.saison,
            m.competition,
            m.poule_id,
            m.timer_status,
            m.timer_start,
            m.timer_elapsed
        FROM 
            matches m
        ORDER BY 
            m.status, m.id DESC
    ");
    
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($matches) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr>
                <th>ID</th>
                <th>Équipe Domicile</th>
                <th>Score</th>
                <th>Équipe Extérieure</th>
                <th>Statut</th>
                <th>Saison</th>
                <th>Compétition</th>
                <th>Poule</th>
                <th>Timer</th>
                <th>Actions</th>
              </tr>";
        
        foreach ($matches as $match) {
            $statusClass = '';
            switch ($match['status']) {
                case 'pending':
                    $statusClass = 'background-color: #fff3cd;';
                    break;
                case 'ongoing':
                    $statusClass = 'background-color: #d1ecf1;';
                    break;
                case 'completed':
                case 'finished':
                    $statusClass = 'background-color: #d4edda;';
                    break;
                case 'cancelled':
                    $statusClass = 'background-color: #f8d7da;';
                    break;
            }
            
            echo "<tr style='$statusClass'>";
            echo "<td>" . htmlspecialchars($match['id']) . "</td>";
            echo "<td>" . htmlspecialchars($match['team_home']) . "</td>";
            echo "<td>" . htmlspecialchars($match['score_home'] ?? '0') . " - " . htmlspecialchars($match['score_away'] ?? '0') . "</td>";
            echo "<td>" . htmlspecialchars($match['team_away']) . "</td>";
            echo "<td>" . htmlspecialchars($match['status']) . "</td>";
            echo "<td>" . htmlspecialchars($match['saison'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($match['competition'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($match['poule_id'] ?? 'N/A') . "</td>";
            
            // Afficher les informations du timer
            $timerInfo = "";
            if ($match['timer_status'] === 'running') {
                $elapsed = $match['timer_elapsed'] ?? 0;
                $minutes = floor($elapsed / 60);
                $seconds = $elapsed % 60;
                $timerInfo = "En cours: " . sprintf("%02d:%02d", $minutes, $seconds);
            } elseif ($match['timer_status'] === 'paused') {
                $elapsed = $match['timer_elapsed'] ?? 0;
                $minutes = floor($elapsed / 60);
                $seconds = $elapsed % 60;
                $timerInfo = "En pause: " . sprintf("%02d:%02d", $minutes, $seconds);
            } else {
                $timerInfo = "Arrêté";
            }
            
            echo "<td>" . htmlspecialchars($timerInfo) . "</td>";
            
            // Actions
            echo "<td>";
            
            // Bouton de débogage de finalisation
            if (in_array($match['status'], ['pending', 'ongoing'])) {
                echo "<a href='debug_finalize.php?match_id=" . $match['id'] . "' 
                     style='display: inline-block; padding: 5px 10px; background-color: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 2px;'>
                        Tester la finalisation
                     </a>";
            }
            
            // Bouton de visualisation des détails
            echo "<a href='view_match.php?id=" . $match['id'] . "' 
                 style='display: inline-block; padding: 5px 10px; background-color: #17a2b8; color: white; text-decoration: none; border-radius: 4px; margin: 2px;'>
                    Voir détails
                 </a>";
            
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Aucun match trouvé dans la base de données.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Erreur de base de données</h2>";
    echo "<p><strong>Message d'erreur :</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Détails de l'erreur :</strong> " . htmlspecialchars($e->errorInfo[2]) . "</p>";
    }
}

// Afficher un lien pour retourner au tableau de bord
echo "<p style='margin-top: 20px;'><a href='admin/dashboard.php' style='padding: 10px 15px; background-color: #6c757d; color: white; text-decoration: none; border-radius: 4px;'>Retour au tableau de bord</a></p>";
?>
