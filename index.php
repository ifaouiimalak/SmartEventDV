<?php
require 'header.php';

$q = trim($_GET['q'] ?? '');
$cat = trim($_GET['cat'] ?? '');
$userId = $_SESSION['user']['id'] ?? null;
$favoriteCategory = $_SESSION['user']['favorite_category_id'] ?? null;
$personalized = $userId && $favoriteCategory && $cat === '' && $q === '';
$params = ['%' . $q . '%'];

$sql = "SELECT e.*, c.name AS category, c.icon, COUNT(r.id) AS reserved_count";
$sql .= $userId ? ", f.id AS favorite_id" : ", NULL AS favorite_id";
$sql .= " FROM events e
          LEFT JOIN categories c ON c.id = e.category_id
          LEFT JOIN reservations r ON r.event_id = e.id AND r.status <> 'annulee'";

if ($userId) {
    $sql .= " LEFT JOIN favorites f ON f.event_id = e.id AND f.user_id = ?";
    array_unshift($params, $userId);
}

$sql .= " WHERE e.title LIKE ?";

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
$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$featured = $pdo->query("SELECT e.*, c.name AS category, c.icon FROM events e LEFT JOIN categories c ON c.id = e.category_id ORDER BY e.event_date ASC LIMIT 6")->fetchAll();
?>
<section class="hero">
    <div class="hero-content reveal visible">
        <span class="hero-label">Tunisie · Réservation premium</span>
        <h1>Vos événements, selon votre profil</h1>
        <p>Concerts, musique, sport, ports, voyages et formations avec réservation rapide, confirmation claire et suivi client.</p>
        <div class="hero-actions">
            <a class="btn" href="#events">Voir les événements</a>
            <?php if (!isset($_SESSION['user'])): ?>
                <a class="btn secondary" href="register.php">Créer un profil</a>
            <?php else: ?>
                <a class="btn secondary" href="user/account.php">Espace client</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="hero-slider" aria-label="Slider événements">
        <?php foreach ($featured as $i => $e): ?>
            <figure class="slide <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= htmlspecialchars(eventImage($e['image'])) ?>" alt="<?= htmlspecialchars($e['title']) ?>">
                <figcaption>
                    <span><?= htmlspecialchars(($e['icon'] ?? '✨') . ' ' . ($e['category'] ?? 'Événement')) ?></span>
                    <b><?= htmlspecialchars($e['title']) ?></b>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
</section>

<main class="container">
    <section class="workspace-grid reveal visible">
        <a class="workspace-card" href="<?= isset($_SESSION['user']) ? 'user/account.php' : 'register.php' ?>">
            <b>Espace client</b>
            <span>Profil, favoris, historique, QR code et suivi des réservations.</span>
        </a>
        <a class="workspace-card" href="<?= isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin' ? 'admin/dashboard.php' : 'login.php' ?>">
            <b>Espace admin</b>
            <span>Création d’événements, confirmations, statistiques et gestion complète.</span>
        </a>
    </section>

    <section class="profile-strip reveal visible">
        <div>
            <span class="eyebrow">Sélection intelligente</span>
            <h2><?= $personalized ? 'Pour votre profil' : 'Catalogue événementiel' ?></h2>
            <p><?= $personalized ? 'Les événements affichés correspondent à votre catégorie préférée.' : 'Connectez-vous pour voir une sélection adaptée à votre profil.' ?></p>
        </div>
        <?php if (isset($_SESSION['user'])): ?>
            <a class="btn secondary" href="user/account.php">Modifier mon profil</a>
        <?php endif; ?>
    </section>

    <form method="get" class="form wide filter-form reveal" id="filterForm">
        <input id="searchInput" name="q" placeholder="Rechercher par nom, lieu ou ambiance..." value="<?= htmlspecialchars($q) ?>">
        <select id="categorySelect" name="cat">
            <option value="">Mon profil / toutes catégories</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['icon'] . ' ' . $c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn">Filtrer</button>
    </form>

    <div class="section-title" id="events">
        <h2>Événements disponibles</h2>
    </div>

    <div class="grid" id="eventsGrid">
        <?php if (!$events): ?><div class="empty">Aucun événement disponible.</div><?php endif; ?>
        <?php foreach ($events as $e): ?>
            <?php $remaining = max(0, (int) $e['places'] - (int) $e['reserved_count']); ?>
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
    </div>
</main>

<script>
const input = document.getElementById('searchInput');
const cat = document.getElementById('categorySelect');
const grid = document.getElementById('eventsGrid');
let timer;

function loadEvents() {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        grid.classList.add('loading');
        const params = new URLSearchParams({ q: input.value, cat: cat.value });
        const response = await fetch('search_ajax.php?' + params.toString());
        grid.innerHTML = await response.text();
        grid.classList.remove('loading');
    }, 220);
}

let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
setInterval(() => {
    if (!slides.length) return;
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
}, 3600);

input.addEventListener('input', loadEvents);
cat.addEventListener('change', loadEvents);
</script>
<?php require 'footer.php'; ?>
