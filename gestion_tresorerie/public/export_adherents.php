<?php
// public/export_adherents.php

require_once __DIR__ . '/../config/database.php';

try {
    // Extraction complète de la liste
    $stmt = $pdo->query("SELECT nom, prenom, email, telephone, statut_cotisation, date_adhesion FROM Adherents ORDER BY nom ASC, prenom ASC");
    $lines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Configuration des headers HTTP pour forcer le téléchargement du fichier
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="registre_adherents_' . date('Ymd') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Ouverture du flux de sortie direct
    $output = fopen('php://output', 'w');

    // INJECTION DU BOM UTF-8 (Indispensable pour qu'Excel lise correctement les accents comme "À jour")
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Définition de la ligne d'en-tête (Séparateur point-virgule pour Excel France)
    fputcsv($output, ['Nom', 'Prénom', 'Adresse Email', 'Téléphone', 'Statut Cotisation', 'Date Inscription'], ';');

    // Injection des lignes de données
    foreach ($lines as $line) {
        fputcsv($output, [
            $line['nom'],
            $line['prenom'],
            $line['email'],
            $line['telephone'],
            $line['statut_cotisation'],
            date('d/m/Y', strtotime($line['date_adhesion']))
        ], ';');
    }

    fclose($output);
    exit;

} catch (Exception $e) {
    die("Erreur lors de la génération du fichier export : " . $e->getMessage());
}