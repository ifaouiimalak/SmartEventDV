<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Actions: confirm / cancel / delete
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    switch ($_GET['action']) {
        case 'confirm':
            $pdo->prepare("UPDATE reservations SET status='confirmee' WHERE id=?")->execute([$id]);
            break;
        case 'cancel':
            $pdo->prepare("UPDATE reservations SET status='annulee'  WHERE id=?")->execute([$id]);
            break;
        case 'delete':
            $pdo->prepare("DELETE FROM reservations WHERE id=?")->execute([$id]);
            break;
    }
    header('Location: reservations.php');
    exit;
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$searchFilter = trim($_GET['q'] ?? '');

$params = [];
$where  = [];

if ($statusFilter) {
    $where[]  = "r.status = ?";
    $params[] = $statusFilter;
}
if ($searchFilter) {
    $where[]  = "(u.name LIKE ? OR e.title LIKE ?)";
    $params[] = '%'.$searchFilter.'%';
    $params[] = '%'.$searchFilter.'%';
}

$sql = "SELECT r.*, u.name AS user_name, u.email AS user_email,
               e.title, e.event_date, e.location, e.price, c.name AS category, c.icon
        FROM reservations r
        JOIN users u  ON u.id  = r.user_id
        JOIN events e ON e.id  = r.event_id
        LEFT JOIN categories c ON c.id = e.category_id";

if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

// Summary counts
$counts = $pdo->query("SELECT status, COUNT(*) AS n FROM reservations GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once __DIR__ . '/../header.php';
?>
<div class="dashboard">
  <aside class="sidebar">
    <span class="sidebar-title">Administration</span>
    <a href="dashboard.php">📊 Tableau de bord</a>
    <a href="events.php">🎫 Événements</a>
    <a href="reservations.php" class="active">🎟️ Réservations</a>
    <a href="users.php">👥 Utilisateurs</a>
    <span class="sidebar-title">Site</span>
    <a href="../index.php">🌐 Voir le site</a>
    <a href="../logout.php">🚪 Déconnexion</a>
  </aside>

  <div class="dash-content">
    <h1>Gestion des réservations</h1>

    <!-- Quick stats -->
    <div class="stats-row" style="margin-bottom:1.5rem">
      <div class="stat-card reveal visible">
        <span class="stat-label">En attente</span>
        <span class="stat-value" style="color:var(--gold)"><?= $counts['en attente'] ?? 0 ?></span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Confirmées</span>
        <span class="stat-value" style="color:var(--success)"><?= $counts['confirmee'] ?? 0 ?></span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Annulées</span>
        <span class="stat-value" style="color:var(--danger)"><?= $counts['annulee'] ?? 0 ?></span>
      </div>
      <div class="stat-card reveal visible">
        <span class="stat-label">Total</span>
        <span class="stat-value"><?= array_sum($counts) ?></span>
      </div>
    </div>

    <!-- Filters -->
    <form method="get" class="filter-form reveal visible" style="margin-bottom:1.5rem">
      <input name="q" placeholder="Chercher client ou événement…" value="<?= htmlspecialchars($searchFilter) ?>">
      <select name="status">
        <option value="">Tous les statuts</option>
        <option value="en attente" <?= $statusFilter==='en attente'?'selected':'' ?>>En attente</option>
        <option value="confirmee"  <?= $statusFilter==='confirmee' ?'selected':'' ?>>Confirmées</option>
        <option value="annulee"    <?= $statusFilter==='annulee'   ?'selected':'' ?>>Annulées</option>
      </select>
      <button class="btn">Filtrer</button>
      <?php if ($statusFilter || $searchFilter): ?>
        <a href="reservations.php" class="btn secondary">Réinitialiser</a>
      <?php endif; ?>
    </form>

    <!-- Table -->
    <div class="section-card reveal visible">
      <div class="section-card-header">
        <h2>Réservations (<?= count($reservations) ?>)</h2>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Client</th>
              <th>Événement</th>
              <th>Catégorie</th>
              <th>Date événement</th>
              <th>Qté</th>
              <th>Total</th>
              <th>Statut</th>
              <th>QR</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$reservations): ?>
              <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:2rem">Aucune réservation trouvée.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $r): ?>
              <?php
                $statusMap = ['en attente'=>['pending','En attente'],'confirmee'=>['ok','Confirmée'],'annulee'=>['canceled','Annulée']];
                [$cls,$label] = $statusMap[$r['status']] ?? ['pending',$r['status']];
                $code = 'SMARTEVENT-'.$r['id'].'-'.$r['user_id'];
              ?>
              <tr>
                <td class="muted">#<?= $r['id'] ?></td>
                <td>
                  <strong><?= htmlspecialchars($r['user_name']) ?></strong><br>
                  <span class="muted" style="font-size:.75rem"><?= htmlspecialchars($r['user_email']) ?></span>
                </td>
                <td>
                  <strong><?= htmlspecialchars($r['title']) ?></strong><br>
                  <span class="muted">📍 <?= htmlspecialchars($r['location']) ?></span>
                </td>
                <td><?= htmlspecialchars(($r['icon']??'✨').' '.($r['category']??'')) ?></td>
                <td><?= htmlspecialchars($r['event_date']) ?></td>
                <td><?= $r['quantity'] ?></td>
                <td><span class="price" style="font-size:.85rem"><?= number_format($r['price']*$r['quantity'],2,',',' ') ?> DT</span></td>
                <td><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                <td><img class="qr-inline" src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($code) ?>" alt="QR"></td>
                <td>
                  <?php if ($r['status'] !== 'confirmee'): ?>
                    <a href="reservations.php?action=confirm&id=<?= $r['id'] ?>" class="btn sm success" title="Confirmer">✓</a>
                  <?php endif; ?>
                  <?php if ($r['status'] !== 'annulee'): ?>
                    <a href="reservations.php?action=cancel&id=<?= $r['id'] ?>"  class="btn sm danger"  title="Annuler" style="margin-left:.25rem">✕</a>
                  <?php endif; ?>
                  <a href="reservations.php?action=delete&id=<?= $r['id'] ?>" class="btn sm secondary" style="margin-left:.25rem"
                     title="Supprimer" onclick="return confirm('Supprimer cette réservation ?')">🗑️</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
<?php require_once __DIR__ . '/../footer.php'; ?>
