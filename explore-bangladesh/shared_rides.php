<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Share a Ride';

$destinations = $pdo->query("SELECT destination_id, name FROM destinations ORDER BY name")->fetchAll();
$destinationId = isset($_GET['destination']) ? (int) $_GET['destination'] : ($destinations[0]['destination_id'] ?? 0);
$message = '';

// Create a new ride
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ride'])) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $destId = (int) $_POST['destination_id'];
    $pickup = trim($_POST['pickup_point']);
    $drop = trim($_POST['drop_point']);
    $when = $_POST['ride_datetime'];
    $fare = (float) $_POST['total_fare'];
    $seats = max(2, (int) $_POST['seats_total']);

    $ins = $pdo->prepare(
        "INSERT INTO shared_rides (destination_id, created_by, pickup_point, drop_point, ride_datetime, total_fare, seats_total, seats_taken)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
    );
    $ins->execute([$destId, $_SESSION['user_id'], $pickup, $drop, $when, $fare, $seats]);

    // Creator auto-joins as the first (already-accepted) member
    $rideId = $pdo->lastInsertId();
    $pdo->prepare("INSERT INTO shared_ride_members (ride_id, user_id, status) VALUES (?, ?, 'Accepted')")
        ->execute([$rideId, $_SESSION['user_id']]);

    $message = 'Ride posted! Others can now request to join and split the fare with you.';
    $destinationId = $destId;
}

// Tourist requests to join an existing ride (owner must accept/reject it — see below)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_ride'])) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $rideId = (int) $_POST['ride_id'];

    $rideStmt = $pdo->prepare("SELECT created_by, status FROM shared_rides WHERE ride_id = ?");
    $rideStmt->execute([$rideId]);
    $ride = $rideStmt->fetch();

    $check = $pdo->prepare("SELECT status FROM shared_ride_members WHERE ride_id = ? AND user_id = ?");
    $check->execute([$rideId, $_SESSION['user_id']]);
    $existing = $check->fetch();

    if (!$ride) {
        $message = "That ride doesn't exist anymore.";
    } elseif ((int) $ride['created_by'] === (int) $_SESSION['user_id']) {
        $message = "That's your own ride — no need to request to join it.";
    } elseif ($ride['status'] === 'Full') {
        $message = 'Sorry, this ride is already full.';
    } elseif ($existing) {
        $message = $existing['status'] === 'Pending'
            ? 'You already requested to join this ride — waiting for the owner to respond.'
            : 'You already have a status on this ride.';
    } else {
        $pdo->prepare("INSERT INTO shared_ride_members (ride_id, user_id, status) VALUES (?, ?, 'Pending')")
            ->execute([$rideId, $_SESSION['user_id']]);
        $message = 'Request sent! The ride owner will accept or decline it.';
    }
}

// Ride owner accepts a join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_request'])) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $rideId = (int) $_POST['ride_id'];
    $reqUserId = (int) $_POST['request_user_id'];

    $own = $pdo->prepare("SELECT * FROM shared_rides WHERE ride_id = ? AND created_by = ?");
    $own->execute([$rideId, $_SESSION['user_id']]);
    $ride = $own->fetch();

    if (!$ride) {
        $message = 'You can only manage requests on rides you posted.';
    } elseif ($ride['seats_taken'] >= $ride['seats_total']) {
        $message = 'This ride is already full.';
    } else {
        $pdo->prepare("UPDATE shared_ride_members SET status = 'Accepted' WHERE ride_id = ? AND user_id = ? AND status = 'Pending'")
            ->execute([$rideId, $reqUserId]);
        $pdo->prepare("UPDATE shared_rides SET seats_taken = seats_taken + 1 WHERE ride_id = ?")->execute([$rideId]);
        $pdo->prepare("UPDATE shared_rides SET status = 'Full' WHERE ride_id = ? AND seats_taken >= seats_total")->execute([$rideId]);
        // Once the ride is full, auto-decline anyone still waiting so they aren't left hanging.
        $pdo->prepare(
            "UPDATE shared_ride_members srm
             JOIN shared_rides sr ON sr.ride_id = srm.ride_id
             SET srm.status = 'Rejected'
             WHERE srm.ride_id = ? AND srm.status = 'Pending' AND sr.seats_taken >= sr.seats_total"
        )->execute([$rideId]);
        $message = 'Request accepted — seat confirmed.';
    }
}

