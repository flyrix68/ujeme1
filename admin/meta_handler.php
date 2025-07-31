<?php
require_once __DIR__ . '/includes/db-ssl.php';

function generateSocialMeta($contentId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT titre, description, media_url, media_type 
                              FROM medias_actualites 
                              WHERE id = ?");
        $stmt->execute([$contentId]);
        $content = $stmt->fetch();

        if (!$content) return [];

        return [
            'title' => $content['titre'] . " | UJEM",
            'description' => mb_substr(strip_tags($content['description']), 0, 160),
            'image' => ($content['media_type'] === 'image')
                ? 'https://' . $_SERVER['HTTP_HOST'] . '../uploads/medias/' . $content['media_url']
                : 'https://' . $_SERVER['HTTP_HOST'] . '../assets/social_default.jpg',
            'url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
        ];
    } catch (PDOException $e) {
        error_log("Meta tags error: " . $e->getMessage());
        return [];
    }
}