<?php
// public/adherents.php

require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';
$titrePage = "Gestion des Adhésions";

// INITIALISATION AUTOMATIQUE DE LA TABLE SI ELLE N'EXISTE PAS
$pdo->exec("
    CREATE TABLE IF NOT EXISTS Adherents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nom TEXT NOT NULL,
        prenom TEXT NOT NULL,
        email TEXT,
        telephone TEXT,
        statut_cotisation TEXT DEFAULT 'En retard',
        date_adhesion TEXT DEFAULT CURRENT_DATE
    )
");

// 1. TRAITEMENT : AJOUT D'UN ADHÉRENT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ajouter') {
    $nom = strtoupper(trim($_POST['nom']));
    $prenom = ucfirst(trim($_POST['prenom']));
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $statut = $_POST['statut_cotisation'] === 'À jour' ? 'À jour' : 'En retard';

    try {
        if (!empty($nom) && !empty($prenom)) {
            $stmt = $pdo->prepare("INSERT INTO Adherents (nom, prenom, email, telephone, statut_cotisation) VALUES (:nom, :prenom, :email, :telephone, :statut)");
            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':telephone' => $telephone,
                ':statut' => $statut
            ]);
            $message = "L'adhérent $prenom $nom a été enregistré avec succès.";
            $messageType = "success";
        } else {
            throw new Exception("Le nom et le prénom sont obligatoires.");
        }
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $messageType = "danger";
    }
}

// 2. TRAITEMENT : CHANGEMENT DE STATUT DE COTISATION EN UN CLIC
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_statut') {
    $idAdherent = intval($_POST['id_adherent']);
    $statutActuel = $_POST['statut_actuel'];
    $nouveauStatut = ($statutActuel === 'À jour') ? 'En retard' : 'À jour';

    $stmt = $pdo->prepare("UPDATE Adherents SET statut_cotisation = :statut WHERE id = :id");
    $stmt->execute([
        ':statut' => $nouveauStatut,
        ':id' => $idAdherent
    ]);
    
    // Redirection rapide pour éviter le renvoi du formulaire au rafraîchissement
    header("Location: adherents.php" . (!empty($_GET['search']) ? '?search=' . urlencode($_GET['search']) : ''));
    exit;
}

// 3. MOTEUR DE RECHERCHE & LECTURE DES DONNÉES
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM Adherents WHERE nom LIKE :search OR prenom LIKE :search OR email LIKE :search ORDER BY nom ASC, prenom ASC");
    $stmt->execute([':search' => "%$search%"]);
    $adherents = $stmt->fetchAll();
} else {
    $adherents = $pdo->query("SELECT * FROM Adherents ORDER BY nom ASC, prenom ASC")->fetchAll();
}

include_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fa-solid fa-users" style="color: var(--fo-blue);"></i> Fichier des Adhérents</h2>
    <p>Suivez les effectifs, contrôlez le paiement des cotisations et exportez vos registres.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <div><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<div class="toolbar-adherents">
    <form action="adherents.php" method="GET" class="search-form-container">
        <div class="search-input-group">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Rechercher un nom, prénom, email...">
            <?php if (!empty($search)): ?>
                <a href="adherents.php" class="clear-search"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-edit">Filtrer</button>
    </form>

    <a href="export_adherents.php" class="btn-export-csv">
        <i class="fa-solid fa-file-excel"></i> Exporter la liste (CSV Excel)
    </a>
</div>

<div class="accounting-layout">
    
    <div class="card table-card table-container-large">
        <h3>Registre du personnel adhérent (<?php echo count($adherents); ?>)</h3>
        
        <?php if (count($adherents) === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-user-slash text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <p>Aucun adhérent trouvé dans la base de données.</p>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="responsive-table data-table">
                    <thead>
                        <tr>
                            <th>Identité</th>
                            <th>Coordonnées</th>
                            <th>Date d'entrée</th>
                            <th class="text-center">Cotisation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($adherents as $adherent): ?>
                            <tr>
                                <td data-label="Identité">
                                    <div class="user-identity">
                                        <span class="user-name"><?php echo htmlspecialchars($adherent['nom']); ?></span>
                                        <span class="user-firstname"><?php echo htmlspecialchars($adherent['prenom']); ?></span>
                                    </div>
                                </td>
                                <td data-label="Coordonnées" class="font-mono" style="font-size: 0.85rem;">
                                    <div><i class="fa-solid fa-envelope text-muted"></i> <?php echo htmlspecialchars($adherent['email'] ?: '-'); ?></div>
                                    <div><i class="fa-solid fa-phone text-muted"></i> <?php echo htmlspecialchars($adherent['telephone'] ?: '-'); ?></div>
                                </td>
                                <td data-label="Date d'entrée" class="font-mono">
                                    <?php echo date('d/m/Y', strtotime($adherent['date_adhesion'])); ?>
                                </td>
                                <td data-label="Cotisation" class="text-center">
                                    <form action="adherents.php<?php echo !empty($search) ? '?search='.urlencode($search) : ''; ?>" method="POST" style="margin: 0;">
                                        <input type="hidden" name="action" value="toggle_statut">
                                        <input type="hidden" name="id_adherent" value="<?php echo $adherent['id']; ?>">
                                        <input type="hidden" name="statut_actuel" value="<?php echo $adherent['statut_cotisation']; ?>">
                                        
                                        <button type="submit" class="badge-status-toggle <?php echo $adherent['statut_cotisation'] === 'À jour' ? 'status-active' : 'status-overdue'; ?>" title="Cliquer pour changer le statut">
                                            <i class="fa-solid <?php echo $adherent['statut_cotisation'] === 'À jour' ? 'fa-check-circle' : 'fa-circle-exclamation'; ?>"></i>
                                            <?php echo htmlspecialchars($adherent['statut_cotisation']); ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card form-card-sidebar">
        <h3>Nouvelle Adhésion</h3>
        <form action="adherents.php" method="POST" class="form-standard">
            <input type="hidden" name="action" value="ajouter">
            
            <div class="form-group">
                <label for="nom">Nom de famille <span class="required">*</span></label>
                <input type="text" id="nom" name="nom" required placeholder="ex: DUPONT" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="prenom">Prénom <span class="required">*</span></label>
                <input type="text" id="prenom" name="prenom" required placeholder="ex: Jean" autocomplete="off">
            </div>

            <div class="form-group">
                <label for="email">Adresse Email</label>
                <input type="email" id="email" name="email" placeholder="jean.dupont@exemple.fr">
            </div>

            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" placeholder="06 00 00 00 00">
            </div>

            <div class="form-group">
                <label for="statut_cotisation">État de la cotisation initiale</label>
                <select id="statut_cotisation" name="statut_cotisation">
                    <option value="En retard" selected>En retard (À régulariser)</option>
                    <option value="À jour">À jour (Payée)</option>
                </select>
            </div>

            <button type="submit" class="btn btn-edit" style="width: 100%; justify-content: center; margin-top: 1rem;">
                <i class="fa-solid fa-user-plus"></i> Valider l'inscription
            </button>
        </form>
    </div>

</div>

<?php 
include_once __DIR__ . '/../templates/footer.php'; 
?>