<?php
require_once '../includes/check-auth.php';
// Vérifie que l'utilisateur est membre
if ($_SESSION['user']['type_compte'] !== 'membre') {
    header('Location: ../index.php');
    exit();
}

// Récupérer toutes les activités
try {
    $stmt = $pdo->prepare("SELECT * FROM activites WHERE user_id = ? ORDER BY date_activite DESC");
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
    <title>Mes Activités | UJEM Membre</title>
    <?php include '../includes/head.php'; ?>
</head>
<body>
    <?php include '../includes/navbar-member.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1><i class="fas fa-list-check"></i> Mes Activités</h1>
                    <a href="ajouter-activite.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Ajouter
                    </a>
                </div>

                <div class="card shadow">
                    <div class="card-body">
                        <?php if (!empty($activites)): ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Activité</th>
                                            <th>Description</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activites as $activite): ?>
                                        <tr>
                                            <td><?= date('d/m/Y', strtotime($activite['date_activite'])) ?></td>
                                            <td><?= htmlspecialchars($activite['titre']) ?></td>
                                            <td><?= htmlspecialchars(substr($activite['description'], 0, 50)) ?>...</td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $activite['statut'] === 'terminé' ? 'success' : 
                                                    ($activite['statut'] === 'en cours' ? 'warning' : 'secondary')
                                                ?>">
                                                    <?= ucfirst($activite['statut']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="voir-activite.php?id=<?= $activite['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="modifier-activite.php?id=<?= $activite['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info text-center py-4">
                                <i class="fas fa-info-circle fa-2x mb-3"></i>
                                <h4>Vous n'avez aucune activité enregistrée</h4>
                                <p class="mb-0">Commencez par ajouter votre première activité</p>
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