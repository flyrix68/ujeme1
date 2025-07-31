<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Database connection
require_once __DIR__ . '/includes/db-ssl.php';

// Verify database connection
if (!isset($pdo) || $pdo === null) {
    die("Erreur de connexion à la base de données");
}

// Pagination settings
$articlesPerPage = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articlesPerPage;

// Fetch total number of articles
try {
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM medias_actualites WHERE statut = 'publie'");
    $totalArticles = $totalStmt->fetchColumn();
    $totalPages = ceil($totalArticles / $articlesPerPage);
} catch (PDOException $e) {
    error_log("Error fetching total articles: " . $e->getMessage());
    $totalArticles = 0;
    $totalPages = 1;
}

// Fetch news articles with author name
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom AS author_name,
               CASE 
                   WHEN m.categorie = 'match' THEN CONCAT(ma.team_home, ' vs ', ma.team_away)
                   WHEN m.categorie = 'concours' THEN bc.title
                   ELSE m.titre
               END AS reference_title
        FROM medias_actualites m
        JOIN users u ON m.auteur_id = u.id
        LEFT JOIN matches ma ON m.categorie = 'match' AND m.reference_id = ma.id
        LEFT JOIN beauty_contests bc ON m.categorie = 'concours' AND m.reference_id = bc.id
        WHERE m.statut = 'publie'
        ORDER BY m.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $articlesPerPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $newsArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching news articles: " . $e->getMessage());
    $newsArticles = [];
}

// Map category to display names
$categoryDisplay = [
    'match' => 'Football',
    'concours' => 'Concours Miss',
    'cours' => 'Cours de vacances',
    'general' => 'Actualité générale'
];

// Map media types to icons
$mediaIcons = [
    'image' => 'far fa-image',
    'video' => 'fas fa-video',
    'document' => 'fas fa-file-pdf'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actualités | UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .news-card {
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }
        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .news-img-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        .news-img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }
        .media-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 1.5rem;
            color: white;
            text-shadow: 0 0 5px rgba(0,0,0,0.5);
        }
        .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation (identique à votre code existant) -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Main Content -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Actualités UJEM</h2>
            
            <?php if (empty($newsArticles)): ?>
                <div class="alert alert-info text-center">Aucune actualité disponible pour le moment.</div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($newsArticles as $article): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card news-card h-100">
                                <div class="news-img-container">
                                    <?php if ($article['media_type'] === 'image'): ?>
                                        <img src="uploads/medias/<?= htmlspecialchars($article['media_url']) ?>" 
                                             class="news-img" 
                                             alt="<?= htmlspecialchars($article['titre']) ?>"
                                             onerror="this.onerror=null;this.src='assets/img/default-news.jpg'">
                                    <?php else: ?>
                                        <div class="news-img bg-secondary d-flex align-items-center justify-content-center">
                                            <i class="<?= $mediaIcons[$article['media_type']] ?> fa-4x text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                    <i class="media-badge <?= $mediaIcons[$article['media_type']] ?>"></i>
                                    <span class="badge bg-primary category-badge">
                                        <?= htmlspecialchars($categoryDisplay[$article['categorie']]) ?>
                                    </span>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title"><?= htmlspecialchars($article['titre']) ?></h5>
                                    <p class="card-text flex-grow-1">
                                        <?= htmlspecialchars(substr($article['description'], 0, 100)) ?>
                                        <?= strlen($article['description']) > 100 ? '...' : '' ?>
                                    </p>
                                    <?php if ($article['categorie'] !== 'general'): ?>
                                        <p class="small text-muted mb-2">
                                            <i class="fas fa-link me-1"></i>
                                            <?= htmlspecialchars($article['reference_title']) ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer bg-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <?= htmlspecialchars($article['author_name']) ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y', strtotime($article['created_at'])) ?>
                                        </small>
                                    </div>
                                    <a href="news-detail.php?id=<?= $article['id'] ?>" class="stretched-link"></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Pagination" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer (identique à votre code existant) -->
    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activer les tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>