<?php
// Autoriser les requêtes depuis le même domaine
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Répondre immédiatement aux requêtes OPTIONS (prévol)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
require_once '../../includes/db-config.php';

// Vérifier l'authentification de l'administrateur
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

try {
    $matches = $pdo->query("
        SELECT 
            id,
            team_home,
            team_away,
            score_home,
            score_away,
            timer_start,
            timer_paused,
            timer_elapsed,
            status,
            first_half_duration,
            second_half_duration
        FROM matches 
        WHERE status IN ('ongoing', 'paused')
        ORDER BY 
            CASE 
                WHEN status = 'ongoing' THEN 0
                WHEN status = 'paused' THEN 1
                ELSE 2
            END,
            match_date ASC, 
            match_time ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Calculer le temps écoulé pour chaque match
    $currentTime = time();
    foreach ($matches as &$match) {
        $elapsed = (int)$match['timer_elapsed'];
        
        if ($match['status'] === 'ongoing' && $match['timer_start'] !== null) {
            $elapsed += ($currentTime - strtotime($match['timer_start']));
        }
        
        $match['current_elapsed'] = $elapsed;
        unset($match['timer_start']); // Ne pas exposer cette information
    }
    unset($match); // Casser la référence

    echo json_encode(['success' => true, 'matches' => $matches]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
