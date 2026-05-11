<?php
require 'header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT r.*, e.title, e.event_date, e.location, e.price, e.image, c.name AS category, c.icon
                       FROM reservations r
                       JOIN events e ON e.id = r.event_id
                       LEFT JOIN categories c ON c.id = e.category_id
                       WHERE r.id = ? AND r.user_id = ?");
$stmt->execute([$id, $_SESSION['user']['id']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    echo '<main class="container"><div class="empty">Réservation introuvable.</div></main>';
    require 'footer.php';
    exit;
}

$code = 'SMARTEVENT-' . $reservation['id'] . '-' . $reservation['user_id'];
?>
<main class="container auth-page">
    <section class="confirmation-card reveal visible">
        <div class="success-icon">✓</div>
        <h2>Réservation enregistrée</h2>
        <p class="muted">Votre demande est en attente de validation par l'administration.</p>
        <img class="detail-img" src="<?= htmlspecialchars(eventImage($reservation['image'])) ?>" alt="<?= htmlspecialchars($reservation['title']) ?>">
        <h3><?= htmlspecialchars($reservation['title']) ?></h3>
        <div class="event-meta centered">
            <span class="chip"><?= htmlspecialchars(($reservation['icon'] ?? '✨') . ' ' . ($reservation['category'] ?? 'Événement')) ?></span>
            <span class="chip">📍 <?= htmlspecialchars($reservation['location']) ?></span>
            <span class="chip">📅 <?= htmlspecialchars($reservation['event_date']) ?></span>
        </div>
        <p class="price"><?= number_format((float) $reservation['price'] * $reservation['quantity'], 2, ',', ' ') ?> DT</p>
        <img class="qr big" src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=<?= urlencode($code) ?>" alt="QR Code">
        <div class="hero-actions centered">
            <a class="btn" href="user/account.php">Voir mes réservations</a>
            <a class="btn secondary" href="index.php">Accueil</a>
        </div>
    </section>
</main>
<?php require 'footer.php'; ?>
