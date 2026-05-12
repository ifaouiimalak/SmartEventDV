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

$cats     = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$featured = $pdo->query("SELECT e.*, c.name AS category, c.icon FROM events e LEFT JOIN categories c ON c.id = e.category_id ORDER BY e.event_date ASC LIMIT 6")->fetchAll();

$totalEvts = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalUsrs = $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalRes2 = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='confirmee'")->fetchColumn();
?>

<section class="hero">
    <div class="hero-content reveal visible">
        <span class="hero-label">Tunisie &middot; Reservation premium</span>
        <h1>Vos evenements,<br><span class="highlight">selon votre profil</span></h1>
        <p>Concerts, sport, ports, voyages et formations — reservation rapide, confirmation claire et suivi en temps reel.</p>
        <div class="hero-actions">
            <a class="btn lg" href="#events">&#127915; Voir les evenements</a>
            <?php if (!isset($_SESSION['user'])): ?>
                <a class="btn secondary lg" href="register.php">&#10024; Creer un profil</a>
            <?php else: ?>
                <a class="btn secondary lg" href="user/account.php">&#128100; Mon espace</a>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><strong><?= $totalEvts ?>+</strong><span>Evenements</span></div>
            <div class="hero-stat"><strong><?= $totalUsrs ?>+</strong><span>Membres</span></div>
            <div class="hero-stat"><strong><?= $totalRes2 ?>+</strong><span>Reservations</span></div>
        </div>
    </div>
    <div class="hero-slider" aria-label="Slider evenements">
        <?php foreach ($featured as $i => $e): ?>
            <figure class="slide <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= htmlspecialchars(eventImage($e['image'])) ?>" alt="<?= htmlspecialchars($e['title']) ?>">
                <figcaption>
                    <span><?= htmlspecialchars(($e['icon'] ?? '') . ' ' . ($e['category'] ?? 'Evenement')) ?></span>
                    <b><?= htmlspecialchars($e['title']) ?></b>
                </figcaption>
            </figure>
        <?php endforeach; ?>
        <div class="slider-dots" id="sliderDots"></div>
    </div>
</section>

