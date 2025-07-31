<?php
session_start();
require_once __DIR__ . '/includes/db-ssl.php';
require_once 'includes/team_function.php';

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Vérifier que le formulaire a été soumis
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Accès invalide à cette page");
    }

    // Journalisation des données reçues (pour débogage)
    error_log("Données POST reçues: " . print_r($_POST, true));
    error_log("Fichiers reçus: " . print_r($_FILES, true));

    // Valider les champs obligatoires
    $requiredFields = [
        'team_name' => 'Nom de l\'équipe',
        'team_category' => 'Catégorie',
        'team_location' => 'Localisation',
        'manager_name' => 'Nom du responsable',
        'manager_email' => 'Email du responsable',
        'manager_phone' => 'Téléphone du responsable'
    ];

    foreach ($requiredFields as $field => $label) {
        if (empty($_POST[$field] ?? '')) {
            throw new InvalidArgumentException("Le champ '$label' est obligatoire");
        }
    }

    // Valider l'email
    if (!filter_var($_POST['manager_email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Format d'email invalide");
    }

    // Préparer les données pour la base
    $teamData = [
        'team_name' => htmlspecialchars(trim($_POST['team_name'])),
        'team_category' => htmlspecialchars(trim($_POST['team_category'])),
        'team_location' => htmlspecialchars(trim($_POST['team_location'])),
        'team_description' => !empty($_POST['team_description']) 
            ? htmlspecialchars(trim($_POST['team_description'])) 
            : null,
        'manager_name' => htmlspecialchars(trim($_POST['manager_name'])),
        'manager_email' => filter_var(trim($_POST['manager_email']), FILTER_SANITIZE_EMAIL),
        'manager_phone' => htmlspecialchars(trim($_POST['manager_phone']))
    ];

    // Enregistrer l'équipe
    $teamId = saveTeam($teamData, $_FILES['team_logo'] ?? null);

    // Enregistrer les joueurs (si applicable)
    if (isset($_POST['players']) && is_array($_POST['players'])) {
        savePlayers($teamId, $_POST['players']);
    }

    // Envoyer un email de confirmation
    sendConfirmationEmail($teamData['manager_email'], $teamData['team_name']);

    // Redirection en cas de succès
    $_SESSION['success'] = "L'équipe a été enregistrée avec succès!";
    header("Location: team_detail.php?id=" . $teamId);
    exit;

    // Après l'enregistrement réussi :
$emailSent = sendConfirmationEmail(
    $_POST['manager_email'], 
    $_POST['team_name']
);

if (!$emailSent) {
    $_SESSION['warning'] = "Inscription réussie, mais l'email de confirmation n'a pas pu être envoyé";
}
} catch (InvalidArgumentException $e) {
    // Erreur de validation
    $_SESSION['error'] = $e->getMessage();
    $_SESSION['old_input'] = $_POST;
    header("Location: team-register.php");
    exit;

} catch (PDOException $e) {
    // Erreur de base de données
    error_log("Erreur PDO: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur technique est survenue. Veuillez réessayer.";
    $_SESSION['old_input'] = $_POST;
    header("Location: team-register.php");
    exit;

} catch (Exception $e) {
    // Autres erreurs
    error_log("Erreur: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur inattendue est survenue: " . $e->getMessage();
    $_SESSION['old_input'] = $_POST;
    header("Location: team-register.php");
    exit;
}