<?php
require 'header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId  = (int) $_SESSION['user']['id'];
$eventId = (int) ($_GET['event_id'] ?? 0);

// Load event — must exist and be in the past
$stmt = $pdo->prepare("SELECT e.*, c.name AS category, c.icon FROM events e
                        LEFT JOIN categories c ON c.id = e.category_id
                        WHERE e.id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    echo '<main class="container"><div class="empty">Événement introuvable.</div></main>';
    require 'footer.php'; exit;
}

if (strtotime($event['event_date']) >= strtotime('today')) {
    echo '<main class="container"><div class="empty">Vous ne pouvez laisser un avis qu\'après la date de l\'événement.</div></main>';
    require 'footer.php'; exit;
}

// User must have a confirmed reservation for this event
$stmt2 = $pdo->prepare("SELECT id FROM reservations WHERE user_id = ? AND event_id = ? AND status = 'confirmee' LIMIT 1");
$stmt2->execute([$userId, $eventId]);
if (!$stmt2->fetch()) {
    echo '<main class="container"><div class="empty">Vous devez avoir une réservation confirmée pour laisser un avis.</div></main>';
    require 'footer.php'; exit;
}

// Already reviewed?
$stmt3 = $pdo->prepare("SELECT * FROM reviews WHERE user_id = ? AND event_id = ?");
$stmt3->execute([$userId, $eventId]);
$existing = $stmt3->fetch();

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$existing) {
    $rating  = max(1, min(5, (int) ($_POST['rating'] ?? 0)));
    $comment = trim($_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Veuillez choisir une note entre 1 et 5.';
    } else {
        try {
            $pdo->prepare("INSERT INTO reviews(user_id, event_id, rating, comment) VALUES(?, ?, ?, ?)")
                ->execute([$userId, $eventId, $rating, $comment]);
            $success = true;
            // Reload existing
            $stmt3->execute([$userId, $eventId]);
            $existing = $stmt3->fetch();
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'enregistrement de l\'avis.';
        }
    }
}

// Load all reviews for this event
$allReviews = $pdo->prepare("SELECT r.*, u.name AS user_name
                              FROM reviews r
                              JOIN users u ON u.id = r.user_id
                              WHERE r.event_id = ?
                              ORDER BY r.created_at DESC");
$allReviews->execute([$eventId]);
$reviews = $allReviews->fetchAll();

$avgRating = count($reviews)
    ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1)
    : null;
?>

