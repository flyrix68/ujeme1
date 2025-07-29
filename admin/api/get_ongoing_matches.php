<?php
// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/api_errors.log');

try {
    // Définir les en-têtes CORS
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

    // Vérifier la session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Vérifier l'authentification de l'administrateur
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        throw new Exception('Accès non autorisé', 403);
    }

    // Inclure le fichier de configuration de la base de données
    $dbConfigPath = __DIR__ . '/../../includes/db-config.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception('Fichier de configuration de la base de données introuvable', 500);
    }
    require_once $dbConfigPath;

    // Vérifier si la classe DatabaseConfig existe
    if (!class_exists('DatabaseConfig')) {
        throw new Exception('La classe DatabaseConfig est introuvable', 500);
    }

    // Obtenir une connexion PDO via DatabaseConfig
    try {
        $pdo = DatabaseConfig::getConnection();
        if (!($pdo instanceof PDO)) {
            throw new Exception('Échec de la connexion à la base de données', 500);
        }
    } catch (Exception $e) {
        throw new Exception('Impossible de se connecter à la base de données: ' . $e->getMessage(), 500);
    }

    // Récupérer les matchs en cours
    $query = "
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
    ";
    
    try {
        $stmt = $pdo->prepare($query);
        if (!$stmt->execute()) {
            throw new Exception('Erreur lors de l\'exécution de la requête SQL', 500);
        }
        
        $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Erreur PDO: ' . $e->getMessage());
        throw new Exception('Erreur de base de données: ' . $e->getMessage(), 500);
    }

    // Durée par défaut pour un match de maracana (en secondes)
    $defaultMatchDuration = 50 * 60; // 50 minutes
    $defaultHalfDuration = $defaultMatchDuration / 2; // 25 minutes par mi-temps
    
    // Calculer le temps écoulé pour chaque match
    $currentTime = time();
    foreach ($matches as &$match) {
        // Utiliser la durée du match depuis la base de données ou la valeur par défaut (50 min)
        $matchDuration = (int)($match['timer_duration'] ?? $defaultMatchDuration);
        $halfDuration = $matchDuration / 2; // 25 minutes par défaut pour le maracana
        
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
        
        // Calculer le temps d'affichage en fonction de la mi-temps (format maracana 2x25 min)
        if ($isFirstHalf) {
            // Pour la première mi-temps, on ne dépasse pas la durée de la mi-temps (25 min)
            $maxElapsed = $halfDuration; // Pas de temps additionnel pour le maracana
            
            if ($elapsed > $maxElapsed) {
                $elapsed = $maxElapsed;
            }
            
            $displayMinutes = floor($elapsed / 60);
            $displaySeconds = $elapsed % 60;
            $match['half'] = '1ère';
            $match['max_minutes'] = floor($maxElapsed / 60);
            
        } elseif ($isSecondHalf) {
            // Pour la seconde mi-temps, on commence à 25 min et on ne dépasse pas 50 min
            $firstHalfDuration = $halfDuration; // 25 minutes pour la première mi-temps
            $maxElapsed = $halfDuration; // 25 minutes pour la seconde mi-temps (pas de temps additionnel)
            
            // On ajuste le temps écoulé pour la seconde mi-temps (on soustrait la durée de la première mi-temps)
            $elapsed = max(0, $elapsed - $firstHalfDuration);
            
            if ($elapsed > $maxElapsed) {
                $elapsed = $maxElapsed;
            }
            
            // Affichage du temps total (25 + temps écoulé en seconde mi-temps)
            $displayMinutes = 25 + floor($elapsed / 60);
            $displaySeconds = $elapsed % 60;
            $match['half'] = '2ème';
            $match['max_minutes'] = 50; // 25 + 25 minutes
        } elseif ($isHalfTime) {
            // Pendant la mi-temps, on affiche 25:00 (fin de la première mi-temps)
            $displayMinutes = 25; // 25 minutes pour la première mi-temps
            $displaySeconds = 0;
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

    // Simplifier la réponse pour le minuteur
    $response = [
        'success' => true,
        'matches' => array_map(function($match) {
            return [
                'id' => $match['id'],
                'home_team' => $match['home_team'],
                'away_team' => $match['away_team'],
                'score_home' => $match['score_home'],
                'score_away' => $match['score_away'],
                'timer_start' => strtotime($match['timer_start'] ?? 'now'),
                'timer_elapsed' => (int)($match['timer_elapsed'] ?? 0),
                'match_duration' => (int)($match['timer_duration'] ?? $defaultMatchDuration), // 50 minutes par défaut pour le maracana
                'timer_status' => $match['timer_status'] ?? 'not_started',
                'first_half_duration' => (int)($match['first_half_duration'] ?? $defaultHalfDuration), // 25 minutes par défaut
                'second_half_duration' => (int)($match['second_half_duration'] ?? $defaultHalfDuration) // 25 minutes par défaut
            ];
        }, $matches),
        'timestamp' => time()
    ];
    
    // Envoyer la réponse JSON
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    // Erreur de base de données
    error_log('Erreur PDO: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données',
        'debug' => (ENVIRONMENT === 'development') ? $e->getMessage() : null
    ]);
    
} catch (Exception $e) {
    // Autres erreurs
    $statusCode = $e->getCode() ?: 500;
    http_response_code($statusCode);
    error_log('Erreur API: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $statusCode
    ]);
    
} finally {
    // S'assurer qu'aucune sortie supplémentaire n'est envoyée
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
    exit();
}
