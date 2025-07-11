<?php
session_start();
require_once '.include/db_config.php';

$errors = [];
$success = false;
$token = $_GET['token'] ?? '';

// Vérifier le token
if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = "Lien invalide ou expiré";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur de base de données: " . $e->getMessage();
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (strlen($new_password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    if (empty($errors)) {
        try {
            // Hasher le nouveau mot de passe
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Mettre à jour et effacer le token
            $update_stmt = $pdo->prepare("UPDATE utilisateurs SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
            $update_stmt->execute([$password_hash, $token]);
            
            $success = true;
            $_SESSION['success'] = "Mot de passe réinitialisé avec succès";
            header('Location: login.php');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de la réinitialisation: " . $e->getMessage();
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
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Nouveau mot de passe</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?= $error ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$success && empty($errors) && !empty($token)): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>