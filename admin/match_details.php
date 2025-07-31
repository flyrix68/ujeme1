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
require_once __DIR__ . '/includes/db-ssl.php';

try {
    // Get database connection using DatabaseConfig
    $pdo = DatabaseSSL::getInstance()->getConnection();
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("A database connection error occurred. Please contact the administrator. Error: " . $e->getMessage());
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

// Fetch match details with team logos and only completed matches
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               UNIX_TIMESTAMP(m.timer_start) AS timer_start_unix,
               t1.logo AS home_logo,
               t2.logo AS away_logo
        FROM matches m
        LEFT JOIN teams t1 ON m.team_home = t1.team_name
        LEFT JOIN teams t2 ON m.team_away = t2.team_name
        WHERE m.id = ?
        AND (m.status = 'completed' OR m.timer_status = 'ended')
    ");
    
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        error_log("Match not found or not completed for match_id: $matchId");
        $_SESSION['error'] = "Match non trouvé ou non terminé. Seuls les matchs terminés peuvent être consultés ici.";
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

// Calculate match timing information
$timerDuration = $match['timer_duration'] ?? 5400; // Default 90 minutes
$halfDuration = floor($timerDuration / 2); // e.g., 2700 seconds (45 minutes)
$firstHalfDuration = $match['first_half_duration'] ?? $halfDuration;
$firstHalfEndMinute = floor($firstHalfDuration / 60); // Includes extra time

// Ensure we have valid timing data for completed matches
if (empty($match['timer_elapsed']) || $match['timer_elapsed'] < $timerDuration) {
    error_log("Warning: timer_elapsed is invalid for completed match ID $matchId: " . ($match['timer_elapsed'] ?? 'null'));
    $match['timer_elapsed'] = $timerDuration; // Fallback to regulation time
}

if (empty($match['first_half_duration'])) {
    $match['first_half_duration'] = $halfDuration;
}

// Fetch and organize goals
$goals = [];
$homeGoals = [];
$awayGoals = [];
$firstHalfGoals = [];
$secondHalfGoals = [];

try {
    $goalsStmt = $pdo->prepare("SELECT * FROM goals WHERE match_id = ? ORDER BY minute");
    $goalsStmt->execute([$matchId]);
    $goals = $goalsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize goals by team and half
    foreach ($goals as $goal) {
        if ($goal['team'] === $match['team_home']) {
            $homeGoals[] = $goal;
        } else {
            $awayGoals[] = $goal;
        }
        
        if ($goal['minute'] <= $firstHalfEndMinute) {
            $firstHalfGoals[] = $goal;
        } else {
            $secondHalfGoals[] = $goal;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching goals: " . $e->getMessage());
}

// Fetch and organize cards
$cards = [];
$homeCards = [];
$awayCards = [];
$redCards = [];
$yellowCards = [];

try {
    $cardsStmt = $pdo->prepare("SELECT * FROM cards WHERE match_id = ? ORDER BY minute");
    $cardsStmt->execute([$matchId]);
    $cards = $cardsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize cards by team and type
    foreach ($cards as $card) {
        if ($card['team'] === $match['team_home']) {
            $homeCards[] = $card;
        } else {
            $awayCards[] = $card;
        }
        
        if ($card['card_type'] === 'red') {
            $redCards[] = $card;
        } else {
            $yellowCards[] = $card;
        }
    }
} catch (PDOException $e) {
    error_log("Error fetching cards: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Match - UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/match-card.css">
    <style>
        .team-logo {
            max-height: 80px;
            max-width: 100%;
            object-fit: contain;
        }
        .score-display {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .match-detail {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .event-icon {
            width: 24px;
            text-align: center;
            margin-right: 0.5rem;
        }
        .goal {
            color: #28a745;
        }
        .yellow-card {
            color: #ffc107;
        }
        .red-card {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar bg-dark text-white">
                <div class="text-center py-4">
                    <h4>Panel Admin</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="teams.php">
                            <i class="fas fa-users me-2"></i>Équipes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="players.php">
                            <i class="fas fa-user me-2"></i>Joueurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="matches.php">
                            <i class="fas fa-futbol me-2"></i>Matchs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../logout.php">
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
                            </h5>
                            <div>
                                <span class="badge bg-light text-dark me-2">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?= date('d/m/Y', strtotime($match['match_date'])) ?>
                                </span>
                                <span class="badge bg-light text-dark">
                                    <i class="far fa-clock me-1"></i>
                                    <?= date('H:i', strtotime($match['match_time'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body">
                        <!-- Score -->
                        <div class="row align-items-center text-center mb-4">
                            <div class="col-5">
                                <div class="mb-2">
                                    <img src="<?= !empty($match['home_logo']) ? htmlspecialchars($match['home_logo']) : '../assets/img/teams/default.png' ?>" 
                                         alt="<?= htmlspecialchars($match['team_home']) ?>" 
                                         class="team-logo">
                                </div>
                                <h5><?= htmlspecialchars($match['team_home']) ?></h5>
                                <div class="display-4 fw-bold"><?= $match['score_home'] ?? 0 ?></div>
                            </div>
                            
                            <div class="col-2 text-center">
                                <div class="score-display">-</div>
                                <div class="text-muted">Score final</div>
                            </div>
                            
                            <div class="col-5">
                                <div class="mb-2">
                                    <img src="<?= !empty($match['away_logo']) ? htmlspecialchars($match['away_logo']) : '../assets/img/teams/default.png' ?>" 
                                         alt="<?= htmlspecialchars($match['team_away']) ?>" 
                                         class="team-logo">
                                </div>
                                <h5><?= htmlspecialchars($match['team_away']) ?></h5>
                                <div class="display-4 fw-bold"><?= $match['score_away'] ?? 0 ?></div>
                            </div>
                        </div>
                        
                        <!-- Match Details -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="match-detail">
                                    <h6 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Informations du Match</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Compétition:</strong> <?= htmlspecialchars($match['competition']) ?></li>
                                        <li><strong>Date:</strong> <?= date('d/m/Y', strtotime($match['match_date'])) ?></li>
                                        <li><strong>Heure:</strong> <?= date('H:i', strtotime($match['match_time'])) ?></li>
                                        <li><strong>Lieu:</strong> <?= htmlspecialchars($match['location'] ?? 'Non spécifié') ?></li>
                                        <li><strong>Arbitre:</strong> <?= htmlspecialchars($match['referee'] ?? 'Non spécifié') ?></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="match-detail">
                                    <h6 class="fw-bold"><i class="fas fa-trophy me-2"></i>Détails du Score</h6>
                                    <ul class="list-unstyled">
                                        <li>
                                            <strong>Mi-temps:</strong> 
                                            <?= floor(($match['first_half_duration'] ?? $halfDuration) / 60) ?> minutes
                                        </li>
                                        <li><strong>Buts <?= htmlspecialchars($match['team_home']) ?>:</strong> <?= count($homeGoals) ?></li>
                                        <li><strong>Buts <?= htmlspecialchars($match['team_away']) ?>:</strong> <?= count($awayGoals) ?></li>
                                        <li><strong>Cartons jaunes:</strong> <?= count($yellowCards) ?></li>
                                        <li><strong>Cartons rouges:</strong> <?= count($redCards) ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Goals -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="match-detail">
                                    <h6 class="fw-bold"><i class="fas fa-futbol me-2"></i>Buteurs</h6>
                                    
                                    <?php if (empty($goals)): ?>
                                        <p class="text-muted">Aucun but marqué pendant ce match.</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ($goals as $goal): ?>
                                                <li class="mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <span class="event-icon">
                                                            <i class="fas fa-futbol goal"></i>
                                                        </span>
                                                        <div>
                                                            <strong><?= htmlspecialchars($goal['player_name']) ?></strong>
                                                            <span class="text-muted">(<?= $goal['minute'] ?>')</span>
                                                            <?php if ($goal['is_penalty']): ?>
                                                                <span class="badge bg-primary ms-2">Pénalty</span>
                                                            <?php endif; ?>
                                                            <?php if ($goal['is_own_goal']): ?>
                                                                <span class="badge bg-danger ms-1">CSC</span>
                                                            <?php endif; ?>
                                                            <div class="text-muted small">
                                                                <?= htmlspecialchars($goal['team']) ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="match-detail">
                                    <h6 class="fw-bold"><i class="fas fa-clipboard-list me-2"></i>Cartons</h6>
                                    
                                    <?php if (empty($cards)): ?>
                                        <p class="text-muted">Aucun carton pendant ce match.</p>
                                    <?php else: ?>
                                        <ul class="list-unstyled">
                                            <?php foreach ($cards as $card): ?>
                                                <li class="mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <span class="event-icon">
                                                            <?php if ($card['card_type'] === 'red'): ?>
                                                                <i class="fas fa-square red-card"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-square yellow-card"></i>
                                                            <?php endif; ?>
                                                        </span>
                                                        <div>
                                                            <strong><?= htmlspecialchars($card['player_name']) ?></strong>
                                                            <span class="text-muted">(<?= $card['minute'] ?>')</span>
                                                            <div class="text-muted small">
                                                                <?= htmlspecialchars($card['team']) ?>
                                                                <?php if (!empty($card['reason'])): ?>
                                                                    - <?= htmlspecialchars($card['reason']) ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer text-end">
                        <a href="dashboard.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour au tableau de bord
                        </a>
                        <a href="edit_match.php?id=<?= $matchId ?>" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i> Modifier le match
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activer les tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>
