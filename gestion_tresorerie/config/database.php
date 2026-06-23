<?php
// config/database.php

$dbPath = __DIR__ . '/../database/tresorerie.db';

try {
    // Connexion ou création de la base de données SQLite
    $pdo = new PDO("sqlite:" . $dbPath);
    // Configuration des erreurs sur Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}