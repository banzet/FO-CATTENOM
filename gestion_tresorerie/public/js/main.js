document.addEventListener('DOMContentLoaded', () => {
    const menuToggle = document.getElementById('mobile-menu');
    const navLinks = document.getElementById('nav-links');

    menuToggle.addEventListener('click', () => {
        // Alterne la classe active pour afficher/masquer le menu
        navLinks.classList.toggle('active');
        // Alterne l'animation du bouton burger en croix
        menuToggle.classList.toggle('active');
    });

    // Optionnel : Ferme le menu si on clique en dehors
    document.addEventListener('click', (event) => {
        if (!menuToggle.contains(event.target) && !navLinks.contains(event.target)) {
            navLinks.classList.remove('active');
            menuToggle.classList.remove('active');
        }
    });
});