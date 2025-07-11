<?php
// Connexion à la base de données
require_once 'includes/db-config.php';

// Récupération des filtres - vérification exacte du type de compétition
$rawCompetition = $_GET['competition'] ?? 'coupe';
$competition = strtolower($rawCompetition) === 'tournoi' ? 'tournoi' : 'coupe';
error_log("Using competition type: " . $competition);
$period = $_GET['period'] ?? 'all';
$searchTeam = $_GET['searchTeam'] ?? '';

// Requête pour les matchs avec logging des erreurs
$queryMatches = "SELECT *, UNIX_TIMESTAMP(timer_start) AS timer_start_unix, 
                COALESCE(score_home, 0) AS score_home, COALESCE(score_away, 0) AS score_away,
                first_half_duration, timer_status, first_half_extra, second_half_extra
                FROM matches WHERE LOWER(competition) = LOWER(:competition)";
error_log("Fetching matches for competition: " . $competition);
$params = [':competition' => $competition];

if (!empty($searchTeam)) {
    $queryMatches .= " AND (team_home LIKE :search OR team_away LIKE :search)";
    $params[':search'] = "%$searchTeam%";
}

if ($period === 'upcoming') {
    $queryMatches .= " AND match_date >= CURDATE() AND score_home IS NULL AND status != 'ongoing'";
} elseif ($period === 'past') {
    $queryMatches .= " AND score_home IS NOT NULL AND status = 'completed'";
} elseif ($period === 'live') {
    $queryMatches .= " AND status = 'ongoing'";
}

$queryMatches .= " ORDER BY match_date " . ($period === 'upcoming' ? 'ASC' : 'DESC');
try {
    $stmtMatches = $pdo->prepare($queryMatches);
    $stmtMatches->execute($params);
    $matches = $stmtMatches->fetchAll(PDO::FETCH_ASSOC);
    error_log("Found " . count($matches) . " matches for competition " . $competition);
} catch (PDOException $e) {
    error_log("SQL Error fetching matches: " . $e->getMessage());
    $matches = [];
}

