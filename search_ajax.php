<?php
require_once __DIR__ . '/config/db.php';

$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$userId = $_SESSION['user']['id'] ?? null;
$favoriteCategory = $_SESSION['user']['favorite_category_id'] ?? null;
$personalized = $userId && $favoriteCategory && $cat === '' && $q === '';
$params = ['%' . $q . '%', '%' . $q . '%'];

$sql = "SELECT e.*, c.name AS category, c.icon, COALESCE(SUM(CASE WHEN r.status <> 'annulee' THEN r.quantity ELSE 0 END), 0) AS reserved_count";
$sql .= $userId ? ", f.id AS favorite_id" : ", NULL AS favorite_id";
$sql .= " FROM events e
          LEFT JOIN categories c ON c.id = e.category_id
          LEFT JOIN reservations r ON r.event_id = e.id";

if ($userId) {
    $sql .= " LEFT JOIN favorites f ON f.event_id = e.id AND f.user_id = ?";
    array_unshift($params, $userId);
}

$sql .= " WHERE (e.title LIKE ? OR e.location LIKE ?)";

if ($cat !== '') {
    $sql .= " AND e.category_id = ?";
    $params[] = $cat;
} elseif ($personalized) {
    $sql .= " AND e.category_id = ?";
    $params[] = $favoriteCategory;
}

$sql .= " GROUP BY e.id, c.name, c.icon" . ($userId ? ", f.id" : "") . " ORDER BY e.event_date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

if (!$events) {
    echo '<div class="empty">Aucun événement trouvé.</div>';
    exit;
}

foreach ($events as $e):
    $remaining = max(0, (int) $e['places'] - (int) $e['reserved_count']);
?>
<article class="card reveal visible">
    <div class="card-img">
        <img src="<?= htmlspecialchars(eventImage($e['image'])) ?>" alt="<?= htmlspecialchars($e['title']) ?>">
        <span class="category-badge"><?= htmlspecialchars(($e['icon'] ?? '✨') . ' ' . ($e['category'] ?? 'Événement')) ?></span>
        <?php if ($userId): ?><a class="favorite <?= $e['favorite_id'] ? 'active' : '' ?>" href="favorite.php?id=<?= $e['id'] ?>">★</a><?php endif; ?>
    </div>
    <div class="card-body">
        <h3><?= htmlspecialchars($e['title']) ?></h3>
        <p class="muted"><?= htmlspecialchars($e['description']) ?></p>
        <div class="event-meta">
            <span class="chip">📍 <?= htmlspecialchars($e['location']) ?></span>
            <span class="chip">📅 <?= htmlspecialchars($e['event_date']) ?></span>
            <span class="chip">🎟️ <?= $remaining ?> places</span>
        </div>
        <div class="card-bottom">
            <p class="price"><?= number_format((float) $e['price'], 2, ',', ' ') ?> DT</p>
            <a class="btn" href="reserve.php?id=<?= $e['id'] ?>">Réserver</a>
        </div>
    </div>
</article>
<?php endforeach; ?>
