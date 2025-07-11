<?php
require 'includes/db-config.php';

try {
    $stmt = $pdo->query("
        SELECT t.*, COUNT(p.id) as player_count
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id
        GROUP BY t.id
        ORDER BY t.created_at DESC
    ");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erreur SQL : " . $e->getMessage());
    $teams = [];
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
        
        <h1 class="text-center mb-5">Équipes Participantes</h1>
        
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
                            <?php if ($team['logo_path']): ?>
                                <?php 
                                $logoPath = $team['logo_path'];
                                if ($logoPath && file_exists($_SERVER['DOCUMENT_ROOT'] . $logoPath)): ?>
                                    <img src="<?= htmlspecialchars($logoPath) ?>" class="card-img-top img-fluid" alt="<?= htmlspecialchars($team['name']) ?>" style="max-height: 200px; width: auto;">
                                <?php else: ?>
                                    <img src="/assets/img/teams/default.png" class="card-img-top img-fluid" alt="Logo par défaut" style="max-height: 200px; width: auto;">
                                <?php endif; ?>
                            <?php endif; ?>
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
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
