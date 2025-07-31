# Script de nettoyage des fichiers inutiles
# Conserve uniquement les sauvegardes (*.bak) et les fichiers de configuration

# Fonction pour supprimer les fichiers en toute sécurité
function Remove-FilesSafely {
    param (
        [string]$directory,
        [string]$filter
    )
    
    $files = Get-ChildItem -Path $directory -Filter $filter -File -Recurse -ErrorAction SilentlyContinue
    
    foreach ($file in $files) {
        try {
            Remove-Item -Path $file.FullName -Force -ErrorAction Stop
            Write-Host "Supprimé : $($file.FullName)"
        }
        catch {
            Write-Host "Erreur lors de la suppression de $($file.FullName) : $_" -ForegroundColor Red
        }
    }
}

# Dossiers à exclure du nettoyage
$excludedDirs = @(
    "admin",
    "api",
    "assets",
    "css",
    "img",
    "includes",
    "js",
    "logs",
    "membre",
    "ressources",
    "sql",
    "uploads"
)

# Fichiers à conserver (sauvegardes et configurations)
$keepFiles = @(
    "*.bak",
    "*.config",
    "*.conf",
    "*.env",
    "*.json",
    "*.toml",
    "Dockerfile",
    "docker-compose.yml",
    "README.md",
    "*.md",
    "*.pem"
)

# Dossier racine du projet
$rootDir = "$PSScriptRoot"

# Supprimer les fichiers de débogage et de test
$patternsToRemove = @(
    "check_*.php",
    "debug_*.php",
    "test_*.php",
    "temp_*.php",
    "fix_*.php",
    "migrate_*.php",
    "*.test.php",
    "*_test.php",
    "test*.php",
    "*.new"
)

Write-Host "Début du nettoyage..." -ForegroundColor Green

# Parcourir tous les fichiers du répertoire racine
Get-ChildItem -Path $rootDir -File | ForEach-Object {
    $keepFile = $false
    
    # Vérifier si le fichier doit être conservé
    foreach ($pattern in $keepFiles) {
        if ($_.Name -like $pattern) {
            $keepFile = $true
            break
        }
    }
    
    # Vérifier si le fichier doit être supprimé
    if (-not $keepFile) {
        foreach ($pattern in $patternsToRemove) {
            if ($_.Name -like $pattern) {
                try {
                    Remove-Item -Path $_.FullName -Force -ErrorAction Stop
                    Write-Host "Supprimé : $($_.FullName)" -ForegroundColor Yellow
                }
                catch {
                    Write-Host "Erreur lors de la suppression de $($_.FullName) : $_" -ForegroundColor Red
                }
                break
            }
        }
    }
}

# Supprimer les fichiers vides
Get-ChildItem -Path $rootDir -File | Where-Object { $_.Length -eq 0 } | ForEach-Object {
    try {
        Remove-Item -Path $_.FullName -Force -ErrorAction Stop
        Write-Host "Supprimé (fichier vide) : $($_.FullName)" -ForegroundColor Yellow
    }
    catch {
        Write-Host "Erreur lors de la suppression du fichier vide $($_.FullName) : $_" -ForegroundColor Red
    }
}

Write-Host "Nettoyage terminé !" -ForegroundColor Green
