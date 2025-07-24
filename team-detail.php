<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log current working directory
error_log("Current working directory: " . getcwd());
error_log("__DIR__ value: " . __DIR__);

// Verify include path exists
$dbConfigPath = __DIR__ . '/includes/db-config.php';
error_log("DB config path: $dbConfigPath");
if (!file_exists($dbConfigPath)) {
    die("DB config file not found at: $dbConfigPath");
}

require $dbConfigPath;

try {
    $pdo = DatabaseConfig::getConnection();

    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header('Location: teams.php');
        exit;
    }

    $teamId = (int)$_GET['id'];

    // Récupérer les infos de l'équipe
    $stmt = $pdo->prepare("SELECT * FROM teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$team) {
        header('Location: teams.php');
        exit;
    }

    // Log team data for debugging
    error_log("Team data for ID $teamId: " . print_r($team, true));

    // Récupérer les joueurs
    $stmt = $pdo->prepare("SELECT name, position, jersey_number, photo FROM players WHERE team_id = ? ORDER BY position, name");
    $stmt->execute([$teamId]);
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer l'historique des matchs
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.team_home,
            m.team_away,
            m.score_home,
            m.score_away,
            m.match_date,
            m.match_time,
            m.competition,
            CASE
                WHEN m.team_home = ? AND m.score_home > m.score_away THEN 'W'
                WHEN m.team_away = ? AND m.score_away > m.score_home THEN 'W'
                WHEN m.team_home = ? AND m.score_home < m.score_away THEN 'L'
                WHEN m.team_away = ? AND m.score_away < m.score_home THEN 'L'
                WHEN m.score_home = m.score_away AND m.score_home IS NOT NULL THEN 'D'
                ELSE '-'
            END AS result,
            CASE
                WHEN m.team_home = ? THEN m.team_away
                ELSE m.team_home
            END AS opponent
        FROM matches m
        WHERE m.team_home = ? OR m.team_away = ? 
        ORDER BY m.match_date DESC, m.match_time DESC
    ");
    $stmt->execute([
        $team['team_name'], // 1 
        $team['team_name'], // 2
        $team['team_name'], // 3 
        $team['team_name'], // 4
        $team['team_name'], // 5
        $team['team_name'], // 6
        $team['team_name']  // 7
    ]);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log matches data for debugging
    error_log("Matches for team ID $teamId: " . print_r($matches, true));

} catch (Exception $e) {
    error_log("Database error in team-detail.php: " . $e->getMessage());
    http_response_code(500);
    die("Une erreur est survenue lors du chargement des données de l'équipe.");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de l'Équipe - UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .team-logo { width: 100px; height: 100px; object-fit: contain; }
        .player-photo { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
        .result-w { color: green; font-weight: bold; }
        .result-l { color: red; font-weight: bold; }
        .result-d { color: yellow; font-weight: bold; }
        .result-- { color: gray; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="container py-5">
        <div class="text-center mb-4">
            <a href="teams.php" class="btn btn-outline-primary mb-3">
                <i class="fas fa-arrow-left me-2"></i>Retour aux équipes
            </a>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><?= htmlspecialchars($team['team_name']) ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <?php if ($team['logo_path']): ?>
                                    <img src="<?= htmlspecialchars($team['logo_path']) ?>" 
                                         alt="Logo de <?= htmlspecialchars($team['team_name']) ?>" 
                                         class="team-logo mb-3">
                                <?php else: ?>
                                    <img src="assets/img/teams/default.png" 
                                         alt="Logo par défaut" 
                                         class="team-logo mb-3">
                                <?php endif; ?>
                                <p><strong>Catégorie :</strong> <?= htmlspecialchars($team['category'] ?? 'Non spécifié') ?></p>
                                <p><strong>Localisation :</strong> <?= htmlspecialchars($team['location'] ?? 'Non spécifié') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Responsable :</strong> <?= htmlspecialchars($team['manager_name'] ?? 'Non spécifié') ?></p>
                                <p><strong>Contact :</strong> <?= htmlspecialchars($team['manager_phone'] ?? 'Non spécifié') ?></p>
                                <p><strong>Email :</strong> <?= htmlspecialchars($team['manager_email'] ?? 'Non spécifié') ?></p>
                            </div>
                        </div>

                        <?php if ($team['description']): ?>
                            <h5 class="mb-3">Description</h5>
                            <p><?= nl2br(htmlspecialchars($team['description'])) ?></p>
                        <?php endif; ?>

                        <h4 class="mb-3">Liste des joueurs</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Nom</th>
                                        <th>Poste</th>
                                        <th>Numéro</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($players)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center">Aucun joueur enregistré.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($players as $player): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($player['photo']): ?>
                                                        <img src="<?= htmlspecialchars($player['photo']) ?>" 
                                                             alt="Photo de <?= htmlspecialchars($player['name']) ?>" 
                                                             class="player-photo">
                                                    <?php else: ?>
                                                        <img src="assets/img/players/default.png" 
                                                             alt="Photo par défaut" 
                                                             class="player-photo">
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($player['name']) ?></td>
                                                <td><?= htmlspecialchars($player['position'] ?: 'Non spécifié') ?></td>
                                                <td><?= htmlspecialchars($player['jersey_number'] ?: '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <h4 class="mb-3">Historique des matchs</h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Adversaire</th>
                                        <th>Score</th>
                                        <th>Résultat</th>
                                        <th>Compétition</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($matches)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">Aucun match enregistré.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($matches as $match): ?>
                                            <tr>
                                                <td>
                                                    <?php
                                                    $dateTime = $match['match_date'];
                                                    if ($match['match_time']) {
                                                        $dateTime .= ' ' . $match['match_time'];
                                                        echo date('d/m/Y H:i', strtotime($dateTime));
                                                    } else {
                                                        echo date('d/m/Y', strtotime($dateTime));
                                                    }
                                                    ?>
                                                </td>
                                                <td><?= htmlspecialchars($match['opponent']) ?></td>
                                                <td>
                                                    <?php
                                                    if ($match['team_home'] === $team['team_name']) {
                                                        echo htmlspecialchars(($match['score_home'] ?? '-') . ' - ' . ($match['score_away'] ?? '-'));
                                                    } else {
                                                        echo htmlspecialchars(($match['score_away'] ?? '-') . ' - ' . ($match['score_home'] ?? '-'));
                                                    }
                                                    ?>
                                                </td>
                                                <td class="result-<?= strtolower($match['result']) ?>">
                                                    <?= htmlspecialchars($match['result']) ?>
                                                </td>
                                                <td><?= htmlspecialchars($match['competition']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
