<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/weather_api.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Destinations';

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_id")->fetchAll();
$divisions  = $pdo->query("SELECT * FROM divisions ORDER BY division_name")->fetchAll();

$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$divisionId = isset($_GET['division']) ? (int) $_GET['division'] : 0;
$search     = trim($_GET['q'] ?? '');

$sql = "SELECT d.*, c.category_name, c.icon, dist.district_name, dist.division_id
        FROM destinations d
        JOIN categories c ON c.category_id = d.category_id
        JOIN districts dist ON dist.district_id = d.district_id
        WHERE 1=1";
$params = [];

if ($categoryId) { $sql .= " AND d.category_id = :cat"; $params['cat'] = $categoryId; }
if ($divisionId) { $sql .= " AND dist.division_id = :div"; $params['div'] = $divisionId; }
if ($search !== '') { $sql .= " AND d.name LIKE :q"; $params['q'] = "%$search%"; }

$sql .= " ORDER BY d.name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container">
    <span class="eyebrow">Browse</span>
    <h2>All destinations</h2>
    <p style="color:var(--ink-soft); max-width:60ch;">Filter by terrain type or region, then open a destination to see live weather, transport routes, and nearby stays.</p>

    <form method="get" class="filter-bar" style="margin-top:24px;">
      <input type="text" name="q" placeholder="Search destination…" value="<?= htmlspecialchars($search) ?>">
      <select name="category" onchange="this.form.submit()">
        <option value="0">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['category_id'] ?>" <?= $categoryId === (int)$c['category_id'] ? 'selected' : '' ?>>
            <?= $c['icon'] ?> <?= htmlspecialchars($c['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="division" onchange="this.form.submit()">
        <option value="0">All divisions</option>
        <?php foreach ($divisions as $d): ?>
          <option value="<?= $d['division_id'] ?>" <?= $divisionId === (int)$d['division_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($d['division_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-forest btn-sm">Filter</button>
    </form>

    <?php if (empty($destinations)): ?>
      <div class="info-note">No destinations match those filters yet. Try clearing a filter.</div>
    <?php else: ?>
    <div class="destination-grid" style="margin-top:30px;">
      <?php foreach ($destinations as $d):
          $score = null;
          if ($d['latitude'] && $d['longitude']) {
              $cached = getCachedForecast($pdo, $d['destination_id']);
              if (!empty($cached)) { $score = $cached[0]['weather_score']; }
          }
      ?>
      <div class="dest-card">
        <div class="dest-media cat-<?= $d['category_id'] ?>">
          <?= $d['icon'] ?>
          <span class="dest-tag"><?= htmlspecialchars($d['category_name']) ?></span>
          <button class="fav-btn" data-dest-id="<?= $d['destination_id'] ?>">🤍</button>
        </div>
        <div class="dest-body">
          <h3><?= htmlspecialchars($d['name']) ?></h3>
          <div class="dest-loc">📍 <?= htmlspecialchars($d['district_name']) ?></div>
          <p class="dest-desc"><?= htmlspecialchars(truncateText($d['description'], 90)) ?></p>
          <div class="dest-meta">
            <span><?= $d['entry_fee'] > 0 ? '৳' . number_format($d['entry_fee']) . ' entry' : 'Free entry' ?></span>
            <?php if ($score !== null): $lbl = weatherScoreLabel($score); ?>
              <span class="weather-chip <?= $lbl['class'] ?>">● <?= $score ?>/100</span>
            <?php else: ?>
              <span class="weather-chip score-good">Forecast pending</span>
            <?php endif; ?>
          </div>
          <a href="/destination_details.php?id=<?= $d['destination_id'] ?>" class="btn btn-forest btn-block btn-sm">View details</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
