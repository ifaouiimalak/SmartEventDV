<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user']['id'];

// Stats
$totalRes  = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ?");
$totalRes->execute([$userId]);
$totalRes = (int) $totalRes->fetchColumn();

$confirmed = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id = ? AND status = 'confirmee'");
$confirmed->execute([$userId]);
$confirmed = (int) $confirmed->fetchColumn();

$totalFavs = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$totalFavs->execute([$userId]);
$totalFavs = (int) $totalFavs->fetchColumn();

$spent = $pdo->prepare("SELECT COALESCE(SUM(r.quantity * e.price),0) FROM reservations r JOIN events e ON e.id = r.event_id WHERE r.user_id = ? AND r.status = 'confirmee'");
$spent->execute([$userId]);
$spent = (float) $spent->fetchColumn();

// Recent reservations
$recent = $pdo->prepare("SELECT r.*, e.title, e.event_date, e.location, e.price, e.image, c.name AS category, c.icon
                          FROM reservations r
                          JOIN events e ON e.id = r.event_id
                          LEFT JOIN categories c ON c.id = e.category_id
                          WHERE r.user_id = ?
                          ORDER BY r.created_at DESC LIMIT 5");
$recent->execute([$userId]);
$recent = $recent->fetchAll();

require_once __DIR__ . '/../header.php';
?>
<div class="dashboard">
  <aside class="sidebar">
    <span class="sidebar-title">Client</span>
    <a href="dashboard.php" class="active">🏠 Tableau de bord</a>
    <a href="account.php">👤 Mon compte</a>
    <a href="account.php#reservations">🎟️ Mes réservations</a>
    <a href="account.php#favorites">⭐ Mes favoris</a>
    <a href="../index.php">🔍 Événements</a>
    <span class="sidebar-title">Compte</span>
    <a href="../logout.php">🚪 Déconnexion</a>
  </aside>

  <div class="dash-content">
    <h1>Bonjour, <?= htmlspecialchars($_SESSION['user']['name']) ?> 👋</h1>

    <div class="stats-row">
      <div class="stat-card reveal visible">
        <span class="stat-label">Réservations</span>
        <span class="stat-value"><?= $totalRes ?></span>
        <span class="stat-sub">Total</span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Confirmées</span>
        <span class="stat-value" style="color:var(--success)"><?= $confirmed ?></span>
        <span class="stat-sub">Validées par l'admin</span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Favoris</span>
        <span class="stat-value" style="color:var(--gold)"><?= $totalFavs ?></span>
        <span class="stat-sub">Événements sauvegardés</span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Dépensé</span>
        <span class="stat-value" style="color:var(--accent2)"><?= number_format($spent,0,',',' ') ?></span>
        <span class="stat-sub">DT sur réservations confirmées</span>
      </div>
    </div>

    <!-- Recent reservations -->
    <div class="section-card reveal visible">
      <div class="section-card-header">
        <h2>Réservations récentes</h2>
        <a href="account.php#reservations" class="btn sm secondary">Voir tout</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Événement</th>
              <th>Date</th>
              <th>Qté</th>
              <th>Total</th>
              <th>Statut</th>
              <th>QR</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$recent): ?>
              <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:2rem">Aucune réservation pour l'instant.</td></tr>
            <?php endif; ?>
            <?php foreach ($recent as $r): ?>
              <?php
                $statusMap = ['en attente'=>['pending','En attente'], 'confirmee'=>['ok','Confirmée'], 'annulee'=>['canceled','Annulée']];
                [$cls,$label] = $statusMap[$r['status']] ?? ['pending', $r['status']];
                $code = 'SMARTEVENT-'.$r['id'].'-'.$r['user_id'];
              ?>
              <tr>
                <td><strong><?= htmlspecialchars($r['title']) ?></strong><br><span class="muted"><?= htmlspecialchars($r['location']) ?></span></td>
                <td><?= htmlspecialchars($r['event_date']) ?></td>
                <td><?= $r['quantity'] ?></td>
                <td class="price" style="font-size:.9rem"><?= number_format($r['price']*$r['quantity'],2,',',' ') ?> DT</td>
                <td><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                <td><img class="qr-inline" src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($code) ?>" alt="QR"></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Quick actions -->
    <div class="section-card reveal visible">
      <div class="section-card-header"><h2>Actions rapides</h2></div>
      <div style="padding:1.25rem;display:flex;gap:.75rem;flex-wrap:wrap">
        <a class="btn secondary" href="../index.php">🔍 Découvrir des événements</a>
        <a class="btn secondary" href="account.php">✏️ Modifier mon profil</a>
        <a class="btn secondary" href="account.php#favorites">⭐ Voir mes favoris</a>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../footer.php'; ?>
