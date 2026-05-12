<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Global stats
$totalEvents   = (int) $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalUsers    = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$totalRes      = (int) $pdo->query("SELECT COUNT(*) FROM reservations")->fetchColumn();
$pendingRes    = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status='en attente'")->fetchColumn();
$revenue       = (float) $pdo->query("SELECT COALESCE(SUM(r.quantity*e.price),0) FROM reservations r JOIN events e ON e.id=r.event_id WHERE r.status='confirmee'")->fetchColumn();

// Reservations by category (for chart)
$byCat = $pdo->query("SELECT c.name, c.icon, COUNT(r.id) AS total
                      FROM reservations r
                      JOIN events e ON e.id = r.event_id
                      LEFT JOIN categories c ON c.id = e.category_id
                      GROUP BY e.category_id ORDER BY total DESC LIMIT 8")->fetchAll();

// Recent reservations
$recentRes = $pdo->query("SELECT r.*, u.name AS user_name, e.title, e.event_date, e.price
                           FROM reservations r
                           JOIN users u ON u.id = r.user_id
                           JOIN events e ON e.id = r.event_id
                           ORDER BY r.created_at DESC LIMIT 10")->fetchAll();

// Upcoming events
$upcomingEvents = $pdo->query("SELECT e.*, c.name AS category, c.icon,
                                COALESCE(SUM(CASE WHEN r.status<>'annulee' THEN r.quantity ELSE 0 END),0) AS reserved
                                FROM events e
                                LEFT JOIN categories c ON c.id = e.category_id
                                LEFT JOIN reservations r ON r.event_id = e.id
                                WHERE e.event_date >= CURDATE()
                                GROUP BY e.id, c.name, c.icon ORDER BY e.event_date ASC LIMIT 5")->fetchAll();

require_once __DIR__ . '/../header.php';
?>
<div class="dashboard">
  <aside class="sidebar">
    <span class="sidebar-title">Administration</span>
    <a href="dashboard.php" class="active">📊 Tableau de bord</a>
    <a href="events.php">🎫 Événements</a>
    <a href="reservations.php">🎟️ Réservations</a>
    <a href="users.php">👥 Utilisateurs</a>
    <span class="sidebar-title">Site</span>
    <a href="../index.php">🌐 Voir le site</a>
    <a href="../logout.php">🚪 Déconnexion</a>
  </aside>

  <div class="dash-content">
    <h1>Tableau de bord admin</h1>

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card reveal visible">
        <span class="stat-label">Événements</span>
        <span class="stat-value"><?= $totalEvents ?></span>
        <span class="stat-sub">Publiés</span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Utilisateurs</span>
        <span class="stat-value"><?= $totalUsers ?></span>
        <span class="stat-sub">Inscrits</span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Réservations</span>
        <span class="stat-value"><?= $totalRes ?></span>
        <span class="stat-sub"><?= $pendingRes ?> en attente</span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Revenus</span>
        <span class="stat-value" style="color:var(--gold)"><?= number_format($revenue,0,',',' ') ?></span>
        <span class="stat-sub">DT confirmés</span>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:2rem">
      <!-- Chart: reservations by category -->
      <div class="section-card reveal visible">
        <div class="section-card-header"><h2>Réservations par catégorie</h2></div>
        <div class="chart-wrap"><canvas id="catChart"></canvas></div>
      </div>

      <!-- Upcoming events -->
      <div class="section-card reveal visible">
        <div class="section-card-header">
          <h2>Prochains événements</h2>
          <a href="events.php" class="btn sm secondary">Gérer</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Titre</th><th>Date</th><th>Places</th></tr></thead>
            <tbody>
              <?php foreach ($upcomingEvents as $e): ?>
                <?php $remaining = max(0, (int)$e['places'] - (int)$e['reserved']); ?>
                <tr>
                  <td><strong><?= htmlspecialchars($e['title']) ?></strong></td>
                  <td><?= htmlspecialchars($e['event_date']) ?></td>
                  <td>
                    <span class="badge <?= $remaining > 10 ? 'ok' : ($remaining > 0 ? 'pending' : 'canceled') ?>">
                      <?= $remaining ?> / <?= $e['places'] ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Recent reservations -->
    <div class="section-card reveal visible">
      <div class="section-card-header">
        <h2>Dernières réservations</h2>
        <a href="reservations.php" class="btn sm secondary">Voir tout</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Client</th><th>Événement</th><th>Date</th><th>Qté</th><th>Total</th><th>Statut</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recentRes as $r): ?>
              <?php
                $statusMap = ['en attente'=>['pending','En attente'],'confirmee'=>['ok','Confirmée'],'annulee'=>['canceled','Annulée']];
                [$cls,$label] = $statusMap[$r['status']] ?? ['pending',$r['status']];
              ?>
              <tr>
                <td><?= htmlspecialchars($r['user_name']) ?></td>
                <td><?= htmlspecialchars($r['title']) ?></td>
                <td><?= htmlspecialchars($r['event_date']) ?></td>
                <td><?= $r['quantity'] ?></td>
                <td><?= number_format($r['price']*$r['quantity'],2,',',' ') ?> DT</td>
                <td><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                <td>
                  <a href="reservations.php?action=confirm&id=<?= $r['id'] ?>" class="btn sm success" title="Confirmer">✓</a>
                  <a href="reservations.php?action=cancel&id=<?= $r['id'] ?>"  class="btn sm danger"  title="Annuler" style="margin-left:.25rem">✕</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script>
const ctx = document.getElementById('catChart');
if (ctx) {
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: <?= json_encode(array_map(fn($c) => $c['icon'].' '.$c['name'], $byCat)) ?>,
      datasets: [{
        data: <?= json_encode(array_column($byCat, 'total')) ?>,
        backgroundColor: ['#6366f1','#8b5cf6','#f59e0b','#22c55e','#ef4444','#06b6d4','#ec4899','#f97316'],
        borderWidth: 2,
        borderColor: '#161d2e'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: {
        legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 11 }, padding: 12 } }
      }
    }
  });
}
</script>
<?php require_once __DIR__ . '/../footer.php'; ?>
