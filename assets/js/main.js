/**
 * Script principal - AssociationPlus
 * Fonctionnalités JavaScript pour l'interface utilisateur
 */

// Attendre que le document soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // Animation de chargement
    const loader = document.createElement('div');
    loader.className = 'loading';
    loader.innerHTML = '<div class="spinner"></div>';
    document.body.prepend(loader);
    
    // Cacher l'animation après chargement complet
    window.addEventListener('load', function() {
        setTimeout(function() {
            loader.classList.add('hidden');
            setTimeout(function() {
                loader.remove();
            }, 800);
        }, 500);
    });

    // Bouton retour en haut
    const backToTopBtn = document.createElement('a');
    backToTopBtn.href = '#';
    backToTopBtn.className = 'back-to-top';
    backToTopBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    document.body.appendChild(backToTopBtn);

    // Afficher/masquer le bouton de retour en haut
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopBtn.classList.add('visible');
        } else {
            backToTopBtn.classList.remove('visible');
        }
    });

    // Action du bouton retour en haut
    backToTopBtn.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Initialisation des tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialisation des popovers Bootstrap
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Validation du formulaire de don
    const donationForm = document.getElementById('donationForm');
    const submitDonation = document.getElementById('submitDonation');
    
    if (submitDonation) {
        submitDonation.addEventListener('click', function() {
            // Simple validation
            const donorName = document.getElementById('donorName').value;
            const donorEmail = document.getElementById('donorEmail').value;
            const donationAmount = document.getElementById('donationAmount').value;
            
            if (!donorName || !donorEmail || !donationAmount) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            // Simuler la soumission du formulaire
            alert('Merci pour votre don! Vous allez être redirigé vers la page de paiement.');
            
            // En production, on redirigerait vers la page de paiement
            // window.location.href = 'payment.php';
        });
    }

    // Animation pour les sections lors du défilement
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 100) {
                element.classList.add('animated');
            }
        });
    };

    // Ajouter la classe animate-on-scroll aux sections
    document.querySelectorAll('section').forEach(section => {
        section.classList.add('animate-on-scroll');
    });

    // Activer l'animation au défilement
    window.addEventListener('scroll', animateOnScroll);
    animateOnScroll(); // Animation initiale

    // Formulaire d'inscription à la newsletter
    const newsletterForm = document.querySelector('form');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const emailInput = this.querySelector('input[type="email"]');
            
            if (emailInput && emailInput.value) {
                // Simuler l'enregistrement
                alert('Merci de vous être inscrit à notre newsletter!');
                emailInput.value = '';
            }
        });
    }

    // Navigation active
    const currentLocation = location.pathname;
    const navLinks = document.querySelectorAll('.nav-link');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        
        if (currentLocation.endsWith(linkPath) && linkPath !== 'index.php') {
            link.classList.add('active');
        }
    });
// Gestion des événements à venir (surbrillance)
const todayDate = new Date();
const eventItems = document.querySelectorAll('.list-group-item');

eventItems.forEach(item => {
    const dateElement = item.querySelector('.badge');
    
    if (dateElement) {
        const dayElement = dateElement.querySelector('.h5');
        const monthElement = dateElement.querySelector('small');
        
        if (dayElement && monthElement) {
            const day = parseInt(dayElement.textContent);
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            const monthIndex = monthNames.indexOf(monthElement.textContent.trim());
            
            if (monthIndex !== -1) {
                const eventDate = new Date(todayDate.getFullYear(), monthIndex, day);
                
                // Vérification si l'événement est à venir
                if (eventDate >= todayDate) {
                    item.classList.add('list-group-item-success');
                } else {
                    item.classList.add('list-group-item-secondary');
                }
            }
        }
    }
});

// Gestion des événements passés (surbrillance)
const pastEventItems = document.querySelectorAll('.list-group-item-secondary');
pastEventItems.forEach(item => {
    const dateElement = item.querySelector('.badge');
    
    if (dateElement) {
        const dayElement = dateElement.querySelector('.h5');
        const monthElement = dateElement.querySelector('small');
        
        if (dayElement && monthElement) {
            const day = parseInt(dayElement.textContent);
            const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            const monthIndex = monthNames.indexOf(monthElement.textContent.trim());
            
            if (monthIndex !== -1) {
                const eventDate = new Date(todayDate.getFullYear(), monthIndex, day);
                
                // Vérification si l'événement est passé
                if (eventDate < todayDate) {
                    item.classList.add('list-group-item-danger');
                }
            }
        }
    }
})});
