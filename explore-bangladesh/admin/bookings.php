<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Manage Bookings';
$message = '';

$tab = $_GET['tab'] ?? 'tickets';

if (isset($_GET['ticket_status'], $_GET['id'])) {
    $status = $_GET['ticket_status'];
    if (in_array($status, ['Pending', 'Confirmed', 'Cancelled'], true)) {
        $pdo->prepare("UPDATE ticket_bookings SET status = ? WHERE ticket_booking_id = ?")->execute([$status, (int) $_GET['id']]);
        $message = 'Ticket booking updated.';
    }
}
if (isset($_GET['hotel_status'], $_GET['id']) && $tab === 'hotels') {
    $status = $_GET['hotel_status'];
    if (in_array($status, ['Pending', 'Confirmed', 'Cancelled'], true)) {
        $pdo->prepare("UPDATE hotel_bookings SET status = ? WHERE booking_id = ?")->execute([$status, (int) $_GET['id']]);
        $message = 'Hotel booking updated.';
    }
}

$tickets = $pdo->query(
    "SELECT tb.*, u.full_name, u.email, d.name AS destination_name, t.transport_type
     FROM ticket_bookings tb
     JOIN users u ON u.user_id = tb.user_id
     JOIN transport_routes r ON r.route_id = tb.route_id
     JOIN transport t ON t.transport_id = r.transport_id
     JOIN destinations d ON d.destination_id = r.destination_id
     ORDER BY tb.booked_at DESC"
)->fetchAll();

$hotelBookings = $pdo->query(
    "SELECT hb.*, u.full_name, u.email, h.hotel_name, d.name AS destination_name
     FROM hotel_bookings hb
     JOIN users u ON u.user_id = hb.user_id
     JOIN hotel_rooms hr ON hr.room_id = hb.room_id
     JOIN hotels h ON h.hotel_id = hr.hotel_id
     JOIN destinations d ON d.destination_id = h.destination_id
     ORDER BY hb.booked_at DESC"
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="admin-topbar">
  <div><span class="eyebrow">Operations</span><h2 style="margin:0;">Bookings</h2></div>
</div>

<?php if ($message): ?><div class="advice-box"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="filter-bar">
  <a href="/admin/bookings.php?tab=tickets" class="filter-chip <?= $tab === 'tickets' ? 'active' : '' ?>">🎟️ Tickets (<?= count($tickets) ?>)</a>
  <a href="/admin/bookings.php?tab=hotels" class="filter-chip <?= $tab === 'hotels' ? 'active' : '' ?>">🏨 Hotels (<?= count($hotelBookings) ?>)</a>
</div>

<?php if ($tab === 'tickets'): ?>
  <?php if (empty($tickets)): ?>
    <div class="info-note">No ticket bookings yet.</div>
  <?php else: ?>
  <table class="data-table">
    <thead><tr><th>Traveler</th><th>Route</th><th>Date</th><th>Seats</th><th>Total</th><th>Weather warned</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($tickets as $t): ?>
      <tr>
        <td><?= htmlspecialchars($t['full_name']) ?></td>
        <td><?= htmlspecialchars($t['transport_type']) ?> → <?= htmlspecialchars($t['destination_name']) ?></td>
        <td><?= htmlspecialchars($t['travel_date']) ?></td>
        <td><?= (int) $t['seats'] ?></td>
        <td>৳<?= number_format($t['total_price']) ?></td>
        <td><?= $t['weather_warning_shown'] ? '⚠️ Yes' : '—' ?></td>
        <td><?= htmlspecialchars($t['status']) ?></td>
        <td>
          <?php if ($t['status'] !== 'Confirmed'): ?><a href="/admin/bookings.php?tab=tickets&id=<?= $t['ticket_booking_id'] ?>&ticket_status=Confirmed" class="btn btn-forest btn-sm">Confirm</a><?php endif; ?>
          <?php if ($t['status'] !== 'Cancelled'): ?><a href="/admin/bookings.php?tab=tickets&id=<?= $t['ticket_booking_id'] ?>&ticket_status=Cancelled" class="btn btn-ghost btn-sm">Cancel</a><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
<?php else: ?>
  <?php if (empty($hotelBookings)): ?>
    <div class="info-note">No hotel bookings yet.</div>
  <?php else: ?>
  <table class="data-table">
    <thead><tr><th>Guest</th><th>Hotel</th><th>Check-in</th><th>Check-out</th><th>Guests</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($hotelBookings as $h): ?>
      <tr>
        <td><?= htmlspecialchars($h['full_name']) ?></td>
        <td><?= htmlspecialchars($h['hotel_name']) ?> (<?= htmlspecialchars($h['destination_name']) ?>)</td>
        <td><?= htmlspecialchars($h['check_in']) ?></td>
        <td><?= htmlspecialchars($h['check_out']) ?></td>
        <td><?= (int) $h['guests'] ?></td>
        <td>৳<?= number_format($h['total_price']) ?></td>
        <td><?= htmlspecialchars($h['status']) ?></td>
        <td>
          <?php if ($h['status'] !== 'Confirmed'): ?><a href="/admin/bookings.php?tab=hotels&id=<?= $h['booking_id'] ?>&hotel_status=Confirmed" class="btn btn-forest btn-sm">Confirm</a><?php endif; ?>
          <?php if ($h['status'] !== 'Cancelled'): ?><a href="/admin/bookings.php?tab=hotels&id=<?= $h['booking_id'] ?>&hotel_status=Cancelled" class="btn btn-ghost btn-sm">Cancel</a><?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
