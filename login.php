<?php
require 'header.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([trim($_POST['email'])]);
    $user = $stmt->fetch();

    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user'] = $user;
        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: user/dashboard.php');
        }
        exit;
    }

    $error = 'Email ou mot de passe incorrect.';
}
?>
<main class="container auth-page">
    <form class="form auth-card reveal visible" method="post">
        <span class="eyebrow">Accès sécurisé</span>
        <h2>Connexion</h2>
        <?php if (isset($_GET['created'])): ?><div class="alert success">Compte créé. Connectez-vous maintenant.</div><?php endif; ?>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button class="btn">Se connecter</button>
    </form>
</main>
<?php require 'footer.php'; ?>
