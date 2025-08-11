<?php
// Inclure la configuration
require_once __DIR__ . '/bootstrap.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Accueil</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/assets/css/styles.css">
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
                <!-- Boutons Connexion/Inscription -->
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-light me-2 active">Connexion</a>
                    <a href="register.php" class="btn btn-light">Inscription</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Bannière principale avec carousel -->
    <div id="mainCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#mainCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <img src="img/football-banner.jpg" class="d-block w-100" alt="Football">
                <div class="carousel-caption">
                    <h2>Football pour tous</h2>
                    <p>Rejoignez nos équipes et partagez votre passion pour le foot.</p>
                    <a href="teams.php" class="btn btn-primary">Voir les équipes</a>
                </div>
            </div>
            <div class="carousel-item">
                <img src="img/miss-banner.jpg" class="d-block w-100" alt="Miss Contest">
                <div class="carousel-caption">
                    <h2>Concours Miss 2025</h2>
                    <p>Inscriptions ouvertes pour notre concours annuel.</p>
                    <a href="contest.php" class="btn btn-primary">En savoir plus</a>
                </div>
            </div>
            <div class="carousel-item">
                <img src="img/education-banner.jpg" class="d-block w-100" alt="Cours de vacances">
                <div class="carousel-caption">
                    <h2>Cours de vacances</h2>
                    <p>Préparez la rentrée avec nos cours de soutien scolaire.</p>
                    <a href="courses.php" class="btn btn-primary">Découvrir les cours</a>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#mainCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Précédent</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#mainCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Suivant</span>
        </button>
    </div>

    <!-- Présentation de l'association -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="section-title mb-4">Notre Association</h2>
                    <p class="lead mb-5">
                        UJEM est une Association à but non lucratif qui offre des activités sportives, culturelles et éducatives 
                        pour favoriser l'épanouissement de notre jeunesse. Fondée en 2020, notre mission est de créer un environnement 
                        inclusif où chacun peut développer ses talents aussi promouvoir notre localité.
                    </p>
                    <div class="row text-start mt-5">
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-futbol fa-3x text-primary mb-3"></i>
                                    <h3 class="card-title h5">Volet Sportif</h3>
                                    <p class="card-text">Organistion football (MARACANA) pour tous les âges, compétitions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-crown fa-3x text-primary mb-3"></i>
                                    <h3 class="card-title h5">Volet Culturel</h3>
                                    <p class="card-text">Concours Miss annuel valorisant talent, personnalité et engagement social.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-book fa-3x text-primary mb-3"></i>
                                    <h3 class="card-title h5">Volet Éducatif</h3>
                                    <p class="card-text">Cours de soutien pendant les vacances scolaires avec encadrement professionnel.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Calendrier des événements -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Prochains Événements</h2>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="list-group">
                        <div class="list-group-item list-group-item-action d-flex gap-3 align-items-center">
                            <div class="badge bg-primary text-white text-center p-2" style="min-width: 60px;">
                                <div class="h5 mb-0">15</div>
                                <small>Mai</small>
                            </div>
                            <div class="w-100">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><i class="fas fa-futbol text-primary me-2"></i> Match - Seniors vs FC Riviera</h5>
                                    <small>15:00</small>
                                </div>
                                <p class="mb-1">Stade Municipal, entrée libre pour les membres.</p>
                            </div>
                        </div>
                        <div class="list-group-item list-group-item-action d-flex gap-3 align-items-center">
                            <div class="badge bg-primary text-white text-center p-2" style="min-width: 60px;">
                                <div class="h5 mb-0">20</div>
                                <small>Mai</small>
                            </div>
                            <div class="w-100">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><i class="fas fa-crown text-primary me-2"></i> Casting Miss 2025</h5>
                                    <small>10:00</small>
                                </div>
                                <p class="mb-1">Centre culturel, inscriptions ouvertes jusqu'au 18 mai.</p>
                            </div>
                        </div>
                        <div class="list-group-item list-group-item-action d-flex gap-3 align-items-center">
                            <div class="badge bg-primary text-white text-center p-2" style="min-width: 60px;">
                                <div class="h5 mb-0">01</div>
                                <small>Juin</small>
                            </div>
                            <div class="w-100">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><i class="fas fa-book text-primary me-2"></i> Début des cours de vacances</h5>
                                    <small>08:00</small>
                                </div>
                                <p class="mb-1">Lycée Central, toutes matières pour primaire et secondaire.</p>
                            </div>
                        </div>
                        <div class="list-group-item list-group-item-action d-flex gap-3 align-items-center">
                            <div class="badge bg-primary text-white text-center p-2" style="min-width: 60px;">
                                <div class="h5 mb-0">12</div>
                                <small>Juin</small>
                            </div>
                            <div class="w-100">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><i class="fas fa-futbol text-primary me-2"></i> Tournoi juniors inter-écoles</h5>
                                    <small>09:00</small>
                                </div>
                                <p class="mb-1">Complexe sportif de la ville, journée entière de compétition.</p>
                            </div>
                        </div>
                    </div>
                    <div class="text-center mt-4">
                        <a href="events.php" class="btn btn-outline-primary">Voir tous les événements</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Actualités récentes -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-5">Dernières Actualités</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="img/news-football.jpg" class="card-img-top" alt="Actualité football">
                        <div class="card-body">
                            <span class="badge bg-primary mb-2">Football</span>
                            <h5 class="card-title">Victoire écrasante de notre équipe senior</h5>
                            <p class="card-text">Notre équipe senior a remporté une victoire 4-0 contre FC Riviera samedi dernier.</p>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <small class="text-muted">Publié le 05 mai 2025</small>
                            <a href="news-detail.php" class="btn btn-sm btn-link float-end">Lire plus</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="img/news-miss.jpg" class="card-img-top" alt="Actualité miss">
                        <div class="card-body">
                            <span class="badge bg-primary mb-2">Culture</span>
                            <h5 class="card-title">Ouverture des inscriptions pour Miss 2025</h5>
                            <p class="card-text">Les candidates peuvent désormais s'inscrire pour le concours Miss 2025.</p>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <small class="text-muted">Publié le 03 mai 2025</small>
                            <a href="news-detail.php" class="btn btn-sm btn-link float-end">Lire plus</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <img src="img/news-education.jpg" class="card-img-top" alt="Actualité éducation">
                        <div class="card-body">
                            <span class="badge bg-primary mb-2">Éducation</span>
                            <h5 class="card-title">Programme des cours de vacances 2025</h5>
                            <p class="card-text">Découvrez notre programme complet des cours de vacances pour cet été.</p>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <small class="text-muted">Publié le 28 avril 2025</small>
                            <a href="news-detail.php" class="btn btn-sm btn-link float-end">Lire plus</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="news.php" class="btn btn-outline-primary">Voir toutes les actualités</a>
            </div>
        </div>
    </section>

    <!-- Galerie photo -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Galerie Photos</h2>
            <div class="row g-3">
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-1.jpg" class="img-fluid rounded" alt="Galerie 1">
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-2.jpg" class="img-fluid rounded" alt="Galerie 2">
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-3.jpg" class="img-fluid rounded" alt="Galerie 3">
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-4.jpg" class="img-fluid rounded" alt="Galerie 4">
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 d-none d-md-block">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-5.jpg" class="img-fluid rounded" alt="Galerie 5">
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 d-none d-md-block">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-6.jpg" class="img-fluid rounded" alt="Galerie 6">
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 d-none d-lg-block">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-7.jpg" class="img-fluid rounded" alt="Galerie 7">
                    </a>
                </div>
                <div class="col-6 col-md-4 col-lg-3 d-none d-lg-block">
                    <a href="gallery.php" class="gallery-item">
                        <img src="img/gallery-8.jpg" class="img-fluid rounded" alt="Galerie 8">
                    </a>
                </div>
            </div>
            <div class="text-center mt-4">
                <a href="gallery.php" class="btn btn-outline-primary">Explorer la galerie</a>
            </div>
        </div>
    </section>

    <!-- Section de dons -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="mb-4">Soutenez notre association</h2>
                    <p class="lead mb-5">
                        Aidez-nous à continuer nos actions sportives, culturelles et éducatives en faisant un don. 
                        Chaque contribution compte pour financer nos projets et soutenir notre jeunesse.
                    </p>
                    <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#donationModal">
                        Faire un don
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Formulaire d'inscription à la newsletter -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 text-center">
                    <h3>Restez informé</h3>
                    <p class="mb-4">Abonnez-vous à notre newsletter pour recevoir nos actualités et événements à venir.</p>
                    <form class="row g-3 justify-content-center">
                        <div class="col-8">
                            <input type="email" class="form-control form-control-lg" placeholder="Votre adresse email" required>
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-primary btn-lg">S'abonner</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
    <!-- Carte de localisation -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title text-center mb-5">Où nous trouver</h2>
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div id="map" style="height: 400px;"></div>
                </div>
            </div>
        </div>
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
                        <li class="mb-2"><a href="news.php" class="text-light">Actualités</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h5 class="mb-3">Nous contacter</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Rue Principale, Ville</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +XX XX XX XX XX</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> contact@associationplus.org</li>
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

    <!-- Modal de don -->
    <div class="modal fade" id="donationModal" tabindex="-1" aria-labelledby="donationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="donationModalLabel">Faire un don</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <form id="donationForm">
                        <div class="mb-3">
                            <label for="donorName" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="donorName">
                        </div>
                        <div class="mb-3">
                            <label for="donorEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="donorEmail">
                        </div>
                        <div class="mb-3">
                            <label for="donorPhone" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="donorPhone">
                        </div>
                        <div class="mb-3">
                            <label for="donationAmount" class="form-label">Montant du don</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="donationAmount" min="5">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label d-block">Mode de paiement</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="paymentPaypal" value="paypal" checked>
                                <label class="form-check-label" for="paymentPaypal">PayPal</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="paymentMobile" value="mobile_money">
                                <label class="form-check-label" for="paymentMobile">Mobile Money</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="paymentMethod" id="paymentBank" value="bank_transfer">
                                <label class="form-check-label" for="paymentBank">Virement bancaire</label>
                            </div>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="anonymousDonation">
                            <label class="form-check-label" for="anonymousDonation">Faire un don anonyme</label>
                        </div>
                        <div class="mb-3">
                            <label for="donationMessage" class="form-label">Message (optionnel)</label>
                            <textarea class="form-control" id="donationMessage" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary" id="submitDonation">Confirmer le don</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Bootstrap & jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- JavaScript personnalisé -->
    <script src="assets/js/main.js"></script>
</body>
</html>
