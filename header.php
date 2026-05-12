<?php
require_once __DIR__ . '/config/db.php';

$docRoot    = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$projectDir = str_replace('\\', '/', __DIR__);
$basePath   = '/' . ltrim(str_replace($docRoot, '', $projectDir), '/');
?>
<!DOCTYPE html>
<html lang="fr" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartEvent</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= $basePath ?>/assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
      // Apply saved theme immediately to prevent flash
      (function(){
        const t = localStorage.getItem('se-theme') || 'dark';
        document.documentElement.setAttribute('data-theme', t);
      })();
    </script>
</head>
<body>

<!-- Toast container -->
<div class="toast-container" id="toastContainer"></div>

<header class="nav">
    <a class="brand" href="<?= $basePath ?>/index.php">
        <div class="logo-icon">🎫</div>
        <span>Smart</span>Event
    </a>

    <nav class="nav-links" id="navLinks">
        <a href="<?= $basePath ?>/index.php">Accueil</a>

        <?php if (isset($_SESSION['user'])): ?>
            <?php if ($_SESSION['user']['role'] === 'admin'): ?>
                <a href="<?= $basePath ?>/admin/dashboard.php">⚙️ Admin</a>
            <?php else: ?>
                <a href="<?= $basePath ?>/user/dashboard.php">👤 Mon espace</a>
            <?php endif; ?>
            <a href="<?= $basePath ?>/logout.php" class="nav-btn">Déconnexion</a>
        <?php else: ?>
            <a href="<?= $basePath ?>/login.php">Connexion</a>
            <a href="<?= $basePath ?>/register.php" class="nav-btn">S'inscrire</a>
        <?php endif; ?>
    </nav>

    <div class="nav-right">
        <button class="theme-toggle" id="themeToggle" title="Changer le thème">🌙</button>
        <button class="nav-hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
