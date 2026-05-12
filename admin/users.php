<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Delete user
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    if ($id !== (int) $_SESSION['user']['id']) { // can't delete self
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    }
    header('Location: users.php');
    exit;
}

$users = $pdo->query("SELECT u.*, c.name AS fav_category, c.icon,
                             (SELECT COUNT(*) FROM reservations r WHERE r.user_id=u.id) AS total_res
                      FROM users u
                      LEFT JOIN categories c ON c.id=u.favorite_category_id
                      ORDER BY u.created_at DESC")->fetchAll();

require_once __DIR__ . '/../header.php';
?>
<div class="dashboard">
  <aside class="sidebar">
    <span class="sidebar-title">Administration</span>
    <a href="dashboard.php">📊 Tableau de bord</a>
    <a href="events.php">🎫 Événements</a>
    <a href="reservations.php">🎟️ Réservations</a>
    <a href="users.php" class="active">👥 Utilisateurs</a>
    <span class="sidebar-title">Site</span>
    <a href="../index.php">🌐 Voir le site</a>
    <a href="../logout.php">🚪 Déconnexion</a>
  </aside>

  <div class="dash-content">
    <h1>Utilisateurs (<?= count($users) ?>)</h1>

    <div class="section-card reveal visible">
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>#</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Catégorie préférée</th><th>Réservations</th><th>Inscrit le</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td class="muted"><?= $u['id'] ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:.5rem">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem">
                      <?= mb_strtoupper(mb_substr($u['name'],0,1)) ?>
                    </div>
                    <?= htmlspecialchars($u['name']) ?>
                  </div>
                </td>
                <td class="muted"><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge <?= $u['role']==='admin'?'ok':'pending' ?>"><?= ucfirst($u['role']) ?></span></td>
                <td><?= $u['fav_category'] ? htmlspecialchars(($u['icon']??'').' '.$u['fav_category']) : '<span class="muted">—</span>' ?></td>
                <td><?= $u['total_res'] ?></td>
                <td class="muted" style="font-size:.8rem"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <?php if ($u['id'] !== (int)$_SESSION['user']['id'] && $u['role'] !== 'admin'): ?>
                    <a href="users.php?delete=<?= $u['id'] ?>" class="btn sm danger"
                       onclick="return confirm('Supprimer cet utilisateur et toutes ses données ?')">🗑️</a>
                  <?php else: ?>
                    <span class="muted" style="font-size:.75rem">—</span>
                  <?php endif; ?>
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
