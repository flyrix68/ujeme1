<?php
require_once 'includes/db-config.php';

if (!isset($_GET['id'])) {
    header('Location: events.php');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM evenements WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $event = $stmt->fetch();
    
    if (!$event) {
        header('Location: events.php');
        exit();
    }
} catch (PDOException $e) {
    header('Location: events.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($event['titre']) ?> | UJEM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow">
                        <?php if ($event['image']): ?>
                            <img src="uploads/events/<?= htmlspecialchars($event['image']) ?>" 
                                 class="card-img-top" 
                                 alt="<?= htmlspecialchars($event['titre']) ?>"
                                 style="max-height: 400px; object-fit: cover;">
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h1 class="card-title"><?= htmlspecialchars($event['titre']) ?></h1>
                                    <span class="badge bg-<?= 
                                        $event['categorie'] === 'sport' ? 'primary' : 
                                        ($event['categorie'] === 'culture' ? 'warning' : 'success')
                                    ?>">
                                        <?= ucfirst($event['categorie']) ?>
                                    </span>
                                </div>
                                <span class="badge bg-<?= 
                                    $event['statut'] === 'actif' ? 'success' : 
                                    ($event['statut'] === 'annulé' ? 'danger' : 'secondary')
                                ?>">
                                    <?= ucfirst($event['statut']) ?>
                                </span>
                            </div>
                            
                            <div class="mb-4">
                                <p><i class="fas fa-calendar-day me-2"></i> 
                                    <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?>
                                    <?= $event['date_fin'] ? ' - ' . date('d/m/Y H:i', strtotime($event['date_fin'])) : '' ?>
                                </p>
                                <p><i class="fas fa-map-marker-alt me-2"></i> 
                                    <?= htmlspecialchars($event['lieu'] ?? 'Lieu non spécifié') ?>
                                </p>
                            </div>
                            
                            <div class="mb-4">
                                <h4>Description</h4>
                                <p class="card-text"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                                    <i class="fas fa-user-plus me-2"></i> S'inscrire
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal d'inscription -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Inscription à l'événement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="registrationForm">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nom complet *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="comments" class="form-label">Commentaires</label>
                            <textarea class="form-control" id="comments" name="comments" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="submitRegistration">Valider</button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion de l'inscription
        document.getElementById('submitRegistration').addEventListener('click', function() {
            const formData = new FormData(document.getElementById('registrationForm'));
            
            fetch('api/register_event.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Inscription réussie !');
                    bootstrap.Modal.getInstance(document.getElementById('registerModal')).hide();
                } else {
                    alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
                }
            })
            .catch(error => {
                alert('Erreur réseau');
            });
        });
    </script>
</body>
</html>
