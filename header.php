<?php
require_once __DIR__ . '/config/db.php';

// Détecte automatiquement le chemin de base (ex: /smartevent ou /mon-dossier)
$scriptDir  = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Remonte jusqu'à la racine du projet (header.php est à la racine)
// On cherche le dossier commun entre __DIR__ et DOCUMENT_ROOT
$docRoot    = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$projectDir = str_replace('\\', '/', __DIR__);
$basePath   = '/' . ltrim(str_replace($docRoot, '', $projectDir), '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEvent</title>
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<header class="nav">
    <a class="brand" href="<?= $basePath ?>/index.php"><span>Smart</span>Event</a>
    <nav>
    <a href="<?= $basePath ?>/index.php">Accueil</a>

    <?php if (isset($_SESSION['user'])): ?>

        <?php if ($_SESSION['user']['role'] === 'admin'): ?>
            <a href="<?= $basePath ?>/admin/dashboard.php">Espace admin</a>
        <?php else: ?>
            <a href="<?= $basePath ?>/user/dashboard.php">Espace client</a>
        <?php endif; ?>

        <a href="<?= $basePath ?>/logout.php">Déconnexion</a>

    <?php else: ?>
        <a href="<?= $basePath ?>/login.php">Connexion</a>
        <a href="<?= $basePath ?>/register.php">Inscription</a>
    <?php endif; ?>
</nav>
</header>
