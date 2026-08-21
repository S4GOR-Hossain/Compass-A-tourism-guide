<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Manage Destinations';
$error = '';
$success = '';

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_id")->fetchAll();
$districts = $pdo->query("SELECT * FROM districts ORDER BY district_name")->fetchAll();

// DELETE
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM destinations WHERE destination_id = ?")->execute([(int) $_GET['delete']]);
    header('Location: /admin/destinations.php');
    exit;
}

// ADD / EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['destination_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int) $_POST['category_id'];
    $districtId = (int) $_POST['district_id'];
    $description = trim($_POST['description'] ?? '');
    $bestTime = trim($_POST['best_time_to_visit'] ?? '');
    $entryFee = (float) ($_POST['entry_fee'] ?? 0);
    $hours = trim($_POST['opening_hours'] ?? '');
    $lat = $_POST['latitude'] !== '' ? (float) $_POST['latitude'] : null;
    $lng = $_POST['longitude'] !== '' ? (float) $_POST['longitude'] : null;
    $mapLink = trim($_POST['map_link'] ?? '');
    $safety = trim($_POST['safety_tips'] ?? '');
    $isOutdoor = isset($_POST['is_outdoor']) ? 1 : 0;

    if ($name && $categoryId && $districtId) {
        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE destinations SET name=?, category_id=?, district_id=?, description=?, best_time_to_visit=?,
                 entry_fee=?, opening_hours=?, latitude=?, longitude=?, map_link=?, safety_tips=?, is_outdoor=?
                 WHERE destination_id=?"
            );
            $stmt->execute([$name, $categoryId, $districtId, $description, $bestTime, $entryFee, $hours, $lat, $lng, $mapLink, $safety, $isOutdoor, $id]);
            $success = 'Destination updated.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO destinations (name, category_id, district_id, description, best_time_to_visit, entry_fee, opening_hours, latitude, longitude, map_link, safety_tips, is_outdoor)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$name, $categoryId, $districtId, $description, $bestTime, $entryFee, $hours, $lat, $lng, $mapLink, $safety, $isOutdoor]);
            $success = 'Destination added.';
        }
    } else {
        $error = 'Name, category, and district are required.';
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRow = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE destination_id = ?");
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$destinations = $pdo->query(
    "SELECT d.*, c.category_name, dist.district_name FROM destinations d
     JOIN categories c ON c.category_id = d.category_id
     JOIN districts dist ON dist.district_id = d.district_id
     ORDER BY d.name"
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="admin-topbar">
  <div><span class="eyebrow">Content</span><h2 style="margin:0;">Destinations</h2></div>
</div>

<?php if ($error): ?><div class="advice-box warn"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="advice-box"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="two-col" style="align-items:start;">
  <div>
    <h3><?= $editRow ? 'Edit destination' : 'Add new destination' ?></h3>
    <form method="post" class="weather-form" style="background:#fff; padding:22px; border-radius:var(--radius-lg); box-shadow:var(--shadow-card);">
      <input type="hidden" name="destination_id" value="<?= $editRow['destination_id'] ?? 0 ?>">
      <div class="field"><label>Name</label><input type="text" name="name" value="<?= htmlspecialchars($editRow['name'] ?? '') ?>" required></div>
      <div class="field">
        <label>Category</label>
        <select name="category_id" required>
          <?php foreach ($categories as $c): ?>
            <option value="<?= $c['category_id'] ?>" <?= (($editRow['category_id'] ?? 0) == $c['category_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['category_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>District</label>
        <select name="district_id" required>
          <?php foreach ($districts as $d): ?>
            <option value="<?= $d['district_id'] ?>" <?= (($editRow['district_id'] ?? 0) == $d['district_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['district_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Description</label><textarea name="description" rows="3" style="width:100%; padding:.8em 1em; border-radius:var(--radius-sm); border:1px solid rgba(31,92,74,.25);"><?= htmlspecialchars($editRow['description'] ?? '') ?></textarea></div>
      <div class="field"><label>Best time to visit</label><input type="text" name="best_time_to_visit" value="<?= htmlspecialchars($editRow['best_time_to_visit'] ?? '') ?>"></div>
      <div class="field"><label>Entry fee (৳)</label><input type="number" step="0.01" name="entry_fee" value="<?= htmlspecialchars($editRow['entry_fee'] ?? '0') ?>"></div>
      <div class="field"><label>Opening hours</label><input type="text" name="opening_hours" value="<?= htmlspecialchars($editRow['opening_hours'] ?? '') ?>"></div>
      <div class="field"><label>Latitude</label><input type="text" name="latitude" value="<?= htmlspecialchars($editRow['latitude'] ?? '') ?>" placeholder="e.g. 21.4272"></div>
      <div class="field"><label>Longitude</label><input type="text" name="longitude" value="<?= htmlspecialchars($editRow['longitude'] ?? '') ?>" placeholder="e.g. 92.0058"></div>
      <div class="field"><label>Map link</label><input type="text" name="map_link" value="<?= htmlspecialchars($editRow['map_link'] ?? '') ?>"></div>
      <div class="field"><label>Safety tips</label><textarea name="safety_tips" rows="2" style="width:100%; padding:.8em 1em; border-radius:var(--radius-sm); border:1px solid rgba(31,92,74,.25);"><?= htmlspecialchars($editRow['safety_tips'] ?? '') ?></textarea></div>
      <div class="field" style="display:flex; align-items:center; gap:8px;">
        <input type="checkbox" name="is_outdoor" style="width:auto;" <?= (!isset($editRow) || $editRow['is_outdoor']) ? 'checked' : '' ?>>
        <label style="margin:0;">Outdoor / weather-sensitive spot</label>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><?= $editRow ? 'Update destination' : 'Add destination' ?></button>
      <?php if ($editRow): ?><a href="/admin/destinations.php" class="btn btn-ghost btn-block" style="margin-top:8px;">Cancel edit</a><?php endif; ?>
    </form>
  </div>

  <div>
    <h3>All destinations (<?= count($destinations) ?>)</h3>
    <table class="data-table">
      <thead><tr><th>Name</th><th>Category</th><th>District</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($destinations as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['name']) ?></td>
          <td><?= htmlspecialchars($d['category_name']) ?></td>
          <td><?= htmlspecialchars($d['district_name']) ?></td>
          <td>
            <a href="/admin/destinations.php?edit=<?= $d['destination_id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
            <a href="/admin/destinations.php?delete=<?= $d['destination_id'] ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Delete this destination and all related data?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
