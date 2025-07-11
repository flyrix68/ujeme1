<?php
require_once '../includes/check_admin.php';

// Actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Suppression
        $stmt = $pdo->prepare("DELETE FROM evenements WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $_SESSION['success'] = "Événement supprimé";
    } else {
        // Ajout/Modification
        $data = [
            'titre' => $_POST['titre'],
            'description' => $_POST['description'],
            'date_debut' => $_POST['date_debut'],
            'date_fin' => $_POST['date_fin'] ?: null,
            'lieu' => $_POST['lieu'],
            'categorie' => $_POST['categorie'],
            'statut' => $_POST['statut'],
            'id' => $_POST['id'] ?? null
        ];

        if (empty($data['id'])) {
            // Ajout
            $stmt = $pdo->prepare("INSERT INTO evenements (titre, description, date_debut, date_fin, lieu, categorie, statut) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute(array_values($data));
            $_SESSION['success'] = "Événement ajouté";
        } else {
            // Modification
            $stmt = $pdo->prepare("UPDATE evenements 
                                  SET titre = ?, description = ?, date_debut = ?, date_fin = ?, lieu = ?, categorie = ?, statut = ?
                                  WHERE id = ?");
            $stmt->execute(array_values($data));
            $_SESSION['success'] = "Événement mis à jour";
        }
    }
    header('Location: events_manager.php');
    exit();
}

// Récupérer tous les événements
$stmt = $pdo->query("SELECT * FROM evenements ORDER BY date_debut DESC");
$events = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion Événements | Admin UJEM</title>
    <?php include '../includes/admin_head.php'; ?>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-calendar-alt me-2"></i> Gestion des Événements</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#eventModal">
                <i class="fas fa-plus me-2"></i> Nouvel Événement
            </button>
        </div>

        <?php include '../includes/alerts.php'; ?>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Date</th>
                                <th>Lieu</th>
                                <th>Catégorie</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['titre']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?></td>
                                    <td><?= htmlspecialchars($event['lieu']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $event['categorie'] === 'sport' ? 'primary' : 
                                            ($event['categorie'] === 'culture' ? 'warning' : 'success')
                                        ?>">
                                            <?= ucfirst($event['categorie']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $event['statut'] === 'actif' ? 'success' : 
                                            ($event['statut'] === 'annulé' ? 'danger' : 'secondary')
                                        ?>">
                                            <?= ucfirst($event['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary edit-event" 
                                                data-id="<?= $event['id'] ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#eventModal">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Supprimer cet événement ?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Ajout/Modification -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Nouvel Événement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="eventId">
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre *</label>
                            <input type="text" class="form-control" id="titre" name="titre" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_debut" class="form-label">Date début *</label>
                                <input type="datetime-local" class="form-control" id="date_debut" name="date_debut" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_fin" class="form-label">Date fin</label>
                                <input type="datetime-local" class="form-control" id="date_fin" name="date_fin">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="lieu" class="form-label">Lieu</label>
                                <input type="text" class="form-control" id="lieu" name="lieu">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="categorie" class="form-label">Catégorie *</label>
                                <select class="form-select" id="categorie" name="categorie" required>
                                    <option value="sport">Sport</option>
                                    <option value="culture">Culture</option>
                                    <option value="education">Éducation</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="statut" class="form-label">Statut *</label>
                                <select class="form-select" id="statut" name="statut" required>
                                    <option value="actif">Actif</option>
                                    <option value="annulé">Annulé</option>
                                    <option value="complet">Complet</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="image" class="form-label">Image</label>
                            <input class="form-control" type="file" id="image" name="image" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du modal d'édition
        document.querySelectorAll('.edit-event').forEach(btn => {
            btn.addEventListener('click', function() {
                const eventId = this.getAttribute('data-id');
                fetch(`../api/get_event.php?id=${eventId}`)
                    .then(response => response.json())
                    .then(event => {
                        document.getElementById('modalTitle').textContent = 'Modifier Événement';
                        document.getElementById('eventId').value = event.id;
                        document.getElementById('titre').value = event.titre;
                        document.getElementById('description').value = event.description;
                        document.getElementById('date_debut').value = event.date_debut.replace(' ', 'T');
                        document.getElementById('date_fin').value = event.date_fin ? event.date_fin.replace(' ', 'T') : '';
                        document.getElementById('lieu').value = event.lieu;
                        document.getElementById('categorie').value = event.categorie;
                        document.getElementById('statut').value = event.statut;
                    });
            });
        });

        // Réinitialiser le modal quand il se ferme
        document.getElementById('eventModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('modalTitle').textContent = 'Nouvel Événement';
            this.querySelector('form').reset();
        });
    </script>
</body>
</html>