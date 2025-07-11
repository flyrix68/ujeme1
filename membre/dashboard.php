<?php
require_once '../includes/check-auth.php';
// Vérifie que l'utilisateur est membre
if ($_SESSION['user']['type_compte'] !== 'membre') {
    header('Location: ../index.php');
    exit();
}

// Récupérer les activités récentes
try {
    $stmt = $pdo->prepare("SELECT * FROM activites WHERE user_id = ? ORDER BY date_activite DESC LIMIT 5");
    $stmt->execute([$_SESSION['user']['id']]);
    $activites = $stmt->fetchAll();
} catch (PDOException $e) {
    $activites = [];
    error_log("Erreur lors de la récupération des activités: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord | UJEM Membre</title>
    <?php include '../includes/head.php'; ?>
</head>
<body>
    <?php include '../includes/navbar-member.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-tachometer-alt"></i> Tableau de Bord</h1>
                    <span class="badge bg-primary">Membre</span>
                </div>

                <!-- Bienvenue -->
                <div class="card shadow mb-4">
                    <div class="card-body text-center py-4">
                        <h3>Bonjour, <?= htmlspecialchars($_SESSION['user']['prenom']) ?> !</h3>
                        <p class="lead mb-0">Bienvenue sur votre espace membre UJEM</p>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card bg-primary text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-calendar-check"></i> Activités</h5>
                                <p class="display-6">12</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-success text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-trophy"></i> Récompenses</h5>
                                <p class="display-6">3</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card bg-info text-white h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users"></i> Amis</h5>
                                <p class="display-6">24</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activités récentes -->
                <div class="card shadow">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Vos activités récentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($activites)): ?>
                            <div class="list-group">
                                <?php foreach ($activites as $activite): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?= htmlspecialchars($activite['titre']) ?></h6>
                                        <small><?= date('d/m/Y', strtotime($activite['date_activite'])) ?></small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($activite['description']) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="mes-activites.php" class="btn btn-outline-primary">
                                    Voir toutes les activités
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Vous n'avez aucune activité récente.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>