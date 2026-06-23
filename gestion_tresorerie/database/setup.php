<?php
// database/setup.php

$dbPath = __DIR__ . '/tresorerie.db';

try {
    // 1. Connexion (crée le fichier s'il n'existe pas)
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Activer le support des clés étrangères dans SQLite
    $pdo->exec("PRAGMA foreign_keys = ON;");
    
    echo "=== Initialisation de la base de données ===<br>";

    // 2. Création de la table Adherents
    // Le NNI est configuré en UNIQUE pour éviter les doublons de personnel
    $pdo->exec("CREATE TABLE IF NOT EXISTS Adherents (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        nni TEXT UNIQUE NOT NULL,
        nom TEXT NOT NULL,
        prenom TEXT NOT NULL,
        email TEXT,
        telephone TEXT,
        taux_horaire REAL DEFAULT 0.0,
        date_adhesion TEXT,
        statut_cotisation TEXT
    );");
    echo "Table 'Adherents' créée avec succès.<br>";

    // 3. Création de la table PlanComptable
    $pdo->exec("CREATE TABLE IF NOT EXISTS PlanComptable (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code_compte TEXT UNIQUE NOT NULL,
        libelle TEXT NOT NULL
    );");
    echo "Table 'PlanComptable' créée avec succès.<br>";

    // 4. Création de la table Ecritures
    // Note : SQLite ne possède pas de type DATE natif, le format TEXT (YYYY-MM-DD) est la norme standard recommandée.
    $pdo->exec("CREATE TABLE IF NOT EXISTS Ecritures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        libelle TEXT NOT NULL,
        code_compte TEXT NOT NULL,
        debit REAL DEFAULT 0.0,
        credit REAL DEFAULT 0.0,
        FOREIGN KEY (code_compte) REFERENCES PlanComptable(code_compte) ON DELETE RESTRICT
    );");
    echo "Table 'Ecritures' créée avec succès.<br>";

    // 5. Pré-remplissage du Plan Comptable (Standard Associatif & Syndical Français)
    $comptes = [
        // Classe 5 : Comptes financiers (Trésorerie)
        ['512000', 'Banque (Compte courant)'],
        ['512100', 'Banque (Livret / Réserve)'],
        ['530000', 'Caisse (Espèces)'],

        // Classe 6 : Charges (Dépenses courantes du syndicat)
        ['606300', 'Fournitures d\'entretien et petit matériel'],
        ['606400', 'Fournitures de bureau, papeterie et reprographie'],
        ['606800', 'Achats de matériel promotionnel et tracts'],
        ['618000', 'Documentation générale et abonnements techniques'],
        ['622600', 'Honoraires (Conseils juridiques, avocats)'],
        ['623000', 'Publications, relations publiques et communication'],
        ['625100', 'Voyages et déplacements (Missions, Congrès, AG)'],
        ['625600', 'Frais de réceptions et réunions syndicales'],
        ['626000', 'Frais postaux et de télécommunications'],
        ['627000', 'Services bancaires (Frais de tenue de compte)'],
        ['657000', 'Secours et aides financières accordés aux adhérents'],

        // Classe 7 : Produits (Recettes)
        ['756000', 'Cotisations des adhérents'],
        ['740000', 'Subventions d\'exploitation (CSE, Fédération...)'],
        ['706000', 'Prestations de services et ventes diverses (Boutique, revues)'],
        ['760000', 'Produits financiers (Intérêts des livrets)']
    ];

    // Utilisation d'une requête préparée avec INSERT OR IGNORE pour éviter les doublons si le script est rejoué
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO PlanComptable (code_compte, libelle) VALUES (:code, :libelle);");
    
    $compteur = 0;
    foreach ($comptes as $compte) {
        $stmt->execute([
            ':code' => $compte[0],
            ':libelle' => $compte[1]
        ]);
        // rowCount() renvoie 1 si la ligne est insérée, 0 si elle existait déjà (IGNORÉE)
        $compteur += $stmt->rowCount();
    }
    
    echo "Plan comptable initialisé : $compteur nouveaux comptes insérés.<br>";
    echo "<b>Initialisation terminée avec succès !</b>";

} catch (PDOException $e) {
    die("Erreur critique lors de l'initialisation : " . $e->getMessage());
}