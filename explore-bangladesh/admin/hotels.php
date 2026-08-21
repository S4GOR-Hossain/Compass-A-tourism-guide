<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Manage Hotels';
$error = '';
$success = '';

$destinationsList = $pdo->query("SELECT destination_id, name FROM destinations ORDER BY name")->fetchAll();

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM hotels WHERE hotel_id = ?")->execute([(int) $_GET['delete']]);
    header('Location: /admin/hotels.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['hotel_id'] ?? 0);
    $destId = (int) $_POST['destination_id'];
    $name = trim($_POST['hotel_name'] ?? '');
    $type = $_POST['hotel_type'] ?? 'Hotel';
    $min = (float) $_POST['price_range_min'];
    $max = (float) $_POST['price_range_max'];
    $rating = (float) $_POST['rating'];
    $contact = trim($_POST['contact_no'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $breakfast = isset($_POST['free_breakfast']) ? 1 : 0;
    $pool = isset($_POST['swimming_pool']) ? 1 : 0;

    if ($destId && $name) {
        if ($id) {
            $stmt = $pdo->prepare(
                "UPDATE hotels SET destination_id=?, hotel_name=?, hotel_type=?, price_range_min=?, price_range_max=?,
                 rating=?, contact_no=?, address=?, free_breakfast=?, swimming_pool=? WHERE hotel_id=?"
            );
            $stmt->execute([$destId, $name, $type, $min, $max, $rating, $contact, $address, $breakfast, $pool, $id]);
            $success = 'Hotel updated.';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO hotels (destination_id, hotel_name, hotel_type, price_range_min, price_range_max, rating, contact_no, address, free_breakfast, swimming_pool)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$destId, $name, $type, $min, $max, $rating, $contact, $address, $breakfast, $pool]);
            $success = 'Hotel added.';
        }
    } else {
        $error = 'Destination and hotel name are required.';
    }
}

$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$editRow = null;
if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM hotels WHERE hotel_id = ?");
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
}

$hotels = $pdo->query(
    "SELECT h.*, d.name AS destination_name FROM hotels h JOIN destinations d ON d.destination_id = h.destination_id ORDER BY h.hotel_name"
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="admin-topbar">
  <div><span class="eyebrow">Content</span><h2 style="margin:0;">Hotels &amp; resorts</h2></div>
</div>

<?php if ($error): ?><div class="advice-box warn"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="advice-box"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="two-col" style="align-items:start;">
  <div>
    <h3><?= $editRow ? 'Edit hotel' : 'Add new hotel' ?></h3>
    <form method="post" class="weather-form" style="background:#fff; padding:22px; border-radius:var(--radius-lg); box-shadow:var(--shadow-card);">
      <input type="hidden" name="hotel_id" value="<?= $editRow['hotel_id'] ?? 0 ?>">
      <div class="field">
        <label>Destination</label>
        <select name="destination_id" required>
          <?php foreach ($destinationsList as $d): ?>
            <option value="<?= $d['destination_id'] ?>" <?= (($editRow['destination_id'] ?? 0) == $d['destination_id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Hotel name</label><input type="text" name="hotel_name" value="<?= htmlspecialchars($editRow['hotel_name'] ?? '') ?>" required></div>
      <div class="field">
        <label>Type</label>
        <select name="hotel_type">
          <?php foreach (['Hotel','Resort','Guest House'] as $t): ?>
            <option <?= (($editRow['hotel_type'] ?? '') === $t) ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field"><label>Min price/night (৳)</label><input type="number" step="0.01" name="price_range_min" value="<?= htmlspecialchars($editRow['price_range_min'] ?? '') ?>"></div>
      <div class="field"><label>Max price/night (৳)</label><input type="number" step="0.01" name="price_range_max" value="<?= htmlspecialchars($editRow['price_range_max'] ?? '') ?>"></div>
      <div class="field"><label>Rating (0-5)</label><input type="number" step="0.1" min="0" max="5" name="rating" value="<?= htmlspecialchars($editRow['rating'] ?? '0') ?>"></div>
      <div class="field"><label>Contact</label><input type="text" name="contact_no" value="<?= htmlspecialchars($editRow['contact_no'] ?? '') ?>"></div>
      <div class="field"><label>Address</label><input type="text" name="address" value="<?= htmlspecialchars($editRow['address'] ?? '') ?>"></div>
      <div class="field" style="display:flex; gap:16px;">
        <label style="display:flex; align-items:center; gap:6px; margin:0;"><input type="checkbox" name="free_breakfast" style="width:auto;" <?= !empty($editRow['free_breakfast']) ? 'checked' : '' ?>> Free breakfast</label>
        <label style="display:flex; align-items:center; gap:6px; margin:0;"><input type="checkbox" name="swimming_pool" style="width:auto;" <?= !empty($editRow['swimming_pool']) ? 'checked' : '' ?>> Swimming pool</label>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><?= $editRow ? 'Update hotel' : 'Add hotel' ?></button>
      <?php if ($editRow): ?><a href="/admin/hotels.php" class="btn btn-ghost btn-block" style="margin-top:8px;">Cancel edit</a><?php endif; ?>
    </form>
  </div>

  <div>
    <h3>All hotels (<?= count($hotels) ?>)</h3>
    <table class="data-table">
      <thead><tr><th>Name</th><th>Destination</th><th>Price</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($hotels as $h): ?>
        <tr>
          <td><?= htmlspecialchars($h['hotel_name']) ?></td>
          <td><?= htmlspecialchars($h['destination_name']) ?></td>
          <td>৳<?= number_format($h['price_range_min']) ?>–<?= number_format($h['price_range_max']) ?></td>
          <td>
            <a href="/admin/hotels.php?edit=<?= $h['hotel_id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
            <a href="/admin/hotels.php?delete=<?= $h['hotel_id'] ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Delete this hotel?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
