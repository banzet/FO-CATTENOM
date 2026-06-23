<?php 
// Inclusion du header
include_once __DIR__ . '/../templates/header.php'; 
?>

<section class="dashboard-welcome">
    <h2>Tableau de Bord</h2>
    <p>Bienvenue sur l'application de gestion financière du syndicat FO de Cattenom.</p>
    
    <div class="cards-grid">
        <div class="card summary-box balance">
            <h3>Solde Actuel</h3>
            <p class="amount">0,00 €</p>
        </div>
        <div class="card summary-box income">
            <h3>Recettes (Mois)</h3>
            <p class="amount">+ 0,00 €</p>
        </div>
        <div class="card summary-box expense">
            <h3>Dépenses (Mois)</h3>
            <p class="amount">- 0,00 €</p>
        </div>
    </div>
</section>

<?php 
// Inclusion du footer
include_once __DIR__ . '/../templates/footer.php'; 
?>