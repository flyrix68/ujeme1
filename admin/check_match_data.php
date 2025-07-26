<?php
require __DIR__ . '/../includes/db-config.php';
require __DIR__ . '/admin_header.php';

// Vérifier les matchs avec des données manquantes
function checkMatchData($pdo, $matchId = null) {
    $sql = "SELECT id, team_home, team_away, score_home, score_away, saison, competition, poule_id, status 
            FROM matches ";
    
    if ($matchId) {
        $sql .= " WHERE id = :match_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['match_id' => $matchId]);
    } else {
        $sql .= " WHERE status != 'completed' AND status != 'finished'";
        $stmt = $pdo->query($sql);
    }
    
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $invalidMatches = [];
    
    foreach ($matches as $match) {
        $missingFields = [];
        
        // Vérifier les champs requis
        if (empty($match['saison'])) $missingFields[] = 'saison';
        if (empty($match['competition'])) $missingFields[] = 'competition';
        if (empty($match['poule_id'])) $missingFields[] = 'poule_id';
        if ($match['score_home'] === null) $missingFields[] = 'score_home';
        if ($match['score_away'] === null) $missingFields[] = 'score_away';
        
        if (!empty($missingFields)) {
            $match['missing_fields'] = $missingFields;
            $invalidMatches[] = $match;
        }
    }
    
    return $invalidMatches;
}

// Mettre à jour les données d'un match
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_match'])) {
    $matchId = filter_input(INPUT_POST, 'match_id', FILTER_VALIDATE_INT);
    $saison = filter_input(INPUT_POST, 'saison', FILTER_SANITIZE_STRING);
    $competition = filter_input(INPUT_POST, 'competition', FILTER_SANITIZE_STRING);
    $poule_id = filter_input(INPUT_POST, 'poule_id', FILTER_VALIDATE_INT);
    $score_home = filter_input(INPUT_POST, 'score_home', FILTER_VALIDATE_INT);
    $score_away = filter_input(INPUT_POST, 'score_away', FILTER_VALIDATE_INT);
    
    if ($matchId) {
        try {
            $sql = "UPDATE matches SET 
                    saison = COALESCE(?, saison),
                    competition = COALESCE(?, competition),
                    poule_id = COALESCE(?, poule_id),
                    score_home = COALESCE(?, score_home),
                    score_away = COALESCE(?, score_away)
                    WHERE id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$saison, $competition, $poule_id, $score_home, $score_away, $matchId]);
            
            $_SESSION['message'] = "Les données du match ont été mises à jour avec succès.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la mise à jour du match : " . $e->getMessage();
        }
    }
    
    header("Location: check_match_data.php");
    exit();
}

// Récupérer les matchs avec des données manquantes
$invalidMatches = checkMatchData($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des données des matchs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <h2 class="h3 mb-4"><i class="fas fa-clipboard-check me-2"></i>Vérification des données des matchs</h2>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= $_SESSION['error'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Matchs avec des données manquantes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($invalidMatches)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i> Tous les matchs ont des données complètes !
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Match</th>
                                            <th>Données manquantes</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($invalidMatches as $match): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($match['id']) ?></td>
                                                <td>
                                                    <?= htmlspecialchars($match['team_home']) ?> vs <?= htmlspecialchars($match['team_away']) ?><br>
                                                    <small class="text-muted">
                                                        <?= $match['score_home'] !== null ? $match['score_home'] : '?' ?> - 
                                                        <?= $match['score_away'] !== null ? $match['score_away'] : '?' ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php foreach ($match['missing_fields'] as $field): ?>
                                                        <span class="badge bg-danger me-1"><?= $field ?></span>
                                                    <?php endforeach; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editMatchModal<?= $match['id'] ?>">
                                                        <i class="fas fa-edit"></i> Modifier
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Modal d'édition -->
                                            <div class="modal fade" id="editMatchModal<?= $match['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Modifier le match #<?= $match['id'] ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="post" action="">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="match_id" value="<?= $match['id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Score <?= htmlspecialchars($match['team_home']) ?></label>
                                                                    <input type="number" class="form-control" name="score_home" 
                                                                           value="<?= $match['score_home'] !== null ? $match['score_home'] : '' ?>" min="0">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Score <?= htmlspecialchars($match['team_away']) ?></label>
                                                                    <input type="number" class="form-control" name="score_away" 
                                                                           value="<?= $match['score_away'] !== null ? $match['score_away'] : '' ?>" min="0">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Saison</label>
                                                                    <input type="text" class="form-control" name="saison" 
                                                                           value="<?= htmlspecialchars($match['saison'] ?: '2024-2025') ?>">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">Compétition</label>
                                                                    <input type="text" class="form-control" name="competition" 
                                                                           value="<?= htmlspecialchars($match['competition']) ?>">
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label">ID de la poule</label>
                                                                    <input type="number" class="form-control" name="poule_id" 
                                                                           value="<?= $match['poule_id'] ?: '' ?>" min="1">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <button type="submit" name="update_match" class="btn btn-primary">Enregistrer</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
