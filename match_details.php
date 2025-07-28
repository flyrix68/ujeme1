<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once 'includes/db-config.php';

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();



// Verify database connection
if (!isset($pdo) || $pdo === null) {
    error_log("Critical error: Database connection not established in match_details.php");
    die("A database connection error occurred. Please contact the administrator.");
}

// Get match ID from URL
$matchId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$matchId) {
    error_log("Invalid or missing match_id in match_details.php");
    $_SESSION['error'] = "ID de match invalide.";
    header('Location: matches.php');
    exit();
}

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
        header('Location: matches.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Error fetching match details: " . $e->getMessage());
    $_SESSION['error'] = "Erreur lors du chargement des détails du match.";
    header('Location: matches.php');
    exit();
}

// Fetch goals
try {
    $goalsStmt = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
    $goalsStmt->execute([$matchId]);
    $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching goals: " . $e->getMessage());
    $goals = [];
    $_SESSION['error'] = "Erreur lors du chargement des buts.";
}

// Fetch cards
try {
    $cardsStmt = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
    $cardsStmt->execute([$matchId]);
    $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching cards: " . $e->getMessage());
    $cards = [];
    $_SESSION['error'] = "Erreur lors du chargement des cartons.";
}

// Calculate half duration
$timerDuration = $match['timer_duration'] ?? 5400; // Default 90 minutes
$halfDuration = floor($timerDuration / 2); // e.g., 2700 seconds (45 minutes)
$firstHalfEndMinute = floor(($match['first_half_duration'] ?? $halfDuration) / 60); // Includes extra time

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
    <link rel="stylesheet" href="styles.css">
    <style>
        .goal-badge, .card-badge {
            font-size: 0.8rem;
            margin-right: 5px;
        }
        .match-card {
            transition: all 0.3s;
            margin: 2rem auto;
            max-width: 900px;
        }
        .match-card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .timer-display {
            font-family: monospace;
            font-size: 2rem;
            font-weight: bold;
            color: #dc3545;
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
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <header class="bg-primary text-white py-5">
        <div class="container text-center">
            <h1 class="display-4 fw-bold"><i class="fas fa-futbol me-2"></i>Détails du Match</h1>
            <p class="lead">
                <?= htmlspecialchars($match['team_home']) ?> vs <?= htmlspecialchars($match['team_away']) ?>
            </p>
        </div>
    </header>

    <section class="py-5">
        <div class="container">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="card match-card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <?= htmlspecialchars($match['team_home']) ?> vs <?= htmlspecialchars($match['team_away']) ?>
                        <span class="badge bg-light text-dark float-end"><?= htmlspecialchars($match['phase']) ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="team-display">
                        <div class="team-display__logos text-center">
                            <?php
                            $home_logo = 'assets/img/teams/' . htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_home']))) . '.png';
                            if (!file_exists($home_logo)) {
                                $home_logo = 'assets/img/teams/default.png';
                            }
                            ?>
                            <img src="<?= DatabaseConfig::getTeamLogo($match['team_home']) ?>" 
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
                                    $totalTime = $timerDuration; // Regulation time
                                    echo gmdate('i:s', $totalTime) . '/' . gmdate('i:s', $totalTime);
                                } else {
                                    // Initial time for ongoing matches
                                    $elapsed = $match['timer_elapsed'] ?? 0;
                                    if ($match['timer_start_unix']) {
                                        $elapsed += time() - $match['timer_start_unix'];
                                    }
                                    if ($elapsed < 0) $elapsed = 0;
                                    echo gmdate('i:s', $elapsed) . '/' . gmdate('i:s', $timerDuration);
                                }
                                ?>
                            </div>
                                <small class="text-muted">
                                    Statut: 
                                    <?php if ($match['status'] === 'pending'): ?>
                                        En attente
                                    <?php elseif ($match['status'] === 'ongoing'): ?>
                                        En cours
                                    <?php else: ?>
                                        Terminé
                                    <?php endif; ?>
                                </small>
                        </div>
                        <div class="team-display__logos text-center">
                            <?php
                            $away_logo = 'assets/img/teams/' . htmlspecialchars(strtolower(str_replace(' ', '-', $match['team_away']))) . '.png';
                            if (!file_exists($away_logo)) {
                                $away_logo = 'assets/img/teams/default.png';
                            }
                            ?>
                            <img src="<?= DatabaseConfig::getTeamLogo($match['team_away']) ?>" 
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
                                <li><strong>Temps écoulé:</strong> 
                                    <?php
                                    if ($match['status'] === 'completed' || $match['timer_status'] === 'ended') {
                                        echo gmdate('i:s', $timerDuration);
                                    } else {
                                        echo gmdate('i:s', $match['timer_elapsed']);
                                    }
                                    ?>
                                </li>
                                <li><strong>Durée réglementaire:</strong> <?= gmdate('i:s', $timerDuration) ?></li>
                                <?php if ($match['timer_start']): ?>
                                    <li><strong>Timer actif:</strong> <?= $match['status'] === 'ongoing' ? 'Oui' : 'Non' ?></li>
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

                    <!-- Back Button -->
                    <a href="matches.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour aux Matchs
                    </a>
                </div>
                <div class="card-footer text-muted small">
                    Dernière mise à jour: <?= date('d/m/Y H:i:s') ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

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
            <?php if ($match['status'] === 'ongoing'): ?>
                (function() {
                    var timerElement = document.getElementById('match-timer');
                    var startTime = <?= $match['timer_start_unix'] ?? 'null' ?>;
                    var elapsed = <?= $match['timer_elapsed'] ?? 0 ?>;
                    var duration = <?= $timerDuration ?>;
                    var halfDuration = <?= $halfDuration ?>;
                    var additionalTime = <?= $match['timer_status'] === 'first_half' ? ($match['first_half_extra'] ?? 0) : ($match['second_half_extra'] ?? 0) ?>;
                    var isSecondHalf = <?= $match['timer_status'] === 'second_half' ? 'true' : 'false' ?>;
                    var timerStatus = '<?= $match['timer_status'] ?>';
                    var intervalId = null;

                    function updateTimer() {
                        var now = Math.floor(Date.now() / 1000);
                        var totalSeconds = elapsed + (startTime ? (now - startTime) : 0);
                        if (totalSeconds < 0) totalSeconds = 0;

                        // Calculate display time
                        var minutes = Math.floor(totalSeconds / 60);
                        var seconds = totalSeconds % 60;
                        var displayTime = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

                        // Update display
                        timerElement.textContent = displayTime + ' / ' + formatTime(duration);

                        // Check if match should end
                        if (timerStatus === 'first_half' && totalSeconds >= halfDuration + additionalTime) {
                            timerElement.textContent = 'Mi-temps / ' + formatTime(duration);
                            if (intervalId) clearInterval(intervalId);
                        }
                        else if (timerStatus === 'second_half' && totalSeconds >= duration) {
                            timerElement.textContent = 'Terminé / ' + formatTime(duration);
                            if (intervalId) clearInterval(intervalId);
                        }
                    }

                    function formatTime(seconds) {
                        var mins = Math.floor(seconds / 60);
                        var secs = seconds % 60;
                        return (mins < 10 ? '0' : '') + mins + ':' + (secs < 10 ? '0' : '') + secs;
                    }

                    // Update immediately and every second
                    updateTimer();
                    if (startTime && timerStatus !== 'half_time' && timerStatus !== 'ended') {
                        intervalId = setInterval(updateTimer, 1000);
                    }
                })();
            <?php endif; ?>
        });
    </script>
</body>
</html>
