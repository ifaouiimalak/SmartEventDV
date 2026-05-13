<?php
/**
 * admin/promo_codes.php — Manage promo codes
 * Place this file inside your /admin/ directory.
 */
require dirname(__DIR__) . '/header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php'); exit;
}

$msg = '';

// ── Create ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    try {
        $pdo->prepare("INSERT INTO promo_codes
                        (code, discount_type, discount_value, min_amount, max_uses, valid_from, valid_until)
                       VALUES(?, ?, ?, ?, ?, ?, ?)")
            ->execute([
                strtoupper(trim($_POST['code'])),
                $_POST['discount_type'],
                (float) $_POST['discount_value'],
                (float) ($_POST['min_amount'] ?? 0),
                $_POST['max_uses'] !== '' ? (int) $_POST['max_uses'] : null,
                $_POST['valid_from']  ?: null,
                $_POST['valid_until'] ?: null,
            ]);
        $msg = ['type'=>'success', 'text'=>'Code promo créé avec succès.'];
    } catch (Exception $e) {
        $msg = ['type'=>'error', 'text'=>'Code déjà existant ou erreur : ' . $e->getMessage()];
    }
}

// ── Toggle active ────────────────────────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE promo_codes SET is_active = NOT is_active WHERE id = ?")
        ->execute([(int) $_GET['toggle']]);
    header('Location: promo_codes.php'); exit;
}

// ── Delete ───────────────────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM promo_codes WHERE id = ?")->execute([(int) $_GET['delete']]);
    header('Location: promo_codes.php'); exit;
}

// ── Load codes ───────────────────────────────────────────────────────────────
$codes = $pdo->query("SELECT * FROM promo_codes ORDER BY created_at DESC")->fetchAll();
?>

