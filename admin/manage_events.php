<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and check admin auth
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? 'membre') !== 'admin') {
    header('Location: ../index.php');
    exit();
}

require_once '../includes/db-config.php';

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ajout d'un nouvel événement
        if (isset($_POST['add_event'])) {
            $titre = filter_input(INPUT_POST, 'titre', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $date_debut = filter_input(INPUT_POST, 'date_debut', FILTER_SANITIZE_STRING);
            $date_fin = filter_input(INPUT_POST, 'date_fin', FILTER_SANITIZE_STRING);
            $lieu = filter_input(INPUT_POST, 'lieu', FILTER_SANITIZE_STRING);
            $categorie = filter_input(INPUT_POST, 'categorie', FILTER_SANITIZE_STRING);
            
            // Gestion de l'upload d'image
            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/events/';
                $imageName = basename($_FILES['image']['name']);
                $uploadFile = $uploadDir . $imageName;
                
                // Vérifier le type de fichier
                $allowedTypes = ['jpg' => 'image/jpeg', 'png' => 'image/png'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Seuls les fichiers JPG et PNG sont autorisés.");
                }
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                    throw new Exception("Erreur lors de l'upload de l'image.");
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO evenements 
                                  (titre, description, date_debut, date_fin, lieu, image, categorie, statut) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, 'actif')");
            $stmt->execute([$titre, $description, $date_debut, $date_fin, $lieu, $imageName, $categorie]);
            
            $_SESSION['message'] = "Événement ajouté avec succès!";
            header("Location: manage_events.php");
            exit();
        }
        
        // Mise à jour d'un événement
        if (isset($_POST['update_event'])) {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $titre = filter_input(INPUT_POST, 'titre', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
            $date_debut = filter_input(INPUT_POST, 'date_debut', FILTER_SANITIZE_STRING);
            $date_fin = filter_input(INPUT_POST, 'date_fin', FILTER_SANITIZE_STRING);
            $lieu = filter_input(INPUT_POST, 'lieu', FILTER_SANITIZE_STRING);
            $categorie = filter_input(INPUT_POST, 'categorie', FILTER_SANITIZE_STRING);
            $statut = filter_input(INPUT_POST, 'statut', FILTER_SANITIZE_STRING);
            
            // Gestion de l'upload d'image si une nouvelle image est fournie
            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../uploads/events/';
                $imageName = basename($_FILES['image']['name']);
                $uploadFile = $uploadDir . $imageName;
                
                // Vérifier le type de fichier
                $allowedTypes = ['jpg' => 'image/jpeg', 'png' => 'image/png'];
                $fileType = mime_content_type($_FILES['image']['tmp_name']);
                if (!in_array($fileType, $allowedTypes)) {
                    throw new Exception("Seuls les fichiers JPG et PNG sont autorisés.");
                }
                
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                    throw new Exception("Erreur lors de l'upload de l'image.");
                }
                
                // Supprimer l'ancienne image si elle existe
                $oldImage = $pdo->prepare("SELECT image FROM evenements WHERE id = ?");
                $oldImage->execute([$id]);
                $oldImage = $oldImage->fetchColumn();
                if ($oldImage && file_exists($uploadDir . $oldImage)) {
                    unlink($uploadDir . $oldImage);
                }
            } else {
                // Garder l'image existante
                $stmt = $pdo->prepare("SELECT image FROM evenements WHERE id = ?");
                $stmt->execute([$id]);
                $imageName = $stmt->fetchColumn();
            }
            
            $stmt = $pdo->prepare("UPDATE evenements SET 
                                  titre = ?, description = ?, date_debut = ?, date_fin = ?, 
                                  lieu = ?, image = ?, categorie = ?, statut = ? 
                                  WHERE id = ?");
            $stmt->execute([$titre, $description, $date_debut, $date_fin, $lieu, $imageName, $categorie, $statut, $id]);
            
            $_SESSION['message'] = "Événement mis à jour avec succès!";
            header("Location: manage_events.php");
            exit();
        }
        
        // Suppression d'un événement
        if (isset($_POST['delete_event'])) {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            
            // Supprimer l'image associée
            $image = $pdo->prepare("SELECT image FROM evenements WHERE id = ?");
            $image->execute([$id]);
            $image = $image->fetchColumn();
            if ($image && file_exists('../uploads/events/' . $image)) {
                unlink('../uploads/events/' . $image);
            }
            
            $stmt = $pdo->prepare("DELETE FROM evenements WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['message'] = "Événement supprimé avec succès!";
            header("Location: manage_events.php");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
        header("Location: manage_events.php");
        exit();
    }
}

