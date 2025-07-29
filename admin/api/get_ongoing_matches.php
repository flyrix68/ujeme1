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
            m.id,
            m.team_home,
            m.team_away,
            m.score_home,
            m.score_away,
            m.timer_start,
            m.timer_paused,
            m.timer_elapsed,
            m.status,
            m.timer_status,
            m.first_half_duration,
            m.second_half_duration,
            m.first_half_extra,
            m.second_half_extra,
            m.timer_duration,
            m.match_date,
            m.match_time,
            th.logo as home_logo,
            ta.logo as away_logo
        FROM matches m
        LEFT JOIN teams th ON m.team_home = th.team_name
        LEFT JOIN teams ta ON m.team_away = ta.team_name
        WHERE m.status IN ('ongoing', 'paused')
        ORDER BY 
            CASE 
                WHEN m.status = 'ongoing' THEN 0
                WHEN m.status = 'paused' THEN 1
                ELSE 2
            END,
            m.match_date ASC, 
            m.match_time ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Calculer le temps écoulé pour chaque match
    $currentTime = time();
    foreach ($matches as &$match) {
        $elapsed = (int)$match['timer_elapsed'];
        $displayMinutes = 0;
        $displaySeconds = 0;
        $isFirstHalf = ($match['timer_status'] ?? '') === 'first_half';
        $isSecondHalf = ($match['timer_status'] ?? '') === 'second_half';
        $isHalfTime = ($match['timer_status'] ?? '') === 'half_time';
        $isEnded = ($match['status'] ?? '') === 'completed';
        
        if ($match['status'] === 'ongoing' && $match['timer_start'] !== null) {
            $elapsed += ($currentTime - strtotime($match['timer_start']));
        }
        
        // Calculer le temps d'affichage en fonction de la mi-temps
        if ($isFirstHalf) {
            $halfDuration = ($match['timer_duration'] ?? 2700) / 2; // 45 minutes par défaut
            $extraTime = (int)($match['first_half_extra'] ?? 0);
            $maxElapsed = $halfDuration + $extraTime;
            
            if ($elapsed > $maxElapsed) {
                $elapsed = $maxElapsed;
            }
            
            $displayMinutes = floor($elapsed / 60);
            $displaySeconds = $elapsed % 60;
            $match['half'] = '1ère';
            $match['max_minutes'] = floor($maxElapsed / 60);
            
        } elseif ($isSecondHalf) {
            $halfDuration = ($match['timer_duration'] ?? 2700) / 2; // 45 minutes par défaut
            $firstHalfDuration = (int)($match['first_half_duration'] ?? $halfDuration);
            $extraTime = (int)($match['second_half_extra'] ?? 0);
            $maxElapsed = $halfDuration + $extraTime;
            
            if ($elapsed > $maxElapsed) {
                $elapsed = $maxElapsed;
            }
            
            $displayMinutes = floor($elapsed / 60) + floor($firstHalfDuration / 60);
            $displaySeconds = $elapsed % 60;
            $match['half'] = '2ème';
            $match['max_minutes'] = floor($maxElapsed / 60) + floor($firstHalfDuration / 60);
        } elseif ($isHalfTime) {
            $displayMinutes = floor(($match['first_half_duration'] ?? $halfDuration) / 60);
            $displaySeconds = ($match['first_half_duration'] ?? $halfDuration) % 60;
            $match['half'] = 'Mi-temps';
        } elseif ($isEnded) {
            $firstHalfDuration = (int)($match['first_half_duration'] ?? $halfDuration);
            $secondHalfDuration = (int)($match['second_half_duration'] ?? $halfDuration);
            $displayMinutes = floor(($firstHalfDuration + $secondHalfDuration) / 60);
            $displaySeconds = ($firstHalfDuration + $secondHalfDuration) % 60;
            $match['half'] = 'Terminé';
        }
        
        // Formatage du temps d'affichage
        $match['current_elapsed'] = $elapsed;
        $match['display_time'] = sprintf('%02d:%02d', $displayMinutes, $displaySeconds);
        $match['is_first_half'] = $isFirstHalf;
        $match['is_second_half'] = $isSecondHalf;
        $match['is_half_time'] = $isHalfTime;
        $match['is_ended'] = $isEnded;
        
        // Nettoyage des données sensibles
        unset($match['timer_start']);
        unset($match['timer_paused']);
    }
    unset($match); // Casser la référence

    echo json_encode(['success' => true, 'matches' => $matches]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
