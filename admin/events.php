<?php
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$cats    = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$success = '';
$error   = '';

// ── Delete ─────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([(int)$_GET['delete']]);
    header('Location: events.php?deleted=1');
    exit;
}

// ── Insert / Update ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = (int) ($_POST['id'] ?? 0);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location    = trim($_POST['location']);
    $event_date  = $_POST['event_date'];
    $price       = (float) $_POST['price'];
    $places      = (int)   $_POST['places'];
    $category_id = (int)   $_POST['category_id'];
    $image       = trim($_POST['image']);

    try {
        if ($id) {
            $pdo->prepare("UPDATE events SET category_id=?,title=?,description=?,location=?,event_date=?,price=?,places=?,image=? WHERE id=?")
                ->execute([$category_id,$title,$description,$location,$event_date,$price,$places,$image,$id]);
            $success = 'Événement mis à jour.';
        } else {
            $pdo->prepare("INSERT INTO events(category_id,title,description,location,event_date,price,places,image) VALUES(?,?,?,?,?,?,?,?)")
                ->execute([$category_id,$title,$description,$location,$event_date,$price,$places,$image]);
            $success = 'Événement créé avec succès.';
        }
    } catch (Exception $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}

// ── Edit mode ──────────────────────────────────────────────
$editEvent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editEvent = $stmt->fetch();
}

// ── List ───────────────────────────────────────────────────
$events = $pdo->query("SELECT e.*, c.name AS category, c.icon,
                        COALESCE(SUM(CASE WHEN r.status<>'annulee' THEN r.quantity ELSE 0 END),0) AS reserved
                        FROM events e
                        LEFT JOIN categories c ON c.id=e.category_id
                        LEFT JOIN reservations r ON r.event_id=e.id
                        GROUP BY e.id, c.name, c.icon ORDER BY e.event_date ASC")->fetchAll();

require_once __DIR__ . '/../header.php';
?>
<div class="dashboard">
  <aside class="sidebar">
    <span class="sidebar-title">Administration</span>
    <a href="dashboard.php">📊 Tableau de bord</a>
    <a href="events.php" class="active">🎫 Événements</a>
    <a href="reservations.php">🎟️ Réservations</a>
    <a href="users.php">👥 Utilisateurs</a>
    <span class="sidebar-title">Site</span>
    <a href="../index.php">🌐 Voir le site</a>
    <a href="../logout.php">🚪 Déconnexion</a>
  </aside>

  <div class="dash-content">
    <h1>Gestion des événements</h1>

    <?php if ($success): ?><div class="alert success reveal visible"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert error   reveal visible"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="alert info reveal visible">Événement supprimé.</div><?php endif; ?>

    <!-- Form: create / edit -->
    <div class="section-card reveal visible" style="margin-bottom:2rem">
      <div class="section-card-header">
        <h2><?= $editEvent ? '✏️ Modifier l\'événement' : '➕ Nouvel événement' ?></h2>
        <?php if ($editEvent): ?><a href="events.php" class="btn sm secondary">Annuler</a><?php endif; ?>
      </div>
      <form method="post" style="padding:1.5rem">
        <?php if ($editEvent): ?>
          <input type="hidden" name="id" value="<?= $editEvent['id'] ?>">
        <?php endif; ?>
        <div class="event-form-grid">
          <div style="display:flex;flex-direction:column;gap:.75rem">
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Titre *</label>
              <input name="title" placeholder="Titre de l'événement" value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>" required>
            </div>
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Lieu *</label>
              <input name="location" placeholder="Lieu de l'événement" value="<?= htmlspecialchars($editEvent['location'] ?? '') ?>" required>
            </div>
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Date *</label>
              <input type="date" name="event_date" value="<?= $editEvent['event_date'] ?? '' ?>" required>
            </div>
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Catégorie</label>
              <select name="category_id">
                <option value="">-- Sans catégorie --</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= $c['id'] ?>" <?= ($editEvent['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['icon'].' '.$c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div style="display:flex;flex-direction:column;gap:.75rem">
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Description</label>
              <textarea name="description" placeholder="Description de l'événement"><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem">
              <div>
                <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Prix (DT) *</label>
                <input type="number" step="0.01" min="0" name="price" placeholder="0.00" value="<?= $editEvent['price'] ?? '' ?>" required>
              </div>
              <div>
                <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">Places *</label>
                <input type="number" min="1" name="places" placeholder="100" value="<?= $editEvent['places'] ?? '' ?>" required>
              </div>
            </div>
            <div>
              <label style="font-size:.8rem;color:var(--muted);margin-bottom:.3rem;display:block">URL Image</label>
              <input name="image" placeholder="https://..." value="<?= htmlspecialchars($editEvent['image'] ?? '') ?>">
            </div>
          </div>
        </div>
        <div style="margin-top:1.25rem">
          <button class="btn"><?= $editEvent ? '💾 Enregistrer les modifications' : '➕ Créer l\'événement' ?></button>
        </div>
      </form>
    </div>

    <!-- Events list -->
    <div class="section-card reveal visible">
      <div class="section-card-header">
        <h2>Tous les événements (<?= count($events) ?>)</h2>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Titre</th><th>Catégorie</th><th>Date</th><th>Prix</th><th>Places</th><th>Réservées</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($events as $e): ?>
              <?php $remaining = max(0,(int)$e['places']-(int)$e['reserved']); ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:.6rem">
                    <img src="<?= htmlspecialchars(eventImage($e['image'])) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px" alt="">
                    <strong><?= htmlspecialchars($e['title']) ?></strong>
                  </div>
                </td>
                <td><?= htmlspecialchars(($e['icon']??'✨').' '.($e['category']??'')) ?></td>
                <td><?= htmlspecialchars($e['event_date']) ?></td>
                <td><?= number_format((float)$e['price'],2,',',' ') ?> DT</td>
                <td><?= $e['places'] ?></td>
                <td>
                  <span class="badge <?= $remaining>10?'ok':($remaining>0?'pending':'canceled') ?>">
                    <?= (int)$e['reserved'] ?> / <?= $e['places'] ?>
                  </span>
                </td>
                <td>
                  <a href="events.php?edit=<?= $e['id'] ?>" class="btn sm secondary">✏️</a>
                  <a href="events.php?delete=<?= $e['id'] ?>" class="btn sm danger" style="margin-left:.25rem"
                     onclick="return confirm('Supprimer cet événement ?')">🗑️</a>
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
