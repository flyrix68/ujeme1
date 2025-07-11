<?php
require 'auth-check.php';

// Vérifier que l'utilisateur est bien le responsable de cette équipe
if ($_SESSION['team_id'] != $_GET['id'] && $_SESSION['role'] !== 'admin') {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$teamId = $_GET['id'];
$team = $pdo->query("SELECT * FROM teams WHERE id = $teamId")->fetch();
$players = $pdo->query("SELECT * FROM players WHERE team_id = $teamId")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Traitement de la mise à jour
    $stmt = $pdo->prepare("UPDATE teams SET name=?, category=?, location=?, description=? WHERE id=?");
    $stmt->execute([
        $_POST['name'],
        $_POST['category'],
        $_POST['location'],
        $_POST['description'],
        $teamId
    ]);
    
    // Mise à jour des joueurs (simplifié)
    $pdo->prepare("DELETE FROM players WHERE team_id = ?")->execute([$teamId]);
    $stmt = $pdo->prepare("INSERT INTO players (team_id, name, position, number) VALUES (?, ?, ?, ?)");
    
    foreach ($_POST['players'] as $player) {
        $stmt->execute([$teamId, $player['name'], $player['position'], $player['number']]);
    }
    
    header("Location: team-dashboard.php?id=$teamId&updated=1");
    exit;
}
?>

<form method="POST">
    <!-- Formulaire similaire à team-register.php mais avec les valeurs pré-remplies -->
    <input type="text" name="name" value="<?= htmlspecialchars($team['name']) ?>">
    
    <!-- Liste des joueurs modifiable -->
    <div id="playersList">
        <?php foreach ($players as $index => $player): ?>
        <div class="player-item">
            <input type="text" name="players[<?= $index ?>][name]" value="<?= htmlspecialchars($player['name']) ?>">
            <!-- Autres champs... -->
        </div>
        <?php endforeach; ?>
    </div>
    
    <button type="submit" class="btn btn-primary">Mettre à jour</button>
</form>