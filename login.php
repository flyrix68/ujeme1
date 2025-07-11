<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Connexion</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .auth-container {
            max-width: 500px;
            margin: auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .social-login .btn {
            width: 100%;
            margin-bottom: 1rem;
            text-align: left;
        }
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
        }
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        .divider-text {
            padding: 0 1rem;
            color: #6c757d;
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
            
                <div class="d-flex">
                    <a href="index.php" class="btn btn-outline-light me-2 active">Connexion</a>
                    <a href="register.php" class="btn btn-light">Inscription</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Section de connexion -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="auth-container bg-white">
                        <div class="auth-header">
                            <h2><i class="fas fa-sign-in-alt text-primary me-2"></i> Connexion</h2>
                            <p class="text-muted">Accédez à votre compte UJEM</p>
                        </div>

                        <!-- Connexion avec réseaux sociaux -->
                        <div class="social-login mb-4">
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fab fa-google me-2"></i> Continuer avec Google
                            </a>
                            <a href="#" class="btn btn-outline-primary">
                                <i class="fab fa-facebook-f me-2"></i> Continuer avec Facebook
                            </a>
                        </div>

                        <!-- Séparateur -->
                        <div class="divider">
                            <span class="divider-text">OU</span>
                        </div>

                        <!-- Formulaire de connexion -->
                        <form action="process_login.php" method="POST">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="votre@email.com" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3 d-flex justify-content-between">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe">
                                    <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                                </div>
                                <a href="forgot_password.php" class="text-primary">Mot de passe oublié ?</a>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                                </button>
                            </div>
                        </form>

                        <div class="text-center mt-4">
                            <p>Vous n'avez pas de compte ? <a href="register.php" class="text-primary">S'inscrire</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pied de page (identique à l'accueil) -->
   <?php include 'includes/footer.php'; ?>

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
    </script>
</body>
</html>