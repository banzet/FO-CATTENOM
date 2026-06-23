<?php
// public/import_ofx.php

require_once __DIR__ . '/../config/database.php';

$message = '';
$messageType = '';
$titrePage = "Importation Relevé Crédit Mutuel";

// TRAITEMENT DU FICHIER APRÈS SOUMISSION
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['ofx_file'])) {
    $file = $_FILES['ofx_file'];

    try {
        // 1. Vérifications de base
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur lors du téléversement du fichier.");
        }

        // Vérification de l'extension
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (strtolower($extension) !== 'ofx') {
            throw new Exception("Le fichier doit obligatoirement être au format .ofx");
        }

        // 2. Lecture du contenu et conversion d'encodage si nécessaire
        $content = file_get_contents($file['tmp_name']);
        
        // Le Crédit Mutuel encode souvent en Windows-1252 / ISO-8859-1. On convertit en UTF-8 pour la base de données.
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252');
        }

        // 3. Extraction des blocs de transactions <STMTTRN>
        // Le modificateur /s permet au point (.) de matcher aussi les sauts de ligne
        preg_match_all('/<STMTTRN>(.*?)<\/STMTTRN>/s', $content, $transactions);

        if (empty($transactions[1])) {
            throw new Exception("Aucune transaction trouvée dans le fichier OFX. Vérifiez le format.");
        }

        // 4. Préparation de la requête d'insertion SQLite
        $stmt = $pdo->prepare("
            INSERT INTO Ecritures (date, libelle, code_compte, debit, credit) 
            VALUES (:date, :libelle, :code_compte, :debit, :credit)
        ");

        $insertCount = 0;
        $compteCourantBancaire = '512000'; // Compte de classe 5 défini lors de l'initialisation

        // 5. Boucle sur chaque transaction
        foreach ($transactions[1] as $trn) {
            
            // Extraction de la date (<DTPOSTED>20260623120000)
            $date = '';
            if (preg_match('/<DTPOSTED>(\d{4})(\d{2})(\d{2})/', $trn, $mDate)) {
                $date = "{$mDate[1]}-{$mDate[2]}-{$mDate[3]}"; // Format standard ISO YYYY-MM-DD
            }

            // Extraction du montant (<TRNAMT>-45.50 ou <TRNAMT>120.00)
            $montant = 0.0;
            if (preg_match('/<TRNAMT>([-\d.]+)/', $trn, $mAmt)) {
                $montant = floatval($mAmt[1]);
            }

            // Extraction du libellé (<NAME> ou à défaut <MEMO>)
            $libelle = 'Opération bancaire';
            if (preg_match('/<NAME>([^<\r\n]+)/', $trn, $mName)) {
                $libelle = trim($mName[1]);
            } elseif (preg_match('/<MEMO>([^<\r\n]+)/', $trn, $mMemo)) {
                $libelle = trim($mMemo[1]);
            }
            
            // Nettoyage rapide des caractères résiduels de fin de balise SGML
            $libelle = htmlspecialchars_decode(trim($libelle), ENT_QUOTES);

            // 6. Application de votre règle Débit / Crédit
            $debit = 0.0;
            $credit = 0.0;

            if ($montant < 0) {
                $debit = abs($montant); // On passe la valeur négative en positif pour la colonne débit
            } else {
                $credit = $montant;
            }

            // 7. Exécution de l'insertion
            if (!empty($date)) {
                $stmt->execute([
                    ':date'        => $date,
                    ':libelle'     => $libelle,
                    ':code_compte' => $compteCourantBancaire,
                    ':debit'       => $debit,
                    ':credit'      => $credit
                ]);
                $insertCount++;
            }
        }

        $message = "Succès ! Le fichier a été traité. $insertCount écritures ont été intégrées au compte $compteCourantBancaire.";
        $messageType = "success";

    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
        $messageType = "danger";
    }
}

// INCLUSION DU HEADER RESPONSIVE
include_once __DIR__ . '/../templates/header.php';
?>

<div class="page-header">
    <h2><i class="fa-solid fa-file-import" style="color: var(--fo-blue);"></i> Importation des flux bancaires</h2>
    <p>Téléversez le fichier extrait de votre espace Crédit Mutuel pour automatiser la saisie du journal de banque.</p>
</div>

<?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?>">
        <i class="fa-solid <?php echo $messageType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
        <div><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<div class="accounting-layout single-column">
    
    <div class="card import-card">
        <div class="import-icon-zone">
            <i class="fa-solid fa-university text-muted"></i>
            <i class="fa-solid fa-arrow-right text-muted mx-2"></i>
            <i class="fa-solid fa-file-code text-primary"></i>
        </div>
        
        <form action="import_ofx.php" method="POST" enctype="multipart/form-data" class="form-standard">
            <div class="form-group dropzone-area">
                <label for="ofx_file" class="dropzone-label">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Cliquez pour choisir le fichier <strong>.ofx</strong></span>
                    <small>Fichier brut téléchargé depuis le site de la banque</small>
                </label>
                <input type="file" id="ofx_file" name="ofx_file" accept=".ofx" required onchange="displayFileName()">
            </div>
            
            <div id="file-name-preview" class="file-preview-box" style="display: none;">
                <i class="fa-solid fa-file-lines"></i> <span id="file-name-text"></span>
            </div>

            <div class="form-actions text-center">
                <button type="submit" class="btn btn-edit" style="width: 100%; justify-content: center;">
                    <i class="fa-solid fa-gears"></i> Lancer l'intégration automatique
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function displayFileName() {
    const input = document.getElementById('ofx_file');
    const previewBox = document.getElementById('file-name-preview');
    const previewText = document.getElementById('file-name-text');
    
    if (input.files && input.files.length > 0) {
        previewText.textContent = input.files[0].name;
        previewBox.style.display = 'block';
    } else {
        previewBox.style.display = 'none';
    }
}
</script>

<?php 
// INCLUSION DU FOOTER
include_once __DIR__ . '/../templates/footer.php'; 
?>