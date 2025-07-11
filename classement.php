<?php
// Connexion à la base de données
require_once 'includes/db-config.php';

// Récupération des filtres avec des valeurs par défaut
$season = $_GET['season'] ?? '2024-2025';
$competition = $_GET['competition'] ?? 'coupe';
$searchTeam = $_GET['searchTeam'] ?? '';

// Requête pour récupérer les poules distinctes
$poulesQuery = "
    SELECT DISTINCT p.id, p.name 
    FROM poules p
    JOIN classement c ON p.id = c.poule_id
    WHERE c.saison = :season AND c.competition = :competition";
$poulesStmt = $pdo->prepare($poulesQuery);
$poulesStmt->bindValue(':season', $season);
$poulesStmt->bindValue(':competition', $competition);
$poulesStmt->execute();
$poules = $poulesStmt->fetchAll(PDO::FETCH_KEY_PAIR); // id => name

// Requête pour le classement par poule
$teamsByPoule = [];
foreach (array_keys($poules) as $pouleId) {
    $query = "
        SELECT c.*, t.logo_path 
        FROM classement c
        LEFT JOIN teams t ON c.nom_equipe = t.team_name
        WHERE c.saison = :season AND c.competition = :competition AND c.poule_id = :poule_id";
    if (!empty($searchTeam)) {
        $query .= " AND c.nom_equipe LIKE :searchTeam";
    }
    $query .= " ORDER BY c.points DESC, c.difference_buts DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':season', $season);
    $stmt->bindValue(':competition', $competition);
    $stmt->bindValue(':poule_id', $pouleId);
    if (!empty($searchTeam)) {
        $stmt->bindValue(':searchTeam', "%$searchTeam%");
    }
    $stmt->execute();
    $teamsByPoule[$pouleId] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Requête pour coupe et tournoi uniquement
$competitionsQuery = "SELECT DISTINCT competition FROM classement 
                     WHERE competition IN ('coupe', 'tournoi')";
$competitionsStmt = $pdo->query($competitionsQuery);
$competitions = $competitionsStmt->fetchAll(PDO::FETCH_COLUMN);

// Fonction pour générer les badges de forme
function generateFormBadges($formString) {
    $forms = explode(',', $formString);
    $badges = '';
    foreach ($forms as $form) {
        $color = ($form === 'V') ? 'success' : ($form === 'N' ? 'warning' : 'danger');
        $badges .= '<span class="badge bg-'.$color.'">'.$form.'</span> ';
    }
    return $badges;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Classement</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .table-responsive { overflow-x: auto; }
        .bg-opacity-10 { --bs-bg-opacity: 0.1; }
        .badge-form { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <header class="bg-primary text-white py-5">
        <div class="container text-center">
            <h1 class="display-4 fw-bold"><i class="fas fa-trophy me-2"></i>Classement</h1>
            <p class="lead">Classement <?= ucfirst($competition) ?> UJEM <?= $season ?></p>
        </div>
    </header>

    <section class="py-4 bg-light">
        <div class="container">
            <form id="filterForm" method="get" class="row g-3">
                <div class="col-md-3">
                    <select class="form-select" name="season" id="seasonFilter">
                        <option value="2024-2025" <?= $season === '2024-2025' ? 'selected' : '' ?>>Saison 2024-2025</option>
                        <option value="2023-2024" <?= $season === '2023-2024' ? 'selected' : '' ?>>Saison 2023-2024</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" name="competition" id="competitionFilter">
                        <?php foreach ($competitions as $comp): ?>
                            <option value="<?= htmlspecialchars($comp) ?>" <?= $competition === $comp ? 'selected' : '' ?>>
                                <?= ucfirst($comp) ?>
                            </option>
                        <?php endforeach; ?>
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
            <?php if (empty($poules)): ?>
                <div class="alert alert-warning text-center">
                    Aucun classement disponible pour les critères sélectionnés.
                </div>
            <?php else: ?>
                <?php foreach ($teamsByPoule as $pouleId => $teams): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white py-3">
                            <h4 class="mb-0"><i class="fas fa-table me-2"></i>Classement <?= ucfirst($competition) ?> - <?= htmlspecialchars($poules[$pouleId]) ?></h4>
                        </div>
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
                                        <?php foreach ($teams as $index => $team): ?>
                                            <tr class="<?= $index < 2 ? 'bg-success bg-opacity-10' : ($index >= count($teams) - 2 ? 'bg-danger bg-opacity-10' : '') ?>">
                                                <td class="fw-bold"><?= $index + 1 ?></td>
                                                <td>
                                                    <img src="assets/img/teams/<?= $team['logo_path'] ?? strtolower(str_replace(' ', '-', $team['nom_equipe'])) . '.png' ?>" 
                                                         alt="<?= htmlspecialchars($team['nom_equipe']) ?>" width="24" class="me-2" onerror="this.src='assets/img/teams/default.png'">
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
                                                <td><?= generateFormBadges($team['forme']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-success bg-opacity-25 me-2" style="width:15px;height:15px;"></div>
                                        <small>Qualification pour la phase finale</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="badge bg-danger bg-opacity-25 me-2" style="width:15px;height:15px;"></div>
                                        <small>Zone de relégation</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <footer class="bg-dark text-white py-4">
        <div class="container text-center">
            <p>© <?= date('Y') ?> UJEM. Tous droits réservés.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            // Le formulaire s'envoie normalement avec méthode GET
        });
    </script>
</body>
</html>
