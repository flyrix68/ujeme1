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
    
    // Requête pour obtenir les informations du match
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
    $stmt->execute([26]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($match) {
        echo "<h1>Détails du match ID 26</h1>";
        echo "<pre>";
        print_r($match);
        echo "</pre>";
        
        // Vérifier les champs requis pour la finalisation
        $requiredFields = [
            'score_home' => 'Score domicile',
            'score_away' => 'Score extérieur',
            'saison' => 'Saison',
            'competition' => 'Compétition',
            'poule_id' => 'ID de la poule'
        ];
        
        $missingFields = [];
        
        echo "<h2>Vérification des champs requis :</h2>";
        echo "<ul>";
        foreach ($requiredFields as $field => $label) {
            if (empty($match[$field]) && $match[$field] !== '0') {
                echo "<li style='color: red;'>$label: MANQUANT</li>";
                $missingFields[] = $field;
            } else {
                echo "<li style='color: green;'>$label: " . htmlspecialchars($match[$field]) . "</li>";
            }
        }
        echo "</ul>";
        
        if (!empty($missingFields)) {
            echo "<div style='background-color: #ffebee; padding: 15px; border-left: 5px solid #f44336;'>";
            echo "<h3>Erreur de finalisation</h3>";
            echo "<p>Le match ne peut pas être finalisé car les champs suivants sont manquants ou invalides :</p>";
            echo "<ul>";
            foreach ($missingFields as $field) {
                echo "<li>" . $requiredFields[$field] . "</li>";
            }
            echo "</ul>";
            
            // Afficher le formulaire de correction
            echo "<h3>Mettre à jour les informations du match</h3>";
            echo "<form method='post' action='update_match.php'>";
            echo "<input type='hidden' name='match_id' value='26'>";
            
            if (in_array('score_home', $missingFields)) {
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='score_home'>Score domicile:</label>";
                echo "<input type='number' name='score_home' id='score_home' min='0' required>";
                echo "</div>";
            }
            
            if (in_array('score_away', $missingFields)) {
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='score_away'>Score extérieur:</label>";
                echo "<input type='number' name='score_away' id='score_away' min='0' required>";
                echo "</div>";
            }
            
            if (in_array('saison', $missingFields)) {
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='saison'>Saison (ex: 2024-2025):</label>";
                echo "<input type='text' name='saison' id='saison' required>";
                echo "</div>";
            }
            
            if (in_array('competition', $missingFields)) {
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='competition'>Compétition:</label>";
                echo "<input type='text' name='competition' id='competition' required>";
                echo "</div>";
            }
            
            if (in_array('poule_id', $missingFields)) {
                echo "<div style='margin-bottom: 10px;'>";
                echo "<label for='poule_id'>ID de la poule:</label>";
                echo "<input type='number' name='poule_id' id='poule_id' min='1' required>";
                echo "</div>";
            }
            
            echo "<button type='submit' name='update_match'>Mettre à jour le match</button>";
            echo "</form>";
            echo "</div>";
        } else {
            echo "<div style='background-color: #e8f5e9; padding: 15px; border-left: 5px solid #4caf50;'>";
            echo "<h3>Le match peut être finalisé</h3>";
            echo "<p>Tous les champs requis sont présents. Vous pouvez procéder à la finalisation.</p>";
            echo "<form method='post' action='finalize_match.php'>";
            echo "<input type='hidden' name='match_id' value='26'>";
            echo "<button type='submit' name='finalize_match'>Finaliser le match</button>";
            echo "</form>";
            echo "</div>";
        }
        
    } else {
        echo "<h1>Erreur</h1>";
        echo "<p>Aucun match trouvé avec l'ID 26.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h1>Erreur de base de données</h1>";
    echo "<p><strong>Message d'erreur:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<p><strong>Détails de l'erreur SQL:</strong></p>";
        echo "<pre>" . print_r($e->errorInfo, true) . "</pre>";
    }
}

// Afficher un lien pour retourner au tableau de bord
echo "<p><a href='admin/dashboard.php'>&larr; Retour au tableau de bord</a></p>";
?>
