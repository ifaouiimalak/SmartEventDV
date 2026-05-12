<?php
require 'header.php';
$error = '';
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("INSERT INTO users(name, email, password, favorite_category_id) VALUES(?, ?, ?, ?)");
        $stmt->execute([
            trim($_POST['name']),
            trim($_POST['email']),
            password_hash($_POST['password'], PASSWORD_DEFAULT),
            (int) $_POST['favorite_category_id']
        ]);
        header('Location: login.php?created=1');
        exit;
    } catch (Exception $e) {
        $error = 'Cet email existe déjà.';
    }
}
?>
<main class="container auth-page">
    <form class="form auth-card reveal visible" method="post">
        <span class="eyebrow">Nouveau profil</span>
        <h2>Créer un compte</h2>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <input name="name" placeholder="Nom complet" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <select name="favorite_category_id" required>
            <option value="">Choisir votre profil d'intérêt</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['icon'] . ' ' . $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn">Créer le compte</button>
    </form>
</main>
<?php require 'footer.php'; ?>
