<?php
session_start();
require_once 'includes/db-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si la méthode n'est pas POST, rediriger vers register.php
    header('Location: register.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données
    $prenom = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
    $nom = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $birthDate = $_POST['birthDate'] ?? null;
    $interests = $_POST['interests'] ?? [];
    $memberCode = $_POST['memberCode'] ?? '';
    $role = $_POST['role'] ?? 'utilisateur'; // Valeur par défaut
    
    // Validation
    $errors = [];

    if (empty($prenom)) $errors[] = "Le prénom est obligatoire";
    if (empty($nom)) $errors[] = "Le nom est obligatoire";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    if (strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    if ($password !== $confirmPassword) $errors[] = "Les mots de passe ne correspondent pas";

    // Vérification de l'email existant
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "Cet email est déjà utilisé";
        }
    } catch (PDOException $e) {
        $errors[] = "Erreur de vérification de l'email";
    }

    // Validation spécifique aux membres
    if ($role === 'membre') {
        if (empty($memberCode)) {
            $errors[] = "Le code membre est obligatoire";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM codes_membres WHERE code = ? AND utilise = 0");
                $stmt->execute([$memberCode]);
                if (!$stmt->fetch()) {
                    $errors[] = "Code membre invalide ou déjà utilisé";
                }
            } catch (PDOException $e) {
                $errors[] = "Erreur de vérification du code membre";
            }
        }
    }

    // Gestion de l'image de profil
    $profileImage = null;
    if (isset($_FILES['profileImage']) && $_FILES['profileImage']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = $_FILES['profileImage']['type'];
        
        if (in_array($fileType, $allowedTypes)) {
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $extension = pathinfo($_FILES['profileImage']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $destination = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['profileImage']['tmp_name'], $destination)) {
                $profileImage = $destination;
            } else {
                $errors[] = "Erreur lors de l'upload de l'image";
            }
        } else {
            $errors[] = "Type de fichier non autorisé. Formats acceptés: JPEG, PNG, GIF";
        }
    }

    // Si erreurs, rediriger
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: register.php');
        exit();
    }

    // Hasher le mot de passe
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    $interestsJson = json_encode($interests);

    // Démarrer une transaction
    $pdo->beginTransaction();

    try {
        // Insertion de l'utilisateur
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (prenom, nom, email, password, telephone, adresse, date_naissance, photo_profil, centres_interet, role, date_inscription) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $prenom,
            $nom,
            $email,
            $passwordHash,
            $phone,
            $address,
            $birthDate,
            $profileImage,
            $interestsJson,
            $role
        ]);

        $userId = $pdo->lastInsertId();

        // Si membre, marquer le code comme utilisé
        if ($role === 'membre') {
            $stmt = $pdo->prepare("UPDATE codes_membres SET utilise = 1, user_id = ? WHERE code = ?");
            $stmt->execute([$userId, $memberCode]);
        }

        // Valider la transaction
        $pdo->commit();

        // Créer la session
        $_SESSION['user'] = [
            'id' => $userId,
            'email' => $email,
            'role' => $role,
            'photo' => $profileImage,
            'nom' => $nom,
            'prenom' => $prenom
        ];

        // Redirection selon le rôle
        $redirectPage = match($role) {
            'admin' => 'admin/dashboard.php',
            'membre' => 'membre/dashboard.php',
            default => 'accueil.php'
        };

        $_SESSION['success'] = "Inscription réussie! Bienvenue sur UJEM";
        header("Location: $redirectPage");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['errors'] = ["Erreur lors de l'inscription: " . $e->getMessage()];
        header('Location: register.php');
        exit();
    }
} else {
    header('Location: register.php');
    exit();
}
?>