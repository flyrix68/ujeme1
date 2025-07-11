<?php
require_once '../includes/db-config.php';

// Définir le type de contenu
header('Content-Type: application/json');

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer et valider les données
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $input = $_POST; // Fallback pour les anciens navigateurs
}

$required = ['event_id', 'name', 'email'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Le champ $field est requis"]);
        exit();
    }
}

// Nettoyer les données
$event_id = filter_var($input['event_id'], FILTER_VALIDATE_INT);
$name = filter_var($input['name'], FILTER_SANITIZE_STRING);
$email = filter_var($input['email'], FILTER_SANITIZE_EMAIL);
$phone = isset($input['phone']) ? filter_var($input['phone'], FILTER_SANITIZE_STRING) : null;
$comments = isset($input['comments']) ? filter_var($input['comments'], FILTER_SANITIZE_STRING) : null;

// Validation supplémentaire
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email invalide']);
    exit();
}

try {
    // Vérifier que l'événement existe et est actif
    $stmt = $pdo->prepare("SELECT id FROM evenements WHERE id = ? AND statut = 'actif'");
    $stmt->execute([$event_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Événement non trouvé ou inactif']);
        exit();
    }

    // Vérifier si l'utilisateur est déjà inscrit
    $stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ?");
    $stmt->execute([$event_id, $email]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Vous êtes déjà inscrit à cet événement']);
        exit();
    }

    // Enregistrer l'inscription
    $stmt = $pdo->prepare("INSERT INTO event_registrations 
                          (event_id, name, email, phone, comments, registration_date) 
                          VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$event_id, $name, $email, $phone, $comments]);

    // Envoyer une confirmation (exemple basique)
    $subject = "Confirmation d'inscription";
    $message = "Bonjour $name,\n\n";
    $message .= "Votre inscription à l'événement a bien été enregistrée.\n\n";
    $message .= "Cordialement,\nL'équipe UJEM";
    $headers = "From: no-reply@ujem.org";

    mail($email, $subject, $message, $headers);

    // Réponse JSON
    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie ! Un email de confirmation vous a été envoyé.'
    ]);

} catch (PDOException $e) {
    error_log("Erreur d'inscription: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
}