<main class="container">
    <section class="workspace-grid reveal visible">
        <a class="workspace-card" href="<?= isset($_SESSION['user']) ? 'user/account.php' : 'register.php' ?>">
            <div class="ws-icon">&#128100;</div>
            <b>Espace client</b>
            <span>Profil, favoris, historique, QR codes et suivi des reservations.</span>
            <span class="ws-arrow">&#8599;</span>
        </a>
        <a class="workspace-card" href="<?= isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin' ? 'admin/dashboard.php' : 'login.php' ?>">
            <div class="ws-icon">&#9881;</div>
            <b>Espace admin</b>
            <span>Creation d'evenements, confirmations, statistiques et gestion complete.</span>
            <span class="ws-arrow">&#8599;</span>
        </a>
    </section>

    <section class="profile-strip reveal visible">
        <div>
            <span class="eyebrow">Selection intelligente</span>
            <h2><?= $personalized ? 'Pour votre profil' : 'Catalogue evenementiel' ?></h2>
            <p><?= $personalized ? 'Les evenements affiches correspondent a votre categorie preferee.' : 'Connectez-vous pour une selection adaptee a votre profil.' ?></p>
        </div>
        <?php if (isset($_SESSION['user'])): ?>
            <a class="btn secondary" href="user/account.php">&#9999; Modifier mon profil</a>
        <?php endif; ?>
    </section>

    <form method="get" class="filter-form reveal visible" id="filterForm">
        <input id="searchInput" name="q" placeholder="&#128269; Rechercher par nom, lieu..." value="<?= htmlspecialchars($q) ?>">
        <select id="categorySelect" name="cat">
            <option value="">Toutes les categories</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['icon'] . ' ' . $c['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button class="btn">Filtrer</button>
        <?php if ($q || $cat): ?>
            <a href="index.php" class="btn secondary">Reinitialiser</a>
        <?php endif; ?>
    </form>

    <div class="section-divider" id="events">
        <h2>&#127915; Evenements disponibles</h2>
    </div>

    <div class="grid" id="eventsGrid">
        <?php if (!$events): ?>
            <div class="empty">Aucun evenement disponible.</div>
        <?php endif; ?>
        <?php foreach ($events as $e): ?>
            <?php
              $remaining = max(0, (int)$e['places'] - (int)$e['reserved_count']);
              $pct = $e['places'] > 0 ? round(($e['reserved_count'] / $e['places']) * 100) : 0;
              $barColor = $pct > 80 ? '#ef4444' : ($pct > 50 ? '#f59e0b' : '#22c55e');
            ?>
            <article class="card reveal visible">
                <div class="card-img">
                    <img src="<?= htmlspecialchars(eventImage($e['image'])) ?>" alt="<?= htmlspecialchars($e['title']) ?>" loading="lazy">
                    <span class="category-badge"><?= htmlspecialchars(($e['icon'] ?? '') . ' ' . ($e['category'] ?? 'Evenement')) ?></span>
                    <?php if ($userId): ?>
                        <a class="favorite <?= $e['favorite_id'] ? 'active' : '' ?>" href="favorite.php?id=<?= $e['id'] ?>" title="Ajouter aux favoris">&#9733;</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?= htmlspecialchars($e['title']) ?></h3>
                    <p class="muted"><?= htmlspecialchars($e['description']) ?></p>
                    <div class="event-meta">
                        <span class="chip">&#128205; <?= htmlspecialchars($e['location']) ?></span>
                        <span class="chip">&#128197; <?= htmlspecialchars($e['event_date']) ?></span>
                        <span class="chip">&#127903; <?= $remaining ?> places</span>
                    </div>
                    <div class="places-bar">
                        <div class="places-bar-fill" style="width:<?= $pct ?>%;background:<?= $barColor ?>"></div>
                    </div>
                    <div class="card-bottom">
                        <p class="price"><?= number_format((float)$e['price'], 2, ',', ' ') ?> DT</p>
                        <a class="btn <?= $remaining === 0 ? 'secondary' : '' ?>"
                           href="<?= $remaining > 0 ? 'reserve.php?id='.$e['id'] : '#' ?>">
                            <?= $remaining > 0 ? 'Reserver' : 'Complet' ?>
                        </a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</main>

<script>
// Search & filter AJAX
const input = document.getElementById('searchInput');
const cat   = document.getElementById('categorySelect');
const grid  = document.getElementById('eventsGrid');
let timer;
function loadEvents() {
    clearTimeout(timer);
    timer = setTimeout(async () => {
        grid.classList.add('loading');
        const p = new URLSearchParams({ q: input.value, cat: cat.value });
        const r = await fetch('search_ajax.php?' + p.toString());
        grid.innerHTML = await r.text();
        grid.classList.remove('loading');
    }, 220);
}
if (input) input.addEventListener('input', loadEvents);
if (cat)   cat.addEventListener('change', loadEvents);

// Slider with dots
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dotsContainer = document.getElementById('sliderDots');

if (slides.length && dotsContainer) {
    slides.forEach((_, i) => {
        const d = document.createElement('div');
        d.className = 'slider-dot' + (i === 0 ? ' active' : '');
        d.addEventListener('click', () => goSlide(i));
        dotsContainer.appendChild(d);
    });
}

function goSlide(n) {
    const dots = dotsContainer?.querySelectorAll('.slider-dot');
    if (dots) dots[currentSlide]?.classList.remove('active');
    slides[currentSlide]?.classList.remove('active');
    currentSlide = n;
    slides[currentSlide]?.classList.add('active');
    if (dots) dots[currentSlide]?.classList.add('active');
}

setInterval(() => {
    if (!slides.length) return;
    goSlide((currentSlide + 1) % slides.length);
}, 4000);
</script>
<?php require 'footer.php'; ?>
