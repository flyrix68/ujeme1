<?php
session_start();
require_once 'db_config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$errors = [];
$success = false;
$user_id = $_SESSION['user_id'];

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($current_password)) {
        $errors[] = "Le mot de passe actuel est requis";
    }
    if (strlen($new_password) < 8) {
        $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
    }
    if ($new_password !== $confirm_password) {
        $errors[] = "Les nouveaux mots de passe ne correspondent pas";
    }

    // Si pas d'erreurs, vérifier et mettre à jour
    if (empty($errors)) {
        try {
            // Récupérer le mot de passe actuel
            $stmt = $pdo->prepare("SELECT password FROM utilisateurs WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if ($user && password_verify($current_password, $user['password'])) {
                // Hasher le nouveau mot de passe
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Mettre à jour dans la base
                $update_stmt = $pdo->prepare("UPDATE utilisateurs SET password = ? WHERE id = ?");
                $update_stmt->execute([$new_password_hash, $user_id]);
                
                $success = true;
                $_SESSION['success'] = "Mot de passe mis à jour avec succès";
                header('Location: profil.php');
                exit();
            } else {
                $errors[] = "Mot de passe actuel incorrect";
            }
        } catch (PDOException $e) {
            $errors[] = "Erreur de base de données: " . $e->getMessage();
        }
    }
    
    // Stocker les erreurs pour les afficher
    $_SESSION['errors'] = $errors;
    header('Location: profil.php#password');
    exit();
} else {
    header('Location: profil.php');
    exit();
}
?>