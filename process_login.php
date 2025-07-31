<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session with consistent settings
ini_set('session.gc_maxlifetime', 3600);
session_set_cookie_params(3600, '/');
session_start();

// Database connection with enhanced validation
require_once __DIR__ . '/includes/db-ssl.php';

try {
    $pdo = DatabaseSSL::getInstance()->getConnection();
    
    if (!$pdo) {
        throw new RuntimeException('Failed to get database connection');
    }
    
    // Test connection
    $pdo->query('SELECT 1');
    
    error_log("DB connection validated for login attempt");    
    
} catch (Exception $e) {
    error_log("Login DB error: " . $e->getMessage());
    $_SESSION['error'] = "Database connection failed. Please try again later.";
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        error_log("Login failed: Missing email or password");
        $_SESSION['error'] = "Tous les champs sont obligatoires";
        header('Location: index.php');
        exit();
    }

    try {
        // Fetch user
        $stmt = $pdo->prepare("SELECT id, prenom, nom, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['password']) && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'membre';

            error_log("Login successful for user_id: " . $user['id'] . ", role: " . $_SESSION['role']);

            // Redirect based on role
            $redirect = match($user['role'] ?? 'membre') {
                'admin' => 'admin/dashboard.php',
                'membre' => 'profil.php',
                default => 'profil.php'
            };

            // Debug: Verify redirect path
            if (!file_exists($redirect)) {
                error_log("Redirect file does not exist: $redirect");
                $_SESSION['error'] = "Page de redirection introuvable";
                header('Location: index.php');
                exit();
            }

            header("Location: $redirect");
            exit();
        } else {
            error_log("Login failed: Invalid credentials for email: $email");
            $_SESSION['error'] = "Identifiants incorrects";
            header('Location: index.php');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "Erreur technique lors de la connexion";
        header('Location: index.php');
        exit();
    }
}

// Fallback for non-POST requests
header('Location: index.php');
exit();
?>
