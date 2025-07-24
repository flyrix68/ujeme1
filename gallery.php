<!DOCTYPE html>
<html lang="fr">
<head>
    <!-- Même head que index.php -->
    <title>UJEM - Galerie Photos</title>
    <!-- Lightbox CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Même navbar que index.php -->
    <!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Galerie Photos</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Lightbox CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <!-- Barre de navigation -->
    <?php include 'includes/navbar.php'; ?>

    <main class="container py-5">
        <h1 class="text-center mb-5">Galerie Photos</h1>
        
        <ul class="nav nav-tabs mb-4" id="galleryTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all">Toutes</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#football">Football</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#miss">Concours Miss</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#education">Cours</button>
            </li>
        </ul>
        
        <div class="tab-content">
            <div class="tab-pane fade show active" id="all">
                <div class="row g-3">
                    <!-- Toutes les photos -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/gallery-1.jpg" data-lightbox="gallery" data-title="Match de football">
                            <img src="img/gallery-1-thumb.jpg" class="img-fluid rounded" alt="Match de football">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/gallery-2.jpg" data-lightbox="gallery" data-title="Finale Miss 2024">
                            <img src="img/gallery-2-thumb.jpg" class="img-fluid rounded" alt="Finale Miss 2024">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/gallery-3.jpg" data-lightbox="gallery" data-title="Cours de mathématiques">
                            <img src="img/gallery-3-thumb.jpg" class="img-fluid rounded" alt="Cours de mathématiques">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/gallery-4.jpg" data-lightbox="gallery" data-title="Entraînement d'équipe">
                            <img src="img/gallery-4-thumb.jpg" class="img-fluid rounded" alt="Entraînement d'équipe">
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="football">
                <div class="row g-3">
                    <!-- Photos football -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/football-1.jpg" data-lightbox="football" data-title="Entraînement des juniors">
                            <img src="img/football-1-thumb.jpg" class="img-fluid rounded" alt="Entraînement des juniors">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/football-2.jpg" data-lightbox="football" data-title="Match des seniors">
                            <img src="img/football-2-thumb.jpg" class="img-fluid rounded" alt="Match des seniors">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/football-3.jpg" data-lightbox="football" data-title="Tournoi inter-écoles">
                            <img src="img/football-3-thumb.jpg" class="img-fluid rounded" alt="Tournoi inter-écoles">
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="miss">
                <div class="row g-3">
                    <!-- Photos concours Miss -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/miss-1.jpg" data-lightbox="miss" data-title="Cérémonie d'ouverture">
                            <img src="img/miss-1-thumb.jpg" class="img-fluid rounded" alt="Cérémonie d'ouverture">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/miss-2.jpg" data-lightbox="miss" data-title="Défilé des candidates">
                            <img src="img/miss-2-thumb.jpg" class="img-fluid rounded" alt="Défilé des candidates">
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="tab-pane fade" id="education">
                <div class="row g-3">
                    <!-- Photos cours -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/education-1.jpg" data-lightbox="education" data-title="Cours de sciences">
                            <img src="img/education-1-thumb.jpg" class="img-fluid rounded" alt="Cours de sciences">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/education-2.jpg" data-lightbox="education" data-title="Atelier littéraire">
                            <img src="img/education-2-thumb.jpg" class="img-fluid rounded" alt="Atelier littéraire">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Pied de page -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5 class="mb-3"><i class="fas fa-trophy"></i> UJEM</h5>
                    <p>Une association engagée pour l'épanouissement de notre jeunesse à travers le sport, la culture et l'éducation.</p>
                    <div class="mt-3">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-md-2 mb-4 mb-md-0">
                    <h5 class="mb-3">Liens</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-light">Accueil</a></li>
                        <li class="mb-2"><a href="teams.php" class="text-light">Football</a></li>
                        <li class="mb-2"><a href="contest.php" class="text-light">Concours Miss</a></li>
                        <li class="mb-2"><a href="courses.php" class="text-light">Cours</a></li>
                        <li class="mb-2"><a href="gallery.php" class="text-light">Galerie</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h5 class="mb-3">Nous contacter</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Rue Principale, Ville</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +XX XX XX XX XX</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@ujem.org</li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5 class="mb-3">Horaires</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">Lundi - Vendredi: 09:00 - 18:00</li>
                        <li class="mb-2">Samedi: 10:00 - 16:00</li>
                        <li class="mb-2">Dimanche: Fermé</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; 2025 UJEM. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="privacy.php" class="text-light me-3">Politique de confidentialité</a>
                    <a href="terms.php" class="text-light">Conditions d'utilisation</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <!-- JavaScript personnalisé -->
    <script src="/assets/js/main.js"></script>
</body>
</html>
    <main class="container py-5">
        <h1 class="text-center mb-5">Galerie Photos</h1>
        
        <ul class="nav nav-tabs mb-4" id="galleryTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#all">Toutes</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#football">Football</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#miss">Concours Miss</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#education">Cours</button>
            </li>
        </ul>
        
        <div class="tab-content">
            <div class="tab-pane fade show active" id="all">
                <div class="row g-3">
                    <!-- Toutes les photos -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/gallery-1.jpg" data-lightbox="gallery" data-title="Match de football">
                            <img src="img/gallery-1-thumb.jpg" class="img-fluid rounded" alt="">
                        </a>
                    </div>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/gallery-2.jpg" data-lightbox="gallery" data-title="Finale Miss 2024">
                            <img src="img/gallery-2-thumb.jpg" class="img-fluid rounded" alt="">
                        </a>
                    </div>
                    <!-- Ajouter d'autres images -->
                </div>
            </div>
            
            <div class="tab-pane fade" id="football">
                <div class="row g-3">
                    <!-- Photos football -->
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="img/football-1.jpg" data-lightbox="football" data-title="Entraînement des juniors">
                            <img src="img/football-1-thumb.jpg" class="img-fluid rounded" alt="">
                        </a>
                    </div>
                    <!-- Ajouter d'autres images football -->
                </div>
            </div>
            
            <!-- Autres onglets de galerie -->
        </div>
    </main>
    
    <!-- Même footer que index.php -->
    <?php include 'includes/footer.php'; ?>
    <!-- JavaScript Bootstrap -->   
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
</body>
</html>