// Ride owner rejects a join request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_request'])) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $rideId = (int) $_POST['ride_id'];
    $reqUserId = (int) $_POST['request_user_id'];

    $stmt = $pdo->prepare(
        "UPDATE shared_ride_members srm
         JOIN shared_rides sr ON sr.ride_id = srm.ride_id
         SET srm.status = 'Rejected'
         WHERE srm.ride_id = ? AND srm.user_id = ? AND sr.created_by = ? AND srm.status = 'Pending'"
    );
    $stmt->execute([$rideId, $reqUserId, $_SESSION['user_id']]);
    $message = $stmt->rowCount() ? 'Request declined.' : 'Could not find that request.';
}

// Ride owner cancels/deletes their own ride
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_ride'])) {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
    $rideId = (int) $_POST['ride_id'];
    $stmt = $pdo->prepare("UPDATE shared_rides SET status = 'Cancelled' WHERE ride_id = ? AND created_by = ?");
    $stmt->execute([$rideId, $_SESSION['user_id']]);
    $message = $stmt->rowCount() ? 'Ride cancelled.' : 'You can only cancel rides you posted.';
}

$rides = $pdo->prepare(
    "SELECT sr.*, u.full_name AS creator_name
     FROM shared_rides sr JOIN users u ON u.user_id = sr.created_by
     WHERE sr.destination_id = ? AND sr.ride_datetime >= NOW() AND sr.status != 'Cancelled'
     ORDER BY sr.ride_datetime ASC"
);
$rides->execute([$destinationId]);
$rides = $rides->fetchAll();

// Current user's request/membership status on each ride shown above.
$myMemberships = [];
if (!empty($_SESSION['user_id']) && !empty($rides)) {
    $ids = array_column($rides, 'ride_id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT ride_id, status FROM shared_ride_members WHERE user_id = ? AND ride_id IN ($placeholders)");
    $stmt->execute(array_merge([$_SESSION['user_id']], $ids));
    foreach ($stmt->fetchAll() as $row) {
        $myMemberships[$row['ride_id']] = $row['status'];
    }
}

// Pending join requests on rides the current user owns, so they can accept/reject — like Uber/Pathao.
$pendingByRide = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare(
        "SELECT srm.ride_id, srm.user_id, u.full_name, srm.joined_at
         FROM shared_ride_members srm
         JOIN shared_rides sr ON sr.ride_id = srm.ride_id
         JOIN users u ON u.user_id = srm.user_id
         WHERE sr.created_by = ? AND sr.destination_id = ? AND srm.status = 'Pending'
         ORDER BY srm.joined_at ASC"
    );
    $stmt->execute([$_SESSION['user_id'], $destinationId]);
    foreach ($stmt->fetchAll() as $row) {
        $pendingByRide[$row['ride_id']][] = $row;
    }
}

include __DIR__ . '/includes/header.php';
?>