<main class="container" style="max-width:1000px;padding-top:2rem">

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem">
        <div>
            <span class="eyebrow">Administration</span>
            <h2>🎟️ Codes Promo</h2>
        </div>
        <a class="btn secondary" href="dashboard.php">← Dashboard</a>
    </div>

    <?php if ($msg): ?>
        <div class="alert <?= $msg['type'] ?>" style="margin-bottom:1rem"><?= htmlspecialchars($msg['text']) ?></div>
    <?php endif; ?>

    <!-- Create form -->
    <section class="auth-card reveal visible" style="margin-bottom:2rem;padding:1.5rem 2rem">
        <h3 style="margin-bottom:1.2rem">➕ Nouveau code promo</h3>
        <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:.8rem">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="form-label">Code *</label>
                <input name="code" placeholder="EX: SUMMER25" required style="text-transform:uppercase">
            </div>
            <div>
                <label class="form-label">Type de réduction *</label>
                <select name="discount_type" required>
                    <option value="percent">Pourcentage (%)</option>
                    <option value="fixed">Montant fixe (DT)</option>
                </select>
            </div>
            <div>
                <label class="form-label">Valeur *</label>
                <input type="number" name="discount_value" min="0.01" step="0.01" placeholder="10" required>
            </div>
            <div>
                <label class="form-label">Montant min. (DT)</label>
                <input type="number" name="min_amount" min="0" step="0.01" value="0">
            </div>
            <div>
                <label class="form-label">Nb max utilisations</label>
                <input type="number" name="max_uses" min="1" placeholder="Illimité">
            </div>
            <div>
                <label class="form-label">Valide du</label>
                <input type="date" name="valid_from">
            </div>
            <div>
                <label class="form-label">Valide jusqu'au</label>
                <input type="date" name="valid_until">
            </div>
            <div style="display:flex;align-items:flex-end">
                <button class="btn" style="width:100%">Créer</button>
            </div>
        </form>
    </section>

    <!-- Codes table -->
    <section class="auth-card reveal visible" style="padding:1.5rem 2rem;overflow-x:auto">
        <h3 style="margin-bottom:1.2rem">Liste des codes (<?= count($codes) ?>)</h3>
        <?php if (!$codes): ?>
            <div class="empty">Aucun code promo pour l'instant.</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse;font-size:.88rem">
            <thead>
                <tr style="border-bottom:2px solid var(--border);text-align:left">
                    <th style="padding:.6rem .8rem">Code</th>
                    <th style="padding:.6rem .8rem">Réduction</th>
                    <th style="padding:.6rem .8rem">Min.</th>
                    <th style="padding:.6rem .8rem">Utilisations</th>
                    <th style="padding:.6rem .8rem">Validité</th>
                    <th style="padding:.6rem .8rem">Statut</th>
                    <th style="padding:.6rem .8rem">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($codes as $c):
                $today   = date('Y-m-d');
                $expired = ($c['valid_until'] && $today > $c['valid_until']);
                $pending = ($c['valid_from']  && $today < $c['valid_from']);
                $maxed   = ($c['max_uses'] !== null && $c['used_count'] >= $c['max_uses']);
            ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:.6rem .8rem;font-weight:700;letter-spacing:.05em;color:var(--accent)">
                    <?= htmlspecialchars($c['code']) ?>
                </td>
                <td style="padding:.6rem .8rem">
                    <?php if ($c['discount_type']==='percent'): ?>
                        <span style="color:#22c55e;font-weight:600">-<?= (int)$c['discount_value'] ?>%</span>
                    <?php else: ?>
                        <span style="color:#22c55e;font-weight:600">-<?= number_format((float)$c['discount_value'],2,',',' ') ?> DT</span>
                    <?php endif; ?>
                </td>
                <td style="padding:.6rem .8rem">
                    <?= $c['min_amount'] > 0 ? number_format((float)$c['min_amount'],2,',',' ').' DT' : '—' ?>
                </td>
                <td style="padding:.6rem .8rem">
                    <?= $c['used_count'] ?>
                    <?= $c['max_uses'] !== null ? ' / '.$c['max_uses'] : ' / ∞' ?>
                </td>
                <td style="padding:.6rem .8rem;font-size:.82rem;color:var(--muted)">
                    <?= $c['valid_from']  ? date('d/m/y', strtotime($c['valid_from']))  : '∞' ?>
                    →
                    <?= $c['valid_until'] ? date('d/m/y', strtotime($c['valid_until'])) : '∞' ?>
                </td>
                <td style="padding:.6rem .8rem">
                    <?php if (!$c['is_active']): ?>
                        <span class="chip" style="background:#ef4444;color:#fff">Désactivé</span>
                    <?php elseif ($expired): ?>
                        <span class="chip" style="background:#6b7280;color:#fff">Expiré</span>
                    <?php elseif ($pending): ?>
                        <span class="chip" style="background:#f59e0b;color:#fff">En attente</span>
                    <?php elseif ($maxed): ?>
                        <span class="chip" style="background:#6b7280;color:#fff">Épuisé</span>
                    <?php else: ?>
                        <span class="chip" style="background:#22c55e;color:#fff">Actif</span>
                    <?php endif; ?>
                </td>
                <td style="padding:.6rem .8rem;white-space:nowrap">
                    <a href="?toggle=<?= $c['id'] ?>" class="btn secondary"
                       style="padding:.3rem .7rem;font-size:.8rem"
                       onclick="return confirm('Changer le statut ?')">
                        <?= $c['is_active'] ? 'Désactiver' : 'Activer' ?>
                    </a>
                    <a href="?delete=<?= $c['id'] ?>" class="btn"
                       style="padding:.3rem .7rem;font-size:.8rem;background:#ef4444;margin-left:.3rem"
                       onclick="return confirm('Supprimer ce code ?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
</main>

<style>
.form-label { display:block; font-size:.85rem; font-weight:600; margin-bottom:.3rem; color:var(--muted); }
</style>

<?php require dirname(__DIR__) . '/footer.php'; ?>