// Requête pour le classement
$queryStandings = "SELECT * FROM classement WHERE competition = :competition ORDER BY points DESC, difference_buts DESC";
$stmtStandings = $pdo->prepare($queryStandings);
$stmtStandings->execute([':competition' => $competition]);
$standings = $stmtStandings->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour formater la date
function formatMatchDate($date) {
    $dateObj = new DateTime($date);
    $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
    return $dateObj->format('d') . ' ' . $months[$dateObj->format('n') - 1] . ' ' . $dateObj->format('Y');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Matchs & Résultats</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .bg-opacity-10 { --bs-bg-opacity: 0.1; }
        .badge-form { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; }
        .match-score { font-weight: bold; }
        .table-responsive { overflow-x: auto; }
        .live-badge { animation: pulse 2s infinite; }
        .live-match-card .card-body { padding: 1.5rem; }
        .event-list { max-height: 150px; overflow-y: auto; }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.7; }
            100% { transform: scale(1); opacity: 1; }
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
                    <h1 class="display-4 fw-bold"><i class="fas fa-futbol me-2"></i>Matchs & Résultats</h1>
                    <p class="lead">Suivez les matchs en direct, résultats et classements de l'UJEM</p>
                </div>
            </div>
        </div>
    </header>

    <section class="py-4 bg-light">
        <div class="container">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="competition" id="competitionFilter">
                    <option value="coupe" <?= $competition === 'coupe' ? 'selected' : '' ?>>Coupe UJEM</option>
                    <option value="tournoi" <?= $competition === 'tournoi' ? 'selected' : '' ?>>Tournoi</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="period" id="periodFilter">
                        <option value="all" <?= $period === 'all' ? 'selected' : '' ?>>Toutes les dates</option>
                        <option value="upcoming" <?= $period === 'upcoming' ? 'selected' : '' ?>>Prochains matchs</option>
                        <option value="past" <?= $period === 'past' ? 'selected' : '' ?>>Matchs passés</option>
                        <option value="live" <?= $period === 'live' ? 'selected' : '' ?>>Matchs en direct</option>
                    </select>
                </div>
                <div class="col-md-4">
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
            <ul class="nav nav-tabs mb-4" id="matchesTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $competition === 'coupe' ? 'active' : '' ?>" 
                            id="coupe-tab" data-bs-toggle="tab" data-bs-target="#coupe" 
                            type="button" role="tab">Coupe</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $competition === 'tournoi' ? 'active' : '' ?>" 
                            id="tournoi-tab" data-bs-toggle="tab" data-bs-target="#tournoi" 
                            type="button" role="tab">Tournoi</button>
                </li>
            </ul>

            <?php
            ob_start();
            ?>
            <h3 class="mb-4"><?= ucfirst($competition) ?> UJEM 2024-2025</h3>

            <!-- Live Matches Section -->
            <?php if ($period === 'live' || $period === 'all'): ?>
            <div class="card mb-4 live-match-card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-broadcast-tower me-2"></i>Matchs en Direct</h4>
                </div>
                <div class="card-body" id="live-matches">
                    <div class="text-center text-muted">Chargement des matchs en direct...</div>
                </div>
            </div>
            <?php endif; ?>

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
                                    <tr>
                                        <td>
                                            <?= formatMatchDate($match['match_date']) ?>
                                            <br><small class="text-muted"><?= substr($match['match_time'], 0, 5) ?></small>
                                            <?php if ($match['status'] === 'ongoing'): ?>
                                                <span class="badge bg-danger live-badge ms-1">Live</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($match['phase']) ?></td>
                                        <td <?= $match['score_home'] > $match['score_away'] ? 'class="fw-bold"' : '' ?>>
                                            <?= htmlspecialchars($match['team_home']) ?>
                                        </td>
                                            <!-- Matches Table (Update only the score column) -->
                                            <td class="match-score">
                                                <?= $match['score_home'] . ' - ' . $match['score_away'] ?>
                                                <?php if ($match['status'] === 'ongoing'): ?>
                                                    <br><small class="timer-display" id="timer-<?= $match['id'] ?>">
                                                        <?php
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
                                                        echo sprintf('%02d:%02d', $displayMinutes, $displaySeconds);
                                                        ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                                                                    <td <?= $match['score_away'] > $match['score_home'] ? 'class="fw-bold"' : '' ?>>
                                            <?= htmlspecialchars($match['team_away']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($match['venue']) ?></td>
                                        <td>
                                            <?php if ($match['score_home'] === null && $match['status'] !== 'ongoing'): ?>
                                                <a href="#" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Rappel">
                                                    <i class="far fa-bell"></i>
                                                </a>
                                            <?php elseif ($match['score_home'] !== null): ?>
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
                <?php if ($period === 'all' || $period === 'past'): ?>
                <div class="card-footer text-center">
                    <a href="matches.php?competition=<?= $competition ?>&period=past" class="btn btn-outline-primary">
                        Voir tous les résultats
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php
            $matchesContent = ob_get_clean();
            ?>

            <div class="tab-content" id="matchesTabsContent">
                <div class="tab-pane fade <?= $competition === 'coupe' ? 'show active' : '' ?>" id="coupe" role="tabpanel">
                    <?= $matchesContent ?>
                </div>
                <div class="tab-pane fade <?= $competition === 'tournoi' ? 'show active' : '' ?>" id="tournoi" role="tabpanel">
                    <?= $matchesContent ?>
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
            <h2 class="section-title text-center mb-5">Classement <?= ucfirst($competition) ?> UJEM</h2>
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
                                            <img src="assets/img/teams/<?= htmlspecialchars(strtolower(str_replace(' ', '-', $team['nom_equipe']))) ?>.png" 
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
                    <a href="classement.php?competition=<?= $competition ?>" class="btn btn-outline-primary">
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

    // Fetch live matches
    function fetchLiveMatches() {
        $.ajax({
            url: 'api/live_match.php?_=' + new Date().getTime(),
            method: 'GET',
            data: { competition: '<?= $competition ?>' },
            dataType: 'json',
            success: function(response) {
                console.log('Live matches response:', response); // Debug log
                var liveMatchesContainer = $('#live-matches');
                liveMatchesContainer.empty();

                if (!response.success) {
                    liveMatchesContainer.html('<div class="text-center text-danger">Erreur lors du chargement des matchs en direct.</div>');
                    return;
                }

                var liveMatches = response.matches;
                if (liveMatches.length === 0) {
                    liveMatchesContainer.html('<div class="text-center text-muted">Aucun match en direct pour cette compétition.</div>');
                    return;
                }

                liveMatches.forEach(function(match) {
                    // Initial currentTime from API or fallback
                    var currentTime = match.current_time || '';
                    if (!currentTime || currentTime === 'undefined' || currentTime === '') {
                        var elapsed = match.timer_elapsed || 0;
                        if (match.timer_start_unix) {
                            elapsed += Math.floor(Date.now() / 1000) - match.timer_start_unix;
                        }
                        if (elapsed < 0) elapsed = 0;
                        var minutes = Math.floor(elapsed / 60);
                        var seconds = elapsed % 60;
                        currentTime = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                    }

                    // Handle special timer states
                    if (match.status === 'ongoing') {
                        if (match.timer_status === 'half_time') {
                            currentTime = 'Mi-temps';
                        } else if (match.timer_status === 'ended') {
                            currentTime = 'Terminé';
                        }
                    }

                    // Render match card with unique timer ID
                    var scoreHome = match.score_home !== null ? match.score_home : 0;
                    var scoreAway = match.score_away !== null ? match.score_away : 0;
                    var html = `
                        <div class="live-match-card mb-3" id="live-match-${match.id}">
                            <div class="card">
                                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">${match.team_home} vs ${match.team_away}</h5>
                                    <span class="badge bg-light text-dark">${match.phase}</span>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="text-center">
                                            <img src="assets/img/teams/${match.team_home.toLowerCase().replace(' ', '-')}.png" 
                                                 alt="${match.team_home}" width="50" 
                                                 onerror="this.src='assets/img/teams/default.png'">
                                            <div class="fw-bold mt-2">${match.team_home}</div>
                                        </div>
                                        <div class="text-center">
                                            <div class="display-4 fw-bold">
                                                ${scoreHome} - ${scoreAway}
                                            </div>
                                            <div class="timer-display mt-2" id="live-timer-${match.id}">${currentTime}</div>
                                        </div>
                                        <div class="text-center">
                                            <img src="assets/img/teams/${match.team_away.toLowerCase().replace(' ', '-')}.png" 
                                                 alt="${match.team_away}" width="50" 
                                                 onerror="this.src='assets/img/teams/default.png'">
                                            <div class="fw-bold mt-2">${match.team_away}</div>
                                        </div>
                                    </div>
                                    <div class="event-list mb-3">
                                        <h6 class="fw-bold">Événements:</h6>
                                        ${match.goals.map(goal => `
                                            <div class="badge bg-${goal.team === 'home' ? 'primary' : 'danger'} goal-badge">
                                                ${goal.player} (${goal.minute}')
                                            </div>
                                        `).join('')}
                                        ${match.cards.map(card => `
                                            <div class="badge bg-${card.card_type === 'yellow' ? 'warning' : (card.card_type === 'red' ? 'danger' : 'info')} card-badge">
                                                ${card.player} (${card.minute}' - ${card.card_type.charAt(0).toUpperCase() + card.card_type.slice(1)})
                                            </div>
                                        `).join('')}
                                    </div>
                                    <a href="match_details.php?id=${match.id}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-info-circle"></i> Détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                    liveMatchesContainer.append(html);

                    // Start real-time timer for this match
                    if (match.status === 'ongoing' && match.timer_start_unix && match.timer_status !== 'half_time' && match.timer_status !== 'ended') {
                        (function() {
                            var timerElement = document.getElementById('live-timer-' + match.id);
                            var startTime = match.timer_start_unix;
                            var elapsed = match.timer_elapsed || 0;
                            var duration = match.timer_duration || 5400;
                            var halfDuration = Math.floor(duration / 2);
                            var additionalTime = match.timer_status === 'first_half' ? (match.first_half_extra || 0) : (match.second_half_extra || 0);
                            var isSecondHalf = match.timer_status === 'second_half';
                            var timerStatus = match.timer_status;
                            var intervalId = null;

                            function updateLiveTimer() {
                                if (timerStatus === 'half_time') {
                                    timerElement.textContent = 'Mi-temps';
                                    clearInterval(intervalId);
                                    return;
                                }
                                if (timerStatus === 'ended') {
                                    timerElement.textContent = 'Terminé';
                                    clearInterval(intervalId);
                                    return;
                                }

                                var now = Math.floor(Date.now() / 1000);
                                var totalSeconds = elapsed + (now - startTime);
                                if (totalSeconds < 0) totalSeconds = 0;

                                // Cap elapsed time
                                var limit = halfDuration + additionalTime;
                                if (totalSeconds > limit) {
                                    totalSeconds = limit;
                                    timerElement.textContent = isSecondHalf ? 'Terminé' : 'Mi-temps';
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
                                timerElement.textContent =
                                    (minutes < 10 ? '0' : '') + minutes + ':' +
                                    (seconds < 10 ? '0' : '') + seconds;
                            }

                            updateLiveTimer();
                            intervalId = setInterval(updateLiveTimer, 1000);
                        })();
                    } else if (match.timer_status === 'half_time') {
                        document.getElementById('live-timer-' + match.id).textContent = 'Mi-temps';
                    } else if (match.timer_status === 'ended') {
                        document.getElementById('live-timer-' + match.id).textContent = 'Terminé';
                    }
                });
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX error:', textStatus, errorThrown, jqXHR.responseText);
                $('#live-matches').html('<div class="text-center text-danger">Erreur serveur: ' + textStatus + '</div>');
            }
        });
    }

    // Initial fetch and periodic updates
    fetchLiveMatches();
    setInterval(fetchLiveMatches, 10000);

    // Update timers for ongoing matches in the table
    <?php foreach ($matches as $match): ?>
        <?php if ($match['status'] === 'ongoing' && $match['timer_start_unix']): ?>
            (function() {
                var timerElement = document.getElementById('timer-<?= $match['id'] ?>');
                var startTime = <?= $match['timer_start_unix'] ?>;
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
                        clearInterval(intervalId);
                        return;
                    }
                    if (timerStatus === 'ended') {
                        timerElement.textContent = 'Terminé';
                        clearInterval(intervalId);
                        return;
                    }

                    var now = Math.floor(Date.now() / 1000);
                    var totalSeconds = elapsed + (now - startTime);
                    if (totalSeconds < 0) totalSeconds = 0;

                    // Cap elapsed time
                    var limit = halfDuration + additionalTime;
                    if (totalSeconds > limit) {
                        totalSeconds = limit;
                        timerElement.textContent = isSecondHalf ? 'Terminé' : 'Mi-temps';
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
                    timerElement.textContent =
                        (minutes < 10 ? '0' : '') + minutes + ':' +
                        (seconds < 10 ? '0' : '') + seconds;
                }

                updateTimer();
                intervalId = setInterval(updateTimer, 1000);
            })();
        <?php endif; ?>
    <?php endforeach; ?>
});
</script>
