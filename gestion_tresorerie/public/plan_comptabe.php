<?php
// public/plan_comptable.php

// 1. Inclusion de la connexion à la base SQLite
require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = ''; // 'success' ou 'danger'
$editMode = false;
$editAccount = ['id' => '', 'code_compte' => '', 'libelle' => ''];

// 2. TRAITEMENT DES ACTIONS (POST & GET)
try {
    // AJOUT d'un compte
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $code = trim($_POST['code_compte']);
        $libelle = trim($_POST['libelle']);

        if (!empty($code) && !empty($libelle)) {
            $stmt = $pdo->prepare("INSERT INTO PlanComptable (code_compte, libelle) VALUES (:code, :libelle)");
            $stmt->execute([':code' => $code, ':libelle' => $libelle]);
            $message = "Le compte $code a été ajouté avec succès.";
            $messageType = "success";
        } else {
            $message = "Tous les champs sont obligatoires.";
            $messageType = "danger";
        }
    }

    // MODIFICATION (Enregistrement du nouveau libellé)
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        $id = intval($_POST['id']);
        $libelle = trim($_POST['libelle']);

        if (!empty($id) && !empty($libelle)) {
            $stmt = $pdo->prepare("UPDATE PlanComptable SET libelle = :libelle WHERE id = :id");
            $stmt->execute([':libelle' => $libelle, ':id' => $id]);
            $message = "Le libellé du compte a été mis à jour.";
            $messageType = "success";
        }
    }

    // SUPPRESSION d'un compte
    if (isset($_GET['delete'])) {
        $idToDelete = intval($_GET['delete']);
        
        // On active la vérification des clés étrangères pour SQLite
        $pdo->exec("PRAGMA foreign_keys = ON;");
        
        $stmt = $pdo->prepare("DELETE FROM PlanComptable WHERE id = :id");
        $stmt->execute([':id' => $idToDelete]);
        
        $message = "Le compte a été supprimé du plan comptable.";
        $messageType = "success";
    }

    // MODE ÉDITION (Chargement des données dans le formulaire)
    if (isset($_GET['edit'])) {
        $idToEdit = intval($_GET['edit']);
        $stmt = $pdo->prepare("SELECT * FROM PlanComptable WHERE id = :id");
        $stmt->execute([':id' => $idToEdit]);
        $account = $stmt->fetch();
        if ($account) {
            $editMode = true;
            $editAccount = $account;
        }
    }

} catch (PDOException $e) {
    // Gestion fine de l'erreur de contrainte (compte utilisé dans la table Ecritures)
    if ($e->getCode() == '23000' || strpos($e->getMessage(), 'FOREIGN KEY') !== false) {
        $message = "Impossible de supprimer ce compte car il est utilisé dans des écritures comptables.";
    } else {
        $message = "Erreur : " . $e->getMessage();
    }
    $messageType = "danger";
}

// 3. RÉCUPÉRATION DE LA LISTE DES COMPTES
$comptes = $pdo->query("SELECT * FROM PlanComptable ORDER BY code_compte ASC")->fetchAll();

// 4. INCLUSION DU HEADER
include_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2>Gestion du Plan Comptable</h2>
    <p>Configurez et personnalisez les comptes de charges, produits et trésorerie du syndicat.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="accounting-layout">
    
    <div class="card form-card">
        <h3><?php echo $editMode ? 'Modifier le libellé' : 'Ajouter un compte'; ?></h3>
        <form action="plan_comptable.php" method="POST" class="form-standard">
            <input type="hidden" name="action" value="<?php echo $editMode ? 'update' : 'add'; ?>">
            <?php if ($editMode): ?>
                <input type="hidden" name="id" value="<?php echo $editAccount['id']; ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="code_compte">Numéro de compte</label>
                <input type="text" id="code_compte" name="code_compte" 
                       placeholder="Ex: 606400" pattern="[0-9]{3,8}" required
                       value="<?php echo htmlspecialchars($editAccount['code_compte']); ?>"
                       <?php echo $editMode ? 'disabled class="input-disabled"' : ''; ?>>
                <?php if ($editMode): ?>
                    <small>Le numéro de compte ne peut pas être modifié.</small>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="libelle">Libellé du compte</label>
                <input type="text" id="libelle" name="libelle" placeholder="Ex: Fournitures de bureau" required
                       value="<?php echo htmlspecialchars($editAccount['libelle']); ?>">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-submit <?php echo $editMode ? 'btn-edit' : 'btn-success'; ?>">
                    <i class="fa-solid <?php echo $editMode ? 'fa-pen-to-square' : 'fa-plus'; ?>"></i>
                    <?php echo $editMode ? 'Enregistrer' : 'Ajouter'; ?>
                </button>
                <?php if ($editMode): ?>
                    <a href="plan_comptable.php" class="btn btn-cancel">Annuler</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card table-card">
        <h3>Comptes Actifs</h3>
        <div class="table-wrapper">
            <table class="responsive-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Libellé du compte</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comptes as $compte): ?>
                        <tr>
                            <td data-label="Code" class="font-mono text-bold"><?php echo htmlspecialchars($compte['code_compte']); ?></td>
                            <td data-label="Libellé"><?php echo htmlspecialchars($compte['libelle']); ?></td>
                            <td data-label="Actions" class="text-center action-buttons">
                                <a href="plan_comptable.php?edit=<?php echo $compte['id']; ?>" class="btn-action btn-action-edit" title="Modifier le libellé">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="plan_comptable.php?delete=<?php echo $compte['id']; ?>" class="btn-action btn-action-delete" title="Supprimer" 
                                   onclick="return confirm('Confirmez-vous la suppression du compte <?php echo $compte['code_compte']; ?> ?');">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php 
// 5. INCLUSION DU FOOTER
include_once __DIR__ . '/../templates/footer.php'; 
?>