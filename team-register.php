<?php
// Vérifier si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'includes/db-config.php';
    require 'includes/team_function.php';
    
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // 1. Enregistrer l'équipe
        $teamId = saveTeam($_POST, $_FILES['teamLogo']);
        
        // 2. Enregistrer les joueurs
        savePlayers($teamId, $_POST['players'], $_FILES['players']);
        
        // 3. Envoyer l'email de confirmation si l'email est fourni
        if (!empty($_POST['manager_email'])) {
            sendConfirmationEmail($_POST['manager_email'], $_POST['team_name']);
        }
        
        // Valider la transaction
        $pdo->commit();
        
        // Rediriger avec un message de succès
        header('Location: teams.php?success=1');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Erreur lors de l'inscription: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UJEM - Inscription d'équipe</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
   
    <main class="container py-5">
        <div class="text-center mb-4">
            <a href="teams.php" class="btn btn-outline-primary mb-3">
                <i class="fas fa-arrow-left me-2"></i>Retour aux équipes
            </a>
        </div>
        <h1 class="text-center mb-4">Inscription d'équipe</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" id="teamRegistrationForm" class="needs-validation" novalidate>
                    <!-- Section Informations de l'équipe -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-users me-2"></i>Informations sur l'équipe</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="team_name" class="form-label">Nom de l'équipe*</label>
                                    <input type="text" class="form-control" id="team_name" name="team_name" required>
                                    <div class="invalid-feedback">Veuillez indiquer le nom de l'équipe.</div>
                                </div>
                    <div class="col-md-6">
                        <label for="team_category" class="form-label">Catégorie*</label>
                        <select class="form-select" id="team_category" name="team_category" required>
                            <option value="">Choisir...</option>
                            <option value="Coupe-UJEM">Coupe UJEM</option>
                            <option value="Tournoi">Tournoi</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner une catégorie.</div>
                        <div id="registrationStatus" class="mt-2"></div>
                    </div>
                                <div class="col-12">
                                    <label for="team_location" class="form-label">Localisation*</label>
                                    <input type="text" class="form-control" id="team_location" name="team_location" required>
                                    <div class="invalid-feedback">Veuillez indiquer la localisation de l'équipe.</div>
                                </div>
                                <div class="col-12">
                                    <label for="team_logo" class="form-label">Logo de l'équipe</label>
                                    <input class="form-control" type="file" id="team_logo" name="teamLogo" accept="image/*">
                                    <div class="form-text">Format recommandé: JPG ou PNG, max 2MB.</div>
                                </div>
                                <div class="col-12">
                                    <label for="team_description" class="form-label">Description</label>
                                    <textarea class="form-control" id="team_description" name="team_description" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Responsable -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-user-tie me-2"></i>Responsable de l'équipe</h4>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="manager_name" class="form-label">Nom complet*</label>
                                    <input type="text" class="form-control" id="manager_name" name="manager_name" required>
                                    <div class="invalid-feedback">Veuillez indiquer le nom du responsable.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="manager_phone" class="form-label">Téléphone*</label>
                                    <input type="tel" class="form-control" id="manager_phone" name="manager_phone" required>
                                    <div class="invalid-feedback">Veuillez indiquer un numéro de téléphone valide.</div>
                                </div>
                                <div class="col-12">
                                    <label for="manager_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="manager_email" name="manager_email">
                                    <div class="form-text">Optionnel - utilisé uniquement pour l'envoi de confirmation</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Joueurs -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-running me-2"></i>Joueurs (minimum 7, maximum 10)</h4>
                        </div>
                        <div class="card-body">
                            <div id="playersList">
                                <!-- Les champs des joueurs seront ajoutés ici dynamiquement -->
                                <div class="player-item row g-3 mb-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Nom complet*</label>
                                        <input type="text" class="form-control" name="players[0][name]" required>
                                        <div class="invalid-feedback">Veuillez indiquer le nom du joueur.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Poste*</label>
                                        <select class="form-select" name="players[0][position]" required>
                                            <option value="">Choisir...</option>
                                            <option value="Gardien">Gardien</option>
                                            <option value="Défenseur">Défenseur</option>
                                            <option value="Milieu">Milieu</option>
                                            <option value="Attaquant">Attaquant</option>
                                        </select>
                                        <div class="invalid-feedback">Veuillez sélectionner un poste.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Numéro</label>
                                        <input type="number" class="form-control" name="players[0][number]" min="1" max="99">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Photo</label>
                                        <input type="file" class="form-control" name="players[0][photo]" accept="image/jpeg,image/png">
                                        <div class="form-text">JPG/PNG, max 2MB.</div>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm w-100 remove-player" disabled>
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between mt-3">
                                <button type="button" id="addPlayer" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Ajouter un joueur
                                </button>
                                <span id="playerCount" class="badge bg-info align-self-center p-2">1 joueur</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="acceptRules" name="accept_rules" required>
                                <label class="form-check-label" for="acceptRules">
                                    J'accepte le <a href="rules.php" target="_blank">règlement du tournoi</a>.*
                                </label>
                                <div class="invalid-feedback">Vous devez accepter le règlement pour continuer.</div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Soumettre l'inscription
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </main>
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let registrationPeriods = {};

        // Charger les périodes d'inscription depuis le serveur
        async function loadRegistrationPeriods() {
            try {
                const response = await fetch('api/get_registration_periods.php');
                if (!response.ok) throw new Error('Network response was not ok');
                registrationPeriods = await response.json();
            } catch (error) {
                console.error('Error loading registration periods:', error);
                // Valeurs par défaut si l'API échoue
                registrationPeriods = {
                    'Coupe-UJEM': {
                        start: '2025-09-01',
                        end: '2025-10-15'
                    },
                    'Tournoi': {
                        start: '2025-10-16', 
                        end: '2025-11-30'
                    }
                };
            }
        }

        // Vérifier si la date actuelle est dans une période d'inscription
        function isRegistrationOpen(category) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (!registrationPeriods[category]) return false;
            
            const startDate = new Date(registrationPeriods[category].start);
            const endDate = new Date(registrationPeriods[category].end);
            
            return today >= startDate && today <= endDate;
        }

        // Validation du formulaire
        (() => {
            'use strict'
            
            // Récupérer tous les formulaires auxquels nous voulons appliquer des styles de validation Bootstrap personnalisés
            const forms = document.querySelectorAll('.needs-validation')
            
            // Boucle sur eux et empêche la soumission
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    const category = document.getElementById('team_category').value;
                    
                    // Vérifier la période d'inscription
                    if (!isRegistrationOpen(category)) {
                        alert('Les inscriptions pour cette catégorie sont fermées.');
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    }
                    
                    const playerItems = document.querySelectorAll('.player-item');
                    if (playerItems.length < 7) {
                        alert('Vous devez inscrire au minimum 7 joueurs.');
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    }
        
        // Afficher/masquer les messages de période d'inscription
        function updateRegistrationStatus() {
            const category = document.getElementById('team_category').value;
            const statusElement = document.getElementById('registrationStatus');
            
            if (!category) {
                statusElement.textContent = '';
                statusElement.className = 'mt-2';
                return;
            }

            const period = registrationPeriods[category];
            if (!period) {
                statusElement.textContent = '';
                statusElement.className = 'mt-2';
                return;
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const startDate = new Date(period.start);
            const endDate = new Date(period.end);
            
            if (today < startDate) {
                statusElement.textContent = `Inscriptions ouvertes du ${formatDate(startDate)} au ${formatDate(endDate)}`;
                statusElement.className = 'mt-2 text-info';
            } else if (today > endDate) {
                statusElement.textContent = 'Inscriptions fermées pour cette catégorie';
                statusElement.className = 'mt-2 text-danger';
            } else {
                statusElement.textContent = `Inscriptions ouvertes jusqu'au ${formatDate(endDate)}`;
                statusElement.className = 'mt-2 text-success';
            }
        }

        // Formater une date en français (JJ/MM/AAAA)
        function formatDate(date) {
            return date.toLocaleDateString('fr-FR');
        }
                        event.preventDefault();
                        event.stopPropagation();
                        return;
                    }
                    
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false)
            })
        })()
        
        // Gestion dynamique des joueurs
        document.addEventListener('DOMContentLoaded', function() {
            const playersList = document.getElementById('playersList');
            const addPlayerBtn = document.getElementById('addPlayer');
            const playerCountDisplay = document.getElementById('playerCount');
            
            // Fonction pour mettre à jour le compteur de joueurs
            function updatePlayerCount() {
                const count = document.querySelectorAll('.player-item').length;
                playerCountDisplay.textContent = count + (count === 1 ? ' joueur' : ' joueurs');
                
                // Activer ou désactiver le bouton d'ajout en fonction du nombre de joueurs
                if (count >= 20) {
                    addPlayerBtn.disabled = true;
                    addPlayerBtn.title = 'Maximum 20 joueurs par équipe';
                } else {
                    addPlayerBtn.disabled = false;
                    addPlayerBtn.title = '';
                }
                
                // Activer ou désactiver les boutons de suppression
                const removeButtons = document.querySelectorAll('.remove-player');
                if (count <= 1) {
                    removeButtons.forEach(btn => btn.disabled = true);
                } else {
                    removeButtons.forEach(btn => btn.disabled = false);
                }
            }
            
            // Ajouter un joueur
            addPlayerBtn.addEventListener('click', function() {
                const playerCount = document.querySelectorAll('.player-item').length;
                
                if (playerCount >= 20) {
                    alert('Maximum 20 joueurs par équipe');
                    return;
                }
                
                const newPlayer = document.createElement('div');
                newPlayer.className = 'player-item row g-3 mb-3';
                newPlayer.innerHTML = `
                    <div class="col-md-3">
                        <label class="form-label">Nom complet*</label>
                        <input type="text" class="form-control" name="players[${playerCount}][name]" required>
                        <div class="invalid-feedback">Veuillez indiquer le nom du joueur.</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Poste*</label>
                        <select class="form-select" name="players[${playerCount}][position]" required>
                            <option value="">Choisir...</option>
                            <option value="Gardien">Gardien</option>
                            <option value="Défenseur">Défenseur</option>
                            <option value="Milieu">Milieu</option>
                            <option value="Attaquant">Attaquant</option>
                        </select>
                        <div class="invalid-feedback">Veuillez sélectionner un poste.</div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Numéro</label>
                        <input type="number" class="form-control" name="players[${playerCount}][number]" min="1" max="99">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="players[${playerCount}][photo]" accept="image/jpeg,image/png">
                        <div class="form-text">JPG/PNG, max 2MB.</div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm w-100 remove-player">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                
                playersList.appendChild(newPlayer);
                updatePlayerCount();
                
                // Ajouter l'événement de suppression au nouveau bouton
                newPlayer.querySelector('.remove-player').addEventListener('click', function() {
                    newPlayer.remove();
                    
                    // Réindexer les joueurs
                    reindexPlayerFields();
                    
                    updatePlayerCount();
                });
            });
            
            // Fonction pour réindexer les champs de joueurs après suppression
            function reindexPlayerFields() {
                const playerItems = document.querySelectorAll('.player-item');
                playerItems.forEach((item, index) => {
                    const nameInput = item.querySelector('input[name^="players["][name$="][name]"]');
                    const positionSelect = item.querySelector('select[name^="players["][name$="][position]"]');
                    const numberInput = item.querySelector('input[name^="players["][name$="][number]"]');
                    const photoInput = item.querySelector('input[name^="players["][name$="][photo]"]');
                    
                    nameInput.name = `players[${index}][name]`;
                    positionSelect.name = `players[${index}][position]`;
                    numberInput.name = `players[${index}][number]`;
                    if (photoInput) photoInput.name = `players[${index}][photo]`;
                });
            }
            
            // Ajouter l'événement de suppression aux boutons existants
            document.querySelectorAll('.remove-player').forEach(button => {
                button.addEventListener('click', function() {
                    if (document.querySelectorAll('.player-item').length > 1) {
                        this.closest('.player-item').remove();
                        reindexPlayerFields();
                        updatePlayerCount();
                    }
                });
            });
            
            // Initialiser le compteur de joueurs
            updatePlayerCount();
            
            // Charger les périodes et initialiser le statut
            loadRegistrationPeriods().then(() => {
                updateRegistrationStatus();
                document.getElementById('team_category').addEventListener('change', updateRegistrationStatus);
            });
        });
    </script>
</body>
</html>
