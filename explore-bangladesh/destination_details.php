<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/weather_api.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$stmt = $pdo->prepare(
    "SELECT d.*, c.category_name, c.icon, dist.district_name, dv.division_name
     FROM destinations d
     JOIN categories c ON c.category_id = d.category_id
     JOIN districts dist ON dist.district_id = d.district_id
     JOIN divisions dv ON dv.division_id = dist.division_id
     WHERE d.destination_id = ?"
);
$stmt->execute([$id]);
$dest = $stmt->fetch();

if (!$dest) {
    http_response_code(404);
    $pageTitle = 'Not found';
    include __DIR__ . '/includes/header.php';
    echo '<div class="container section"><div class="info-note">Destination not found.</div></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
$pageTitle = $dest['name'];

$forecast = [];
if ($dest['latitude'] && $dest['longitude']) {
    $forecast = getForecastForDestination($pdo, $id, (float) $dest['latitude'], (float) $dest['longitude']);
}

$attractions = $pdo->prepare("SELECT * FROM attractions WHERE destination_id = ?");
$attractions->execute([$id]);
$attractions = $attractions->fetchAll();

$routes = $pdo->prepare(
    "SELECT r.*, t.transport_type, t.operator_name FROM transport_routes r
     JOIN transport t ON t.transport_id = r.transport_id
     WHERE r.destination_id = ?"
);
$routes->execute([$id]);
$routes = $routes->fetchAll();

$hotels = $pdo->prepare("SELECT * FROM hotels WHERE destination_id = ? ORDER BY rating DESC");
$hotels->execute([$id]);
$hotels = $hotels->fetchAll();

$services = $pdo->prepare("SELECT * FROM nearby_services WHERE destination_id = ?");
$services->execute([$id]);
$services = $services->fetchAll();

// ---- Reviews & ratings ----
$reviewError = '';
$reviewSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (empty($_SESSION['user_id'])) {
        $reviewError = 'Please log in to leave a review.';
    } else {
        $stars = max(1, min(5, (int) ($_POST['stars'] ?? 0)));
        $text = trim($_POST['review_text'] ?? '');

        $ratingStmt = $pdo->prepare(
            "INSERT INTO ratings (user_id, destination_id, stars) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE stars = VALUES(stars)"
        );
        $ratingStmt->execute([$_SESSION['user_id'], $id, $stars]);

        if ($text !== '') {
            $reviewStmt = $pdo->prepare("INSERT INTO reviews (user_id, destination_id, review_text) VALUES (?, ?, ?)");
            $reviewStmt->execute([$_SESSION['user_id'], $id, $text]);
        }
        $reviewSuccess = 'Thanks for sharing your experience!';
    }
}

$avgRating = $pdo->prepare("SELECT ROUND(AVG(stars),1) avg_stars, COUNT(*) total FROM ratings WHERE destination_id = ?");
$avgRating->execute([$id]);
$avgRating = $avgRating->fetch();

$reviews = $pdo->prepare(
    "SELECT rv.review_text, rv.created_at, rv.is_verified, u.full_name FROM reviews rv
     JOIN users u ON u.user_id = rv.user_id
     WHERE rv.destination_id = ? ORDER BY rv.created_at DESC LIMIT 10"
);
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="hero" style="padding-bottom:0;">
  <div class="hero-inner" style="padding-bottom:50px;">
    <span class="hero-eyebrow"><?= $dest['icon'] ?> <?= htmlspecialchars($dest['category_name']) ?> · <?= htmlspecialchars($dest['district_name']) ?>, <?= htmlspecialchars($dest['division_name']) ?></span>
    <h1 style="max-width:20ch;"><?= htmlspecialchars($dest['name']) ?></h1>
    <p class="lede"><?= htmlspecialchars($dest['description']) ?></p>
    <div class="hero-actions">
      <button class="btn btn-primary fav-btn-hero" data-dest-id="<?= $dest['destination_id'] ?>">
        <span class="fav-icon">🤍</span> Save to favourites
      </button>
      <a href="/shared_rides.php?destination=<?= $dest['destination_id'] ?>" class="btn btn-outline-light">🚗 Share a ride</a>
      <?php if ($dest['map_link']): ?><a href="<?= htmlspecialchars($dest['map_link']) ?>" target="_blank" class="btn btn-outline-light">Open in Maps</a><?php endif; ?>
    </div>
  </div>
  <div class="hero-waves">
    <svg viewBox="0 0 1440 110" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="width:100%; height:70px;">
      <path fill="#FAF6EC" d="M0,64L48,58.7C96,53,192,43,288,48C384,53,480,75,576,80C672,85,768,75,864,58.7C960,43,1056,21,1152,21.3C1248,21,1344,43,1392,53.3L1440,64L1440,120L0,120Z"></path>
    </svg>
  </div>
