<?php
require_once __DIR__ . '/includes/db-ssl.php';

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    // Query for teams with proper logo paths
    $stmt = $pdo->query("
        SELECT t.*, 
               COUNT(p.id) as player_count,
               CASE 
                   WHEN t.logo_path IS NOT NULL THEN CONCAT('/uploads/logos/', t.logo_path)
                   ELSE '/assets/img/teams/default.png'
               END AS logo_full_path
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Query for pools with associated teams
    $stmt = $pdo->query("
        SELECT p.*, t.id as team_id, t.team_name, t.category as team_category, t.location
        FROM poules p
        LEFT JOIN teams t ON p.id = t.poule_id
        ORDER BY p.name, t.team_name
    ");
    $pools = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group teams by pool
    $grouped_pools = [];
    foreach ($pools as $pool) {
        if (!isset($grouped_pools[$pool['id']])) {
            $grouped_pools[$pool['id']] = [
                'name' => $pool['name'],
                'category' => $pool['category'],
                'competition' => $pool['competition'],
                'saison' => $pool['saison'],
                'teams' => []
            ];
        }
        if ($pool['team_id']) {
            $grouped_pools[$pool['id']]['teams'][] = [
                'id' => $pool['team_id'],
                'team_name' => $pool['team_name'],
                'team_category' => $pool['team_category'],
                'location' => $pool['location']
            ];
        }
    }
} catch (PDOException $e) {
    error_log("Erreur SQL : " . $e->getMessage());
    $teams = [];
    $grouped_pools = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Accueil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="container py-5">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Votre équipe a été enregistrée avec succès !
            </div>
        <?php endif; ?>
        
        <h1 class="text-center mb-5">Équipes et Poules</h1>
        
        <div class="row mb-5">
            <div class="col-lg-8 mx-auto text-center">
                <div class="card bg-light">
                    <div class="card-body">
                        <h3 class="card-title">Inscrivez votre équipe</h3>
                        <p class="card-text">Vous dirigez une équipe de football ? Inscrivez-vous à nos prochains tournois.</p>
                        <a href="team-register.php" class="btn btn-primary btn-lg">Inscription d'équipe</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs mb-4" id="teamsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="teams-tab" data-bs-toggle="tab" data-bs-target="#teams" type="button" role="tab" aria-controls="teams" aria-selected="true">Équipes Inscrites</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pools-tab" data-bs-toggle="tab" data-bs-target="#pools" type="button" role="tab" aria-controls="pools" aria-selected="false">Composition des Poules</button>
            </li>
        </ul>

        <!-- Tabs Contents -->
        <div class="tab-content" id="teamsTabsContent">
            <!-- Teams Tab -->
            <div class="tab-pane fade show active" id="teams" role="tabpanel" aria-labelledby="teams-tab">
                <div class="row" id="teamsContainer">
                    <?php if (empty($teams)): ?>
                        <div class="col-12 text-center py-5">
                            <div class="alert alert-info">
                                Aucune équipe inscrite pour le moment. Soyez le premier à 
                                <a href="team-register.php">inscrire votre équipe</a> !
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teams as $team): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <?php 
                                    // Utiliser le chemin complet du logo généré par la requête SQL
                                    $logoPath = $team['logo_path'] ? '/uploads/logos/' . $team['logo_path'] : '/assets/img/teams/default.png';
                                    ?>
                                    <img src="<?= htmlspecialchars($logoPath) ?>" 
                                         class="card-img-top img-fluid" 
                                         alt="Logo de <?= htmlspecialchars($team['team_name']) ?>" 
                                         style="max-height: 200px; width: auto; object-fit: contain;">
                                    <div class="card-body">
                                        <h3 class="card-title"><?= htmlspecialchars($team['team_name']) ?></h3>
                                        <p class="text-muted"><i class="fas fa-tag me-2"></i><?= htmlspecialchars($team['category']) ?></p>
                                        <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($team['location']) ?></p>
                                        
                                        <h5 class="mt-4">Joueurs :</h5>
                                        <div class="d-flex flex-wrap gap-2 mb-3">
                                            <span class="badge bg-primary"><?= $team['player_count'] ?> joueurs</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-success">Inscrite</span>
                                            <a href="team-detail.php?id=<?= $team['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                Voir détails
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pools Tab -->
            <div class="tab-pane fade" id="pools" role="tabpanel" aria-labelledby="pools-tab">
                <div class="row">
                    <?php if (empty($grouped_pools)): ?>
                        <div class="col-12 text-center py-5">
                            <div class="alert alert-info">
                                Aucune poule configurée pour le moment.
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($grouped_pools as $pool): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h3 class="card-title"><?= htmlspecialchars($pool['name']) ?></h3>
                                        <p class="text-muted"><i class="fas fa-tag me-2"></i><?= htmlspecialchars($pool['category']) ?></p>
                                        <?php if ($pool['competition']): ?>
                                            <p class="text-muted"><i class="fas fa-trophy me-2"></i><?= htmlspecialchars($pool['competition']) ?></p>
                                        <?php endif; ?>
                                        <p class="text-muted"><i class="fas fa-calendar me-2"></i>Saison: <?= htmlspecialchars($pool['saison']) ?></p>
                                        
                                        <h5 class="mt-4">Équipes :</h5>
                                        <?php if (empty($pool['teams'])): ?>
                                            <p class="text-muted">Aucune équipe assignée à cette poule.</p>
                                        <?php else: ?>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($pool['teams'] as $team): ?>
                                                    <li class="list-group-item">
                                                        <a href="team-detail.php?id=<?= $team['id'] ?>">
                                                            <?= htmlspecialchars($team['team_name']) ?>
                                                        </a>
                                                        <small class="text-muted d-block">
                                                            <?= htmlspecialchars($team['location']) ?>
                                                        </small>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
