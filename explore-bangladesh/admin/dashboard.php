<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Dashboard';

$stats = [
    'destinations' => $pdo->query("SELECT COUNT(*) c FROM destinations")->fetch()['c'],
    'users'        => $pdo->query("SELECT COUNT(*) c FROM users")->fetch()['c'],
    'hotels'       => $pdo->query("SELECT COUNT(*) c FROM hotels")->fetch()['c'],
    'ticket_bookings' => $pdo->query("SELECT COUNT(*) c FROM ticket_bookings")->fetch()['c'],
    'hotel_bookings'  => $pdo->query("SELECT COUNT(*) c FROM hotel_bookings")->fetch()['c'],
    'reviews'      => $pdo->query("SELECT COUNT(*) c FROM reviews")->fetch()['c'],
    'pending_reviews' => $pdo->query("SELECT COUNT(*) c FROM reviews WHERE is_verified = 0")->fetch()['c'],
];

$recentTickets = $pdo->query(
    "SELECT tb.travel_date, tb.status, tb.total_price, u.full_name, d.name AS destination_name
     FROM ticket_bookings tb
     JOIN users u ON u.user_id = tb.user_id
     JOIN transport_routes r ON r.route_id = tb.route_id
     JOIN destinations d ON d.destination_id = r.destination_id
     ORDER BY tb.booked_at DESC LIMIT 5"
)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="admin-topbar">
  <div><span class="eyebrow">Overview</span><h2 style="margin:0;">Dashboard</h2></div>
  <span class="user-pill">👤 <?= htmlspecialchars($_SESSION['admin_username']) ?></span>
</div>

<div class="category-grid" style="grid-template-columns:repeat(4,1fr); margin-bottom:34px;">
  <div class="dest-card" style="padding:22px;"><div class="dest-body" style="padding:0;"><span style="font-size:.8rem; color:var(--ink-soft);">Destinations</span><h2 style="margin:6px 0 0;"><?= $stats['destinations'] ?></h2></div></div>
  <div class="dest-card" style="padding:22px;"><div class="dest-body" style="padding:0;"><span style="font-size:.8rem; color:var(--ink-soft);">Registered users</span><h2 style="margin:6px 0 0;"><?= $stats['users'] ?></h2></div></div>
  <div class="dest-card" style="padding:22px;"><div class="dest-body" style="padding:0;"><span style="font-size:.8rem; color:var(--ink-soft);">Hotels</span><h2 style="margin:6px 0 0;"><?= $stats['hotels'] ?></h2></div></div>
  <div class="dest-card" style="padding:22px;"><div class="dest-body" style="padding:0;"><span style="font-size:.8rem; color:var(--ink-soft);">Pending reviews</span><h2 style="margin:6px 0 0;"><?= $stats['pending_reviews'] ?></h2></div></div>
</div>

<h3>Recent ticket bookings</h3>
<?php if (empty($recentTickets)): ?>
  <div class="info-note">No bookings yet.</div>
<?php else: ?>
<table class="data-table">
  <thead><tr><th>Traveler</th><th>Destination</th><th>Date</th><th>Total</th><th>Status</th></tr></thead>
  <tbody>
  <?php foreach ($recentTickets as $t): ?>
    <tr>
      <td><?= htmlspecialchars($t['full_name']) ?></td>
      <td><?= htmlspecialchars($t['destination_name']) ?></td>
      <td><?= htmlspecialchars($t['travel_date']) ?></td>
      <td>৳<?= number_format($t['total_price']) ?></td>
      <td><?= htmlspecialchars($t['status']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
