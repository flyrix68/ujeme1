<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and check auth
session_start();
require_once __DIR__ . '/includes/db-ssl.php';
// require_once '../includes/check-auth.php';

// Récupérer l'ID du contenu à éditer
$content_id = $_GET['id'] ?? 0;

// Récupérer les données existantes
$stmt = $pdo->prepare("SELECT * FROM medias_actualites WHERE id = ?");
$stmt->execute([$content_id]);
$content = $stmt->fetch();

// Si le contenu n'existe pas, rediriger
if (!$content) {
    $_SESSION['error'] = "Contenu introuvable";
    header("Location: manage_news_media.php");
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titre = htmlspecialchars($_POST['titre']);
        $description = htmlspecialchars($_POST['description']);
        $categorie = $_POST['categorie'];
        $reference_id = $_POST['reference_id'] ?? null;
        $statut = $_POST['statut'];
        
        // Gestion du fichier uploadé (si nouveau fichier fourni)
        $mediaUrl = $content['media_url']; // Conserver l'ancien par défaut
        $mediaType = $content['media_type']; // Conserver l'ancien type
        
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/medias/';
            
            // Supprimer l'ancien fichier (sauf s'il s'agit d'un fichier par défaut)
            if ($content['media_url'] !== 'default.jpg' && file_exists($uploadDir.$content['media_url'])) {
                unlink($uploadDir.$content['media_url']);
            }
            
            // Déterminer le type de média
            $extension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            if (strpos($_FILES['media']['type'], 'video') !== false) {
                $mediaType = 'video';
            } elseif (in_array($extension, ['pdf','doc','docx'])) {
                $mediaType = 'document';
            } else {
                $mediaType = 'image';
            }
            
            $mediaUrl = uniqid().'_'.preg_replace('/[^a-z0-9\._-]/i', '', $_FILES['media']['name']);
            move_uploaded_file($_FILES['media']['tmp_name'], $uploadDir.$mediaUrl);
        }

        // Mettre à jour en base de données
        $stmt = $pdo->prepare("UPDATE medias_actualites 
                              SET titre = ?, description = ?, media_url = ?, media_type = ?, 
                                  categorie = ?, reference_id = ?, statut = ?, updated_at = NOW() 
                              WHERE id = ?");
        $stmt->execute([
            $titre, 
            $description, 
            $mediaUrl, 
            $mediaType,
            $categorie, 
            $reference_id, 
            $statut,
            $content_id
        ]);

        $_SESSION['success'] = "Contenu mis à jour avec succès !";
        header("Location: manage_news_media.php");
        exit();
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur : ".$e->getMessage();
    }
}

// Récupérer les options pour les catégories
$categories = [
    'match' => 'Résultat de match',
    'concours' => 'Concours Miss',
    'cours' => 'Cours de vacances',
    'general' => 'Actualité générale'
];

