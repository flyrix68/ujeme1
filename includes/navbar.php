<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Navigation</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="accueil.php">
                <i class="fas fa-trophy"></i> UJEM
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="accueil.php">Accueil</a>
                    </li>
                    <!-- Menu déroulant Sport -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-futbol"></i> Football
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="teams.php">Équipes</a></li>
                            <li><a class="dropdown-item" href="matches.php">Matchs & Résultats</a></li>
                            <li><a class="dropdown-item" href="facilities.php">Terrains & Réservations</a></li>
                        </ul>
                    </li>
                    <!-- Menu déroulant Culture -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-crown"></i> Concours Miss
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="contest.php">Édition actuelle</a></li>
                            <li><a class="dropdown-item" href="candidates.php">Candidates</a></li>
                            <li><a class="dropdown-item" href="tickets.php">Billetterie</a></li>
                        </ul>
                    </li>
                    <!-- Menu déroulant Éducation -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-book"></i> Cours de Vacances
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="courses.php">Nos cours</a></li>
                            <li><a class="dropdown-item" href="enrollment.php">Inscription</a></li>
                            <li><a class="dropdown-item" href="resources.php">Ressources</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="gallery.php">Galerie</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</body>
</html>
