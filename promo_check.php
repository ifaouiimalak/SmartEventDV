<?php
/**
 * promo_check.php — AJAX endpoint
 * POST: { code, event_id, quantity }
 * Returns JSON: { valid, discount_type, discount_value, discount_amount, final_price, message }
 */
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json');

$userId  = $_SESSION['user']['id'] ?? null;
$code    = strtoupper(trim($_POST['code'] ?? ''));
$eventId = (int) ($_POST['event_id'] ?? 0);
$qty     = max(1, (int) ($_POST['quantity'] ?? 1));

$fail = fn(string $msg) => json_encode(['valid' => false, 'message' => $msg]);

if (!$userId) { echo $fail('Vous devez être connecté.'); exit; }
if (!$code)   { echo $fail('Code vide.'); exit; }

// Load event price
$stmt = $pdo->prepare("SELECT price FROM events WHERE id = ?");
$stmt->execute([$eventId]);
$event = $stmt->fetch();
if (!$event) { echo $fail('Événement introuvable.'); exit; }

$subtotal = round((float)$event['price'] * $qty, 2);

// Load promo
$stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE code = ? AND is_active = 1");
$stmt->execute([$code]);
$promo = $stmt->fetch();

if (!$promo) { echo $fail('Code promo invalide ou désactivé.'); exit; }

$today = date('Y-m-d');
if ($promo['valid_from']  && $today < $promo['valid_from'])  { echo $fail('Ce code n\'est pas encore actif.'); exit; }
if ($promo['valid_until'] && $today > $promo['valid_until'])  { echo $fail('Ce code promo est expiré.'); exit; }
if ($promo['max_uses'] !== null && $promo['used_count'] >= $promo['max_uses']) {
    echo $fail('Ce code a atteint son nombre maximum d\'utilisations.'); exit;
}
if ($subtotal < (float)$promo['min_amount']) {
    echo $fail('Montant minimum requis : ' . number_format($promo['min_amount'], 2, ',', ' ') . ' DT.'); exit;
}

// Check if this user already used the code
$stmt = $pdo->prepare("SELECT id FROM promo_uses WHERE promo_id = ? AND user_id = ?");
$stmt->execute([$promo['id'], $userId]);
if ($stmt->fetch()) { echo $fail('Vous avez déjà utilisé ce code promo.'); exit; }

// Compute discount
if ($promo['discount_type'] === 'percent') {
    $discount = round($subtotal * ($promo['discount_value'] / 100), 2);
} else {
    $discount = min(round((float)$promo['discount_value'], 2), $subtotal);
}
$final = max(0, round($subtotal - $discount, 2));

echo json_encode([
    'valid'          => true,
    'promo_id'       => (int) $promo['id'],
    'code'           => $promo['code'],
    'discount_type'  => $promo['discount_type'],
    'discount_value' => (float) $promo['discount_value'],
    'discount_amount'=> $discount,
    'subtotal'       => $subtotal,
    'final_price'    => $final,
    'message'        => $promo['discount_type'] === 'percent'
        ? '-' . (int)$promo['discount_value'] . '% appliqué !'
        : '-' . number_format($discount, 2, ',', ' ') . ' DT appliqué !',
]);