<section class="section-tight">
  <div class="container">
    <span class="eyebrow">Split the fare</span>
    <h2>Share a ride, split the cost</h2>
    <p style="color:var(--ink-soft); max-width:60ch;">
      Going from the hotel to the beach, or into town? Post your ride and let other tourists request to join —
      more people means a lower fare for everyone. You approve who joins, just like a ride-share request.
    </p>

    <?php if ($message): ?><div class="advice-box"><?= htmlspecialchars($message) ?></div><?php endif; ?>

    <form method="get" style="margin:24px 0;">
      <select name="destination" onchange="this.form.submit()" class="filter-chip" style="padding:.7em 1.4em;">
        <?php foreach ($destinations as $d): ?>
          <option value="<?= $d['destination_id'] ?>" <?= $destinationId === (int)$d['destination_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>

    <div class="two-col">
      <div>
        <h3>Open rides</h3>
        <?php if (empty($rides)): ?>
          <div class="info-note">No open rides here yet — be the first to post one.</div>
        <?php else: ?>
          <?php foreach ($rides as $r):
              // ceil() so the collected total never falls short of total_fare (round() could
              // round down and leave the group under-paying the driver).
              $perHead = $r['seats_taken'] > 0 ? (int) ceil($r['total_fare'] / $r['seats_taken']) : (int) ceil($r['total_fare']);
              $perHeadFull = (int) ceil($r['total_fare'] / max(1, (int) $r['seats_total']));
              $isOwner = !empty($_SESSION['user_id']) && (int) $r['created_by'] === (int) $_SESSION['user_id'];
              $myStatus = $myMemberships[$r['ride_id']] ?? null;
          ?>
          <div class="dest-card" style="margin-bottom:16px;">
            <div class="dest-body">
              <h3><?= htmlspecialchars($r['pickup_point']) ?> → <?= htmlspecialchars($r['drop_point']) ?></h3>
              <div class="dest-loc">🕒 <?= date('D, j M · g:i A', strtotime($r['ride_datetime'])) ?> · Posted by <?= htmlspecialchars($r['creator_name']) ?></div>
              <div class="dest-meta">
                <span>
                  ৳<?= number_format($r['total_fare']) ?> total → ৳<?= number_format($perHead) ?>/person now
                  <?php if ($r['seats_taken'] < $r['seats_total']): ?>
                    (৳<?= number_format($perHeadFull) ?>/person if it fills up)
                  <?php endif; ?>
                </span>
                <span class="weather-chip score-good"><?= $r['seats_taken'] ?>/<?= $r['seats_total'] ?> seats</span>
              </div>

              <?php if ($isOwner): ?>
                <div class="info-note" style="margin:10px 0; font-size:.82rem;">This is your ride.</div>

                <?php if (!empty($pendingByRide[$r['ride_id']])): ?>
                  <div style="margin:10px 0;">
                    <strong style="font-size:.82rem;">Join requests</strong>
                    <?php foreach ($pendingByRide[$r['ride_id']] as $req): ?>
                      <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:6px;">
                        <span style="font-size:.85rem;"><?= htmlspecialchars($req['full_name']) ?></span>
                        <span style="display:flex; gap:6px;">
                          <form method="post" style="display:inline;">
                            <input type="hidden" name="ride_id" value="<?= $r['ride_id'] ?>">
                            <input type="hidden" name="request_user_id" value="<?= $req['user_id'] ?>">
                            <button type="submit" name="accept_request" value="1" class="btn btn-forest btn-sm">Accept</button>
                          </form>
                          <form method="post" style="display:inline;">
                            <input type="hidden" name="ride_id" value="<?= $r['ride_id'] ?>">
                            <input type="hidden" name="request_user_id" value="<?= $req['user_id'] ?>">
                            <button type="submit" name="reject_request" value="1" class="btn btn-ghost btn-sm">Reject</button>
                          </form>
                        </span>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <form method="post" onsubmit="return confirm('Cancel this ride? This cannot be undone.');">
                  <input type="hidden" name="ride_id" value="<?= $r['ride_id'] ?>">
                  <button type="submit" name="cancel_ride" value="1" class="btn btn-ghost btn-block btn-sm">Cancel ride</button>
                </form>

              <?php elseif ($r['status'] === 'Full'): ?>
                <span class="btn btn-ghost btn-block btn-sm" style="pointer-events:none;">Ride full</span>

              <?php elseif ($myStatus === 'Accepted'): ?>
                <span class="btn btn-ghost btn-block btn-sm" style="pointer-events:none;">✓ You're in this ride</span>

              <?php elseif ($myStatus === 'Pending'): ?>
                <span class="btn btn-ghost btn-block btn-sm" style="pointer-events:none;">Request sent — waiting for approval</span>

              <?php elseif ($myStatus === 'Rejected'): ?>
                <span class="btn btn-ghost btn-block btn-sm" style="pointer-events:none;">Request declined</span>

              <?php else: ?>
                <form method="post">
                  <input type="hidden" name="ride_id" value="<?= $r['ride_id'] ?>">
                  <button type="submit" name="join_ride" value="1" class="btn btn-forest btn-block btn-sm">Request to join</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div>
        <h3>Post a new ride</h3>
        <?php if (empty($_SESSION['user_id'])): ?>
          <div class="info-note">Please <a href="/login.php">log in</a> to post a ride.</div>
        <?php else: ?>
        <form method="post" class="weather-form">
          <input type="hidden" name="destination_id" value="<?= $destinationId ?>">
          <div class="field"><label>Pickup point</label><input type="text" name="pickup_point" placeholder="e.g. Sayeman Beach Resort" required></div>
          <div class="field"><label>Drop point</label><input type="text" name="drop_point" placeholder="e.g. Himchari Beach" required></div>
          <div class="field"><label>Date &amp; time</label><input type="datetime-local" name="ride_datetime" required></div>
          <div class="field"><label>Total fare (৳)</label><input type="number" name="total_fare" min="1" required></div>
          <div class="field"><label>Total seats (incl. you)</label><input type="number" name="seats_total" min="2" max="8" value="4"></div>
          <button type="submit" name="create_ride" value="1" class="btn btn-primary btn-block">Post ride</button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>