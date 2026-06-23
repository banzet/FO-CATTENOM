<?php
// public/nouvelle_transaction.php

// 1. Connexion à la base de données
require_once __DIR__ . '/../config/database.php';

$error = '';

// 2. TRAITEMENT DU FORMULAIRE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type      = $_POST['type_transaction'] ?? '';
    $date      = $_POST['date'] ?? '';
    $libelle   = trim($_POST['libelle'] ?? '');
    $montant   = floatval(str_replace(',', '.', $_POST['montant'] ?? 0));
    $codeCompte = $_POST['code_compte'] ?? '';

    // Validation des champs obligatoires
    if (empty($type) || empty($date) || empty($libelle) || $montant <= 0 || empty($codeCompte)) {
        $error = "Veuillez remplir tous les champs avec des valeurs valides.";
    } else {
        try {
            // Détermination du débit / crédit selon le type choisi
            $debit  = ($type === 'depense') ? $montant : 0.00;
            $credit = ($type === 'recette') ? $montant : 0.00;

            // Insertion dans la table Ecritures
            $stmt = $pdo->prepare("
                INSERT INTO Ecritures (date, libelle, debit, credit, code_compte) 
                VALUES (:date, :libelle, :debit, :credit, :code_compte)
            ");
            
            $stmt->execute([
                ':date'        => $date,
                ':libelle'     => $libelle,
                ':debit'       => $debit,
                ':credit'      => $credit,
                ':code_compte' => $codeCompte
            ]);

            // Redirection vers la page principale avec un paramètre de succès
            header("Location: index.php?status=success&msg=" . urlencode("La transaction a été enregistrée manuellement."));
            exit;

        } catch (PDOException $e) {
            $error = "Erreur lors de l'enregistrement en base de données : " . $e->getMessage();
        }
    }
}

// 3. RÉCUPÉRATION DU PLAN COMPTABLE POUR LE DROPDOWN
$comptes = $pdo->query("SELECT code_compte, libelle FROM PlanComptable ORDER BY code_compte ASC")->fetchAll();

// Inclusion du header global de l'application
include_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fa-solid fa-pen-to-square" style="color: var(--fo-blue);"></i> Saisie Manuelle</h2>
    <p>Enregistrez une nouvelle opération financière (hors import de relevé bancaire).</p>
</div>

<div class="accounting-layout-centered">
    <div class="card form-card-main">
        <h3>Nouvelle opération</h3>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form action="nouvelle_transaction.php" method="POST" class="form-standard">
            
            <div class="form-group">
                <label>Type de transaction <span class="required">*</span></label>
                <div class="transaction-type-selector">
                    <label class="type-option option-depense">
                        <input type="radio" name="type_transaction" value="depense" checked onclick="filterAccountingAccounts('depense')">
                        <div class="type-box">
                            <i class="fa-solid fa-arrow-up-from-bracket"></i>
                            <span>Dépense (Débit)</span>
                        </div>
                    </label>
                    <label class="type-option option-recette">
                        <input type="radio" name="type_transaction" value="recette" onclick="filterAccountingAccounts('recette')">
                        <div class="type-box">
                            <i class="fa-solid fa-arrow-down-to-bracket"></i>
                            <span>Recette (Crédit)</span>
                        </div>
                    </label>
                </div>
            </div>

            <div class="form-grid-2col">
                <div class="form-group">
                    <label for="date">Date de l'opération <span class="required">*</span></label>
                    <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="montant">Montant (€) <span class="required">*</span></label>
                    <input type="number" id="montant" name="montant" step="0.01" min="0.01" required placeholder="0,00" autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label for="libelle">Désignation / Libellé <span class="required">*</span></label>
                <input type="text" id="libelle" name="libelle" required placeholder="ex: Achat de timbres, Cotisation reçu..." autocomplete="off">
            </div>

            <div class="form-group">
                <label for="code_compte">Imputation Comptable / Budget <span class="required">*</span></label>
                <select id="code_compte" name="code_compte" required class="select-accounting">
                    <option value="" disabled selected>-- Sélectionner un compte ciblé --</option>
                    
                    <optgroup id="optgroup-charges" label="Classe 6 — Charges et Dépenses">
                        <?php foreach ($comptes as $compte): ?>
                            <?php if (strpos($compte['code_compte'], '6') === 0): ?>
                                <option value="<?php echo htmlspecialchars($compte['code_compte']); ?>">
                                    <?php echo htmlspecialchars($compte['code_compte'] . ' - ' . $compte['libelle']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </optgroup>

                    <optgroup id="optgroup-produits" label="Classe 7 — Produits et Recettes">
                        <?php foreach ($comptes as $compte): ?>
                            <?php if (strpos($compte['code_compte'], '7') === 0): ?>
                                <option value="<?php echo htmlspecialchars($compte['code_compte']); ?>">
                                    <?php echo htmlspecialchars($compte['code_compte'] . ' - ' . $compte['libelle']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>

            <div class="form-actions-row">
                <a href="index.php" class="btn-cancel">Annuler</a>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-floppy-disk"></i> Enregistrer l'écriture
                </button>
            </div>

        </form>
    </div>
</div>

<script>
function filterAccountingAccounts(type) {
    const groupCharges = document.getElementById('optgroup-charges');
    const groupProduits = document.getElementById('optgroup-produits');
    const selectElement = document.getElementById('code_compte');

    // Réinitialise la sélection actuelle pour éviter les incohérences
    selectElement.value = "";

    if (type === 'depense') {
        // Affiche la classe 6, cache la classe 7
        groupCharges.style.display = '';
        groupProduits.style.display = 'none';
    } else if (type === 'recette') {
        // Affiche la classe 7, cache la classe 6
        groupCharges.style.display = 'none';
        groupProduits.style.display = '';
    }
}

// Lancement au chargement pour initialiser l'état par défaut (Dépense cochée)
document.addEventListener("DOMContentLoaded", function() {
    filterAccountingAccounts('depense');
});
</script>

<?php 
include_once __DIR__ . '/../templates/footer.php'; 
?>