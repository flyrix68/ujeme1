<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Log session data for debugging
error_log("Session data in profil.php: " . print_r($_SESSION, true));

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Database connection
require_once 'includes/db-config.php';

// Redirect if user is not logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    error_log("Redirecting to index.php: user_id not set");
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: index.php');
    exit();
}

// Verify database connection
if (!isset($pdo) || $pdo === null) {
    error_log("Critical error: Database connection not established in profil.php");
    die("A database connection error occurred. Please contact the administrator.");
}

// Fetch user info
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        error_log("User not found for user_id: " . $user_id);
        session_destroy();
        header('Location: index.php');
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}

// Handle profile update
$update_success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    // Verify CSRF token
    if (!isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Erreur de sécurité, veuillez réessayer.";
    } else {
        // Retrieve and validate data
        $prenom = trim($_POST['prenom'] ?? '');
        $nom = trim($_POST['nom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $adresse = trim($_POST['adresse'] ?? '');
    
        // Validation
        if (empty($prenom)) $errors[] = "Le prénom est obligatoire";
        if (empty($nom)) $errors[] = "Le nom est obligatoire";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    
        // Check if email is already used by another user
        if (!empty($email) && $email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "Cet email est déjà utilisé par un autre compte";
            }
        }
    
        // Handle photo upload
        $photo_profil = $user['photo_profil'] ?? 'assets/img/default-profile.png';
    
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $fileType = $_FILES['photo']['type'];
            $fileSize = $_FILES['photo']['size'];
            $maxFileSize = 2 * 1024 * 1024; // 2MB
        
            if ($fileSize > $maxFileSize) {
                $errors[] = "La taille du fichier ne doit pas dépasser 2MB";
            } elseif (!array_key_exists($fileType, $allowedTypes)) {
                $errors[] = "Type de fichier non autorisé. Formats acceptés: JPEG, PNG, GIF";
            } else {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $fileMimeType = $finfo->file($_FILES['photo']['tmp_name']);
            
                if (!array_key_exists($fileMimeType, $allowedTypes)) {
                    $errors[] = "Type de fichier incorrect";
                } else {
                    $uploadDir = 'uploads/profiles/';
                    if (!is_dir($uploadDir)) {
                        if (!mkdir($uploadDir, 0755, true)) {
                            $errors[] = "Impossible de créer le répertoire d'upload";
                        }
                    }
                
                    if (empty($errors)) {
                        $extension = $allowedTypes[$fileMimeType];
                        $filename = uniqid('profile_', true) . '.' . $extension;
                        $destination = $uploadDir . $filename;
                    
                        if (move_uploaded_file($_FILES['photo']['tmp_name'], $destination)) {
                            if (!empty($user['photo_profil']) && $user['photo_profil'] !== 'assets/img/default-profile.png' && file_exists($user['photo_profil'])) {
                                @unlink($user['photo_profil']);
                            }
                            $photo_profil = $destination;
                        } else {
                            $errors[] = "Erreur lors de l'upload de l'image";
                        }
                    }
                }
            }
        }
    
        // Update profile if no errors
        if (empty($errors)) {
            try {
                $sql = "UPDATE users SET 
                        prenom = ?, 
                        nom = ?, 
                        email = ?, 
                        telephone = ?, 
                        adresse = ?,
                        photo_profil = ?
                        WHERE id = ?";
            
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$prenom, $nom, $email, $telephone, $adresse, $photo_profil, $user_id]);
            
                if ($result) {
                    // Update session
                    $_SESSION['user_prenom'] = $prenom;
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_email'] = $email;
                
                    $update_success = true;
                    $user = array_merge($user, [
                        'prenom' => $prenom,
                        'nom' => $nom,
                        'email' => $email,
                        'telephone' => $telephone,
                        'adresse' => $adresse,
                        'photo_profil' => $photo_profil
                    ]);
                } else {
                    $errors[] = "Échec de la mise à jour, veuillez réessayer.";
                }
            } catch (PDOException $e) {
                error_log("Update error: " . $e->getMessage());
                $errors[] = "Une erreur est survenue lors de la mise à jour du profil.";
            }
        }
    }
}

