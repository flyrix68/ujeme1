<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Log session data for debugging
error_log("Session data in admin/match_details.php: " . print_r($_SESSION, true));

// Verify admin authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Unauthorized access attempt to admin/match_details.php");
    header('Location: ../index.php');
    exit();
}

// Database connection
require_once '../includes/db-config.php';

// Verify database connection
if (!isset($pdo) || $pdo === null) {
    error_log("Critical error: Database connection not established in admin/match_details.php");
    die("A database connection error occurred. Please contact the administrator.");
}

// Get match ID from URL
    $matchId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$matchId) {
        error_log("Invalid or missing match_id in admin/match_details.php");
        $_SESSION['error'] = "ID de match invalide.";
        header('Location: dashboard.php');
        exit();
    }
    
    error_log("Processing match details for ID: " . $matchId);

// Fetch match details
try {
    $stmt = $pdo->prepare("
        SELECT *, UNIX_TIMESTAMP(timer_start) AS timer_start_unix
        FROM matches 
        WHERE id = ?
    ");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        error_log("Match not found for match_id: $matchId");
        $_SESSION['error'] = "Match non trouvé.";
        header('Location: dashboard.php');
        exit();
    }

    // Log match data for debugging
    error_log("Match data for ID $matchId: " . print_r($match, true));
} catch (PDOException $e) {
    error_log("Error fetching match details: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du chargement des détails du match.";
    header('Location: dashboard.php');
    exit();
}

    // Fetch goals - suppress error messages if none found
    $goals = [];
    try {
        $goalsStmt = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
        $goalsStmt->execute([$matchId]);
        $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching goals: " . $e->getMessage());
    }

    // Fetch cards - suppress error messages if none found  
    $cards = [];
    try {
        $cardsStmt = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
        $cardsStmt->execute([$matchId]);
        $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching cards: " . $e->getMessage());
    }

// Calculate half duration
$timerDuration = $match['timer_duration'] ?? 5400; // Default 90 minutes
$halfDuration = floor($timerDuration / 2); // e.g., 2700 seconds (45 minutes)
$firstHalfDuration = $match['first_half_duration'] ?? $halfDuration;
$firstHalfEndMinute = floor($firstHalfDuration / 60); // Includes extra time

// Validate data for completed matches
if ($match['status'] === 'completed' || $match['timer_status'] === 'ended') {
    if (empty($match['timer_elapsed']) || $match['timer_elapsed'] < $timerDuration) {
        error_log("Warning: timer_elapsed is invalid for completed match ID $matchId: " . ($match['timer_elapsed'] ?? 'null'));
        $match['timer_elapsed'] = $timerDuration; // Fallback to regulation time
    }
    if (empty($match['first_half_duration'])) {
        error_log("Warning: first_half_duration is missing for completed match ID $matchId");
        $firstHalfDuration = $halfDuration;
        $firstHalfEndMinute = floor($halfDuration / 60);
    }
}

// Split goals and cards by half
$firstHalfGoals = array_filter($goals, function($goal) use ($firstHalfEndMinute) {
    return $goal['minute'] <= $firstHalfEndMinute;
});
$secondHalfGoals = array_filter($goals, function($goal) use ($firstHalfEndMinute) {
    return $goal['minute'] > $firstHalfEndMinute;
});
$firstHalfCards = array_filter($cards, function($card) use ($firstHalfEndMinute) {
    return $card['minute'] <= $firstHalfEndMinute;
});
$secondHalfCards = array_filter($cards, function($card) use ($firstHalfEndMinute) {
    return $card['minute'] > $firstHalfEndMinute;
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Match - UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .goal-badge, .card-badge {
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .match-card {
            transition: all 0.3s;
        }
        .match-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .team-display {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .team-display__logos img {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .team-display__score {
            font-size: 3rem;
        }
        .team-display__name {
            font-size: 1.5rem;
            font-weight: bold;
            min-width: 150px;
            text-align: center;
        }
        .timer-display {
            font-family: monospace;
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
        }
        .match-card {
            margin: 2rem auto;
            max-width: 900px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar bg-dark">
                <div class="text-center py-4">
                    <h4 class="text-white">Panel Admin</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_matches.php">
                            <i class="fas fa-calendar-alt me-2"></i>Gestion des matchs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_teams.php">
                            <i class="fas fa-users me-2"></i>Gestion des équipes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_players.php">
                            <i class="fas fa-user me-2"></i>Gestion des joueurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4"><i class="fas fa-info-circle me-2"></i>Détails du Match</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card match-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?= htmlspecialchars($match['team_home']) ?> vs <?= htmlspecialchars($match['team_away']) ?>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($match['phase']) ?></span>
                            </h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                                    <i class="fas fa-futbol me-1"></i> Ajouter un but
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#addCardModal">
                                    <i class="fas fa-yellow-card me-1"></i> Ajouter un carton
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#updateScoreModal">
                                    <i class="fas fa-edit me-1"></i> Modifier le score
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="team-display">
                            <div class="team-display__logos text-center">
                                <?php
                                $home_logo = '../assets/img/teams/' . htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_home']))) . '.png';
                                if (!file_exists($home_logo)) {
                                    $home_logo = '../assets/img/teams/default.png';
                                }
                                ?>
                                <img src="<?= $home_logo ?>" 
                                     alt="<?= htmlspecialchars($match['team_home']) ?>">
                                <div class="team-display__name"><?= htmlspecialchars($match['team_home']) ?></div>
                            </div>
                            
                            <div class="team-display__score text-center">
                                <div class="fw-bold">
                                    <?= $match['score_home'] ?? '0' ?> - <?= $match['score_away'] ?? '0' ?>
                                </div>
                                <div class="timer-display" id="match-timer">
                                    <?php
                                    if ($match['status'] === 'completed' || $match['timer_status'] === 'ended') {
                                        // Show X/X for completed matches
                                        echo gmdate('i:s', $timerDuration) . '/' . gmdate('i:s', $timerDuration);
                                    } else {
                                        // Initial time for ongoing matches
                                        $elapsed = $match['timer_elapsed'] ?? 0;
                                        if ($match['timer_start_unix'] && !$match['timer_paused']) {
                                            $elapsed += time() - $match['timer_start_unix'];
                                        }
                                        if ($elapsed < 0) $elapsed = 0;
                                        $displayMinutes = floor($elapsed / 60);
                                        $displaySeconds = $elapsed % 60;
                                        if ($match['timer_status'] === 'second_half') {
                                            $firstHalfMinutes = floor($halfDuration / 60);
                                            $displayMinutes += $firstHalfMinutes;
                                        }
                                        echo sprintf('%02d:%02d', $displayMinutes, $displaySeconds) . '/' . gmdate('i:s', $timerDuration);
                                    }
                                    ?>
                                </div>
                                <small class="text-muted">
                                    Statut: <?= $match['status'] === 'ongoing' ? 'En cours' : 'Terminé' ?>
                                </small>
                            </div>
                            <div class="team-display__logos text-center">
                                <?php
                                $away_logo = '../assets/img/teams/' . htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_away']))) . '.png';
                                if (!file_exists($away_logo)) {
                                    $away_logo = '../assets/img/teams/default.png';
                                }
                                ?>
                                <img src="<?= $away_logo ?>" 
                                     alt="<?= htmlspecialchars($match['team_away']) ?>">
                                <div class="team-display__name"><?= htmlspecialchars($match['team_away']) ?></div>
                            </div>
                        </div>

                        <!-- Match Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Informations du Match</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Compétition:</strong> <?= htmlspecialchars($match['competition']) ?></li>
                                    <li><strong>Phase:</strong> <?= htmlspecialchars($match['phase']) ?></li>
                                    <li><strong>Date:</strong> <?= date('d/m/Y', strtotime($match['match_date'])) ?></li>
                                    <li><strong>Heure:</strong> <?= date('H:i', strtotime($match['match_time'])) ?></li>
                                    <li><strong>Terrain:</strong> <?= htmlspecialchars($match['venue']) ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Durée du Match</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Durée réglementaire:</strong> <?= gmdate('i:s', $timerDuration) ?></li>
                                    <?php if ($match['status'] === 'completed' || $match['timer_status'] === 'ended'): ?>
                                        <li><strong>1ère mi-temps:</strong> 
                                            <?= gmdate('i:s', $firstHalfDuration) ?> 
                                            <?php if ($match['first_half_extra'] ?? 0 > 0): ?>
                                                (+<?= gmdate('i:s', $match['first_half_extra']) ?>)
                                            <?php endif; ?>
                                        </li>
                                        <li><strong>2ème mi-temps:</strong> 
                                            <?php
                                            $secondHalfTime = ($match['timer_elapsed'] ?? $timerDuration) - $firstHalfDuration;
                                            echo gmdate('i:s', max(0, $secondHalfTime));
                                            ?>
                                            <?php if ($match['second_half_extra'] ?? 0 > 0): ?>
                                                (+<?= gmdate('i:s', $match['second_half_extra']) ?>)
                                            <?php endif; ?>
                                        </li>
                                    <?php else: ?>
                                        <li><strong>Temps écoulé:</strong> <?= gmdate('i:s', $match['timer_elapsed'] ?? 0) ?></li>
                                        <?php if ($firstHalfDuration > 0): ?>
                                            <li><strong>1ère mi-temps:</strong> 
                                                <?= gmdate('i:s', $firstHalfDuration) ?> 
                                                <?php if ($match['first_half_extra'] ?? 0 > 0): ?>
                                                    (+<?= gmdate('i:s', $match['first_half_extra']) ?>)
                                                <?php endif; ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($match['timer_start'] && !$match['timer_paused']): ?>
                                        <li><strong>Timer actif:</strong> Oui</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- First Half Events -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Première Mi-temps</h6>
                            <h6 class="fw-bold">Buteurs</h6>
                            <?php if (empty($firstHalfGoals)): ?>
                                <p class="text-muted">Aucun but marqué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($firstHalfGoals as $goal): ?>
                                        <span class="badge bg-<?= $goal['team'] === 'home' ? 'primary' : 'danger' ?> goal-badge">
                                            <?= htmlspecialchars($goal['player']) ?> (<?= $goal['minute'] ?>')
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <h6 class="fw-bold mt-3">Cartons</h6>
                            <?php if (empty($firstHalfCards)): ?>
                                <p class="text-muted">Aucun carton distribué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($firstHalfCards as $card): ?>
                                        <span class="badge bg-<?= $card['card_type'] === 'yellow' ? 'warning' : ($card['card_type'] === 'red' ? 'danger' : 'info') ?> card-badge">
                                            <?= htmlspecialchars($card['player']) ?> (<?= $card['minute'] ?>' - <?= ucfirst($card['card_type']) ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Second Half Events -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Deuxième Mi-temps</h6>
                            <h6 class="fw-bold">Buteurs</h6>
                            <?php if (empty($secondHalfGoals)): ?>
                                <p class="text-muted">Aucun but marqué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($secondHalfGoals as $goal): ?>
                                        <span class="badge bg-<?= $goal['team'] === 'home' ? 'primary' : 'danger' ?> goal-badge">
                                            <?= htmlspecialchars($goal['player']) ?> (<?= $goal['minute'] ?>')
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <h6 class="fw-bold mt-3">Cartons</h6>
                            <?php if (empty($secondHalfCards)): ?>
                                <p class="text-muted">Aucun carton distribué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($secondHalfCards as $card): ?>
                                        <span class="badge bg-<?= $card['card_type'] === 'yellow' ? 'warning' : ($card['card_type'] === 'red' ? 'danger' : 'info') ?> card-badge">
                                            <?= htmlspecialchars($card['player']) ?> (<?= $card['minute'] ?>' - <?= ucfirst($card['card_type']) ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Match Controls -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour au Dashboard  
                            </a>
                            <?php if ($match['status'] === 'ongoing'): ?>
                                <div>
                                    <button class="btn btn-primary add-time" data-minutes="1">
                                        +1 min
                                    </button>
                                    <button class="btn btn-primary add-time" data-minutes="3">  
                                        +3 min
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer text-muted small">
                        Dernière mise à jour: <?= date('d/m/Y H:i:s') ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Real-time timer for ongoing matches
            // Timer updates via WebSocket
            const socket = new WebSocket('ws://localhost:8080');
            
            socket.onmessage = function(event) {
                const data = JSON.parse
<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Log session data for debugging
error_log("Session data in admin/match_details.php: " . print_r($_SESSION, true));

// Verify admin authentication
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    error_log("Unauthorized access attempt to admin/match_details.php");
    header('Location: ../index.php');
    exit();
}

// Database connection
require_once '../includes/db-config.php';

// Verify database connection
if (!isset($pdo) || $pdo === null) {
    error_log("Critical error: Database connection not established in admin/match_details.php");
    die("A database connection error occurred. Please contact the administrator.");
}

// Get match ID from URL
    $matchId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if (!$matchId) {
        error_log("Invalid or missing match_id in admin/match_details.php");
        $_SESSION['error'] = "ID de match invalide.";
        header('Location: dashboard.php');
        exit();
    }
    
    error_log("Processing match details for ID: " . $matchId);

// Fetch match details
try {
    $stmt = $pdo->prepare("
        SELECT *, UNIX_TIMESTAMP(timer_start) AS timer_start_unix
        FROM matches 
        WHERE id = ?
    ");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$match) {
        error_log("Match not found for match_id: $matchId");
        $_SESSION['error'] = "Match non trouvé.";
        header('Location: dashboard.php');
        exit();
    }

    // Log match data for debugging
    error_log("Match data for ID $matchId: " . print_r($match, true));
} catch (PDOException $e) {
    error_log("Error fetching match details: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du chargement des détails du match.";
    header('Location: dashboard.php');
    exit();
}

    // Fetch goals - suppress error messages if none found
    $goals = [];
    try {
        $goalsStmt = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
        $goalsStmt->execute([$matchId]);
        $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching goals: " . $e->getMessage());
    }

    // Fetch cards - suppress error messages if none found  
    $cards = [];
    try {
        $cardsStmt = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
        $cardsStmt->execute([$matchId]);
        $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching cards: " . $e->getMessage());
    }

// Calculate half duration
$timerDuration = $match['timer_duration'] ?? 5400; // Default 90 minutes
$halfDuration = floor($timerDuration / 2); // e.g., 2700 seconds (45 minutes)
$firstHalfDuration = $match['first_half_duration'] ?? $halfDuration;
$firstHalfEndMinute = floor($firstHalfDuration / 60); // Includes extra time

// Validate data for completed matches
if ($match['status'] === 'completed' || $match['timer_status'] === 'ended') {
    if (empty($match['timer_elapsed']) || $match['timer_elapsed'] < $timerDuration) {
        error_log("Warning: timer_elapsed is invalid for completed match ID $matchId: " . ($match['timer_elapsed'] ?? 'null'));
        $match['timer_elapsed'] = $timerDuration; // Fallback to regulation time
    }
    if (empty($match['first_half_duration'])) {
        error_log("Warning: first_half_duration is missing for completed match ID $matchId");
        $firstHalfDuration = $halfDuration;
        $firstHalfEndMinute = floor($halfDuration / 60);
    }
}

// Split goals and cards by half
$firstHalfGoals = array_filter($goals, function($goal) use ($firstHalfEndMinute) {
    return $goal['minute'] <= $firstHalfEndMinute;
});
$secondHalfGoals = array_filter($goals, function($goal) use ($firstHalfEndMinute) {
    return $goal['minute'] > $firstHalfEndMinute;
});
$firstHalfCards = array_filter($cards, function($card) use ($firstHalfEndMinute) {
    return $card['minute'] <= $firstHalfEndMinute;
});
$secondHalfCards = array_filter($cards, function($card) use ($firstHalfEndMinute) {
    return $card['minute'] > $firstHalfEndMinute;
});
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Match - UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../styles.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .goal-badge, .card-badge {
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .match-card {
            transition: all 0.3s;
        }
        .match-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .team-display {
            display: flex;
            align-items: center;
            gap: 2rem;
        }
        .team-display__logos img {
            width: 100px;
            height: 100px;
            object-fit: contain;
        }
        .team-display__score {
            font-size: 3rem;
        }
        .team-display__name {
            font-size: 1.5rem;
            font-weight: bold;
            min-width: 150px;
            text-align: center;
        }
        .timer-display {
            font-family: monospace;
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
        }
        .match-card {
            margin: 2rem auto;
            max-width: 900px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar bg-dark">
                <div class="text-center py-4">
                    <h4 class="text-white">Panel Admin</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_matches.php">
                            <i class="fas fa-calendar-alt me-2"></i>Gestion des matchs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_teams.php">
                            <i class="fas fa-users me-2"></i>Gestion des équipes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_players.php">
                            <i class="fas fa-user me-2"></i>Gestion des joueurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4"><i class="fas fa-info-circle me-2"></i>Détails du Match</h2>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card match-card mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?= htmlspecialchars($match['team_home']) ?> vs <?= htmlspecialchars($match['team_away']) ?>
                                <span class="badge bg-light text-dark"><?= htmlspecialchars($match['phase']) ?></span>
                            </h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#addGoalModal">
                                    <i class="fas fa-futbol me-1"></i> Ajouter un but
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#addCardModal">
                                    <i class="fas fa-yellow-card me-1"></i> Ajouter un carton
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#updateScoreModal">
                                    <i class="fas fa-edit me-1"></i> Modifier le score
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="team-display">
                            <div class="team-display__logos text-center">
                                <?php
                                $home_logo = '../assets/img/teams/' . htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_home']))) . '.png';
                                if (!file_exists($home_logo)) {
                                    $home_logo = '../assets/img/teams/default.png';
                                }
                                ?>
                                <img src="<?= $home_logo ?>" 
                                     alt="<?= htmlspecialchars($match['team_home']) ?>">
                                <div class="team-display__name"><?= htmlspecialchars($match['team_home']) ?></div>
                            </div>
                            
                            <div class="team-display__score text-center">
                                <div class="fw-bold">
                                    <?= $match['score_home'] ?? '0' ?> - <?= $match['score_away'] ?? '0' ?>
                                </div>
                                <div class="timer-display" id="match-timer">
                                    <?php
                                    if ($match['status'] === 'completed' || $match['timer_status'] === 'ended') {
                                        // Show X/X for completed matches
                                        echo gmdate('i:s', $timerDuration) . '/' . gmdate('i:s', $timerDuration);
                                    } else {
                                        // Initial time for ongoing matches
                                        $elapsed = $match['timer_elapsed'] ?? 0;
                                        if ($match['timer_start_unix'] && !$match['timer_paused']) {
                                            $elapsed += time() - $match['timer_start_unix'];
                                        }
                                        if ($elapsed < 0) $elapsed = 0;
                                        $displayMinutes = floor($elapsed / 60);
                                        $displaySeconds = $elapsed % 60;
                                        if ($match['timer_status'] === 'second_half') {
                                            $firstHalfMinutes = floor($halfDuration / 60);
                                            $displayMinutes += $firstHalfMinutes;
                                        }
                                        echo sprintf('%02d:%02d', $displayMinutes, $displaySeconds) . '/' . gmdate('i:s', $timerDuration);
                                    }
                                    ?>
                                </div>
                                <small class="text-muted">
                                    Statut: <?= $match['status'] === 'ongoing' ? 'En cours' : 'Terminé' ?>
                                </small>
                            </div>
                            <div class="team-display__logos text-center">
                                <?php
                                $away_logo = '../assets/img/teams/' . htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_away']))) . '.png';
                                if (!file_exists($away_logo)) {
                                    $away_logo = '../assets/img/teams/default.png';
                                }
                                ?>
                                <img src="<?= $away_logo ?>" 
                                     alt="<?= htmlspecialchars($match['team_away']) ?>">
                                <div class="team-display__name"><?= htmlspecialchars($match['team_away']) ?></div>
                            </div>
                        </div>

                        <!-- Match Information -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="fw-bold">Informations du Match</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Compétition:</strong> <?= htmlspecialchars($match['competition']) ?></li>
                                    <li><strong>Phase:</strong> <?= htmlspecialchars($match['phase']) ?></li>
                                    <li><strong>Date:</strong> <?= date('d/m/Y', strtotime($match['match_date'])) ?></li>
                                    <li><strong>Heure:</strong> <?= date('H:i', strtotime($match['match_time'])) ?></li>
                                    <li><strong>Terrain:</strong> <?= htmlspecialchars($match['venue']) ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold">Durée du Match</h6>
                                <ul class="list-unstyled">
                                    <li><strong>Durée réglementaire:</strong> <?= gmdate('i:s', $timerDuration) ?></li>
                                    <?php if ($match['status'] === 'completed' || $match['timer_status'] === 'ended'): ?>
                                        <li><strong>1ère mi-temps:</strong> 
                                            <?= gmdate('i:s', $firstHalfDuration) ?> 
                                            <?php if ($match['first_half_extra'] ?? 0 > 0): ?>
                                                (+<?= gmdate('i:s', $match['first_half_extra']) ?>)
                                            <?php endif; ?>
                                        </li>
                                        <li><strong>2ème mi-temps:</strong> 
                                            <?php
                                            $secondHalfTime = ($match['timer_elapsed'] ?? $timerDuration) - $firstHalfDuration;
                                            echo gmdate('i:s', max(0, $secondHalfTime));
                                            ?>
                                            <?php if ($match['second_half_extra'] ?? 0 > 0): ?>
                                                (+<?= gmdate('i:s', $match['second_half_extra']) ?>)
                                            <?php endif; ?>
                                        </li>
                                    <?php else: ?>
                                        <li><strong>Temps écoulé:</strong> <?= gmdate('i:s', $match['timer_elapsed'] ?? 0) ?></li>
                                        <?php if ($firstHalfDuration > 0): ?>
                                            <li><strong>1ère mi-temps:</strong> 
                                                <?= gmdate('i:s', $firstHalfDuration) ?> 
                                                <?php if ($match['first_half_extra'] ?? 0 > 0): ?>
                                                    (+<?= gmdate('i:s', $match['first_half_extra']) ?>)
                                                <?php endif; ?>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ($match['timer_start'] && !$match['timer_paused']): ?>
                                        <li><strong>Timer actif:</strong> Oui</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- First Half Events -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Première Mi-temps</h6>
                            <h6 class="fw-bold">Buteurs</h6>
                            <?php if (empty($firstHalfGoals)): ?>
                                <p class="text-muted">Aucun but marqué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($firstHalfGoals as $goal): ?>
                                        <span class="badge bg-<?= $goal['team'] === 'home' ? 'primary' : 'danger' ?> goal-badge">
                                            <?= htmlspecialchars($goal['player']) ?> (<?= $goal['minute'] ?>')
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <h6 class="fw-bold mt-3">Cartons</h6>
                            <?php if (empty($firstHalfCards)): ?>
                                <p class="text-muted">Aucun carton distribué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($firstHalfCards as $card): ?>
                                        <span class="badge bg-<?= $card['card_type'] === 'yellow' ? 'warning' : ($card['card_type'] === 'red' ? 'danger' : 'info') ?> card-badge">
                                            <?= htmlspecialchars($card['player']) ?> (<?= $card['minute'] ?>' - <?= ucfirst($card['card_type']) ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Second Half Events -->
                        <div class="mb-4">
                            <h6 class="fw-bold">Deuxième Mi-temps</h6>
                            <h6 class="fw-bold">Buteurs</h6>
                            <?php if (empty($secondHalfGoals)): ?>
                                <p class="text-muted">Aucun but marqué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($secondHalfGoals as $goal): ?>
                                        <span class="badge bg-<?= $goal['team'] === 'home' ? 'primary' : 'danger' ?> goal-badge">
                                            <?= htmlspecialchars($goal['player']) ?> (<?= $goal['minute'] ?>')
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <h6 class="fw-bold mt-3">Cartons</h6>
                            <?php if (empty($secondHalfCards)): ?>
                                <p class="text-muted">Aucun carton distribué.</p>
                            <?php else: ?>
                                <div>
                                    <?php foreach ($secondHalfCards as $card): ?>
                                        <span class="badge bg-<?= $card['card_type'] === 'yellow' ? 'warning' : ($card['card_type'] === 'red' ? 'danger' : 'info') ?> card-badge">
                                            <?= htmlspecialchars($card['player']) ?> (<?= $card['minute'] ?>' - <?= ucfirst($card['card_type']) ?>)
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Match Controls -->
                        <div class="d-flex justify-content-between mt-4">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour au Dashboard  
                            </a>
                            <?php if ($match['status'] === 'ongoing'): ?>
                                <div>
                                    <button class="btn btn-primary add-time" data-minutes="1">
                                        +1 min
                                    </button>
                                    <button class="btn btn-primary add-time" data-minutes="3">  
                                        +3 min
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer text-muted small">
                        Dernière mise à jour: <?= date('d/m/Y H:i:s') ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    <!-- Script de mise à jour du minuteur -->
    <script src="js/match-timer.js"></script>

    <!-- Modal Ajouter un but -->
    <div class="modal fade" id="addGoalModal" tabindex="-1" aria-labelledby="addGoalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
</body>
</html>