// Récupérer tous les événements
try {
    $events = $pdo->query("SELECT * FROM evenements ORDER BY date_debut DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $events = [];
    $error = "Erreur lors du chargement des événements: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements | UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-img {
            max-width: 200px;
            max-height: 100px;
            object-fit: cover;
        }
        .modal-img {
            max-width: 200%;
            max-height: 300px;
            object-fit: contain;
        }
        .sidebar {
            min-height: 100vh;
            background: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.75);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .player-photo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
    
    </style>
</head>
<body>

    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4"><i class="fas fa-calendar me-2"></i>Gestion des Événements</h2>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Liste des Événements</h5>
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                <i class="fas fa-plus me-1"></i> Ajouter
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($events)): ?>
                            <div class="alert alert-info">Aucun événement enregistré</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Image</th>
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
                                            <td>
                                                <?php if ($event['image']): ?>
                                                    <img src="../uploads/events/<?= htmlspecialchars($event['image']) ?>" 
                                                         class="event-img rounded" 
                                                         alt="<?= htmlspecialchars($event['titre']) ?>">
                                                <?php else: ?>
                                                    <div class="event-img bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-calendar-alt text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($event['titre']) ?></td>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?>
                                                <?php if ($event['date_fin']): ?>
                                                    <br><small>au <?= date('d/m/Y H:i', strtotime($event['date_fin'])) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($event['lieu'] ?? 'N/A') ?></td>
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
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editEventModal"
                                                        onclick="loadEventData(<?= $event['id'] ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteEventModal"
                                                        onclick="setDeleteId(<?= $event['id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Ajout Événement -->
    <div class="modal fade" id="addEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Ajouter un Événement</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Titre*</label>
                                <input type="text" name="titre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catégorie*</label>
                                <select name="categorie" class="form-select" required>
                                    <option value="">Sélectionner...</option>
                                    <option value="sport">Sport</option>
                                    <option value="culture">Culture</option>
                                    <option value="education">Éducation</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de début*</label>
                                <input type="datetime-local" name="date_debut" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de fin</label>
                                <input type="datetime-local" name="date_fin" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lieu</label>
                                <input type="text" name="lieu" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image</label>
                                <input type="file" name="image" class="form-control" accept="image/jpeg, image/png">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="add_event" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Édition Événement -->
    <div class="modal fade" id="editEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="editEventId">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Modifier l'Événement</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Titre*</label>
                                <input type="text" name="titre" id="editTitre" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catégorie*</label>
                                <select name="categorie" id="editCategorie" class="form-select" required>
                                    <option value="sport">Sport</option>
                                    <option value="culture">Culture</option>
                                    <option value="education">Éducation</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de début*</label>
                                <input type="datetime-local" name="date_debut" id="editDateDebut" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de fin</label>
                                <input type="datetime-local" name="date_fin" id="editDateFin" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lieu</label>
                                <input type="text" name="lieu" id="editLieu" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Statut</label>
                                <select name="statut" id="editStatut" class="form-select">
                                    <option value="actif">Actif</option>
                                    <option value="annulé">Annulé</option>
                                    <option value="complet">Complet</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Image actuelle</label>
                                <div id="currentImageContainer" class="mb-2">
                                    <img id="currentImage" class="modal-img rounded">
                                    <div id="noImage" class="text-muted">Aucune image</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nouvelle image</label>
                                <input type="file" name="image" class="form-control" accept="image/jpeg, image/png">
                                <small class="text-muted">Laisser vide pour conserver l'image actuelle</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" id="editDescription" class="form-control" rows="4"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="update_event" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Suppression Événement -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <input type="hidden" name="id" id="deleteEventId">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">Confirmer la suppression</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Êtes-vous sûr de vouloir supprimer cet événement? Cette action est irréversible.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" name="delete_event" class="btn btn-danger">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Charger les données d'un événement pour l'édition
        function loadEventData(eventId) {
            fetch(`get_event.php?id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editEventId').value = data.id;
                    document.getElementById('editTitre').value = data.titre;
                    document.getElementById('editDescription').value = data.description;
                    
                    // Formater les dates pour l'input datetime-local
                    const dateDebut = new Date(data.date_debut);
                    document.getElementById('editDateDebut').value = dateDebut.toISOString().slice(0, 16);
                    
                    if (data.date_fin) {
                        const dateFin = new Date(data.date_fin);
                        document.getElementById('editDateFin').value = dateFin.toISOString().slice(0, 16);
                    } else {
                        document.getElementById('editDateFin').value = '';
                    }
                    
                    document.getElementById('editLieu').value = data.lieu || '';
                    document.getElementById('editCategorie').value = data.categorie;
                    document.getElementById('editStatut').value = data.statut;
                    
                    // Gestion de l'image
                    const currentImage = document.getElementById('currentImage');
                    const noImage = document.getElementById('noImage');
                    
                    if (data.image) {
                        currentImage.src = `../uploads/events/${data.image}`;
                        currentImage.style.display = 'block';
                        noImage.style.display = 'none';
                    } else {
                        currentImage.style.display = 'none';
                        noImage.style.display = 'block';
                    }
                })
                .catch(error => console.error('Erreur:', error));
        }
        
        // Définir l'ID pour la suppression
        function setDeleteId(eventId) {
            document.getElementById('deleteEventId').value = eventId;
        }
    </script>
</body>
</html>