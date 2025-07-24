<?php
// Définir le fuseau horaire dès le début
date_default_timezone_set('Africa/Abidjan'); // Adaptez à votre fuseau

require_once 'includes/db-config.php';

try {
    $pdo = DatabaseConfig::getConnection();
    // Configuration du debug
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (RuntimeException $e) {
    $error = "Erreur de connexion à la base de données: " . $e->getMessage();
    error_log($error);
    $evenements = [];
    // Skip the SQL query by including the HTML directly
    include 'includes/navbar.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements | UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .debug-panel {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
            <div class="text-center mb-5">
                <h1 class="display-4">Nos Événements</h1>
            </div>
            <div class="col-12 text-center py-5">
                <div class="alert alert-danger">
                    <h4>Connexion à la base de données échouée</h4>
                    <p>Impossible de charger les événements. Veuillez réessayer plus tard.</p>
                </div>
            </div>
        </div>
    </section>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php
    exit();
}

// Activer l'affichage des erreurs (temporairement)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $sql = "SELECT * FROM evenements 
        WHERE (date_debut BETWEEN DATE_SUB(NOW(), INTERVAL 6 HOUR) AND DATE_ADD(NOW(), INTERVAL 1 MONTH))
        AND statut = 'actif'
        ORDER BY date_debut ASC";
    // Debug: Afficher la requête
    
    
    $stmt = $pdo->query($sql);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
   
    
} catch (PDOException $e) {
    $error = "Erreur SQL: " . $e->getMessage();
    error_log($error);
    $evenements = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Événements | UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
        }
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .event-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        .event-category {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .debug-panel {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <section class="py-5 bg-light">
        <div class="container">
            <!-- Panel de debug -->
            <div class="debug-panel">
                <h5>Informations de Debug</h5>
                <p>Nombre d'événements: <?= count($evenements) ?></p>
                <p>Heure serveur: <?= date('Y-m-d H:i:s') ?></p>
                <?php if (!empty($evenements)): ?>
                    <p>Premier événement date: <?= $evenements[0]['date_debut'] ?></p>
                <?php endif; ?>
            </div>

            <div class="text-center mb-5">
                <h1 class="display-4">Nos Événements</h1>
                <p class="lead">Découvrez toutes nos activités à venir</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="row" id="eventsContainer">
                <?php if (!empty($evenements)): ?>
                    <?php foreach ($evenements as $event): ?>
                        <?php
                        // Debug pour chaque événement
                        $imageExists = false;
                        $imagePath = 'uploads/events/' . $event['image'];
                        
                        if (!empty($event['image']) && file_exists($imagePath)) {
                            $imageExists = true;
                        }
                        ?>
                        
                        <div class="col-lg-4 col-md-6 mb-4 event-item" 
                             data-category="<?= htmlspecialchars($event['categorie']) ?>"
                             data-title="<?= strtolower(htmlspecialchars($event['titre'])) ?>">
                             
                            <div class="card h-100 event-card">
                                <?php if ($imageExists): ?>
                                    <img src="<?= $imagePath ?>" 
                                         class="card-img-top" 
                                         alt="<?= htmlspecialchars($event['titre']) ?>"
                                         style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" 
                                         style="height: 200px;">
                                        <i class="fas fa-calendar-alt fa-4x text-white"></i>
                                        <?php if (!empty($event['image'])): ?>
                                            <p class="text-white small mt-2">Image non trouvée</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <span class="event-category badge bg-<?= 
                                    $event['categorie'] === 'sport' ? 'primary' : 
                                    ($event['categorie'] === 'culture' ? 'warning' : 'success')
                                ?>">
                                    <?= ucfirst($event['categorie']) ?>
                                </span>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($event['titre']) ?></h5>
                                    <p class="card-text">
                                        <i class="fas fa-calendar-day me-2"></i>
                                        <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?>
                                    </p>
                                    <p class="card-text">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        <?= htmlspecialchars($event['lieu'] ?? 'Non spécifié') ?>
                                    </p>
                                    <p class="card-text"><?= substr(htmlspecialchars($event['description']), 0, 100) ?>...</p>
                                </div>
                                <div class="card-footer bg-white">
                                    <a href="event_detail.php?id=<?= $event['id'] ?>" class="btn btn-primary">
                                        Voir détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <h4>Aucun événement à venir pour le moment</h4>
                            <p>Revenez plus tard pour découvrir nos prochaines activités</p>
                            
                            <!-- Debug supplémentaire -->
                            <?php if (empty($evenements)): ?>
                                <div class="mt-3 text-start">
                                    <h5>Raisons possibles :</h5>
                                    <ul>
                                        <li>Aucun événement dans la base de données</li>
                                        <li>Tous les événements sont passés</li>
                                        <li>Le statut n'est pas 'actif'</li>
                                        <li>Problème de connexion à la base</li>
                                    </ul>
                                    <p>Requête exécutée :<br><code><?= htmlspecialchars($sql ?? '') ?></code></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Filtrage des événements
        function filterEvents() {
            const category = document.getElementById('categoryFilter').value;
            const searchText = document.getElementById('searchEvent').value.toLowerCase();
            
            document.querySelectorAll('.event-item').forEach(item => {
                const itemCategory = item.getAttribute('data-category');
                const itemTitle = item.getAttribute('data-title');
                
                const categoryMatch = category === 'all' || itemCategory === category;
                const searchMatch = itemTitle.includes(searchText);
                
                item.style.display = categoryMatch && searchMatch ? 'block' : 'none';
            });
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('categoryFilter').addEventListener('change', filterEvents);
            document.getElementById('searchEvent').addEventListener('input', filterEvents);
            
            // Afficher un message si aucun événement après filtrage
            filterEvents();
        });
    </script>
</body>
</html>
