<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Booking History';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
$userId = $_SESSION['user_id'];

$tickets = $pdo->prepare(
    "SELECT tb.*, r.origin, t.transport_type, d.name AS destination_name
     FROM ticket_bookings tb
     JOIN transport_routes r ON r.route_id = tb.route_id
     JOIN transport t ON t.transport_id = r.transport_id
     JOIN destinations d ON d.destination_id = r.destination_id
     WHERE tb.user_id = ? ORDER BY tb.booked_at DESC"
);
$tickets->execute([$userId]);
$tickets = $tickets->fetchAll();

$hotelBookings = $pdo->prepare(
    "SELECT hb.*, hr.room_type, h.hotel_name, d.name AS destination_name
     FROM hotel_bookings hb
     JOIN hotel_rooms hr ON hr.room_id = hb.room_id
     JOIN hotels h ON h.hotel_id = hr.hotel_id
     JOIN destinations d ON d.destination_id = h.destination_id
     WHERE hb.user_id = ? ORDER BY hb.booked_at DESC"
);
$hotelBookings->execute([$userId]);
$hotelBookings = $hotelBookings->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="section-tight">
  <div class="container">
    <span class="eyebrow">Your trips</span>
    <h2>Booking history</h2>

    <h3 style="margin-top:28px;">🎟️ Ticket bookings</h3>
    <?php if (empty($tickets)): ?>
      <div class="info-note">No ticket bookings yet. <a href="/ticket_booking.php">Book one now →</a></div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Route</th><th>Date</th><th>Seats</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($tickets as $t): ?>
          <tr>
            <td><?= htmlspecialchars($t['transport_type']) ?>: <?= htmlspecialchars($t['origin']) ?> → <?= htmlspecialchars($t['destination_name']) ?></td>
            <td><?= htmlspecialchars($t['travel_date']) ?></td>
            <td><?= (int) $t['seats'] ?></td>
            <td>৳<?= number_format($t['total_price']) ?></td>
            <td><?= htmlspecialchars($t['status']) ?><?= $t['weather_warning_shown'] ? ' ⚠️' : '' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <h3 style="margin-top:36px;">🏨 Hotel bookings</h3>
    <?php if (empty($hotelBookings)): ?>
      <div class="info-note">No hotel bookings yet. <a href="/hotels.php">Browse hotels →</a></div>
    <?php else: ?>
      <table class="data-table">
        <thead><tr><th>Hotel</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Guests</th><th>Total</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($hotelBookings as $h): ?>
          <tr>
            <td><?= htmlspecialchars($h['hotel_name']) ?> (<?= htmlspecialchars($h['destination_name']) ?>)</td>
            <td><?= htmlspecialchars($h['room_type']) ?></td>
            <td><?= htmlspecialchars($h['check_in']) ?></td>
            <td><?= htmlspecialchars($h['check_out']) ?></td>
            <td><?= (int) $h['guests'] ?></td>
            <td>৳<?= number_format($h['total_price']) ?></td>
            <td><?= htmlspecialchars($h['status']) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