// Fetch participations
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'participations'");
    $tableExists = $tableCheck->rowCount() > 0;
    
    if ($tableExists) {
        $stmt = $pdo->prepare("SELECT p.*, e.nom as evenement_nom, e.date 
                              FROM participations p 
                              JOIN evenements e ON p.evenement_id = e.id 
                              WHERE p.user_id = ?");
        $stmt->execute([$user_id]);
        $participations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $participations = [
            [
                'evenement_nom' => 'Tournoi de football',
                'date' => '2025-06-15',
                'statut' => 'Inscrit'
            ]
        ];
    }
} catch (PDOException $e) {
    error_log("Participation fetch error: " . $e->getMessage());
    $participations = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Mon Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .profile-card { border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .profile-header { background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; border-radius: 15px 15px 0 0; }
        .profile-pic { width: 150px; height: 150px; object-fit: cover; border: 5px solid white; }
        .nav-pills .nav-link.active { background-color: #0d6efd; }
        .form-control:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25); }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card profile-card">
                    <div class="profile-header text-center py-4">
                        <img src="<?= htmlspecialchars($user['photo_profil'] ?? 'assets/img/default-profile.png') ?>" 
                             alt="Photo de profil" class="profile-pic rounded-circle mb-3">
                        <h3><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></h3>
                        <p class="mb-0">Membre depuis <?= date('d/m/Y', strtotime($user['date_inscription'] ?? 'now')) ?></p>
                    </div>
                    <div class="card-body">
                        <ul class="nav nav-pills flex-column">
                            <li class="nav-item">
                                <a class="nav-link active" href="#infos" data-bs-toggle="tab">
                                    <i class="fas fa-user me-2"></i>Informations personnelles
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#password" data-bs-toggle="tab">
                                    <i class="fas fa-lock me-2"></i>Mot de passe
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#activities" data-bs-toggle="tab">
                                    <i class="fas fa-heart me-2"></i>Activités
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card profile-card">
                    <div class="card-body">
                        <?php if ($update_success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                Profil mis à jour avec succès!
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="infos">
                                <h4 class="mb-4"><i class="fas fa-user me-2"></i>Modifier mon profil</h4>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="prenom" class="form-label">Prénom</label>
                                            <input type="text" class="form-control" id="prenom" name="prenom" 
                                                   value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="nom" class="form-label">Nom</label>
                                            <input type="text" class="form-control" id="nom" name="nom" 
                                                   value="<?= htmlspecialchars($user['nom'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="telephone" class="form-label">Téléphone</label>
                                            <input type="tel" class="form-control" id="telephone" name="telephone" 
                                                   value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="adresse" class="form-label">Adresse</label>
                                            <input type="text" class="form-control" id="adresse" name="adresse" 
                                                   value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="photo" class="form-label">Photo de profil</label>
                                            <input class="form-control" type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/gif">
                                            <div class="form-text">Formats acceptés: JPG, PNG, GIF. Taille max: 2MB</div>
                                            <?php if (!empty($user['photo_profil'])): ?>
                                                <div class="form-text">Photo actuelle: 
                                                    <img src="<?= htmlspecialchars($user['photo_profil']) ?>" alt="Miniature" style="height: 30px; width: auto;">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-12 mt-4">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-save me-2"></i>Enregistrer les modifications
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="password">
                                <h4 class="mb-4"><i class="fas fa-lock me-2"></i>Changer mon mot de passe</h4>
                                <form method="POST" action="update_password.php">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                                       pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                                                       required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Minimum 8 caractères, incluant majuscule, minuscule, chiffre et caractère spécial</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-12 mt-4">
                                            <button type="submit" class="btn btn-primary px-4">
                                                <i class="fas fa-key me-2"></i>Changer le mot de passe
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="activities">
                                <h4 class="mb-4"><i class="fas fa-heart me-2"></i>Mes activités</h4>
                                <?php
                                $interests = [];
                                if (isset($user['centres_interet']) && !empty($user['centres_interet'])) {
                                    $interests = json_decode($user['centres_interet'], true);
                                    if (!is_array($interests)) {
                                        $interests = [];
                                    }
                                }
                                
                                if (!empty($interests)): ?>
                                    <div class="alert alert-info">
                                        <h5>Centres d'intérêt</h5>
                                        <ul class="mb-0">
                                            <?php foreach ($interests as $interest): ?>
                                                <li>
                                                    <?php 
                                                    switch($interest) {
                                                        case 'football': echo '<i class="fas fa-futbol me-2"></i>Football'; break;
                                                        case 'miss': echo '<i class="fas fa-crown me-2"></i>Concours Miss'; break;
                                                        case 'courses': echo '<i class="fas fa-book me-2"></i>Cours de vacances'; break;
                                                        default: echo htmlspecialchars($interest);
                                                    }
                                                    ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-warning">
                                        Vous n'avez sélectionné aucun centre d'intérêt.
                                        <a href="register.php?edit=interests" class="alert-link">Modifier mes préférences</a>
                                    </div>
                                <?php endif; ?>

                                <h5 class="mt-5">Mes participations</h5>
                                <div class="list-group">
                                    <?php if (!empty($participations)): ?>
                                        <?php foreach ($participations as $participation): ?>
                                            <div class="list-group-item">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?= htmlspecialchars($participation['evenement_nom']) ?></h6>
                                                    <small><?= date('d/m/Y', strtotime($participation['date'])) ?></small>
                                                </div>
                                                <p class="mb-1">Statut: <?= htmlspecialchars($participation['statut']) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            Vous n'avez pas encore participé à des événements.
                                            <a href="evenements.php" class="alert-link">Voir les événements disponibles</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            document.querySelectorAll('.toggle-password').forEach(function(button) {
                button.addEventListener('click', function() {
                    const input = this.closest('.input-group').querySelector('input');
                    const icon = this.querySelector('i');
                    if (input.type === 'password') {
                        input.type = 'text';
                        icon.classList.replace('fa-eye', 'fa-eye-slash');
                    } else {
                        input.type = 'password';
                        icon.classList.replace('fa-eye-slash', 'fa-eye');
                    }
                });
            });
            
            const passwordForm = document.querySelector('form[action="update_password.php"]');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;
                    if (newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('Les mots de passe ne correspondent pas');
                    }
                });
            }
        });
    </script>
</body>
</html>