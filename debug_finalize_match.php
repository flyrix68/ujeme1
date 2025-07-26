<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure la configuration de la base de données
require 'includes/db-config.php';

// Fonction pour afficher les erreurs PDO de manière lisible
function displayPDOError($e) {
    echo "<div style='color: red; margin: 10px 0; padding: 10px; border: 1px solid #f00;'>";
    echo "<strong>Erreur PDO :</strong><br>";
    echo "Message : " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Code : " . $e->getCode() . "<br>";
    echo "Fichier : " . $e->getFile() . "<br>";
    echo "Ligne : " . $e->getLine() . "<br>";
    
    if (isset($e->errorInfo) && is_array($e->errorInfo)) {
        echo "<br><strong>Détails de l'erreur :</strong><br>";
        echo "Code d'erreur SQL : " . htmlspecialchars($e->errorInfo[0]) . "<br>";
        echo "Code d'erreur driver : " . htmlspecialchars($e->errorInfo[1]) . "<br>";
        echo "Message d'erreur : " . htmlspecialchars($e->errorInfo[2]) . "<br>";
    }
    
    echo "</div>";
}

// Fonction pour afficher un message d'information
function displayInfo($message) {
    echo "<div style='color: blue; margin: 10px 0; padding: 10px; border: 1px solid #00f;'>";
    echo $message;
    echo "</div>";
}

// Fonction pour afficher un message de succès
function displaySuccess($message) {
    echo "<div style='color: green; margin: 10px 0; padding: 10px; border: 1px solid #0a0;'>";
    echo $message;
    echo "</div>";
}

// Fonction pour afficher un message d'avertissement
function displayWarning($message) {
    echo "<div style='color: orange; margin: 10px 0; padding: 10px; border: 1px solid #f90;'>";
    echo $message;
    echo "</div>";
}

// Vérifier si un ID de match a été fourni
$matchId = isset($_GET['match_id']) ? (int)$_GET['match_id'] : null;