</section>

<section class="section-tight">
  <div class="container two-col">
    <div>
      <h3>At a glance</h3>
      <table class="data-table">
        <tr><th>Best time to visit</th><td><?= htmlspecialchars($dest['best_time_to_visit']) ?></td></tr>
        <tr><th>Entry fee</th><td><?= $dest['entry_fee'] > 0 ? '৳' . number_format($dest['entry_fee']) : 'Free' ?></td></tr>
        <tr><th>Opening hours</th><td><?= htmlspecialchars($dest['opening_hours']) ?></td></tr>
        <tr><th>Safety tips</th><td><?= htmlspecialchars($dest['safety_tips']) ?></td></tr>
      </table>

      <?php if ($attractions): ?>
      <h3 style="margin-top:30px;">Nearby attractions</h3>
      <ul>
        <?php foreach ($attractions as $a): ?>
          <li><strong><?= htmlspecialchars($a['attraction_name']) ?></strong> — <?= $a['distance_km'] ?> km · <?= htmlspecialchars($a['description']) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
    </div>

    <div>
      <h3>5-day forecast</h3>
      <?php if ($forecast): ?>
        <div class="forecast-strip">
          <?php foreach ($forecast as $day): $lbl = weatherScoreLabel((int)$day['weather_score']); ?>
            <div class="forecast-day">
              <div class="d"><?= date('D j', strtotime($day['forecast_date'])) ?></div>
              <div class="icon">🌤️</div>
              <div class="t"><?= $day['temp_max'] ?>° / <?= $day['temp_min'] ?>°</div>
              <span class="weather-chip <?= $lbl['class'] ?>" style="margin-top:6px;"><?= $day['weather_score'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="info-note">No forecast cached yet. Add your OpenWeatherMap API key in <code>config/weather_api.php</code> to fetch live data for this destination.</div>
      <?php endif; ?>

      <h3 style="margin-top:30px;">Getting there</h3>
      <?php if ($routes): ?>
        <table class="data-table">
          <thead><tr><th>Mode</th><th>From</th><th>Time</th><th>Cost</th><th></th></tr></thead>
          <tbody>
          <?php foreach ($routes as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['transport_type']) ?> (<?= htmlspecialchars($r['operator_name']) ?>)</td>
              <td><?= htmlspecialchars($r['origin']) ?><?= $r['stop_over'] ? ' → ' . htmlspecialchars($r['stop_over']) : '' ?></td>
              <td><?= htmlspecialchars($r['estimated_time']) ?></td>
              <td>৳<?= number_format($r['estimated_cost']) ?></td>
              <td><a href="/ticket_booking.php?route=<?= $r['route_id'] ?>" class="btn btn-primary btn-sm">Book</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="info-note">Transport data coming soon for this destination.</div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($hotels): ?>
<section class="section bg-alt">
  <div class="container">
    <h3>Where to stay</h3>
    <div class="destination-grid">
      <?php foreach ($hotels as $h): ?>
      <div class="dest-card">
        <div class="dest-body">
          <h3><?= htmlspecialchars($h['hotel_name']) ?></h3>
          <div class="dest-loc"><?= htmlspecialchars($h['hotel_type']) ?> · ⭐ <?= number_format($h['rating'], 1) ?></div>
          <p class="dest-desc">৳<?= number_format($h['price_range_min']) ?> – ৳<?= number_format($h['price_range_max']) ?> / night
            <?= $h['free_breakfast'] ? ' · 🍳 Free breakfast' : '' ?><?= $h['swimming_pool'] ? ' · 🏊 Pool' : '' ?></p>
          <div class="dest-meta"><span>📍 <?= htmlspecialchars($h['address']) ?></span></div>
          <a href="/hotel_booking.php?hotel=<?= $h['hotel_id'] ?>" class="btn btn-primary btn-block btn-sm">Book this hotel</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($services): ?>
<section class="section">
  <div class="container">
    <h3>Nearby essentials</h3>
    <table class="data-table">
      <thead><tr><th>Type</th><th>Name</th><th>Address</th><th>Rating</th><th>Contact</th></tr></thead>
      <tbody>
      <?php foreach ($services as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['service_type']) ?></td>
          <td><?= $s['map_link'] ? '<a href="' . htmlspecialchars($s['map_link']) . '" target="_blank">' . htmlspecialchars($s['service_name']) . '</a>' : htmlspecialchars($s['service_name']) ?></td>
          <td><?= htmlspecialchars($s['address']) ?></td>
          <td>⭐ <?= number_format($s['rating'], 1) ?></td>
          <td><?= htmlspecialchars($s['contact_no']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<section class="section bg-alt" id="reviews">
  <div class="container two-col">
    <div>
      <h3>Reviews &amp; ratings</h3>
      <p style="color:var(--ink-soft);">
        <?php if ($avgRating['total'] > 0): ?>
          ⭐ <strong><?= $avgRating['avg_stars'] ?></strong> average from <?= (int) $avgRating['total'] ?> rating(s)
        <?php else: ?>
          No ratings yet — be the first!
        <?php endif; ?>
      </p>

      <?php if (empty($reviews)): ?>
        <div class="info-note">No written reviews yet.</div>
      <?php else: ?>
        <?php foreach ($reviews as $rv): ?>
          <div class="info-note" style="margin-bottom:12px;">
            <strong><?= htmlspecialchars($rv['full_name']) ?></strong>
            <?= $rv['is_verified'] ? ' ✅' : '' ?>
            <span style="color:var(--ink-soft); font-size:.8rem;"> · <?= date('M j, Y', strtotime($rv['created_at'])) ?></span>
            <p style="margin:6px 0 0;"><?= htmlspecialchars($rv['review_text']) ?></p>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div>
      <h3>Leave a review</h3>
      <?php if ($reviewError): ?><div class="advice-box warn"><?= htmlspecialchars($reviewError) ?></div><?php endif; ?>
      <?php if ($reviewSuccess): ?><div class="advice-box"><h4>✅ <?= htmlspecialchars($reviewSuccess) ?></h4></div><?php endif; ?>

      <?php if (empty($_SESSION['user_id'])): ?>
        <div class="info-note">Please <a href="/login.php">log in</a> to leave a review or rating.</div>
      <?php else: ?>
        <form method="post" class="weather-form" style="background:#fff; padding:22px; border-radius:var(--radius-lg); box-shadow:var(--shadow-card);">
          <div class="field">
            <label>Your rating</label>
            <select name="stars" required>
              <option value="">Select stars…</option>
              <?php for ($s = 5; $s >= 1; $s--): ?>
                <option value="<?= $s ?>"><?= str_repeat('⭐', $s) ?></option>
              <?php endfor; ?>
            </select>
          </div>
          <div class="field">
            <label>Your review (optional)</label>
            <textarea name="review_text" rows="4" placeholder="Share your experience…" style="width:100%; padding:.8em 1em; border-radius:var(--radius-sm); border:1px solid rgba(31,92,74,.25); font-family:var(--font-body);"></textarea>
          </div>
          <button type="submit" name="submit_review" value="1" class="btn btn-primary btn-block">Submit review</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>