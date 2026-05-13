<?php
require 'header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT e.*, c.name AS category, c.icon,
                        COALESCE(SUM(CASE WHEN r.status <> 'annulee' THEN r.quantity ELSE 0 END), 0) AS reserved
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
$error     = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity   = max(1, (int) ($_POST['quantity'] ?? 1));
    $promoCode  = strtoupper(trim($_POST['promo_code'] ?? ''));
    $promoId    = null;
    $discount   = 0.00;
    $finalPrice = null;

    if ($quantity > $remaining) {
        $error = 'Nombre de places insuffisant.';
    } else {
        $subtotal = round((float)$event['price'] * $quantity, 2);

        // Re-validate promo server-side
        if ($promoCode !== '') {
            $pStmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
            $pStmt->execute([$promoCode]);
            $promo = $pStmt->fetch();

            $today = date('Y-m-d');
            $userId = (int) $_SESSION['user']['id'];

            $promoValid = $promo
                && (!$promo['valid_from']  || $today >= $promo['valid_from'])
                && (!$promo['valid_until'] || $today <= $promo['valid_until'])
                && ($promo['max_uses'] === null || $promo['used_count'] < $promo['max_uses'])
                && $subtotal >= (float)$promo['min_amount'];

            if ($promoValid) {
                // Check if user already used this promo
                $puStmt = $pdo->prepare("SELECT id FROM promo_uses WHERE promo_id = ? AND user_id = ?");
                $puStmt->execute([$promo['id'], $userId]);
                if ($puStmt->fetch()) {
                    $promoValid = false;
                    $error = 'Vous avez déjà utilisé ce code promo.';
                }
            }

            if ($promoValid) {
                $promoId = (int) $promo['id'];
                if ($promo['discount_type'] === 'percent') {
                    $discount = round($subtotal * ($promo['discount_value'] / 100), 2);
                } else {
                    $discount = min((float)$promo['discount_value'], $subtotal);
                }
                $finalPrice = max(0, round($subtotal - $discount, 2));
            } elseif (!$error) {
                $error = 'Code promo invalide ou conditions non remplies.';
            }
        }

        if (!$error) {
            $finalPrice = $finalPrice ?? $subtotal;

            // === MAIN INSERT ===
            $stmt = $pdo->prepare("INSERT INTO reservations 
                (user_id, event_id, quantity, promo_code_id, discount_amount, final_price, status)
                VALUES (?, ?, ?, ?, ?, ?, 'en_attente')");

            $stmt->execute([
                $_SESSION['user']['id'],
                $id,
                $quantity,
                $promoId,
                $discount,
                $finalPrice
            ]);

            $reservationId = (int) $pdo->lastInsertId();

            // Record promo usage
            if ($promoId) {
                $pdo->prepare("INSERT INTO promo_uses (promo_id, user_id, reservation_id) 
                               VALUES (?, ?, ?)")
                    ->execute([$promoId, $_SESSION['user']['id'], $reservationId]);

                $pdo->prepare("UPDATE promo_codes SET used_count = used_count + 1 WHERE id = ?")
                    ->execute([$promoId]);
            }

            header('Location: reservation_success.php?id=' . $reservationId);
            exit;
        }
    }
}
?>

<main class="container auth-page">
    <form class="form auth-card reveal visible" method="post" id="reserveForm">
        <span class="eyebrow"><?= htmlspecialchars(($event['icon'] ?? '✨') . ' ' . ($event['category'] ?? 'Événement')) ?></span>
        <h2><?= htmlspecialchars($event['title']) ?></h2>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <img class="detail-img" src="<?= htmlspecialchars(eventImage($event['image'])) ?>" alt="">
        <p class="muted"><?= htmlspecialchars($event['description']) ?></p>

        <div class="event-meta">
            <span class="chip">📍 <?= htmlspecialchars($event['location']) ?></span>
            <span class="chip">📅 <?= htmlspecialchars($event['event_date']) ?></span>
            <span class="chip">🎟️ <?= $remaining ?> places restantes</span>
        </div>

        <!-- Quantity -->
        <label style="font-weight:600;margin-bottom:.3rem;display:block">Nombre de places</label>
        <input type="number" name="quantity" id="quantityInput"
               value="1" min="1" max="<?= $remaining ?>" required>

        <!-- Price breakdown -->
        <div class="price-breakdown" id="priceBreakdown"
             style="background:var(--surface2);border-radius:12px;padding:1rem 1.2rem;margin:.5rem 0">
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                <span class="muted">Prix unitaire</span>
                <span id="unitPrice"><?= number_format((float)$event['price'],2,',',' ') ?> DT</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-bottom:.3rem">
                <span class="muted">Sous-total</span>
                <span id="subtotalDisplay"><?= number_format((float)$event['price'],2,',',' ') ?> DT</span>
            </div>
            <div id="discountRow" style="display:none;justify-content:space-between;margin-bottom:.3rem;color:#22c55e">
                <span>🎉 Réduction</span>
                <span id="discountDisplay">-0,00 DT</span>
            </div>
            <hr style="border:none;border-top:1px solid var(--border);margin:.5rem 0">
            <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1.1rem">
                <span>Total</span>
                <span id="totalDisplay" style="color:var(--accent)"><?= number_format((float)$event['price'],2,',',' ') ?> DT</span>
            </div>
        </div>

        <!-- Promo code -->
        <label style="font-weight:600;margin-bottom:.3rem;display:block">Code promo (optionnel)</label>
        <div style="display:flex;gap:.5rem">
            <input type="text" id="promoInput" name="promo_code"
                   placeholder="Ex: WELCOME10"
                   style="flex:1;text-transform:uppercase;letter-spacing:.05em"
                   autocomplete="off">
            <button type="button" class="btn secondary" id="promoBtn"
                    style="white-space:nowrap;padding:.6rem 1rem">Appliquer</button>
        </div>
        <div id="promoMsg" style="margin-top:.4rem;font-size:.88rem;min-height:1.2em"></div>

        <button class="btn success" <?= $remaining === 0 ? 'disabled' : '' ?>
                style="margin-top:.8rem">Confirmer la réservation</button>
    </form>
</main>

<script>
const unitPrice   = <?= (float)$event['price'] ?>;
const eventId     = <?= $id ?>;
const maxPlaces   = <?= $remaining ?>;

const qtyInput    = document.getElementById('quantityInput');
const promoInput  = document.getElementById('promoInput');
const promoBtn    = document.getElementById('promoBtn');
const promoMsg    = document.getElementById('promoMsg');
const subtotalEl  = document.getElementById('subtotalDisplay');
const discountRow = document.getElementById('discountRow');
const discountEl  = document.getElementById('discountDisplay');
const totalEl     = document.getElementById('totalDisplay');

let appliedDiscount = 0;

function fmt(n) {
    return n.toLocaleString('fr-TN', { minimumFractionDigits:2, maximumFractionDigits:2 }) + ' DT';
}

function updatePrices() {
    const qty      = Math.max(1, parseInt(qtyInput.value) || 1);
    const subtotal = unitPrice * qty;
    const total    = Math.max(0, subtotal - appliedDiscount);
    
    subtotalEl.textContent = fmt(subtotal);
    totalEl.textContent    = fmt(total);
    
    if (appliedDiscount > 0) {
        discountRow.style.display = 'flex';
        discountEl.textContent = '-' + fmt(appliedDiscount);
    } else {
        discountRow.style.display = 'none';
    }
}

qtyInput.addEventListener('input', () => {
    if (appliedDiscount > 0) {
        appliedDiscount = 0;
        promoMsg.textContent = 'Quantité modifiée — veuillez réappliquer le code promo.';
        promoMsg.style.color = 'var(--muted)';
    }
    updatePrices();
});

promoBtn.addEventListener('click', async () => {
    const code = promoInput.value.trim().toUpperCase();
    if (!code) {
        promoMsg.textContent = 'Entrez un code promo.';
        promoMsg.style.color = 'var(--muted)';
        return;
    }

    promoBtn.disabled = true;
    promoBtn.textContent = '...';
    promoMsg.textContent = '';

    const qty = Math.max(1, parseInt(qtyInput.value) || 1);
    const fd  = new FormData();
    fd.append('code', code);
    fd.append('event_id', eventId);
    fd.append('quantity', qty);

    try {
        const resp = await fetch('promo_check.php', { method: 'POST', body: fd });
        const data = await resp.json();

        if (data.valid) {
            appliedDiscount = data.discount_amount;
            promoMsg.textContent = '✓ ' + data.message;
            promoMsg.style.color = '#22c55e';
            promoInput.readOnly = true;
            promoBtn.textContent = '✓';
        } else {
            appliedDiscount = 0;
            promoMsg.textContent = '✗ ' + data.message;
            promoMsg.style.color = '#ef4444';
            promoBtn.disabled = false;
            promoBtn.textContent = 'Appliquer';
        }
        updatePrices();
    } catch (e) {
        promoMsg.textContent = 'Erreur réseau.';
        promoMsg.style.color = '#ef4444';
        promoBtn.disabled = false;
        promoBtn.textContent = 'Appliquer';
    }
});

// Reset promo when user types again
promoInput.addEventListener('input', () => {
    if (promoInput.readOnly) {
        promoInput.readOnly = false;
        appliedDiscount = 0;
        promoBtn.disabled = false;
        promoBtn.textContent = 'Appliquer';
        promoMsg.textContent = '';
        updatePrices();
    }
});

updatePrices();
</script>

<?php require 'footer.php'; ?>