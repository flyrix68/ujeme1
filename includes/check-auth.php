<?php
session_start();

// Redirige si l'utilisateur n'est pas connecté
if (!isset($_SESSION['user'])) {
    $_SESSION['error'] = "Veuillez vous connecter";
    header('Location: ../index.php');
    exit();
}

// Vérifie que la session est toujours valide
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    // Session expirée après 1h d'inactivité
    session_unset();
    session_destroy();
    header('Location: ../index.php?expired=1');
    exit();
}

$_SESSION['last_activity'] = time();
?>