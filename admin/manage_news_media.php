
<?php
ob_start();
require __DIR__ . '/admin_header.php';

// Initialize empty contenus array to prevent undefined variable errors
$contenus = [];

// Verify database connection
if (!isset($pdo)) {
    error_log("Critical error: Database connection not available in admin/manage_news_media.php");
    die("Database connection error. Please contact administrator.");
}
// require_once '../includes/check-auth.php';

function autoGenererMedia($pdo, $type, $reference_id, $user_id) {
    $contenusAuto = [
        'match' => [
            'titre' => "Résultat du match",
            'description' => "Revivez les temps forts de la rencontre"
        ],
        'concours' => [
            'titre' => "Photos officielles du concours",
            'description' => "Découvrez les moments clés de l'événement"
        ],
        'cours' => [
            'titre' => "Session de formation",
            'description' => "Retour en images sur nos cours de vacances"
        ]
    ];

    if (!array_key_exists($type, $contenusAuto)) return false;

    try {
        $mediaPath = 'auto_'.$type.'_'.$reference_id.'.jpg';
        $defaultImage = 'default_'.$type.'.jpg';

        $stmt = $pdo->prepare("INSERT INTO medias_actualites 
                              (titre, description, media_url, media_type, categorie, reference_id, statut, auteur_id) 
                              VALUES (?, ?, ?, 'image', ?, ?, 'publie', ?)");
        $stmt->execute([
            $contenusAuto[$type]['titre'],
            $contenusAuto[$type]['description'],
            file_exists('../uploads/medias/'.$mediaPath) ? $mediaPath : $defaultImage,
            $type,
            $reference_id,
            $user_id
        ]);
        return true;
    } catch (PDOException $e) {
        error_log("Erreur auto-génération: ".$e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $titre = htmlspecialchars($_POST['titre']);
        $description = htmlspecialchars($_POST['description']);
        $categorie = $_POST['categorie'];
        $reference_id = $_POST['reference_id'] ?? null;
        $statut = $_POST['statut'];
        $mediaType = 'image';
        $mediaUrl = 'default.jpg';
        
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/medias/';
            $extension = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            
            if (strpos($_FILES['media']['type'], 'video') !== false) {
                $mediaType = 'video';
            } elseif (in_array($extension, ['pdf','doc','docx'])) {
                $mediaType = 'document';
            }
            
            $mediaUrl = uniqid().'_'.preg_replace('/[^a-z0-9\._-]/i', '', $_FILES['media']['name']);
            move_uploaded_file($_FILES['media']['tmp_name'], $uploadDir.$mediaUrl);
        }

        $stmt = $pdo->prepare("INSERT INTO medias_actualites 
                              (titre, description, media_url, media_type, categorie, reference_id, statut, auteur_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $mediaUrl, $mediaType, $categorie, $reference_id, $statut, $_SESSION['user_id']]);

        $_SESSION['success'] = "Contenu publié avec succès !";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur : ".$e->getMessage();
    }
    header("Location: manage_news_media.php");
    exit();
}

if (isset($_GET['auto_generate']) && isset($_GET['type']) && isset($_GET['ref_id'])) {
    autoGenererMedia($pdo, $_GET['type'], $_GET['ref_id'], $_SESSION['user_id']);
    $_SESSION['success'] = "Contenu auto-généré avec succès !";
    header("Location: manage_news_media.php");
    exit();
}

$sql = "SELECT m.*, u.nom as auteur,
               ma.team_home, ma.team_away, ma.score_home, ma.score_away,
               bc.title as concours_titre
        FROM medias_actualites m
        JOIN users u ON m.auteur_id = u.id
        LEFT JOIN matches ma ON m.categorie = 'match' AND m.reference_id = ma.id
        LEFT JOIN beauty_contests bc ON m.categorie = 'concours' AND m.reference_id = bc.id
        ORDER BY m.created_at DESC";
$contenus = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Contenus | UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .media-card { transition: all 0.3s; height: 100%; }
        .media-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .media-preview { height: 200px; background-size: cover; background-position: center; }
        .video-preview { background-color: #000; display: flex; align-items: center; justify-content: center; color: white; }
        .sidebar { min-height: 100vh; }
    </style>
</head>
<body>

    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>
                
                <h2 class="h3 mb-4">
                    <i class="fas fa-photo-video me-2"></i>Gestion des Contenus
                </h2>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Ajouter un Contenu</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" enctype="multipart/form-data" id="mediaForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Titre*</label>
                                    <input type="text" name="titre" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catégorie*</label>
                                    <select name="categorie" class="form-select" required id="categorySelect">
                                        <option value="">Sélectionnez...</option>
                                        <option value="match">Résultat de match</option>
                                        <option value="concours">Concours Miss</option>
                                        <option value="cours">Cours de vacances</option>
                                        <option value="general">Actualité générale</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Élément lié</label>
                                    <select name="reference_id" class="form-select" id="referenceSelect">
                                        <option value="">Aucun</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Statut</label>
                                    <select name="statut" class="form-select">
                                        <option value="publie">Publié</option>
                                        <option value="brouillon">Brouillon</option>

                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description*</label>
                                    <textarea name="description" class="form-control" rows="3" required></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Média*</label>
                                    <input type="file" name="media" class="form-control" accept="image/*,video/*,.pdf" required>
                                    <small class="text-muted">Formats acceptés : JPG, PNG, MP4, PDF (max 10MB)</small>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary px-4">
                                        <i class="fas fa-save me-1"></i> Publier
                                    </button>
                                    <a href="manage_news_media.php?auto_generate=1&type=match&ref_id=123" 
                                    class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-magic me-1"></i> Auto-générer
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Contenus Publiés</h5>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="filterCategory">
                                <option value="all">Toutes catégories</option>
                                <option value="match">Sports</option>
                                <option value="concours">Concours</option>
                                <option value="cours">Éducation</option>
                            </select>
                            <select class="form-select" id="filterType">
                                <option value="all">Tous types</option>
                                <option value="image">Images</option>
                                <option value="video">Vidéos</option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="contentGrid">
                            <?php foreach ($contenus as $item): ?>
                            <div class="col-lg-4 col-md-6 mb-4 content-item" 
                                 data-category="<?= $item['categorie'] ?>"
                                 data-type="<?= $item['media_type'] ?>"
                                 data-media-url="<?= $item['media_url'] ?>">
                                <div class="card media-card">
                                    <div class="media-preview <?= $item['media_type'] === 'video' ? 'video-preview' : '' ?>"
                                         style="<?= $item['media_type'] === 'image' ? 'background-image: url(../uploads/medias/' . $item['media_url'] . ')' : '' ?>">
                                        <?php if ($item['media_type'] === 'video'): ?>
                                            <i class="fas fa-play fa-3x"></i>
                                        <?php elseif ($item['media_type'] === 'document'): ?>
                                            <i class="fas fa-file-pdf fa-3x"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($item['titre']) ?></h5>
                                        <span class="badge bg-<?= 
                                            $item['categorie'] === 'match' ? 'primary' : 
                                            ($item['categorie'] === 'concours' ? 'warning' : 
                                            ($item['categorie'] === 'cours' ? 'success' : 'secondary'))
                                        ?> mb-2">
                                            <?= ucfirst($item['categorie']) ?>
                                        </span>
                                        <p class="card-text small"><?= substr(htmlspecialchars($item['description']), 0, 80) ?>...</p>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($item['created_at'])) ?>
                                                par <?= htmlspecialchars($item['auteur']) ?>
                                            </small>
                                            <div class="btn-group">
                                                <a href="edit_content.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="delete_media.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce contenu ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="mediaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="mediaModalLabel">Prévisualisation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center" id="modalMediaContent">
                    <!-- Contenu dynamique -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    <a href="#" class="btn btn-primary" id="downloadMedia">Télécharger</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrage dynamique
        document.querySelectorAll('#filterCategory, #filterType').forEach(select => {
            select.addEventListener('change', filterContent);
        });

        function filterContent() {
            const category = document.getElementById('filterCategory').value;
            const type = document.getElementById('filterType').value;
            
            document.querySelectorAll('.content-item').forEach(item => {
                const showCategory = (category === 'all') || (item.dataset.category === category);
                const showType = (type === 'all') || (item.dataset.type === type);
                
                item.style.display = (showCategory && showType) ? 'block' : 'none';
            });
        }

        // Chargement des références
        document.getElementById('categorySelect').addEventListener('change', async function() {
            const category = this.value;
            const refSelect = document.getElementById('referenceSelect');
            
            if (category === 'general' || category === '') {
                refSelect.innerHTML = '<option value="">Aucun</option>';
                return;
            }
            
            try {
                const response = await fetch(`api/get_references.php?category=${category}`);
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

        // Prévisualisation des médias
        document.querySelectorAll('.media-preview').forEach(preview => {
            preview.addEventListener('click', function() {
                const card = this.closest('.content-item');
                const mediaUrl = card.dataset.mediaUrl;
                const mediaType = card.dataset.type;
                const title = card.querySelector('.card-title').textContent;
                
                document.getElementById('mediaModalLabel').textContent = title;
                document.getElementById('downloadMedia').href = `../uploads/medias/${mediaUrl}`;
                
                let mediaHtml = '';
                if (mediaType === 'image') {
                    mediaHtml = `<img src="../uploads/medias/${mediaUrl}" class="img-fluid" style="max-height: 70vh;">`;
                } else if (mediaType === 'video') {
                    mediaHtml = `<video controls class="w-100"><source src="../uploads/medias/${mediaUrl}"></video>`;
                } else {
                    mediaHtml = `<iframe src="../uploads/medias/${mediaUrl}" class="w-100" style="height: 70vh;"></iframe>`;
                }
                
                document.getElementById('modalMediaContent').innerHTML = mediaHtml;
                new bootstrap.Modal(document.getElementById('mediaModal')).show();
            });
        });

        // Auto-complétion du formulaire
        document.getElementById('referenceSelect').addEventListener('change', async function() {
            const category = document.getElementById('categorySelect').value;
            const refId = this.value;
            
            if (!refId || category === 'general') return;
            
            try {
                const response = await fetch(`api/get_reference_details.php?category=${category}&id=${refId}`);
                const data = await response.json();
                
                if (data) {
                    document.querySelector('input[name="titre"]').value = data.titre || '';
                    document.querySelector('textarea[name="description"]').value = data.description || '';
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        });
    </script>
</body>
</html>
