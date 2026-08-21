<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Home';

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_id")->fetchAll();

$categoryCounts = [];
foreach ($pdo->query("SELECT category_id, COUNT(*) c FROM destinations GROUP BY category_id") as $row) {
    $categoryCounts[$row['category_id']] = $row['c'];
}

$featured = $pdo->query(
    "SELECT d.*, c.category_name, c.icon, dist.district_name
     FROM destinations d
     JOIN categories c ON c.category_id = d.category_id
     JOIN districts dist ON dist.district_id = d.district_id
     ORDER BY d.destination_id ASC LIMIT 6"
)->fetchAll();

$totalDestinations = $pdo->query("SELECT COUNT(*) c FROM destinations")->fetch()['c'];
$totalDistricts = $pdo->query("SELECT COUNT(DISTINCT district_id) c FROM destinations")->fetch()['c'];

include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="hero-inner">
    <span class="hero-eyebrow">🌤️ Weather-smart trip planning for Bangladesh</span>
    <h1>Plan your Bangladesh trip <span>around the sky</span>, not against it.</h1>
    <p class="lede">Explore Bangladesh brings destinations, transport, hotels, and live weather forecasts into one platform — so you know exactly when and where to go.</p>
    <div class="hero-actions">
      <a href="/destinations.php" class="btn btn-primary">Explore destinations</a>
      <a href="/weather_suggestion.php" class="btn btn-outline-light">Check weather planner →</a>
    </div>
    <div class="hero-stats">
      <div class="hero-stat"><b><?= (int)$totalDestinations ?>+</b><span>Curated destinations</span></div>
      <div class="hero-stat"><b><?= (int)$totalDistricts ?>+</b><span>Districts covered</span></div>
      <div class="hero-stat"><b>3</b><span>Terrain categories</span></div>
    </div>
  </div>
  <div class="hero-waves">
    <svg viewBox="0 0 1440 110" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="width:100%; height:90px;">
      <path fill="#FAF6EC" d="M0,64L48,58.7C96,53,192,43,288,48C384,53,480,75,576,80C672,85,768,75,864,58.7C960,43,1056,21,1152,21.3C1248,21,1344,43,1392,53.3L1440,64L1440,120L0,120Z"></path>
    </svg>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Categories</span>
        <h2>Three ways to see Bangladesh</h2>
        <p>From misty hill valleys to the world's longest sea beach and UNESCO heritage ruins.</p>
      </div>
      <a href="/destinations.php" class="btn btn-ghost">View all destinations →</a>
    </div>

    <div class="category-grid">
      <?php
      $slugMap = [1 => 'mountain', 2 => 'sea', 3 => 'heritage'];
      $descMap = [
          1 => 'Cloud-covered valleys, tea gardens, and tribal hill trails.',
          2 => 'Endless beaches, coral islands, and coastal sunsets.',
          3 => 'Ancient mosques, temples, and UNESCO ruins across the delta.',
      ];
      foreach ($categories as $cat):
          $slug = $slugMap[$cat['category_id']] ?? 'mountain';
      ?>
      <a href="/destinations.php?category=<?= $cat['category_id'] ?>" class="category-card <?= $slug ?>">
        <span class="category-icon"><?= $cat['icon'] ?></span>
        <span class="category-count"><?= $categoryCounts[$cat['category_id']] ?? 0 ?> spots</span>
        <h3><?= htmlspecialchars($cat['category_name']) ?></h3>
        <p><?= $descMap[$cat['category_id']] ?? '' ?></p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section bg-alt">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Featured</span>
        <h2>Popular right now</h2>
      </div>
    </div>

    <div class="destination-grid">
      <?php foreach ($featured as $d): ?>
      <div class="dest-card">
        <div class="dest-media cat-<?= $d['category_id'] ?>">
          <?= $d['icon'] ?>
          <span class="dest-tag"><?= htmlspecialchars($d['category_name']) ?></span>
          <button class="fav-btn" data-dest-id="<?= $d['destination_id'] ?>">🤍</button>
        </div>
        <div class="dest-body">
          <h3><?= htmlspecialchars($d['name']) ?></h3>
          <div class="dest-loc">📍 <?= htmlspecialchars($d['district_name']) ?></div>
          <p class="dest-desc"><?= htmlspecialchars(truncateText($d['description'], 100)) ?></p>
          <div class="dest-meta">
            <span><?= $d['entry_fee'] > 0 ? '৳' . number_format($d['entry_fee']) . ' entry' : 'Free entry' ?></span>
            <span>🕒 <?= htmlspecialchars($d['best_time_to_visit']) ?></span>
          </div>
          <a href="/destination_details.php?id=<?= $d['destination_id'] ?>" class="btn btn-forest btn-block btn-sm">View details</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="weather-tool">
      <div>
        <span class="eyebrow">Weather Planner</span>
        <h2>Should you book that ticket today?</h2>
        <p>We pull live forecasts for every destination and score each day for travel — so you can avoid getting caught in monsoon rain or a rough sea crossing.</p>
        <a href="/weather_suggestion.php" class="btn btn-primary">Open the weather planner →</a>
      </div>
      <div>
        <div class="info-note">
          🌦️ <strong>How it works:</strong> every destination has GPS coordinates linked to OpenWeatherMap. We fetch a 5-day forecast, calculate a 0–100 "travel score" from rain probability, storms, and wind — then warn you before you book a ticket into bad weather.
        </div>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
