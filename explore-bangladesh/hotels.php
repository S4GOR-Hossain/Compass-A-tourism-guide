<?php
require_once __DIR__ . '/config/db.php';
$pageTitle = 'Hotels & Resorts';

$maxBudget = isset($_GET['budget']) && $_GET['budget'] !== '' ? (float) $_GET['budget'] : null;
$breakfast = isset($_GET['breakfast']);
$pool      = isset($_GET['pool']);
$destinationId = isset($_GET['destination']) ? (int) $_GET['destination'] : 0;

$sql = "SELECT h.*, d.name AS destination_name FROM hotels h JOIN destinations d ON d.destination_id = h.destination_id WHERE 1=1";
$params = [];
if ($maxBudget !== null) { $sql .= " AND h.price_range_min <= :budget"; $params['budget'] = $maxBudget; }
if ($breakfast) { $sql .= " AND h.free_breakfast = 1"; }
if ($pool) { $sql .= " AND h.swimming_pool = 1"; }
if ($destinationId) { $sql .= " AND h.destination_id = :dest"; $params['dest'] = $destinationId; }
$sql .= " ORDER BY h.rating DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

$destinations = $pdo->query("SELECT destination_id, name FROM destinations ORDER BY name")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container">
    <span class="eyebrow">Stays</span>
    <h2>Hotels &amp; resorts, matched to your budget</h2>
    <p style="color:var(--ink-soft); max-width:60ch;">Filter by destination, nightly budget, or the amenities that matter to you.</p>

    <form method="get" class="filter-bar" style="margin-top:24px;">
      <select name="destination">
        <option value="0">All destinations</option>
        <?php foreach ($destinations as $d): ?>
          <option value="<?= $d['destination_id'] ?>" <?= $destinationId === (int)$d['destination_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="number" name="budget" placeholder="Max budget (৳/night)" value="<?= htmlspecialchars($_GET['budget'] ?? '') ?>">
      <label class="filter-chip" style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="breakfast" <?= $breakfast ? 'checked' : '' ?> style="width:auto;"> 🍳 Free breakfast
      </label>
      <label class="filter-chip" style="display:flex; align-items:center; gap:6px;">
        <input type="checkbox" name="pool" <?= $pool ? 'checked' : '' ?> style="width:auto;"> 🏊 Swimming pool
      </label>
      <button type="submit" class="btn btn-forest btn-sm">Filter</button>
    </form>

    <?php if (empty($hotels)): ?>
      <div class="info-note" style="margin-top:24px;">No hotels match those filters. Try a higher budget or fewer amenity requirements.</div>
    <?php else: ?>
    <div class="destination-grid" style="margin-top:30px;">
      <?php foreach ($hotels as $h): ?>
      <div class="dest-card">
        <div class="dest-body">
          <span class="dest-tag" style="position:static; display:inline-block; margin-bottom:10px;"><?= htmlspecialchars($h['hotel_type']) ?></span>
          <h3><?= htmlspecialchars($h['hotel_name']) ?></h3>
          <div class="dest-loc">📍 <?= htmlspecialchars($h['destination_name']) ?> · ⭐ <?= number_format($h['rating'], 1) ?></div>
          <p class="dest-desc">৳<?= number_format($h['price_range_min']) ?> – ৳<?= number_format($h['price_range_max']) ?> / night
            <?= $h['free_breakfast'] ? ' · 🍳 Breakfast' : '' ?><?= $h['swimming_pool'] ? ' · 🏊 Pool' : '' ?></p>
          <a href="/hotel_booking.php?hotel=<?= $h['hotel_id'] ?>" class="btn btn-primary btn-block btn-sm">Book this hotel</a>
          <a href="/destination_details.php?id=<?= $h['destination_id'] ?>" class="btn btn-ghost btn-block btn-sm" style="margin-top:8px;">View destination</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
