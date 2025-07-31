<?php
require_once 'includes/db-config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);

    // Validation
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Veuillez entrer une adresse email valide";
    } else {
        try {
            // Vérifier si l'email existe
            $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Générer un token de réinitialisation
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Stocker le token dans la base
                $update_stmt = $pdo->prepare("UPDATE utilisateurs SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $update_stmt->execute([$token, $expires, $user['id']]);
                
                // Envoyer l'email (simulé ici)
                $reset_link = "https://votresite.com/reset_password.php?token=$token";
                
                // En production, utilisez une bibliothèque comme PHPMailer
                $success = true;
                
                // Pour le développement, affichez le lien
                $_SESSION['reset_link'] = $reset_link;
            }
            
            // Toujours afficher le succès (pour ne pas révéler si l'email existe)
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Erreur de base de données: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation du mot de passe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .auth-container {
            max-width: 500px;
            margin: 5rem auto;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-container bg-white">
            <h2 class="text-center mb-4"><i class="fas fa-key"></i> Mot de passe oublié</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p>Si votre email est enregistré, vous recevrez un lien de réinitialisation.</p>
                    <?php if (isset($_SESSION['reset_link'])): ?>
                        <p class="mt-3">Lien de test : <a href="<?= $_SESSION['reset_link'] ?>"><?= $_SESSION['reset_link'] ?></a></p>
                        <?php unset($_SESSION['reset_link']); ?>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p class="mb-0"><?= $error ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i> Envoyer le lien
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-2"></i>Retour à la connexion
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>