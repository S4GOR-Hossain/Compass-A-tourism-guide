<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/weather_api.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Book a Ticket';

$routeId = isset($_GET['route']) ? (int) $_GET['route'] : (isset($_POST['route_id']) ? (int) $_POST['route_id'] : 0);
$error = '';
$success = '';

$routes = $pdo->query(
    "SELECT r.route_id, r.origin, r.stop_over, r.estimated_time, r.estimated_cost, r.schedule_info,
            t.transport_type, t.operator_name, d.destination_id, d.name AS destination_name,
            d.latitude, d.longitude
     FROM transport_routes r
     JOIN transport t ON t.transport_id = r.transport_id
     JOIN destinations d ON d.destination_id = r.destination_id
     ORDER BY d.name"
)->fetchAll();

$selectedRoute = null;
foreach ($routes as $r) {
    if ((int) $r['route_id'] === $routeId) { $selectedRoute = $r; break; }
}

$advice = null;
$travelDate = $_POST['travel_date'] ?? $_GET['date'] ?? '';

if ($selectedRoute && $travelDate) {
    $advice = getTravelAdvice(
        $pdo,
        (int) $selectedRoute['destination_id'],
        (float) $selectedRoute['latitude'],
        (float) $selectedRoute['longitude'],
        $travelDate
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $seats = max(1, (int) ($_POST['seats'] ?? 1));
    $cost = $selectedRoute ? $selectedRoute['estimated_cost'] * $seats : 0;
    $weatherWarned = ($advice && $advice['found'] && $advice['suggest_alternate']) ? 1 : 0;

    $ins = $pdo->prepare(
        "INSERT INTO ticket_bookings (user_id, route_id, travel_date, seats, total_price, status, weather_warning_shown)
         VALUES (?, ?, ?, ?, ?, 'Confirmed', ?)"
    );
    $ins->execute([$_SESSION['user_id'], $routeId, $travelDate, $seats, $cost, $weatherWarned]);
    $success = 'Ticket booked! Check your booking history for details.';
}

include __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container" style="max-width:760px;">
    <span class="eyebrow">Transport</span>
    <h2>Book a ticket</h2>

    <?php if ($success): ?>
      <div class="advice-box"><h4><?= htmlspecialchars($success) ?></h4>
        <a href="/booking_history.php" class="btn btn-forest btn-sm" style="margin-top:10px;">View booking history</a>
      </div>
    <?php else: ?>

    <form method="get" class="weather-form" id="routeForm">
      <div class="field">
        <label>Route</label>
        <select name="route" onchange="this.form.submit()" required>
          <option value="">Select a route…</option>
          <?php foreach ($routes as $r): ?>
            <option value="<?= $r['route_id'] ?>" <?= $routeId === (int)$r['route_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($r['transport_type']) ?> — <?= htmlspecialchars($r['origin']) ?> → <?= htmlspecialchars($r['destination_name']) ?> (৳<?= number_format($r['estimated_cost']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <?php if ($selectedRoute): ?>
      <div class="info-note" style="margin-top:18px;">
        <strong><?= htmlspecialchars($selectedRoute['operator_name']) ?></strong> ·
        <?= htmlspecialchars($selectedRoute['origin']) ?><?= $selectedRoute['stop_over'] ? ' → ' . htmlspecialchars($selectedRoute['stop_over']) : '' ?> →
        <?= htmlspecialchars($selectedRoute['destination_name']) ?> ·
        <?= htmlspecialchars($selectedRoute['estimated_time']) ?> ·
        ৳<?= number_format($selectedRoute['estimated_cost']) ?>/seat ·
        <?= htmlspecialchars($selectedRoute['schedule_info']) ?>
      </div>

      <form method="post" class="weather-form" style="margin-top:20px;">
        <input type="hidden" name="route_id" value="<?= $selectedRoute['route_id'] ?>">
        <div class="field">
          <label>Travel date</label>
          <input type="date" name="travel_date" value="<?= htmlspecialchars($travelDate) ?>"
                 <input type="date" name="travel_date" value="<?= htmlspecialchars($travelDate) ?>"
       min="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d', strtotime('+90 days')) ?>" onchange="this.form.submit()" required>
        </div>

        <?php if ($advice && $advice['found']): ?>
          <div class="<?= $advice['suggest_alternate'] ? 'advice-box warn' : 'advice-box' ?>">
            <h4><?= htmlspecialchars($advice['advice']['label']) ?></h4>
            <p style="margin-bottom:0;">
              <?= htmlspecialchars($advice['day']['condition_main']) ?>, <?= $advice['day']['temp_min'] ?>°–<?= $advice['day']['temp_max'] ?>°C,
              <?= $advice['day']['rain_probability'] ?>% rain chance on <?= htmlspecialchars($travelDate) ?>.
              <?= $advice['suggest_alternate'] ? ' We recommend picking a different date if possible.' : ' Good conditions for this trip.' ?>
            </p>
          </div>
        <?php elseif ($travelDate): ?>
          <div class="info-note">No cached forecast for this date yet — add your OpenWeatherMap key in <code>config/weather_api.php</code> for live advice.</div>
        <?php endif; ?>

        <div class="field" style="margin-top:16px;">
          <label>Seats</label>
          <input type="number" name="seats" min="1" max="10" value="1">
        </div>

        <button type="submit" name="confirm_booking" value="1" class="btn btn-primary btn-block">
          <?= ($advice && $advice['suggest_alternate']) ? 'Book anyway' : 'Confirm booking' ?>
        </button>
      </form>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
