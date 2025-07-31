<?php
require_once __DIR__ . '/includes/db-ssl.php';

$article_id = $_GET['id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.nom AS author_name,
               CASE 
                   WHEN m.categorie = 'match' THEN CONCAT(ma.team_home, ' vs ', ma.team_away, ' (', ma.score_home, '-', ma.score_away, ')')
                   WHEN m.categorie = 'concours' THEN bc.title
                   ELSE m.titre
               END AS reference_title
        FROM medias_actualites m
        JOIN users u ON m.auteur_id = u.id
        LEFT JOIN matches ma ON m.categorie = 'match' AND m.reference_id = ma.id
        LEFT JOIN beauty_contests bc ON m.categorie = 'concours' AND m.reference_id = bc.id
        WHERE m.id = ? AND m.statut = 'publie'
    ");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
} catch (PDOException $e) {
    die("Erreur lors de la récupération de l'article");
}

if (!$article) {
    header("Location: news.php");
    exit();
}

$categoryDisplay = [
    'match' => 'Football',
    'concours' => 'Concours Miss',
    'cours' => 'Cours de vacances',
    'general' => 'Actualité générale'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($article['titre']) ?> | UJEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <main class="container my-5">
        <article>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="mb-4">
                        <span class="badge bg-primary mb-2">
                            <?= htmlspecialchars($categoryDisplay[$article['categorie']]) ?>
                        </span>
                        <h1><?= htmlspecialchars($article['titre']) ?></h1>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <span class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?= htmlspecialchars($article['author_name']) ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= date('d/m/Y à H:i', strtotime($article['created_at'])) ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($article['categorie'] !== 'general'): ?>
                            <div class="alert alert-info py-2">
                                <i class="fas fa-link me-2"></i>
                                <?= htmlspecialchars($article['reference_title']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-5">
                        <?php if ($article['media_type'] === 'image'): ?>
                            <img src="uploads/medias/<?= htmlspecialchars($article['media_url']) ?>" 
                                 class="img-fluid rounded mb-4" 
                                 alt="<?= htmlspecialchars($article['titre']) ?>"
                                 onerror="this.onerror=null;this.src='assets/img/default-news.jpg'">
                        <?php elseif ($article['media_type'] === 'video'): ?>
                            <div class="ratio ratio-16x9 mb-4">
                                <video controls>
                                    <source src="uploads/medias/<?= htmlspecialchars($article['media_url']) ?>" type="video/mp4">
                                </video>
                            </div>
                        <?php endif; ?>
                        
                        <div class="article-content">
                            <?= nl2br(htmlspecialchars($article['description'])) ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="news.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Retour aux actualités
                        </a>
                        <div class="share-buttons">
                            <button class="btn btn-outline-secondary me-2">
                                <i class="fas fa-share-alt me-1"></i> Partager
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </article>
    </main>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>