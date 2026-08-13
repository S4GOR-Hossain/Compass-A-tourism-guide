<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/weather_api.php';
$pageTitle = 'Weather Planner';

$destinations = $pdo->query("SELECT destination_id, name FROM destinations ORDER BY name")->fetchAll();

// Make sure every destination has a forecast cached (fetches only what's
// missing or older than WEATHER_REFRESH_HOURS — see config/weather_api.php).
refreshAllDestinationsWeather($pdo);

// Build a weather-ranked list of ALL outdoor destinations using cached forecasts (today).
$ranked = $pdo->query(
    "SELECT d.destination_id, d.name, d.is_outdoor, c.category_name, c.icon, dist.district_name,
            w.weather_score, w.condition_main, w.rain_probability, w.temp_max, w.temp_min
     FROM destinations d
     JOIN categories c ON c.category_id = d.category_id
     JOIN districts dist ON dist.district_id = d.district_id
     LEFT JOIN weather_logs w ON w.destination_id = d.destination_id AND w.forecast_date = CURDATE()
     ORDER BY (w.weather_score IS NULL) ASC, w.weather_score DESC"
)->fetchAll();

// Weather-based food suggestion sample (feature #6) — join restaurants + food_items
$foodSuggestions = $pdo->query(
    "SELECT f.item_name, f.suitable_weather, f.item_rating, f.price, r.restaurant_name, r.map_link, d.name AS destination_name
     FROM food_items f
     JOIN restaurants r ON r.restaurant_id = f.restaurant_id
     JOIN destinations d ON d.destination_id = r.destination_id
     ORDER BY f.item_rating DESC LIMIT 6"
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container">
    <span class="eyebrow">Weather Planner</span>
    <h2>Let the sky help you plan</h2>
    <p style="color:var(--ink-soft); max-width:70ch;">
      Pick a destination and a travel date to see the live forecast and get a clear go / wait recommendation
      before you book a ticket. Below that, browse today's best-weather destinations and weather-matched food picks.
    </p>

    <div class="weather-tool" style="margin-top:30px;">
      <div class="weather-form">
        <form id="weatherForm">
          <div class="field">
            <label for="destinationSelect">Destination</label>
            <select id="destinationSelect" required>
              <option value="">Select a destination…</option>
              <?php foreach ($destinations as $d): ?>
                <option value="<?= $d['destination_id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="travelDate">Planned travel date</label>
           <input type="date" id="travelDate" min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+90 days')) ?>" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Check forecast &amp; get advice</button>
        </form>
      </div>
      <div id="weatherResult">
        <div class="info-note">Choose a destination and date to see the forecast strip and a travel recommendation here.</div>
      </div>
    </div>
  </div>
</section>

<section class="section bg-alt">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Today's ranking</span>
        <h2>Best weather right now</h2>
        <p>Destinations sorted by today's travel score — high scores mean clear skies, low rain risk, and calm conditions.</p>
      </div>
    </div>

    <table class="data-table">
      <thead>
        <tr><th>Destination</th><th>Category</th><th>Region</th><th>Condition</th><th>Rain %</th><th>Score</th></tr>
      </thead>
      <tbody>
        <?php foreach ($ranked as $r): ?>
        <tr>
          <td><a href="/destination_details.php?id=<?= $r['destination_id'] ?>"><?= htmlspecialchars($r['name']) ?></a></td>
          <td><?= $r['icon'] ?> <?= htmlspecialchars($r['category_name']) ?></td>
          <td><?= htmlspecialchars($r['district_name']) ?></td>
          <td><?= htmlspecialchars($r['condition_main'] ?? '—') ?></td>
          <td><?= $r['rain_probability'] !== null ? $r['rain_probability'] . '%' : '—' ?></td>
          <td>
            <?php if ($r['weather_score'] !== null): $lbl = weatherScoreLabel((int)$r['weather_score']); ?>
              <span class="weather-chip <?= $lbl['class'] ?>"><?= $r['weather_score'] ?>/100 · <?= $lbl['label'] ?></span>
            <?php else: ?>
              <span class="weather-chip score-good">Not fetched yet</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php
    $rainySpots = array_filter($ranked, fn($r) => $r['weather_score'] !== null && $r['weather_score'] < 50);
    $clearSpots = array_filter($ranked, fn($r) => $r['weather_score'] !== null && $r['weather_score'] >= 70);
    if (!empty($rainySpots) && !empty($clearSpots)):
    ?>
    <div class="info-note" style="margin-top:20px;">
      🌧️ Rain expected today around <strong><?= implode(', ', array_map(fn($r) => $r['name'], array_slice($rainySpots, 0, 3))) ?></strong> —
      consider <strong><?= implode(', ', array_map(fn($r) => $r['name'], array_slice($clearSpots, 0, 3))) ?></strong> instead.
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Weather-matched food</span>
        <h2>What to eat in this weather</h2>
        <p>Restaurant picks tagged by the kind of weather they suit best, with direction links.</p>
      </div>
    </div>
    <div class="destination-grid">
      <?php foreach ($foodSuggestions as $f): ?>
      <div class="dest-card">
        <div class="dest-body">
          <span class="dest-tag" style="position:static; display:inline-block; margin-bottom:10px;"><?= htmlspecialchars($f['suitable_weather']) ?> weather</span>
          <h3><?= htmlspecialchars($f['item_name']) ?></h3>
          <div class="dest-loc">🍽️ <?= htmlspecialchars($f['restaurant_name']) ?> · <?= htmlspecialchars($f['destination_name']) ?></div>
          <div class="dest-meta">
            <span>⭐ <?= number_format($f['item_rating'], 1) ?></span>
            <span>৳<?= number_format($f['price']) ?></span>
          </div>
          <?php if ($f['map_link']): ?>
            <a href="<?= htmlspecialchars($f['map_link']) ?>" target="_blank" class="btn btn-ghost btn-block btn-sm">Get directions</a>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>