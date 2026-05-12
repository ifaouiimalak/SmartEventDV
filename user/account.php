<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'user') {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$success = '';
$error   = '';
$cats    = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $name     = trim($_POST['name']);
        $catId    = (int) $_POST['favorite_category_id'];
        $password = trim($_POST['password'] ?? '');

        if ($password) {
            $pdo->prepare("UPDATE users SET name=?, favorite_category_id=?, password=? WHERE id=?")
                ->execute([$name, $catId, password_hash($password, PASSWORD_DEFAULT), $userId]);
        } else {
            $pdo->prepare("UPDATE users SET name=?, favorite_category_id=? WHERE id=?")
                ->execute([$name, $catId, $userId]);
        }

        // Refresh session
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $stmt->execute([$userId]);
        $_SESSION['user'] = $stmt->fetch();
        $success = 'Profil mis à jour avec succès.';
    } catch (Exception $e) {
        $error = 'Erreur lors de la mise à jour.';
    }
}

// Reservations
$reservations = $pdo->prepare("SELECT r.*, e.title, e.event_date, e.location, e.price, e.image, c.name AS category, c.icon
                                FROM reservations r
                                JOIN events e ON e.id = r.event_id
                                LEFT JOIN categories c ON c.id = e.category_id
                                WHERE r.user_id = ?
                                ORDER BY r.created_at DESC");
$reservations->execute([$userId]);
$reservations = $reservations->fetchAll();

// Favorites
$favorites = $pdo->prepare("SELECT e.*, c.name AS category, c.icon
                             FROM favorites f
                             JOIN events e ON e.id = f.event_id
                             LEFT JOIN categories c ON c.id = e.category_id
                             WHERE f.user_id = ?
                             ORDER BY f.created_at DESC");
$favorites->execute([$userId]);
$favorites = $favorites->fetchAll();

$user = $_SESSION['user'];
require_once __DIR__ . '/../header.php';
?>
<div class="dashboard">
  <aside class="sidebar">
    <span class="sidebar-title">Client</span>
    <a href="dashboard.php">🏠 Tableau de bord</a>
    <a href="account.php" class="active">👤 Mon compte</a>
    <a href="#reservations">🎟️ Mes réservations</a>
    <a href="#favorites">⭐ Mes favoris</a>
    <a href="../index.php">🔍 Événements</a>
    <span class="sidebar-title">Compte</span>
    <a href="../logout.php">🚪 Déconnexion</a>
  </aside>

  <div class="dash-content">
    <h1>Mon compte</h1>

    <?php if ($success): ?><div class="alert success reveal visible"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert error   reveal visible"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="profile-grid reveal visible">
      <!-- Aside: avatar + quick info -->
      <div class="profile-aside">
        <div class="profile-card section-card">
          <div class="profile-avatar"><?= mb_strtoupper(mb_substr($user['name'],0,1)) ?></div>
          <strong><?= htmlspecialchars($user['name']) ?></strong>
          <span class="muted"><?= htmlspecialchars($user['email']) ?></span>
          <span class="badge ok"><?= ucfirst($user['role']) ?></span>
          <span class="muted" style="font-size:.8rem">Membre depuis <?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
        </div>

        <!-- Category preference -->
        <?php
          $favCat = null;
          foreach ($cats as $c) { if ($c['id'] == $user['favorite_category_id']) { $favCat = $c; break; } }
        ?>
        <?php if ($favCat): ?>
        <div class="section-card" style="padding:1.25rem;display:flex;flex-direction:column;gap:.4rem">
          <span class="eyebrow">Profil d'intérêt</span>
          <span style="font-size:1.5rem"><?= $favCat['icon'] ?></span>
          <strong><?= htmlspecialchars($favCat['name']) ?></strong>
          <span class="muted" style="font-size:.8rem">Les événements de cette catégorie vous sont proposés en priorité.</span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Main: edit form -->
      <div class="profile-main">
        <div class="section-card">
          <div class="section-card-header"><h2>Modifier mon profil</h2></div>
          <form method="post" style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem">
            <input type="hidden" name="update_profile" value="1">
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Nom complet</label>
              <input name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
            </div>
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Email (non modifiable)</label>
              <input value="<?= htmlspecialchars($user['email']) ?>" disabled style="opacity:.5">
            </div>
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Catégorie préférée</label>
              <select name="favorite_category_id">
                <option value="0">-- Aucune --</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= $c['id'] == $user['favorite_category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['icon'].' '.$c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
              <input type="password" name="password" placeholder="Nouveau mot de passe">
            </div>
            <button class="btn" style="align-self:flex-start">💾 Enregistrer</button>
          </form>
        </div>
      </div>
    </div>

    <!-- Reservations -->
    <div class="section-card reveal visible" id="reservations">
      <div class="section-card-header">
        <h2>🎟️ Mes réservations (<?= count($reservations) ?>)</h2>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Événement</th>
              <th>Catégorie</th>
              <th>Date</th>
              <th>Qté</th>
              <th>Total</th>
              <th>Statut</th>
              <th>QR Code</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$reservations): ?>
              <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">Aucune réservation pour l'instant.</td></tr>
            <?php endif; ?>
            <?php foreach ($reservations as $r): ?>
              <?php
                $statusMap = ['en attente'=>['pending','En attente'],'confirmee'=>['ok','Confirmée'],'annulee'=>['canceled','Annulée']];
                [$cls,$label] = $statusMap[$r['status']] ?? ['pending',$r['status']];
                $code = 'SMARTEVENT-'.$r['id'].'-'.$r['user_id'];
              ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.6rem">
                    <img src="<?= htmlspecialchars(eventImage($r['image'])) ?>" alt="" style="width:42px;height:42px;object-fit:cover;border-radius:6px">
                    <div>
                      <strong><?= htmlspecialchars($r['title']) ?></strong><br>
                      <span class="muted">📍 <?= htmlspecialchars($r['location']) ?></span>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars(($r['icon']??'✨').' '.($r['category']??'')) ?></td>
                <td><?= htmlspecialchars($r['event_date']) ?></td>
                <td><?= $r['quantity'] ?></td>
                <td><span class="price" style="font-size:.9rem"><?= number_format($r['price']*$r['quantity'],2,',',' ') ?> DT</span></td>
                <td><span class="badge <?= $cls ?>"><?= $label ?></span></td>
                <td>
                  <img class="qr-inline" src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=<?= urlencode($code) ?>" alt="QR">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Favorites -->
    <div class="section-card reveal visible" id="favorites">
      <div class="section-card-header">
        <h2>⭐ Mes favoris (<?= count($favorites) ?>)</h2>
        <a class="btn sm secondary" href="../index.php">+ Ajouter</a>
      </div>
      <div style="padding:1.25rem">
        <?php if (!$favorites): ?>
          <div class="muted" style="text-align:center;padding:2rem">Aucun favori pour l'instant. Parcourez les événements et cliquez sur ★ pour sauvegarder.</div>
        <?php endif; ?>
        <div class="fav-grid">
          <?php foreach ($favorites as $e): ?>
            <div class="fav-card">
              <img src="<?= htmlspecialchars(eventImage($e['image'])) ?>" alt="<?= htmlspecialchars($e['title']) ?>">
              <div class="fav-card-body">
                <strong><?= htmlspecialchars($e['title']) ?></strong>
                <span class="muted"><?= htmlspecialchars(($e['icon']??'✨').' '.($e['category']??'')) ?></span>
                <span class="muted">📅 <?= htmlspecialchars($e['event_date']) ?></span>
                <span class="price" style="font-size:.85rem"><?= number_format((float)$e['price'],2,',',' ') ?> DT</span>
                <div style="display:flex;gap:.5rem;margin-top:.25rem">
                  <a class="btn sm" href="../reserve.php?id=<?= $e['id'] ?>">Réserver</a>
                  <a class="btn sm secondary" href="../favorite.php?id=<?= $e['id'] ?>" title="Retirer des favoris">★</a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

  </div>
</div>
<?php require_once __DIR__ . '/../footer.php'; ?>
