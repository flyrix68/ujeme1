<?php
require_once '../includes/check-auth.php';
// Vérifie que l'utilisateur est admin
if ($_SESSION['user']['type_compte'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs | UJEM Admin</title>
    <?php include '../includes/head.php'; ?>
    <style>
        .user-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="fas fa-users-cog"></i> Gestion des Utilisateurs</h1>
            <a href="ajouter-utilisateur.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Ajouter un utilisateur
            </a>
        </div>

        <div class="card shadow">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Type de compte</th>
                                <th>Date d'inscription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
try {
    $stmt = $pdo->query("SELECT id, prenom, nom, email, type_compte, date_inscription FROM utilisateurs ORDER BY date_inscription DESC");
    if ($stmt->rowCount() > 0):
        while ($user = $stmt->fetch()):
?>
<tr>
    <td><?= $user['id'] ?></td>
    <td><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></td>
    <td><?= htmlspecialchars($user['email']) ?></td>
    <td>
        <span class="badge bg-<?= 
            $user['type_compte'] === 'admin' ? 'danger' : 
            ($user['type_compte'] === 'membre' ? 'primary' : 'secondary') 
        ?>">
            <?= ucfirst($user['type_compte']) ?>
        </span>
    </td>
    <td><?= date('d/m/Y', strtotime($user['date_inscription'])) ?></td>
    <td class="user-actions">
        <a href="editer-utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-edit"></i>
        </a>
        <button class="btn btn-sm btn-outline-danger delete-user" data-id="<?= $user['id'] ?>">
            <i class="fas fa-trash-alt"></i>
        </button>
    </td>
</tr>
<?php
        endwhile;
    else:
?>
<tr>
    <td colspan="6" class="text-center">Aucun utilisateur trouvé.</td>
</tr>
<?php
    endif;
} catch (PDOException $e) {
    echo '<tr><td colspan="6" class="text-danger text-center">Erreur : ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
}
?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <a href="#" id="confirmDelete" class="btn btn-danger">Supprimer</a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script>
    // Gestion de la suppression
    document.querySelectorAll('.delete-user').forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const deleteLink = document.getElementById('confirmDelete');
            deleteLink.href = `supprimer-utilisateur.php?id=${userId}`;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });
    </script>
</body>
</html>