<?php
// Configuration de l'en-tête pour le format JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors to users

// Désactiver la mise en cache pour les requêtes AJAX
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Connexion à la base de données
require_once __DIR__ . '/../includes/db-config.php';

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

function getDefaultResponse() {
    $currentMonth = date('M y');
    $lastMonth = date('M y', strtotime('-1 month'));
    $twoMonthsAgo = date('M y', strtotime('-2 months'));
    
    return [
        'success' => true,
        'goalsData' => [
            'labels' => [$twoMonthsAgo, $lastMonth, $currentMonth],
            'data' => [2.5, 3.1, 2.8] // Default average goals
        ],
        'matchStats' => [
            'homeWins' => 45,
            'awayWins' => 30,
            'draws' => 25
        ],
        'summary' => [
            'totalGoals' => 0,
            'avgGoals' => 0,
            'highestScoringMatch' => null,
            'topScorer' => null
        ],
        'isFallbackData' => true
    ];
}

try {
    // Get database connection with error handling
    try {
        $pdo = DatabaseConfig::getConnection(2, 1); // 2 retries, 1 second delay
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        
        // Return default data instead of an error
        $response = getDefaultResponse();
        $response['success'] = false;
        $response['error'] = 'Could not connect to the database';
        $response['debug'] = 'Using fallback data';
        
        sendJsonResponse($response);
    }
    
    // Test the connection with a simple query
    try {
        $pdo->query('SELECT 1')->fetchColumn();
    } catch (PDOException $e) {
        error_log('Database test query failed: ' . $e->getMessage());
        
        // Return default data instead of an error
        $response = getDefaultResponse();
        $response['success'] = false;
        $response['error'] = 'Database test query failed';
        $response['debug'] = 'Using fallback data';
        
        sendJsonResponse($response);
    }
    
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
    // Log the error
    error_log('Database error in get_stats_data.php: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
    
    // Return a sanitized error response
    sendJsonResponse([
        'success' => false,
        'error' => 'Erreur lors de la récupération des données statistiques',
        'debug' => 'An error occurred while fetching statistics. Please try again later.'
    ], 500);
} catch (Exception $e) {
    // Handle any other exceptions
    error_log('Unexpected error in get_stats_data.php: ' . $e->getMessage() . '\n' . $e->getTraceAsString());
    
    sendJsonResponse([
        'success' => false,
        'error' => 'Une erreur inattendue s\'est produite',
        'debug' => 'An unexpected error occurred. Please try again later.'
    ], 500);
}
?>
