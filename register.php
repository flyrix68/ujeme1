<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Inscription</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .auth-container {
            max-width: 600px;
            margin: auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .form-section {
            margin-bottom: 2rem;
        }
        .section-title {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }
    </style>
</head>
<body>
    <!-- Barre de navigation (identique à l'accueil) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-trophy"></i> UJEM
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <!-- Autres éléments de menu... -->
                </ul>
                <div class="d-flex">
                    <a href="login.php" class="btn btn-outline-light me-2">Connexion</a>
                    <a href="register.php" class="btn btn-light active">Inscription</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Section d'inscription -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="auth-container bg-white">
                        <div class="auth-header">
                            <h2><i class="fas fa-user-plus text-primary me-2"></i> Créer un compte</h2>
                            <p class="text-muted">Rejoignez la communauté UJEM en quelques étapes</p>
                        </div>

                        <form action="process_register.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <h3 class="section-title"><i class="fas fa-user-tag me-2"></i>Type de Compte</h3>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="roleUser" value="utilisateur" checked>
                                    <label class="form-check-label" for="roleUser">
                                        <i class="fas fa-user me-1"></i> Utilisateur standard
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="radio" name="role" id="roleMember" value="membre">
                                    <label class="form-check-label" for="roleMember">
                                        <i class="fas fa-id-card me-1"></i> Membre de l'association
                                    </label>
                                </div>
                                
                                <div id="memberCodeField" class="member-code-field mt-3">
                                    <label for="memberCode" class="form-label">Code Membre <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                        <input type="password" class="form-control" id="memberCode" name="memberCode" placeholder="Votre code membre">
                                    </div>
                                    <small class="form-text text-muted">Ce code vous a été remis lors de votre adhésion</small>
                                </div>
                            </div>
                            <!-- Section Informations personnelles -->
                            <div class="form-section">
                                <h3 class="section-title"><i class="fas fa-user-circle me-2"></i>Informations personnelles</h3>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="firstName" class="form-label">Prénom</label>
                                        <input type="text" class="form-control" id="firstName" name="firstName" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="lastName" class="form-label">Nom</label>
                                        <input type="text" class="form-control" id="lastName" name="lastName" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="birthDate" class="form-label">Date de naissance</label>
                                    <input type="date" class="form-control" id="birthDate" name="birthDate">
                                </div>
                                <div class="mb-3">
                                    <label for="profileImage" class="form-label">Photo de profil (optionnel)</label>
                                    <input class="form-control" type="file" id="profileImage" name="profileImage" accept="image/*">
                                </div>
                            </div>

                            <!-- Section Contact -->
                            <div class="form-section">
                                <h3 class="section-title"><i class="fas fa-address-book me-2"></i>Coordonnées</h3>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Téléphone</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control" id="phone" name="phone">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="address" class="form-label">Adresse</label>
                                        <input type="text" class="form-control" id="address" name="address">
                                    </div>
                                </div>
                            </div>

                            <!-- Section Sécurité -->
                            <div class="form-section">
                                <h3 class="section-title"><i class="fas fa-lock me-2"></i>Sécurité</h3>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Doit contenir au moins 8 caractères</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirmer le mot de passe</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Section Activités -->
                            <div class="form-section">
                                <h3 class="section-title"><i class="fas fa-heart me-2"></i>Centres d'intérêt</h3>
                                <div class="mb-3">
                                    <label class="form-label">Activités qui vous intéressent (choix multiple)</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="interestFootball" name="interests[]" value="football">
                                        <label class="form-check-label" for="interestFootball">Football</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="interestMiss" name="interests[]" value="miss">
                                        <label class="form-check-label" for="interestMiss">Concours Miss</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="interestCourses" name="interests[]" value="courses">
                                        <label class="form-check-label" for="interestCourses">Cours de vacances</label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="termsAgreement" required>
                                <label class="form-check-label" for="termsAgreement">J'accepte les <a href="terms.php" class="text-primary">conditions d'utilisation</a> et la <a href="privacy.php" class="text-primary">politique de confidentialité</a></label>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i> S'inscrire
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p>Vous avez déjà un compte ? <a href="index.php" class="text-primary">Se connecter</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pied de page (identique à l'accueil) -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <!-- ... Même pied de page que l'accueil ... -->
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour afficher/masquer le mot de passe
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Validation du formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas!');
            }
        });

           document.addEventListener('DOMContentLoaded', function() {
            const roleMember = document.getElementById('roleMember');
            const memberCodeField = document.getElementById('memberCodeField');
            
            document.querySelectorAll('input[name="role"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    memberCodeField.style.display = this.value === 'membre' ? 'block' : 'none';
                    document.getElementById('memberCode').required = this.value === 'membre';
                });
            });
        });
    </script>
</body>
</html>
