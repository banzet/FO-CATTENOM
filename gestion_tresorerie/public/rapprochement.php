<?php
// public/rapprochement.php

// 1. Connexion à la base de données
require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';

// 2. TRAITEMENT DE L'AFFECTATION COMPTABLE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'affecter') {
    $idEcriture = intval($_POST['id_ecriture']);
    $nouveauCompte = trim($_POST['code_compte']);

    try {
        if (!empty($idEcriture) && !empty($nouveauCompte)) {
            // Mise à jour de l'écriture avec le bon compte du plan comptable
            $stmt = $pdo->prepare("UPDATE Ecritures SET code_compte = :code_compte WHERE id = :id");
            $stmt->execute([
                ':code_compte' => $nouveauCompte,
                ':id'          => $idEcriture
            ]);
            $message = "L'opération a bien été affectée au compte $nouveauCompte.";
            $messageType = "success";
        } else {
            $message = "Veuillez sélectionner un compte valide.";
            $messageType = "danger";
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de l'affectation : " . $e->getMessage();
        $messageType = "danger";
    }
}

// 3. RÉCUPÉRATION DU PLAN COMPTABLE (Groupé pour le menu déroulant)
$comptes = $pdo->query("SELECT code_compte, libelle FROM PlanComptable ORDER BY code_compte ASC")->fetchAll();

// 4. RÉCUPÉRATION DES ÉCRITURES NON VENTILÉES (qui sont encore sur le compte Banque 512000)
$ecrituresAFFECTER = $pdo->query("
    SELECT * FROM Ecritures 
    WHERE code_compte = '512000' 
    ORDER BY date DESC
")->fetchAll();

// Inclusion du header global de l'application
include_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fa-solid fa-scale-balanced" style="color: var(--fo-blue);"></i> Rapprochement & Ventilation</h2>
    <p>Attribuez le bon compte budgétaire aux opérations importées de votre relevé bancaire Crédit Mutuel.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <div><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<div class="card table-card">
    <h3>Opérations en attente de qualification (<?php echo count($ecrituresAFFECTER); ?>)</h3>

    <?php if (count($ecrituresAFFECTER) === 0): ?>
        <div class="empty-state">
            <i class="fa-solid fa-circle-check"></i>
            <p>Parfait ! Toutes les opérations bancaires ont été rapprochées et affectées.</p>
        </div>
    <?php else: ?>
        <div class="table-wrapper">
            <table class="responsive-table reconciliation-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Opération / Libellé bancaire</th>
                        <th>Débit (-)</th>
                        <th>Crédit (+)</th>
                        <th>Compte de destination</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ecrituresAFFECTER as $ecriture): ?>
                        <tr>
                            <td data-label="Date" class="font-mono"><?php echo htmlspecialchars($ecriture['date']); ?></td>
                            <td data-label="Libellé" class="text-left font-semibold"><?php echo htmlspecialchars($ecriture['libelle']); ?></td>
                            <td data-label="Débit" class="text-danger font-mono text-bold">
                                <?php echo $ecriture['debit'] > 0 ? number_format($ecriture['debit'], 2, ',', ' ') . ' €' : '-'; ?>
                            </td>
                            <td data-label="Crédit" class="text-success font-mono text-bold">
                                <?php echo $ecriture['credit'] > 0 ? number_format($ecriture['credit'], 2, ',', ' ') . ' €' : '-'; ?>
                            </td>
                            <td data-label="Affectation">
                                <form id="form-reconcile-<?php echo $ecriture['id']; ?>" action="rapprochement.php" method="POST">
                                    <input type="hidden" name="action" value="affecter">
                                    <input type="hidden" name="id_ecriture" value="<?php echo $ecriture['id']; ?>">
                                    
                                    <select name="code_compte" required class="select-accounting">
                                        <option value="" disabled selected>-- Choisir le compte ciblé --</option>
                                        <?php 
                                        $currentClass = '';
                                        foreach ($comptes as $compte): 
                                            // Séparation visuelle par classe comptable (6: Charges, 7: Produits)
                                            $firstDigit = substr($compte['code_compte'], 0, 1);
                                            if ($firstDigit !== $currentClass) {
                                                if ($currentClass !== '') echo '</optgroup>';
                                                $currentClass = $firstDigit;
                                                $groupLabel = ($currentClass == '6') ? 'Classe 6 — Dépenses / Charges' : (($currentClass == '7') ? 'Classe 7 — Recettes / Cotisations' : 'Classe 5 — Trésorerie');
                                                echo "<optgroup label=\"" . htmlspecialchars($groupLabel) . "\">";
                                            }
                                        ?>
                                            <option value="<?php echo htmlspecialchars($compte['code_compte']); ?>">
                                                <?php echo htmlspecialchars($compte['code_compte'] . ' - ' . $compte['libelle']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if ($currentClass !== '') echo '</optgroup>'; ?>
                                    </select>
                                </form>
                            </td>
                            <td data-label="Action" class="text-center">
                                <button type="submit" form="form-reconcile-<?php echo $ecriture['id']; ?>" class="btn-validate">
                                    <i class="fa-solid fa-check"></i> Valider
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php 
// Inclusion du footer global
include_once __DIR__ . '/../templates/footer.php'; 
?>