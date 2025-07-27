<?php
// Configuration de l'en-tête pour le format JSON
header('Content-Type: application/json');

// Connexion à la base de données
require_once 'includes/db.php';

try {
    // Désactiver la mise en cache pour les requêtes AJAX
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // Récupérer les 6 derniers mois avec le nombre de buts par match
    $statsQuery = $pdo->query("
        SELECT 
            DATE_FORMAT(match_date, '%Y-%m') as month,
            COUNT(*) as match_count,
            SUM(score_home + score_away) as total_goals,
            ROUND(SUM(score_home + score_away) / COUNT(*), 2) as avg_goals_per_match
        FROM matches 
        WHERE status = 'termine'
        AND match_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(match_date, '%Y-%m')
        ORDER BY month ASC
    ");
    
    $statsData = $statsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Préparer les données pour le graphique des buts
    $labels = [];
    $goalsData = [];
    
    foreach ($statsData as $row) {
        // Formater le mois (ex: "2023-01" -> "Jan 23")
        $date = DateTime::createFromFormat('Y-m', $row['month']);
        $labels[] = $date->format('M y');
        $goalsData[] = floatval($row['avg_goals_per_match']);
    }
    
    // Si pas de données, on envoie des valeurs par défaut
    if (empty($labels)) {
        $labels = array_map(function($i) {
            $date = new DateTime();
            $date->modify("-$i months");
            return $date->format('M y');
        }, range(0, 5));
        
        $goalsData = array_fill(0, 6, 0);
    }
    
    // Récupérer les statistiques des matchs (victoires domicile/extérieur/matchs nuls)
    $matchStats = [
        'homeWins' => 0,
        'awayWins' => 0,
        'draws' => 0,
        'total' => 0
    ];
    
    $resultStats = $pdo->query("
        SELECT 
            SUM(CASE WHEN score_home > score_away THEN 1 ELSE 0 END) as home_wins,
            SUM(CASE WHEN score_away > score_home THEN 1 ELSE 0 END) as away_wins,
            SUM(CASE WHEN score_home = score_away THEN 1 ELSE 0 END) as draws,
            COUNT(*) as total
        FROM matches 
        WHERE status = 'termine'
    ")->fetch(PDO::FETCH_ASSOC);
    
    if ($resultStats && $resultStats['total'] > 0) {
        $matchStats['homeWins'] = round(($resultStats['home_wins'] / $resultStats['total']) * 100);
        $matchStats['awayWins'] = round(($resultStats['away_wins'] / $resultStats['total']) * 100);
        $matchStats['draws'] = round(($resultStats['draws'] / $resultStats['total']) * 100);
        $matchStats['total'] = (int)$resultStats['total'];
    }
    
    // Récupérer le nombre total de buts marqués
    $totalGoals = $pdo->query("SELECT SUM(score_home + score_away) as total FROM matches WHERE status = 'termine'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Récupérer le nombre moyen de buts par match
    $avgGoals = $pdo->query("SELECT ROUND(AVG(score_home + score_away), 2) as avg_goals FROM matches WHERE status = 'termine'")->fetch(PDO::FETCH_ASSOC)['avg_goals'] ?? 0;
    
    // Récupérer le match avec le plus de buts
    $highestScoringMatch = $pdo->query("
        SELECT 
            team_home, 
            team_away, 
            score_home, 
            score_away,
            (score_home + score_away) as total_goals
        FROM matches 
        WHERE status = 'termine'
        ORDER BY total_goals DESC, match_date DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer le meilleur buteur
    $topScorer = $pdo->query("
        SELECT 
            p.nom as player_name,
            t.nom as team_name,
            COUNT(*) as goals
        FROM buts b
        JOIN joueurs p ON b.id_joueur = p.id
        JOIN equipes t ON p.id_equipe = t.id
        GROUP BY b.id_joueur
        ORDER BY goals DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Préparer les données à renvoyer
    $response = [
        'success' => true,
        'goalsData' => [
            'labels' => $labels,
            'data' => $goalsData
        ],
        'matchStats' => [
            'homeWins' => $matchStats['homeWins'],
            'awayWins' => $matchStats['awayWins'],
            'draws' => $matchStats['draws']
        ],
        'summary' => [
            'totalGoals' => (int)$totalGoals,
            'avgGoals' => (float)$avgGoals,
            'highestScoringMatch' => $highestScoringMatch,
            'topScorer' => $topScorer
        ],
        'lastUpdate' => date('Y-m-d H:i:s')
    ];
    
    // Retourner les données au format JSON
    echo json_encode($response);
    
} catch (PDOException $e) {
    // En cas d'erreur, retourner un message d'erreur
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des données statistiques: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Enregistrer l'erreur dans les logs
    error_log('Erreur dans get_stats_data.php: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
}
?>
