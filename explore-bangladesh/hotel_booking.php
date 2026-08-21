<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Book a Room';

$hotelId = isset($_GET['hotel']) ? (int) $_GET['hotel'] : (isset($_POST['hotel_id']) ? (int) $_POST['hotel_id'] : 0);
$error = '';
$success = '';

$hotelStmt = $pdo->prepare(
    "SELECT h.*, d.name AS destination_name FROM hotels h JOIN destinations d ON d.destination_id = h.destination_id WHERE h.hotel_id = ?"
);
$hotelStmt->execute([$hotelId]);
$hotel = $hotelStmt->fetch();

$rooms = [];
if ($hotel) {
    $roomStmt = $pdo->prepare("SELECT * FROM hotel_rooms WHERE hotel_id = ? AND available_rooms > 0");
    $roomStmt->execute([$hotelId]);
    $rooms = $roomStmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $roomId = (int) $_POST['room_id'];
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $guests = max(1, (int) $_POST['guests']);

    $roomStmt = $pdo->prepare("SELECT * FROM hotel_rooms WHERE room_id = ? AND available_rooms > 0");
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch();

    if (!$room) {
        $error = 'That room is no longer available.';
    } elseif (strtotime($checkOut) <= strtotime($checkIn)) {
        $error = 'Check-out date must be after check-in date.';
    } else {
        $nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
        $total = $room['price'] * $nights;

        $pdo->beginTransaction();
        $ins = $pdo->prepare(
            "INSERT INTO hotel_bookings (user_id, room_id, check_in, check_out, guests, total_price, status)
             VALUES (?, ?, ?, ?, ?, ?, 'Confirmed')"
        );
        $ins->execute([$_SESSION['user_id'], $roomId, $checkIn, $checkOut, $guests, $total]);
        $upd = $pdo->prepare("UPDATE hotel_rooms SET available_rooms = available_rooms - 1 WHERE room_id = ?");
        $upd->execute([$roomId]);
        $pdo->commit();

        $success = "Room booked for $nights night(s) — total ৳" . number_format($total) . '.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container" style="max-width:760px;">
    <span class="eyebrow">Stay</span>
    <h2>Book a room</h2>

    <?php if ($success): ?>
      <div class="advice-box"><h4><?= htmlspecialchars($success) ?></h4>
        <a href="/booking_history.php" class="btn btn-forest btn-sm" style="margin-top:10px;">View booking history</a>
      </div>
    <?php elseif (!$hotel): ?>
      <div class="info-note">Choose a hotel from the <a href="/hotels.php">hotels page</a> first.</div>
    <?php else: ?>
      <?php if ($error): ?><div class="advice-box warn"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <div class="info-note" style="margin-top:10px;">
        <strong><?= htmlspecialchars($hotel['hotel_name']) ?></strong> · <?= htmlspecialchars($hotel['destination_name']) ?> ·
        ⭐ <?= number_format($hotel['rating'], 1) ?>
        <?= $hotel['free_breakfast'] ? ' · 🍳 Free breakfast' : '' ?><?= $hotel['swimming_pool'] ? ' · 🏊 Pool' : '' ?>
      </div>

      <?php if (empty($rooms)): ?>
        <div class="info-note" style="margin-top:16px;">No rooms currently available at this hotel.</div>
      <?php else: ?>
        <form method="post" class="weather-form" style="margin-top:20px;">
          <input type="hidden" name="hotel_id" value="<?= $hotel['hotel_id'] ?>">
          <div class="field">
            <label>Room type</label>
            <select name="room_id" required>
              <?php foreach ($rooms as $r): ?>
                <option value="<?= $r['room_id'] ?>"><?= htmlspecialchars($r['room_type']) ?> — ৳<?= number_format($r['price']) ?>/night (<?= $r['available_rooms'] ?> left)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label>Check-in</label>
            <input type="date" name="check_in" min="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="field">
            <label>Check-out</label>
            <input type="date" name="check_out" min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
          </div>
          <div class="field">
            <label>Guests</label>
            <input type="number" name="guests" min="1" max="10" value="2">
          </div>
          <button type="submit" name="confirm_booking" value="1" class="btn btn-primary btn-block">Confirm booking</button>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
