<?php
require 'header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT e.*, c.name AS category, c.icon, COALESCE(SUM(CASE WHEN r.status <> 'annulee' THEN r.quantity ELSE 0 END), 0) AS reserved
                       FROM events e
                       LEFT JOIN categories c ON c.id = e.category_id
                       LEFT JOIN reservations r ON r.event_id = e.id
                       WHERE e.id = ?
                       GROUP BY e.id, c.name, c.icon");
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    echo '<main class="container"><div class="empty">Événement introuvable.</div></main>';
    require 'footer.php';
    exit;
}

$remaining = max(0, (int) $event['places'] - (int) $event['reserved']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = max(1, (int) $_POST['quantity']);
    if ($quantity > $remaining) {
        $error = 'Nombre de places insuffisant.';
    } else {
        $pdo->prepare("INSERT INTO reservations(user_id, event_id, quantity) VALUES(?, ?, ?)")->execute([
            $_SESSION['user']['id'],
            $id,
            $quantity
        ]);
        $reservationId = $pdo->lastInsertId();
        header('Location: reservation_success.php?id=' . $reservationId);
        exit;
    }
}
?>
<main class="container auth-page">
    <form class="form auth-card reveal visible" method="post">
        <span class="eyebrow"><?= htmlspecialchars(($event['icon'] ?? '✨') . ' ' . ($event['category'] ?? 'Événement')) ?></span>
        <h2><?= htmlspecialchars($event['title']) ?></h2>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <img class="detail-img" src="<?= htmlspecialchars(eventImage($event['image'])) ?>" alt="">
        <p class="muted"><?= htmlspecialchars($event['description']) ?></p>
        <div class="event-meta">
            <span class="chip">📍 <?= htmlspecialchars($event['location']) ?></span>
            <span class="chip">📅 <?= htmlspecialchars($event['event_date']) ?></span>
            <span class="chip">🎟️ <?= $remaining ?> places restantes</span>
        </div>
        <p class="price"><?= number_format((float) $event['price'], 2, ',', ' ') ?> DT</p>
        <input type="number" name="quantity" value="1" min="1" max="<?= $remaining ?>" required>
        <button class="btn success" <?= $remaining === 0 ? 'disabled' : '' ?>>Confirmer la réservation</button>
    </form>
</main>
<?php require 'footer.php'; ?>
