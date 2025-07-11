<?php
require_once '../includes/db-config.php';
// require_once '../includes/check-auth.php';

$content_id = $_GET['id'] ?? 0;

// Récupérer le contenu
$stmt = $pdo->prepare("SELECT * FROM medias_actualites WHERE id = ?");
$stmt->execute([$content_id]);
$content = $stmt->fetch();

if ($content) {
    try {
        // Supprimer le fichier physique (sauf si c'est le fichier par défaut)
        if ($content['media_url'] !== 'default.jpg') {
            $filePath = '../uploads/medias/'.$content['media_url'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // Mettre à jour la base de données
        $pdo->prepare("UPDATE medias_actualites SET media_url = 'default.jpg' WHERE id = ?")
           ->execute([$content_id]);
        
        $_SESSION['success'] = "Média supprimé avec succès";
    } catch (Exception $e) {
        $_SESSION['error'] = "Erreur lors de la suppression : ".$e->getMessage();
    }
}

header("Location: edit_content.php?id=".$content_id);
exit();