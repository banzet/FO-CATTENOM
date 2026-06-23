<?php
// public/rapports.php

require_once __DIR__ . '/../config/database.php';

$titrePage = "Rapports Financiers";

// 1. RÉCUPÉRATION ET CALCUL DES DONNÉES DU COMPTE DE RÉSULTAT

// Charges (Classe 6)
$queryCharges = $pdo->query("
    SELECT p.code_compte, p.libelle, 
           COALESCE(SUM(e.debit), 0) as total_debit, 
           COALESCE(SUM(e.credit), 0) as total_credit
    FROM PlanComptable p
    LEFT JOIN Ecritures e ON p.code_compte = e.code_compte
    WHERE p.code_compte LIKE '6%'
    GROUP BY p.code_compte, p.libelle
    HAVING total_debit > 0 OR total_credit > 0
    ORDER BY p.code_compte ASC
");
$charges = $queryCharges->fetchAll();

// Produits (Classe 7)
$queryProduits = $pdo->query("
    SELECT p.code_compte, p.libelle, 
           COALESCE(SUM(e.debit), 0) as total_debit, 
           COALESCE(SUM(e.credit), 0) as total_credit
    FROM PlanComptable p
    LEFT JOIN Ecritures e ON p.code_compte = e.code_compte
    WHERE p.code_compte LIKE '7%'
    GROUP BY p.code_compte, p.libelle
    HAVING total_debit > 0 OR total_credit > 0
    ORDER BY p.code_compte ASC
");
$produits = $queryProduits->fetchAll();

// Calcul des totaux du Compte de Résultat
$totalCharges = 0;
foreach ($charges as $c) {
    $totalCharges += ($c['total_debit'] - $c['total_credit']);
}

$totalProduits = 0;
foreach ($produits as $p) {
    $totalProduits += ($p['total_credit'] - $p['total_debit']);
}

$resultatNet = $totalProduits - $totalCharges;


// 2. RÉCUPÉRATION ET CALCUL DES DONNÉES DU BILAN

// Trésorerie (Classe 5)
$queryTresorerie = $pdo->query("
    SELECT p.code_compte, p.libelle, 
           COALESCE(SUM(e.debit), 0) as total_debit, 
           COALESCE(SUM(e.credit), 0) as total_credit
    FROM PlanComptable p
    LEFT JOIN Ecritures e ON p.code_compte = e.code_compte
    WHERE p.code_compte LIKE '5%'
    GROUP BY p.code_compte, p.libelle
    HAVING total_debit > 0 OR total_credit > 0
    ORDER BY p.code_compte ASC
");
$tresorerie = $queryTresorerie->fetchAll();

$totalTresorerie = 0;
foreach ($tresorerie as $t) {
    $totalTresorerie += ($t['total_debit'] - $t['total_credit']);
}

// Inclusion du header global
include_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header no-print">
    <h2><i class="fa-solid fa-chart-pie" style="color: var(--fo-blue);"></i> Rapports & États Financiers</h2>
    <p>Consultez les indicateurs de performance et éditez les documents officiels pour l'assemblée générale.</p>
</div>

<div class="print-actions no-print">
    <button onclick="window.print();" class="btn btn-edit">
        <i class="fa-solid fa-print"></i> Imprimer le rapport complet (PDF)
    </button>
</div>

<div class="tabs-container no-print">
    <button class="tab-button active" onclick="switchTab(event, 'compte-resultat')">
        <i class="fa-solid fa-gavel"></i> Compte de Résultat
    </button>
    <button class="tab-button" onclick="switchTab(event, 'bilan')">
        <i class="fa-solid fa-vault"></i> Bilan de Trésorerie
    </button>
</div>

<div id="compte-resultat" class="tab-content report-section current">
    <div class="report-print-header">
        <h2 class="print-title">COMPTE DE RÉSULTAT</h2>
        <p class="print-subtitle">Arrêté au : <?php echo date('d/m/Y'); ?></p>
    </div>

    <div class="report-grid">
        <div class="card table-card">
            <h3>Dépenses (Charges - Classe 6)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Intitulé du compte</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($charges)): ?>
                        <tr><td colspan="3" class="text-muted">Aucune charge enregistrée.</td></tr>
                    <?php else: ?>
                        <?php foreach ($charges as $c): $solde = $c['total_debit'] - $c['total_credit']; ?>
                            <tr>
                                <td class="font-mono"><?php echo htmlspecialchars($c['code_compte']); ?></td>
                                <td><?php echo htmlspecialchars($c['libelle']); ?></td>
                                <td class="text-right font-mono"><?php echo number_format($solde, 2, ',', ' '); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="row-total">
                        <td colspan="2">TOTAL DES CHARGES (I)</td>
                        <td class="text-right font-mono"><?php echo number_format($totalCharges, 2, ',', ' '); ?> €</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card table-card">
            <h3>Recettes (Produits - Classe 7)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Intitulé du compte</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                        <tr><td colspan="3" class="text-muted">Aucun produit enregistré.</td></tr>
                    <?php else: ?>
                        <?php foreach ($produits as $p): $solde = $p['total_credit'] - $p['total_debit']; ?>
                            <tr>
                                <td class="font-mono"><?php echo htmlspecialchars($p['code_compte']); ?></td>
                                <td><?php echo htmlspecialchars($p['libelle']); ?></td>
                                <td class="text-right font-mono"><?php echo number_format($solde, 2, ',', ' '); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="row-total">
                        <td colspan="2">TOTAL DES PRODUITS (II)</td>
                        <td class="text-right font-mono"><?php echo number_format($totalProduits, 2, ',', ' '); ?> €</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="result-bar <?php echo $resultatNet >= 0 ? 'result-positive' : 'result-negative'; ?>">
        <div class="result-label">
            <strong>RÉSULTAT NET (II - I) :</strong> 
            <span><?php echo $resultatNet >= 0 ? 'EXCÉDENT COMPTABLE' : 'DÉFICIT COMPTABLE'; ?></span>
        </div>
        <div class="result-amount font-mono">
            <?php echo number_format(abs($resultatNet), 2, ',', ' '); ?> €
        </div>
    </div>
</div>

<div id="bilan" class="tab-content report-section">
    <div class="page-break-before"></div>
    <div class="report-print-header">
        <h2 class="print-title">BILAN SIMPLIFIÉ</h2>
        <p class="print-subtitle">Situation de la trésorerie et des fonds de l'organisation</p>
    </div>

    <div class="report-grid">
        <div class="card table-card">
            <h3>Actif (Trésorerie disponible - Classe 5)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Compte de Trésorerie</th>
                        <th class="text-right">Solde disponible</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tresorerie)): ?>
                        <tr><td colspan="3" class="text-muted">Aucun compte financier actif.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tresorerie as $t): $solde = $t['total_debit'] - $t['total_credit']; ?>
                            <tr>
                                <td class="font-mono"><?php echo htmlspecialchars($t['code_compte']); ?></td>
                                <td><?php echo htmlspecialchars($t['libelle']); ?></td>
                                <td class="text-right font-mono"><?php echo number_format($solde, 2, ',', ' '); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <tr class="row-total">
                        <td colspan="2">TOTAL ACTIF (Trésorerie globale)</td>
                        <td class="text-right font-mono"><?php echo number_format($totalTresorerie, 2, ',', ' '); ?> €</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="card table-card">
            <h3>Passif (Fonds propres & Engagement)</h3>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Ressource</th>
                        <th class="text-right">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Fonds associatifs / Reports à nouveau antérieurs</td>
                        <td class="text-right font-mono"><?php echo number_format($totalTresorerie - $resultatNet, 2, ',', ' '); ?> €</td>
                    </tr>
                    <tr>
                        <td class="font-semibold">Résultat net de l'exercice (Excédent/Déficit)</td>
                        <td class="text-right font-mono <?php echo $resultatNet >= 0 ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($resultatNet, 2, ',', ' '); ?> €
                        </td>
                    </tr>
                    <tr class="row-total">
                        <td>TOTAL PASSIF (Fonds cumulés)</td>
                        <td class="text-right font-mono"><?php echo number_format($totalTresorerie, 2, ',', ' '); ?> €</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function switchTab(evt, tabId) {
    const tabContents = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabContents.length; i++) {
        tabContents[i].classList.remove("current");
    }
    const tabButtons = document.getElementsByClassName("tab-button");
    for (let i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove("active");
    }
    document.getElementById(tabId).classList.add("current");
    evt.currentTarget.classList.add("active");
}
</script>

<?php 
include_once __DIR__ . '/../templates/footer.php'; 
?>