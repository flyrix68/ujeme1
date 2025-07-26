<?php
// Activer l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

// Fonction pour afficher les en-têtes de section
function section($title) {
    echo "\n\n" . str_repeat("=", 80) . "\n";
    echo "  " . strtoupper($title) . "\n";
    echo str_repeat("=", 80) . "\n\n";
}

// Fonction pour afficher les résultats
function displayResult($title, $data, $isError = false) {
    echo "<div style='margin: 10px 0; padding: 10px; border-left: 4px solid " . ($isError ? '#dc3545' : '#28a745') . "; background-color: #f8f9fa;'>";
    echo "<strong>$title</strong><br>";
    if (is_string($data)) {
        echo $data;
    } elseif (is_array($data) || is_object($data)) {
        echo "<pre>" . htmlspecialchars(print_r($data, true)) . "</pre>";
    } else {
        var_dump($data);
    }
    echo "</div>";
}

echo "<html><head><title>Test de cohérence de la base de données</title>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; }</style></head><body>";
echo "<h1>Test de cohérence de la base de données</h1>";

try {
    // 1. Tester la connexion à la base de données
    section("1. TEST DE CONNEXION À LA BASE DE DONNÉES");
    
    try {
        $pdo = DatabaseConfig::getConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Tester la connexion avec une requête simple
        $stmt = $pdo->query("SELECT CONNECTION_ID() as conn_id, DATABASE() as db_name, VERSION() as db_version");
        $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        displayResult("Connexion réussie :", [
            'ID de connexion' => $dbInfo['conn_id'],
            'Base de données' => $dbInfo['db_name'],
            'Version MySQL' => $dbInfo['db_version']
        ]);
        
        // 2. Vérifier les tables existantes
        section("2. TABLES DISPONIBLES");
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        displayResult("Tables trouvées :", $tables);
        
        // 3. Vérifier la structure des tables clés
        section("3. STRUCTURE DES TABLES CLÉS");
        $keyTables = ['matches', 'classement', 'standings', 'match_logs'];
        
        foreach ($keyTables as $table) {
            if (in_array($table, $tables)) {
                try {
                    $stmt = $pdo->query("DESCRIBE `$table`");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    displayResult("Structure de la table $table :", $columns);
                    
                    // Vérifier les index pour les tables importantes
                    $indexes = $pdo->query("SHOW INDEX FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($indexes)) {
                        $indexSummary = [];
                        foreach ($indexes as $index) {
                            $indexName = $index['Key_name'];
                            if (!isset($indexSummary[$indexName])) {
                                $indexSummary[$indexName] = [
                                    'columns' => [],
                                    'unique' => ($index['Non_unique'] == 0) ? 'Oui' : 'Non',
                                    'type' => $index['Index_type']
                                ];
                            }
                            $indexSummary[$indexName]['columns'][] = $index['Column_name'];
                        }
                        displayResult("Index de la table $table :", $indexSummary);
                    }
                } catch (PDOException $e) {
                    displayResult("Erreur lors de la vérification de la table $table :", $e->getMessage(), true);
                }
            } else {
                displayResult("La table $table n'existe pas dans la base de données.", "", true);
            }
        }
        
        // 4. Vérifier les données de test
        section("4. DONNÉES DE TEST");
        
        // Vérifier les matchs en attente ou en cours
        $matches = $pdo->query("SELECT id, team_home, team_away, score_home, score_away, status, saison, competition, poule_id FROM matches WHERE status IN ('pending', 'ongoing') LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        displayResult("Matchs en attente ou en cours (max 5) :", $matches);
        
        // Vérifier les entrées dans la table classement
        if (in_array('classement', $tables)) {
            $classement = $pdo->query("SELECT DISTINCT saison, competition, poule_id FROM classement LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
            displayResult("Entrées dans la table classement (échantillon) :", $classement);
        }
        
        // 5. Tester la finalisation d'un match
        section("5. TEST DE FINALISATION D'UN MATCH");
        
        if (!empty($matches)) {
            $testMatch = $matches[0];
            displayResult("Test de finalisation pour le match :", $testMatch);
            
            // Vérifier si les scores sont définis
            if ($testMatch['score_home'] === null || $testMatch['score_away'] === null) {
                displayResult("Attention : Les scores du match ne sont pas définis. Impossible de procéder à la finalisation.", "", true);
            } else {
                // Vérifier si les champs requis sont présents
                $requiredFields = ['saison', 'competition', 'poule_id'];
                $missingFields = [];
                foreach ($requiredFields as $field) {
                    if (empty($testMatch[$field]) && $testMatch[$field] !== '0') {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    displayResult("Champs manquants pour la finalisation :", $missingFields, true);
                } else {
                    displayResult("Tous les champs requis sont présents pour la finalisation.", "");
                    
                    // Tester la fonction updateClassementForMatch
                    if (file_exists('temp_update_classement.php')) {
                        require_once 'temp_update_classement.php';
                        
                        $matchData = [
                            'saison' => $testMatch['saison'],
                            'competition' => $testMatch['competition'],
                            'poule_id' => $testMatch['poule_id'],
                            'team_home' => $testMatch['team_home'],
                            'team_away' => $testMatch['team_away'],
                            'score_home' => (int)$testMatch['score_home'],
                            'score_away' => (int)$testMatch['score_away']
                        ];
                        
                        try {
                            $result = updateClassementForMatch($pdo, $matchData);
                            if ($result) {
                                displayResult("La fonction updateClassementForMatch a réussi.", "Le classement a été mis à jour avec succès pour ce match.");
                            } else {
                                displayResult("La fonction updateClassementForMatch a échoué sans lever d'exception.", "Vérifiez les logs d'erreur pour plus de détails.", true);
                            }
                        } catch (Exception $e) {
                            displayResult("Erreur lors de l'exécution de updateClassementForMatch :", $e->getMessage(), true);
                        }
                    } else {
                        displayResult("Le fichier temp_update_classement.php est introuvable.", "", true);
                    }
                }
            }
        } else {
            displayResult("Aucun match en attente ou en cours trouvé pour le test.", "");
        }
        
    } catch (PDOException $e) {
        displayResult("Erreur de connexion à la base de données :", $e->getMessage(), true);
    }
    
} catch (Exception $e) {
    displayResult("Erreur inattendue :", $e->getMessage(), true);
}

echo "</body></html>";
?>