// Si un ID de match est fourni, tenter de finaliser le match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finalize_match']) && $matchId) {
    try {
        $pdo = DatabaseConfig::getConnection();
        $pdo->beginTransaction();
        
        // Récupérer les informations du match
        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
        $stmt->execute([$matchId]);
        $match = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$match) {
            throw new Exception("Aucun match trouvé avec l'ID : " . $matchId);
        }
        
        // Vérifier si le match peut être finalisé
        if ($match['status'] === 'completed' || $match['status'] === 'finished') {
            throw new Exception("Le match a déjà été finalisé.");
        }
        
        // Vérifier les scores
        if (!isset($match['score_home']) || !isset($match['score_away'])) {
            throw new Exception("Les scores du match ne sont pas définis.");
        }
        
        // Mettre à jour le statut du match
        $updateMatch = $pdo->prepare("UPDATE matches SET status = 'completed', updated_at = NOW() WHERE id = ?");
        if (!$updateMatch->execute([$matchId])) {
            throw new Exception("Échec de la mise à jour du statut du match.");
        }
        
        // Inclure la fonction de mise à jour du classement
        require_once 'temp_update_classement.php';
        
        // Préparer les données pour la mise à jour du classement
        $classementData = [
            'saison' => $match['saison'],
            'competition' => $match['competition'],
            'poule_id' => $match['poule_id'],
            'team_home' => $match['team_home'],
            'team_away' => $match['team_away'],
            'score_home' => (int)$match['score_home'],
            'score_away' => (int)$match['score_away']
        ];
        
        // Mettre à jour le classement
        $result = updateClassementForMatch($pdo, $classementData);
        
        if ($result) {
            $pdo->commit();
            $successMessage = "Le match a été finalisé avec succès et le classement a été mis à jour.";
        } else {
            throw new Exception("La mise à jour du classement a échoué.");
        }
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $errorMessage = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Débogage de la finalisation des matchs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Débogage de la finalisation des matchs</h1>
        
        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger">
                <strong>Erreur :</strong> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h2>Finaliser un match</h2>
            </div>
            <div class="card-body">
                <form method="get" class="mb-4">
                    <div class="mb-3">
                        <label for="match_id" class="form-label">ID du match à finaliser :</label>
                        <input type="number" class="form-control" id="match_id" name="match_id" required 
                               value="<?php echo isset($_GET['match_id']) ? htmlspecialchars($_GET['match_id']) : ''; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Vérifier le match</button>
                </form>
                
                <?php if (isset($matchId)): ?>
                    <?php
                    try {
                        $pdo = DatabaseConfig::getConnection();
                        $stmt = $pdo->prepare("SELECT * FROM matches WHERE id = ?");
                        $stmt->execute([$matchId]);
                        $match = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($match): ?>
                            <div class="card">
                                <div class="card-header">
                                    <h3>Détails du match #<?php echo htmlspecialchars($match['id']); ?></h3>
                                </div>
                                <div class="card-body">
                                    <p><strong>Équipe à domicile :</strong> <?php echo htmlspecialchars($match['team_home']); ?></p>
                                    <p><strong>Équipe à l'extérieur :</strong> <?php echo htmlspecialchars($match['team_away']); ?></p>
                                    <p><strong>Score :</strong> <?php echo htmlspecialchars($match['score_home'] ?? '0'); ?> - <?php echo htmlspecialchars($match['score_away'] ?? '0'); ?></p>
                                    <p><strong>Statut :</strong> <?php echo htmlspecialchars($match['status']); ?></p>
                                    <p><strong>Date :</strong> <?php echo htmlspecialchars($match['match_date'] . ' ' . ($match['match_time'] ?? '')); ?></p>
                                    <p><strong>Compétition :</strong> <?php echo htmlspecialchars($match['competition']); ?></p>
                                    <p><strong>Poule :</strong> <?php echo htmlspecialchars($match['poule_id']); ?></p>
                                    
                                    <form method="post" class="mt-3">
                                        <input type="hidden" name="match_id" value="<?php echo $matchId; ?>">
                                        <button type="submit" name="finalize_match" class="btn btn-success" 
                                                <?php echo ($match['status'] === 'completed' || $match['status'] === 'finished') ? 'disabled' : ''; ?>>
                                            Finaliser le match
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Aucun match trouvé avec l'ID : <?php echo htmlspecialchars($matchId); ?>
                            </div>
                        <?php endif; ?>
                    <?php } catch (Exception $e) {
                        echo '<div class="alert alert-danger">';
                        echo '<strong>Erreur :</strong> ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    } ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Liste des matchs non finalisés</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Match</th>
                                <th>Score</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $pdo = DatabaseConfig::getConnection();
                                $stmt = $pdo->query("SELECT * FROM matches WHERE status NOT IN ('completed', 'finished') ORDER BY match_date DESC, match_time DESC LIMIT 20");
                                $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($matches) > 0) {
                                    foreach ($matches as $m) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($m['id']) . '</td>';
                                        echo '<td>' . htmlspecialchars($m['team_home']) . ' vs ' . htmlspecialchars($m['team_away']) . '</td>';
                                        echo '<td>' . htmlspecialchars($m['score_home'] ?? '0') . ' - ' . htmlspecialchars($m['score_away'] ?? '0') . '</td>';
                                        echo '<td>' . htmlspecialchars($m['status']) . '</td>';
                                        echo '<td>' . htmlspecialchars($m['match_date'] . ' ' . ($m['match_time'] ?? '')) . '</td>';
                                        echo '<td><a href="?match_id=' . $m['id'] . '" class="btn btn-sm btn-primary">Sélectionner</a></td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center">Aucun match non finalisé trouvé.</td></tr>';
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="6" class="text-danger">Erreur lors de la récupération des matchs : ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
