<?php
require 'includes/db-config.php';

try {
    // Récupérer les cours avec le nombre d'inscriptions
    $courses = $pdo->query("
        SELECT 
            c.id,
            c.title,
            c.description,
            c.start_date,
            c.end_date,
            c.schedule,
            c.location,
            c.max_students,
            c.fee,
            c.session,
            c.level,
            COUNT(e.id) as enrolled_count,
            s.name as subject_name,
            t.name as teacher_name
        FROM courses c
        LEFT JOIN course_enrollments e ON c.id = e.course_id
        LEFT JOIN subjects s ON c.subject_id = s.id
        LEFT JOIN teachers t ON c.teacher_id = t.id
        WHERE c.session = '2025' AND c.level = 'primaire'
        GROUP BY c.id
        ORDER BY c.start_date, c.title
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les statistiques
    $stats = $pdo->query("
        SELECT 
            COUNT(DISTINCT e.student_id) as total_students,
            COUNT(e.id) as total_enrollments,
            COUNT(DISTINCT c.id) as total_courses
        FROM course_enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE c.session = '2025'
    ")->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de base de données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours de Vacances 2025</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="text-center mb-5">
            <h1 class="display-4">Cours de Vacances 2025</h1>
            <div class="stats-container my-4">
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['total_courses'] ?? 0 ?></div>
                    <div class="stat-label">Cours disponibles</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['total_students'] ?? 0 ?></div>
                    <div class="stat-label">Élèves inscrits</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['total_enrollments'] ?? 0 ?></div>
                    <div class="stat-label">Inscriptions</div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach ($courses as $course): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 course-card">
                        <div class="card-header bg-primary text-white">
                            <h3><?= htmlspecialchars($course['title']) ?></h3>
                            <div class="badge bg-light text-dark">
                                <?= htmlspecialchars($course['subject_name']) ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                <i class="fas fa-chalkboard-teacher"></i> 
                                <?= htmlspecialchars($course['teacher_name']) ?>
                            </p>
                            
                            <p class="card-text">
                                <?= nl2br(htmlspecialchars($course['description'])) ?>
                            </p>
                            
                            <div class="course-meta mb-3">
                                <div class="meta-item">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?= date('d/m/Y', strtotime($course['start_date'])) ?> - 
                                    <?= date('d/m/Y', strtotime($course['end_date'])) ?>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-clock"></i>
                                    <?= htmlspecialchars($course['schedule']) ?>
                                </div>
                                <?php if ($course['location']): ?>
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($course['location']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-info">
                                        <?= $course['enrolled_count'] ?> inscrits
                                        <?php if ($course['max_students']): ?>
                                            / <?= $course['max_students'] ?> places
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-primary">
                                        <?= number_format($course['fee'], 2) ?> €
                                    </span>
                                </div>
                            </div>
                            <a href="course-detail.php?id=<?= $course['id'] ?>" class="btn btn-primary mt-2 w-100">
                                Détails & Inscription
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/scripts.js"></script>
</body>
</html>