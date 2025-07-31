<?php
// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données
require_once 'includes/db-config.php';
$pdo = DatabaseConfig::getConnection();

// Mise à jour automatique des statuts des matchs
try {
    // Mettre à jour les matchs dont l'heure de début est atteinte en statut 'ongoing'
    $updateOngoing = $pdo->prepare("
        UPDATE matches 
        SET status = 'ongoing',
            timer_start = CASE WHEN timer_start IS NULL THEN NOW() ELSE timer_start END,
            timer_status = CASE WHEN timer_status = 'not_started' THEN 'first_half' ELSE timer_status END,
            timer_elapsed = CASE 
                WHEN timer_status = 'not_started' THEN 0 
                WHEN timer_start IS NULL THEN 0
                ELSE TIMESTAMPDIFF(SECOND, timer_start, NOW())
            END
        WHERE match_date = CURDATE() 
        AND match_time <= TIME(NOW()) 
        AND status = 'scheduled'
    ");
    $updateOngoing->execute();
    error_log("Updated ".$updateOngoing->rowCount()." matches to ongoing status");

    // Corriger les matchs en cours avec des timers non initialisés
    $fixOngoing = $pdo->prepare("
        UPDATE matches 
        SET 
            timer_start = CASE WHEN timer_start IS NULL THEN NOW() ELSE timer_start END,
            timer_status = CASE WHEN timer_status = 'not_started' THEN 'first_half' ELSE timer_status END,
            timer_elapsed = CASE 
                WHEN timer_start IS NULL THEN TIMESTAMPDIFF(SECOND, CONCAT(match_date, ' ', match_time), NOW())
                ELSE TIMESTAMPDIFF(SECOND, timer_start, NOW())
            END
        WHERE status = 'ongoing' 
        AND (timer_start IS NULL OR timer_status = 'not_started')
    ");
    $fixOngoing->execute();
    error_log("Fixed ".$fixOngoing->rowCount()." ongoing matches with timer issues");

} catch (PDOException $e) {
    error_log("Error updating match statuses: ".$e->getMessage());
}

// On force la compétition à tournoi uniquement
$competition = 'tournoi';
$period = isset($_GET['period']) ? trim($_GET['period']) : 'all';
$searchTeam = isset($_GET['searchTeam']) ? trim($_GET['searchTeam']) : '';

// Initialisation des paramètres de requête
$params = [':competition' => $competition];

// Requête pour les matchs
$queryMatches = "SELECT *, UNIX_TIMESTAMP(timer_start) AS timer_start_unix, 
                COALESCE(score_home, 0) AS score_home, COALESCE(score_away, 0) AS score_away,
                first_half_duration, timer_status, first_half_extra, second_half_extra
                FROM matches WHERE competition = :competition";
                
if (!empty($searchTeam)) {
    $queryMatches .= " AND (team_home LIKE :search OR team_away LIKE :search)";
    $params[':search'] = "%$searchTeam%";
}

// Filtrage par période
if ($period === 'upcoming') {
    $queryMatches .= " AND match_date >= CURDATE() AND (score_home IS NULL OR score_home = '') AND status = 'scheduled'";
} elseif ($period === 'past') {
    $queryMatches .= " AND (score_home IS NOT NULL AND score_home != '') AND status = 'completed'";
} elseif ($period === 'live') {
    $queryMatches .= " AND status = 'ongoing'";
}

$queryMatches .= " ORDER BY match_date " . ($period === 'upcoming' ? 'ASC' : 'DESC');

try {
    $stmtMatches = $pdo->prepare($queryMatches);
    $stmtMatches->execute($params);
    $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("SQL Error fetching matches: " . $e->getMessage());
    $matches = [];
    $_SESSION['error'] = "Une erreur est survenue lors de la récupération des matchs.";
}

// Requête pour le classement
try {
    $queryStandings = "SELECT * FROM classement WHERE competition = :competition ORDER BY points DESC, difference_buts DESC, buts_pour DESC";
    $stmtStandings = $pdo->prepare($queryStandings);
    $stmtStandings->execute([':competition' => $competition]);
    $standings = $stmtStandings->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("SQL Error fetching standings: " . $e->getMessage());
    $standings = [];
}

// Fonction pour formater la date
function formatMatchDate($date) {
    if (empty($date)) return '';
    try {
        $dateObj = new DateTime($date);
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
        return $dateObj->format('d') . ' ' . $months[$dateObj->format('n') - 1] . ' ' . $dateObj->format('Y');
    } catch (Exception $e) {
        error_log("Error formatting date $date: " . $e->getMessage());
        return $date; // Retourne la date originale en cas d'erreur
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Matchs & Résultats Tournoi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .bg-opacity-10 { --bs-bg-opacity: 0.1; }
        .badge-form { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; }
        .match-score { font-weight: bold; white-space: nowrap; }
        .table-responsive { overflow-x: auto; }
        .live-badge { animation: pulse 2s infinite; }
        .event-list { max-height: 150px; overflow-y: auto; }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        .compact-timer {
            font-size: 0.85rem;
            padding: 0.25rem 0.5rem;
        }
        .team-logo-sm {
            width: 24px;
            height: 24px;
            object-fit: contain;
            margin: 0 5px;
            flex-shrink: 0;
        }
        .live-match-card {
            border-left: 4px solid #dc3545;
            transition: all 0.2s ease;
            margin-bottom: 8px;
        }
        .live-match-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        .error-message {
            color: #dc3545;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        /* Styles pour les écrans très petits */
        @media (max-width: 400px) {
            .live-match-card .badge {
                font-size: 0.65rem !important;
                padding: 0.15em 0.3em !important;
            }
            .live-match-card .fw-bold {
                font-size: 0.9rem;
            }
        }
        /* Styles pour les tablettes et mobiles */
        @media (max-width: 768px) {
            .team-logo-sm {
                width: 20px;
                height: 20px;
            }
            .live-match-card .badge {
                font-size: 0.7rem;
                padding: 0.2em 0.4em;
            }
            .live-match-card .text-muted {
                font-size: 0.75rem;
            }
        }
        /* Amélioration de la lisibilité sur les petits écrans */
        @media (max-width: 576px) {
            .live-match-card .fw-bold {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <header class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold"><i class="fas fa-futbol me-2"></i>Matchs & Résultats Tournoi</h1>
                    <p class="lead">Suivez les matchs en direct, résultats et classements du Tournoi UJEM</p>
                </div>
            </div>
        </div>
    </header>

    <section class="py-4 bg-light">
        <div class="container">
            <form method="get" class="row g-3">
                <input type="hidden" name="competition" value="tournoi">
                <div class="col-md-4">
                    <select class="form-select" name="period" id="periodFilter">
                        <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Toutes les dates</option>
                        <option value="upcoming" <?= $period === 'upcoming' ? 'selected' : '' ?>>Prochains matchs</option>
                        <option value="past" <?= $period === 'past' ? 'selected' : '' ?>>Matchs passés</option>
                        <option value="live" <?= $period === 'live' ? 'selected' : '' ?>>Matchs en direct</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" class="form-control" name="searchTeam" placeholder="Rechercher une équipe..." 
                               value="<?= htmlspecialchars($searchTeam) ?>">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100" type="submit">Filtrer</button>
                </div>
            </form>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="text-center mb-4">
                <a href="teams.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Retour aux équipes
                </a>
            </div>

            <h3 class="mb-4">Tournoi UJEM 2024-2025</h3>

            <!-- Live Matches Section - Version améliorée -->
            <div class="card mb-4">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-broadcast-tower me-2"></i>Matchs en Direct</h4>
                    <span class="badge bg-white text-danger">
                        <i class="fas fa-circle me-1"></i> En direct
                    </span>
                </div>
                <div class="card-body p-2" id="live-matches">
                    <?php 
                    $liveMatches = array_filter($matches, function($match) {
                        return $match['status'] === 'ongoing';
                    });
                    
                    if (empty($liveMatches)): ?>
                        <div class="text-center text-muted py-3">
                            <i class="far fa-futbol fa-2x mb-2"></i>
                            <p>Aucun match en direct actuellement</p>
                        </div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-md-2 g-3">
                            <?php foreach ($liveMatches as $match): ?>
                            <?php
                            // Calcul du temps écoulé
                            $elapsed = $match['timer_elapsed'];
                            if ($match['timer_start_unix']) {
                                $elapsed += (time() - $match['timer_start_unix']);
                            }
                            $minutes = floor($elapsed / 60);
                            $seconds = $elapsed % 60;
                            
                            // Déterminer la période de jeu
                            $halfDuration = floor(($match['first_half_duration'] ?? 45) * 60);
                            $gamePeriod = ($match['timer_status'] === 'first_half') ? '1ère MT' : 
                                        (($match['timer_status'] === 'second_half') ? '2ème MT' : 
                                        (($match['timer_status'] === 'half_time') ? 'MT' : 'FIN'));
                            
                            // Déterminer la classe de la période pour le style
                            $periodClass = 'bg-danger';
                            if ($match['timer_status'] === 'half_time') {
                                $periodClass = 'bg-warning text-dark';
                            } elseif ($match['status'] === 'completed') {
                                $periodClass = 'bg-secondary';
                            }
                            ?>
                            <div class="col-12 mb-2">
                                <div class="card live-match-card">
                                    <div class="card-body p-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <!-- Équipe à domicile -->
                                            <div class="d-flex align-items-center" style="width: 38%;">
                                                <span class="text-truncate text-end fw-bold">
                                                    <?= htmlspecialchars($match['team_home']) ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Score -->
                                            <div class="text-center px-2" style="min-width: 60px;">
                                                <div class="d-flex align-items-center justify-content-center">
                                                    <span class="badge <?= $periodClass ?> me-1"><?= $gamePeriod ?></span>
                                                    <span class="fw-bold">
                                                        <?= (isset($match['score_home']) && $match['score_home'] !== '' ? intval($match['score_home']) : '0') ?>
                                                        - 
                                                        <?= (isset($match['score_away']) && $match['score_away'] !== '' ? intval($match['score_away']) : '0') ?>
                                                    </span>
                                                    <?php if ($match['status'] === 'ongoing' && $match['timer_status'] !== 'ended'): ?>
                                                    <small class="ms-1" id="live-timer-<?= $match['id'] ?>">
                                                        <?= sprintf('%02d', $minutes) ?>:<?= sprintf('%02d', $seconds) ?>
                                                    </small>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($match['phase'])): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars($match['phase']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Équipe à l'extérieur -->
                                            <div class="d-flex align-items-center justify-content-end" style="width: 38%;">
                                                <span class="text-truncate fw-bold">
                                                    <?= htmlspecialchars($match['team_away']) ?>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="row g-2 mb-2 align-items-center">
                                            <div class="col-5 d-flex align-items-center">
                                                <img src="<?= DatabaseConfig::getTeamLogo($match['team_home']) ?>" 
                                                     class="team-logo-sm me-2"
                                                     alt="<?= htmlspecialchars($match['team_home']) ?>" 
                                                     onerror="this.src='assets/img/teams/default.png'">
                                                <span class="fw-bold text-truncate"><?= htmlspecialchars($match['team_home']) ?></span>
                                            </div>
                                            <div class="col-2 text-center px-0">
                                                <div class="fs-4 fw-bold text-danger">
                                                    <?= $match['score_home'] ?? '0' ?> - <?= $match['score_away'] ?? '0' ?>
                                                </div>
                                            </div>
                                            <div class="col-5 d-flex align-items-center justify-content-end">
                                                <span class="fw-bold text-truncate me-2"><?= htmlspecialchars($match['team_away']) ?></span>
                                                <img src="<?= DatabaseConfig::getTeamLogo($match['team_away']) ?>" 
                                                     class="team-logo-sm"
                                                     alt="<?= htmlspecialchars($match['team_away']) ?>"
                                                     onerror="this.src='assets/img/teams/default.png'">
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($match['venue'])): ?>
                                        <div class="text-center mt-1">
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($match['venue']) ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <?php if ($match['status'] === 'ongoing' && $match['timer_status'] !== 'ended' && $match['timer_status'] !== 'half_time'): ?>
                                            <div class="badge bg-dark rounded-pill compact-timer me-2" id="live-timer-<?= $match['id'] ?>">
                                                <?= sprintf('%02d:%02d', $minutes, $seconds) ?>
                                            </div>
                                            <?php endif; ?>
                                                <a href="match_details.php?id=<?= $match['id'] ?>" 
                                                   class="btn btn-sm btn-outline-danger py-0 px-2"
                                                   data-bs-toggle="tooltip" title="Voir les détails">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Matches Section -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-<?= $period === 'past' ? 'history' : 'calendar' ?> me-2"></i>
                        <?= $period === 'upcoming' ? 'Prochains matchs' : ($period === 'past' ? 'Résultats récents' : ($period === 'live' ? 'Matchs en direct' : 'Tous les matchs')) ?>
                    </h4>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Phase</th>
                                    <th>Équipe A</th>
                                    <th>Score</th>
                                    <th>Équipe B</th>
                                    <th>Terrain</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($matches)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">Aucun match trouvé.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($matches as $match): ?>
                                    <?php
                                    // Calcul du temps écoulé pour chaque match
                                    $elapsed = $match['timer_elapsed'];
                                    if ($match['timer_start_unix']) {
                                        $elapsed += (time() - $match['timer_start_unix']);
                                    }
                                    $displayMinutes = floor($elapsed / 60);
                                    $displaySeconds = $elapsed % 60;
                                    if ($match['timer_status'] === 'second_half') {
                                        $halfDuration = floor(($match['timer_duration'] ?? 5400) / 2);
                                        $firstHalfMinutes = floor($halfDuration / 60);
                                        $displayMinutes += $firstHalfMinutes;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <?= formatMatchDate($match['match_date']) ?>
                                            <br><small class="text-muted"><?= substr($match['match_time'], 0, 5) ?></small>
                                            <?php if ($match['status'] === 'ongoing' && $match['timer_status'] !== 'ended'): ?>
                                                <span class="badge bg-danger live-badge ms-1">Live</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($match['phase']) ?></td>
                                        <td <?= $match['score_home'] > $match['score_away'] ? 'class="fw-bold"' : '' ?>>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= DatabaseConfig::getTeamLogo($match['team_home']) ?>" 
                                                     class="team-logo-sm me-2"
                                                     alt="<?= htmlspecialchars($match['team_home']) ?>"
                                                     onerror="this.src='assets/img/teams/default.png'">
                                                <?= htmlspecialchars($match['team_home']) ?>
                                            </div>
                                        </td>
                                        <td class="match-score">
                                            <?= (isset($match['score_home']) && $match['score_home'] !== '' ? intval($match['score_home']) : '0') . ' - ' . (isset($match['score_away']) && $match['score_away'] !== '' ? intval($match['score_away']) : '0') ?>
                                            <?php if ($match['status'] === 'ongoing'): ?>
                                                <br><small class="timer-display" id="timer-<?= $match['id'] ?>">
                                                    <?php
                                                    if ($match['timer_status'] === 'half_time') {
                                                        echo 'Mi-temps';
                                                    } elseif ($match['timer_status'] === 'ended') {
                                                        echo 'Terminé';
                                                    } else {
                                                        echo sprintf('%02d:%02d', $displayMinutes, $displaySeconds);
                                                    }
                                                    ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td <?= (isset($match['score_away']) && isset($match['score_home']) && intval($match['score_away']) > intval($match['score_home'])) ? 'class="fw-bold"' : '' ?>>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= DatabaseConfig::getTeamLogo($match['team_away']) ?>" 
                                                     class="team-logo-sm me-2"
                                                     alt="<?= htmlspecialchars($match['team_away']) ?>"
                                                     onerror="this.src='assets/img/teams/default.png'">
                                                <?= htmlspecialchars($match['team_away']) ?>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($match['venue']) ?></td>
                                        <td>
                                            <?php if ((!isset($match['score_home']) || $match['score_home'] === '') && $match['status'] !== 'ongoing'): ?>
                                                <a href="#" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Rappel">
                                                    <i class="far fa-bell"></i>
                                                </a>
                                            <?php elseif (isset($match['score_home']) && $match['score_home'] !== ''): ?>
                                                <a href="#" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Photos">
                                                    <i class="fas fa-camera"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="match_details.php?id=<?= $match['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Détails">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-4">
                <a href="#" onclick="window.history.back()" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Retour
                </a>
            </div>
            <h2 class="section-title text-center mb-5">Classement Tournoi UJEM</h2>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Pos</th>
                                    <th>Équipe</th>
                                    <th>J</th>
                                    <th>G</th>
                                    <th>N</th>
                                    <th>P</th>
                                    <th>BP</th>
                                    <th>BC</th>
                                    <th>Diff</th>
                                    <th>Pts</th>
                                    <th>Forme</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($standings)): ?>
                                    <tr>
                                        <td colspan="11" class="text-center">Aucun classement disponible.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($standings as $index => $team): ?>
                                    <tr class="<?= $index < 4 ? 'bg-success bg-opacity-10' : ($index >= count($standings) - 3 ? 'bg-danger bg-opacity-10' : '') ?>">
                                        <td class="fw-bold"><?= $index + 1 ?></td>
                                        <td>
                                            <img src="assets/img/teams/<?= htmlspecialchars(strtolower(preg_replace('/[^a-z0-9]/', '-', $team['nom_equipe']))) ?>.png" 
                                                 alt="<?= htmlspecialchars($team['nom_equipe']) ?>" width="24" class="me-2"
                                                 onerror="this.src='assets/img/teams/default.png'">
                                            <?= htmlspecialchars($team['nom_equipe']) ?>
                                        </td>
                                        <td><?= $team['matchs_joues'] ?></td>
                                        <td><?= $team['matchs_gagnes'] ?></td>
                                        <td><?= $team['matchs_nuls'] ?></td>
                                        <td><?= $team['matchs_perdus'] ?></td>
                                        <td><?= $team['buts_pour'] ?></td>
                                        <td><?= $team['buts_contre'] ?></td>
                                        <td class="<?= $team['difference_buts'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $team['difference_buts'] >= 0 ? '+' : '' ?><?= $team['difference_buts'] ?>
                                        </td>
                                        <td class="fw-bold"><?= $team['points'] ?></td>
                                        <td>
                                            <?php 
                                            $forms = explode(',', $team['forme'] ?? '');
                                            foreach ($forms as $form): 
                                            ?>
                                                <span class="badge bg-<?= $form === 'V' ? 'success' : ($form === 'N' ? 'warning' : 'danger') ?>">
                                                    <?= $form ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="classement.php?competition=tournoi" class="btn btn-outline-primary">
                        Voir le classement complet
                    </a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Rafraîchir la page toutes les minutes pour détecter les changements de statut
        // Seulement si des matchs en direct sont affichés
        var hasLiveMatches = document.querySelectorAll('.live-badge').length > 0;
        if (hasLiveMatches) {
            setTimeout(function() {
                window.location.reload();
            }, 60000); // 60 secondes
        }

        // Update timers for ongoing matches in the table
        <?php foreach ($matches as $match): ?>
            <?php if ($match['status'] === 'ongoing' && $match['timer_status'] !== 'ended'): ?>
                (function() {
                    var timerElement = document.getElementById('timer-<?= $match['id'] ?>');
                    var liveTimerElement = document.getElementById('live-timer-<?= $match['id'] ?>');
                    var startTime = <?= $match['timer_start_unix'] ?? 'null' ?>;
                    var elapsed = <?= $match['timer_elapsed'] ?>;
                    var duration = <?= $match['timer_duration'] ?: 5400 ?>;
                    var halfDuration = Math.floor(duration / 2);
                    var additionalTime = <?= $match['timer_status'] === 'first_half' ? ($match['first_half_extra'] ?? 0) : ($match['second_half_extra'] ?? 0) ?>;
                    var isSecondHalf = <?= $match['timer_status'] === 'second_half' ? 'true' : 'false' ?>;
                    var timerStatus = '<?= $match['timer_status'] ?>';
                    var intervalId = null;

                    function updateTimer() {
                        if (timerStatus === 'half_time') {
                            timerElement.textContent = 'Mi-temps';
                            if (liveTimerElement) liveTimerElement.textContent = 'Mi-temps';
                            clearInterval(intervalId);
                            return;
                        }
                        if (timerStatus === 'ended') {
                            timerElement.textContent = 'Terminé';
                            if (liveTimerElement) liveTimerElement.textContent = 'Terminé';
                            clearInterval(intervalId);
                            return;
                        }

                        var now = Math.floor(Date.now() / 1000);
                    var totalSeconds = parseInt(elapsed) || 0;
                    if (startTime) {
                        totalSeconds += (now - parseInt(startTime));
                    } else if ('<?= $match['match_date'] ?>' && '<?= $match['match_time'] ?>') {
                        // Si le timer n'est pas encore démarré, on calcule depuis l'heure du match
                        try {
                            var matchTime = new Date('<?= $match['match_date'] ?> <?= $match['match_time'] ?>').getTime() / 1000;
                            if (!isNaN(matchTime)) {
                                totalSeconds = Math.max(0, now - matchTime);
                            }
                        } catch (e) {
                            console.error('Error calculating match time:', e);
                        }
                    }
                        
                        if (totalSeconds < 0) totalSeconds = 0;

                        // Cap elapsed time
                        var limit = halfDuration + additionalTime;
                        if (totalSeconds > limit) {
                            totalSeconds = limit;
                            timerElement.textContent = isSecondHalf ? 'Terminé' : 'Mi-temps';
                            if (liveTimerElement) liveTimerElement.textContent = isSecondHalf ? 'Terminé' : 'Mi-temps';
                            clearInterval(intervalId);
                            return;
                        }

                        // Calculate display time
                        var minutes = Math.floor(totalSeconds / 60);
                        var seconds = Math.floor(totalSeconds % 60);

                        // Adjust for second half
                        if (isSecondHalf) {
                            var firstHalfMinutes = Math.floor(halfDuration / 60);
                            minutes += firstHalfMinutes;
                        }

                        // Update display
                        var timeString = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                        timerElement.textContent = timeString;
                        if (liveTimerElement) liveTimerElement.textContent = timeString;
                    }

                    updateTimer();
                    intervalId = setInterval(updateTimer, 1000);
                })();
            <?php endif; ?>
        <?php endforeach; ?>
    });
    </script>
</body>
</html>