<main class="container" style="max-width:760px;padding-top:2rem">

    <!-- Event header -->
    <section class="auth-card reveal visible" style="margin-bottom:2rem;padding:1.5rem 2rem">
        <div style="display:flex;gap:1.2rem;align-items:center;flex-wrap:wrap">
            <img src="<?= htmlspecialchars(eventImage($event['image'])) ?>"
                 alt="" style="width:90px;height:70px;object-fit:cover;border-radius:10px">
            <div>
                <span class="eyebrow"><?= htmlspecialchars(($event['icon']??'✨').' '.($event['category']??'Événement')) ?></span>
                <h2 style="margin:.2rem 0 .4rem"><?= htmlspecialchars($event['title']) ?></h2>
                <div class="event-meta" style="flex-wrap:wrap">
                    <span class="chip">📍 <?= htmlspecialchars($event['location']) ?></span>
                    <span class="chip">📅 <?= htmlspecialchars($event['event_date']) ?></span>
                    <?php if ($avgRating): ?>
                        <span class="chip" style="background:var(--accent);color:#fff">
                            ⭐ <?= $avgRating ?>/5
                            <span style="opacity:.8;font-size:.8em">(<?= count($reviews) ?> avis)</span>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Rating distribution -->
    <?php if ($reviews): ?>
    <section class="auth-card reveal visible" style="margin-bottom:2rem;padding:1.5rem 2rem">
        <h3 style="margin-bottom:1rem">📊 Résumé des avis</h3>
        <div style="display:flex;align-items:center;gap:2rem;flex-wrap:wrap">
            <div style="text-align:center">
                <div style="font-size:3rem;font-weight:800;color:var(--accent)"><?= $avgRating ?></div>
                <div class="stars-display" style="font-size:1.4rem">
                    <?php for ($s=1;$s<=5;$s++) echo $s <= round($avgRating) ? '★' : '☆'; ?>
                </div>
                <div class="muted" style="font-size:.85rem"><?= count($reviews) ?> avis</div>
            </div>
            <div style="flex:1;min-width:200px">
                <?php for ($star=5;$star>=1;$star--): ?>
                    <?php $cnt = count(array_filter($reviews, fn($r)=>$r['rating']==$star)); ?>
                    <?php $pct = count($reviews) ? round($cnt/count($reviews)*100) : 0; ?>
                    <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.35rem;font-size:.85rem">
                        <span style="width:1.5rem;text-align:right"><?= $star ?>★</span>
                        <div style="flex:1;height:8px;background:var(--surface2);border-radius:4px;overflow:hidden">
                            <div style="width:<?= $pct ?>%;height:100%;background:var(--accent);border-radius:4px;transition:width .4s"></div>
                        </div>
                        <span class="muted" style="width:2rem"><?= $cnt ?></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Write review form -->
    <?php if (!$existing): ?>
    <section class="auth-card reveal visible" style="margin-bottom:2rem;padding:1.5rem 2rem">
        <h3 style="margin-bottom:1.2rem">✍️ Laisser un avis</h3>
        <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success">Merci pour votre avis !</div><?php endif; ?>

        <form method="post" id="reviewForm">
            <div style="margin-bottom:1.2rem">
                <label style="display:block;margin-bottom:.5rem;font-weight:600">Votre note *</label>
                <div class="star-picker" id="starPicker">
                    <?php for ($i=1;$i<=5;$i++): ?>
                        <span class="star-btn" data-val="<?= $i ?>" title="<?= $i ?> étoile<?= $i>1?'s':'' ?>">☆</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="0">
            </div>
            <div style="margin-bottom:1.2rem">
                <label style="display:block;margin-bottom:.5rem;font-weight:600">Commentaire (optionnel)</label>
                <textarea name="comment" rows="4"
                    placeholder="Partagez votre expérience..."
                    style="width:100%;padding:.75rem 1rem;border-radius:10px;border:1.5px solid var(--border);
                           background:var(--surface2);color:var(--text);font-family:inherit;
                           font-size:.95rem;resize:vertical;box-sizing:border-box"></textarea>
            </div>
            <button class="btn" type="submit">Publier mon avis</button>
        </form>
    </section>
    <?php elseif (!$success && $existing): ?>
    <section class="auth-card reveal visible" style="margin-bottom:2rem;padding:1.5rem 2rem">
        <div class="alert success">Vous avez déjà laissé un avis pour cet événement. Merci !</div>
    </section>
    <?php endif; ?>

    <!-- All reviews list -->
    <?php if ($reviews): ?>
    <section>
        <h3 style="margin-bottom:1.2rem">💬 Tous les avis (<?= count($reviews) ?>)</h3>
        <?php foreach ($reviews as $r): ?>
        <div class="auth-card reveal visible"
             style="margin-bottom:1rem;padding:1.2rem 1.5rem;
                    border-left:3px solid var(--accent)">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.5rem">
                <div>
                    <strong><?= htmlspecialchars($r['user_name']) ?></strong>
                    <span style="color:var(--accent);margin-left:.5rem;font-size:1.1rem">
                        <?php for ($s=1;$s<=5;$s++) echo $s<=$r['rating']?'★':'☆'; ?>
                    </span>
                </div>
                <span class="muted" style="font-size:.82rem">
                    <?= date('d/m/Y', strtotime($r['created_at'])) ?>
                </span>
            </div>
            <?php if ($r['comment']): ?>
                <p style="margin:.6rem 0 0;line-height:1.6;color:var(--text)">
                    <?= htmlspecialchars($r['comment']) ?>
                </p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </section>
    <?php else: ?>
        <div class="empty">Aucun avis pour cet événement pour l'instant.</div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:2rem">
        <a class="btn secondary" href="index.php">← Retour aux événements</a>
    </div>
</main>

<style>
.star-picker { display:flex; gap:.3rem; margin-bottom:.3rem; }
.star-btn {
    font-size:2rem; cursor:pointer; color:var(--muted);
    transition:transform .15s, color .15s;
    user-select:none;
}
.star-btn.active, .star-btn.hover { color:#f59e0b; transform:scale(1.2); }
</style>

<script>
const stars  = document.querySelectorAll('.star-btn');
const input  = document.getElementById('ratingInput');
let selected = 0;

stars.forEach(s => {
    s.addEventListener('mouseenter', () => {
        const v = +s.dataset.val;
        stars.forEach(x => x.classList.toggle('hover', +x.dataset.val <= v));
    });
    s.addEventListener('mouseleave', () => {
        stars.forEach(x => x.classList.remove('hover'));
    });
    s.addEventListener('click', () => {
        selected = +s.dataset.val;
        input.value = selected;
        stars.forEach(x => {
            const on = +x.dataset.val <= selected;
            x.classList.toggle('active', on);
            x.textContent = on ? '★' : '☆';
        });
    });
});

document.getElementById('reviewForm')?.addEventListener('submit', e => {
    if (+input.value < 1) {
        e.preventDefault();
        alert('Veuillez sélectionner une note.');
    }
});
</script>

<?php require 'footer.php'; ?>