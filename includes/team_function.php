<?php
function saveTeam(array $data, array $logoFile = null) {
    global $pdo;
    
    // Valider les données requises
    $requiredFields = [
        'team_name' => 'Nom de l\'équipe',
        'team_category' => 'Catégorie',
        'team_location' => 'Localisation',
        'manager_name' => 'Nom du responsable',
        'manager_email' => 'Email du responsable',
        'manager_phone' => 'Téléphone du responsable'
    ];
    foreach ($requiredFields as $field => $label) {
        if (empty($data[$field])) {
            throw new InvalidArgumentException("Le champ '$label' est obligatoire");
        }
    }
    
    // Valider l'email
    if (!filter_var($data['manager_email'], FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Format d'email invalide");
    }
    
    // Gérer l'upload du logo
    $logoPath = null;
    if ($logoFile && isset($logoFile['error']) && $logoFile['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/logos/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($logoFile['name'], PATHINFO_EXTENSION);
        $filename = uniqid('team_') . '.' . $extension;
        $logoPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($logoFile['tmp_name'], $logoPath)) {
            throw new Exception("Erreur lors de l'upload du logo");
        }
    }
    
    try {
        // Insérer l'équipe dans la base de données
        $stmt = $pdo->prepare("
            INSERT INTO teams (
                team_name, 
                category, 
                location, 
                description, 
                logo_path,
                manager_name,
                manager_email,
                manager_phone
            ) VALUES (
                :team_name, 
                :category, 
                :location, 
                :description, 
                :logo_path,
                :manager_name,
                :manager_email,
                :manager_phone
            )
        ");
        
        $stmt->execute([
            ':team_name' => $data['team_name'],
            ':category' => $data['team_category'],
            ':location' => $data['team_location'],
            ':description' => $data['team_description'] ?? null,
            ':logo_path' => $logoPath,
            ':manager_name' => $data['manager_name'],
            ':manager_email' => $data['manager_email'],
            ':manager_phone' => $data['manager_phone']
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        // Supprimer le logo uploadé si l'insertion échoue
        if ($logoPath && file_exists($logoPath)) {
            unlink($logoPath);
        }
        throw new Exception("Erreur lors de l'enregistrement de l'équipe: " . $e->getMessage());
    }
}

function savePlayers($teamId, array $players) {
    global $pdo;
    
    if (count($players) < 7) {
        throw new Exception("Une équipe doit avoir au moins 7 joueurs");
    }

    // Vérifier si une transaction est déjà active
    $transactionActive = $pdo->inTransaction();
    
    try {
        if (!$transactionActive) {
            $pdo->beginTransaction();
        }

        $stmt = $pdo->prepare("
            INSERT INTO players (
                team_id, 
                name, 
                position, 
                jersey_number,
                photo
            ) VALUES (
                :team_id, 
                :name, 
                :position, 
                :jersey_number,
                :photo
            )
        ");

        foreach ($players as $index => $player) {
            if (empty($player['name'])) {
                throw new Exception("Le nom du joueur #".($index+1)." est obligatoire");
            }

            // Handle player photo upload
            $photoPath = null;
            if (isset($_FILES['players']['name'][$index]['photo']) && 
                $_FILES['players']['error'][$index]['photo'] === UPLOAD_ERR_OK) {
                $uploadDir = 'uploads/players/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['players']['name'][$index]['photo'], PATHINFO_EXTENSION);
                $filename = uniqid('player_' . $teamId . '_') . '.' . $extension;
                $photoPath = $uploadDir . $filename;
                
                if (!move_uploaded_file($_FILES['players']['tmp_name'][$index]['photo'], $photoPath)) {
                    error_log("Échec de l'upload de la photo pour le joueur: " . $player['name']);
                }
            }

            $stmt->execute([
                ':team_id' => $teamId,
                ':name' => trim($player['name']),
                ':position' => trim($player['position'] ?? 'Non spécifié'),
                ':jersey_number' => $player['jersey_number'] ?? $player['number'] ?? null,
                ':photo' => $photoPath
            ]);
        }

        if (!$transactionActive) {
            $pdo->commit();
        }
        
    } catch (Exception $e) {
        if (!$transactionActive && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw new Exception("Erreur joueurs: ".$e->getMessage());
    }
}

function sendConfirmationEmail($to, $teamName) {
    try {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Email invalide");
        }

        $subject = "Confirmation d'inscription - UJEM";
        
        $message = "
        <html>
        <head>
            <title>Confirmation d'inscription</title>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>Confirmation d'inscription</h2>
                <p>Votre équipe <strong>".htmlspecialchars($teamName)."</strong> est bien enregistrée.</p>
                <p>Récapitulatif à venir par email.</p>
            </div>
        </body>
        </html>
        ";
        
        $headers = [
            'From' => 'no-reply@'.$_SERVER['HTTP_HOST'],
            'Reply-To' => 'contact@'.$_SERVER['HTTP_HOST'],
            'MIME-Version' => '1.0',
            'Content-type' => 'text/html; charset=utf-8',
            'X-Mailer' => 'PHP/'.phpversion()
        ];
        
        $headersString = implode("\r\n", $headers);
        
        if (!mail($to, $subject, $message, $headersString)) {
            error_log("Échec d'envoi d'email à $to");
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Erreur d'envoi d'email: ".$e->getMessage());
        return false;
    }
}
?>