// Récupérer les éléments liés selon la catégorie actuelle
$references = [];
if ($content['categorie'] && $content['categorie'] !== 'general') {
    try {
        $stmt = $pdo->query("SELECT id, 
                            CASE 
                                WHEN titre IS NOT NULL THEN titre
                                WHEN team_home IS NOT NULL THEN CONCAT(team_home, ' vs ', team_away)
                                ELSE CONCAT('Élément #', id)
                            END as label
                            FROM ".getTableName($content['categorie'])." 
                            ORDER BY created_at DESC");
        $references = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Erreur récupération références: ".$e->getMessage());
    }
}

// Fonction helper pour obtenir le nom de table
function getTableName($category) {
    switch ($category) {
        case 'match': return 'matches';
        case 'concours': return 'beauty_contests';
        case 'cours': return 'courses';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditer Contenu | UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .media-preview-container {
            position: relative;
            height: 200px;
            margin-bottom: 20px;
            border: 1px dashed #ccc;
            border-radius: 5px;
            overflow: hidden;
        }
        .media-preview {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: #f8f9fa;
        }
        .delete-media-btn {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="h3 mb-4">
                    <i class="fas fa-edit me-2"></i>Éditer le Contenu
                </h2>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Modifier le contenu</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Titre*</label>
                                    <input type="text" name="titre" class="form-control" 
                                           value="<?= htmlspecialchars($content['titre']) ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catégorie*</label>
                                    <select name="categorie" class="form-select" required id="categorySelect">
                                        <option value="">Sélectionnez...</option>
                                        <?php foreach ($categories as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= $content['categorie'] === $value ? 'selected' : '' ?>>
                                                <?= $label ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Élément lié</label>
                                    <select name="reference_id" class="form-select" id="referenceSelect">
                                        <option value="">Aucun</option>
                                        <?php foreach ($references as $ref): ?>
                                            <option value="<?= $ref['id'] ?>" 
                                                <?= $content['reference_id'] == $ref['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($ref['label']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Statut</label>
                                    <select name="statut" class="form-select">
                                        <option value="publie" <?= $content['statut'] === 'publie' ? 'selected' : '' ?>>Publié</option>
                                        <option value="brouillon" <?= $content['statut'] === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description*</label>
                                    <textarea name="description" class="form-control" rows="5" required><?= 
                                        htmlspecialchars($content['description']) 
                                    ?></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Média actuel</label>
                                    <div class="media-preview-container">
                                        <?php if ($content['media_type'] === 'image'): ?>
                                            <img src="../uploads/medias/<?= $content['media_url'] ?>" 
                                                 class="media-preview"
                                                 onerror="this.onerror=null;this.src='../assets/default-image.jpg'">
                                        <?php elseif ($content['media_type'] === 'video'): ?>
                                            <video controls class="media-preview">
                                                <source src="../uploads/medias/<?= $content['media_url'] ?>">
                                            </video>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center justify-content-center h-100">
                                                <i class="fas fa-file fa-5x text-secondary"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <a href="delete_media.php?id=<?= $content['id'] ?>" 
                                           class="btn btn-danger btn-sm delete-media-btn"
                                           onclick="return confirm('Supprimer ce média ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Nouveau média (remplacera l'actuel)</label>
                                    <input type="file" name="media" class="form-control" accept="image/*,video/*,.pdf">
                                    <small class="text-muted">Laissez vide pour conserver le média actuel</small>
                                </div>
                                
                                <div class="col-12 d-flex justify-content-between">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-1"></i> Enregistrer
                                    </button>
                                    
                                    <a href="manage_news_media.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chargement dynamique des références
        document.getElementById('categorySelect').addEventListener('change', async function() {
            const category = this.value;
            const refSelect = document.getElementById('referenceSelect');
            
            if (category === 'general' || category === '') {
                refSelect.innerHTML = '<option value="">Aucun</option>';
                return;
            }
            
            try {
                const response = await fetch(`../api/get_references.php?category=${category}`);
                const data = await response.json();
                
                let options = '<option value="">Aucun</option>';
                data.forEach(item => {
                    const label = item.titre || `${item.team_home} vs ${item.team_away}`;
                    options += `<option value="${item.id}">${label}</option>`;
                });
                
                refSelect.innerHTML = options;
            } catch (error) {
                console.error('Erreur:', error);
                refSelect.innerHTML = '<option value="">Erreur de chargement</option>';
            }
        });

        // Prévisualisation du nouveau média avant upload
        document.querySelector('input[name="media"]').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const previewContainer = document.querySelector('.media-preview-container');
            previewContainer.innerHTML = ''; // Efface l'ancien aperçu
            
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'media-preview';
                    previewContainer.appendChild(img);
                };
                reader.readAsDataURL(file);
            } 
            else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.controls = true;
                video.className = 'media-preview';
                
                const source = document.createElement('source');
                source.src = URL.createObjectURL(file);
                source.type = file.type;
                
                video.appendChild(source);
                previewContainer.appendChild(video);
            } 
            else {
                const div = document.createElement('div');
                div.className = 'd-flex align-items-center justify-content-center h-100';
                div.innerHTML = `<i class="fas fa-file fa-5x text-secondary"></i>
                                <div class="ms-3">${file.name}</div>`;
                previewContainer.appendChild(div);
            }
        });
    </script>
</body>
</html>