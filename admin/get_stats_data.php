<?php
// Set headers for JSON response
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

// Fallback data for when database is unavailable or empty
$fallbackData = [
    'success' => true,
    'message' => 'Using fallback data',
    'data' => [
        'recentMatches' => [
            [
                'id' => 1,
                'team_home' => 'Team A', 
                'team_away' => 'Team B',
                'score_home' => 2,
                'score_away' => 1,
                'match_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'status' => 'termine'
            ],
            [
                'id' => 2,
                'team_home' => 'Team C',
                'team_away' => 'Team D',
                'score_home' => 0, 
                'score_away' => 0,
                'match_date' => date('Y-m-d H:i:s', strtotime('-2 days')),
                'status' => 'termine'
            ]
        ],
        'stats' => [
            'total_matches' => 2,
            'completed_matches' => 2,
            'total_goals' => 3,
            'avg_goals_per_match' => 1.5
        ],
        'lastUpdated' => getCurrentTimestamp()
    ]
];

try {
    require_once __DIR__ . '/../includes/db-config.php';
    
    // Get database connection
    $pdo = DatabaseSSL::getInstance()->getConnection();
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 5);
    $pdo->query('SELECT 1')->fetchColumn(); // Test connection

    // Check if matches table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'matches'")->rowCount() > 0;
    
    $recentMatches = [];
    $matchStats = [];
    
    if ($tableExists) {
        // Get recent matches
        $matchesQuery = $pdo->query(
            "SELECT id, team_home, team_away, score_home, score_away, match_date, status 
             FROM matches WHERE status = 'termine'
             ORDER BY match_date DESC LIMIT 5"
        );
        $recentMatches = $matchesQuery ? $matchesQuery->fetchAll(PDO::FETCH_ASSOC) : [];
        
        // Get match statistics
        $statsQuery = $pdo->query(
            "SELECT COUNT(*) as total_matches,
                    SUM(CASE WHEN status = 'termine' THEN 1 ELSE 0 END) as completed_matches,
                    SUM(score_home + score_away) as total_goals,
                    ROUND(AVG(score_home + score_away), 2) as avg_goals_per_match 
             FROM matches WHERE status = 'termine'"
        );
        $matchStats = $statsQuery ? $statsQuery->fetch(PDO::FETCH_ASSOC) : [];
    }

    // Use database data if available, otherwise fallback
    if (!empty($recentMatches) && !empty($matchStats)) {
        $response = [
            'success' => true,
            'message' => 'Tournament data retrieved successfully',
            'data' => [
                'recentMatches' => $recentMatches,
                'stats' => $matchStats,
                'lastUpdated' => getCurrentTimestamp()
            ]
        ];
    } else {
        $response = $fallbackData;
        $response['message'] = 'No match data found - using fallback';
    }

} catch (Exception $e) {
    $response = $fallbackData;
    $response['error'] = $e->getMessage();
    $response['message'] = 'Database error - using fallback data';
    error_log('Database error in get_stats_data.php: ' . $e->getMessage());
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
