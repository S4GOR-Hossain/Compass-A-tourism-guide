<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Favourites';

if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT d.*, c.category_name, c.icon, dist.district_name
     FROM favourites f
     JOIN destinations d ON d.destination_id = f.destination_id
     JOIN categories c ON c.category_id = d.category_id
     JOIN districts dist ON dist.district_id = d.district_id
     WHERE f.user_id = ?
     ORDER BY f.saved_at DESC"
);
$stmt->execute([$_SESSION['user_id']]);
$favourites = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="section-tight">
  <div class="container">
    <span class="eyebrow">Saved</span>
    <h2>Your favourite destinations</h2>

    <?php if (empty($favourites)): ?>
      <div class="info-note" style="margin-top:20px;">You haven't saved anything yet. Browse <a href="/destinations.php">destinations</a> and tap the heart icon.</div>
    <?php else: ?>
    <div class="destination-grid" style="margin-top:30px;">
      <?php foreach ($favourites as $d): ?>
      <div class="dest-card">
        <div class="dest-media cat-<?= $d['category_id'] ?>">
          <?= $d['icon'] ?>
          <span class="dest-tag"><?= htmlspecialchars($d['category_name']) ?></span>
          <button class="fav-btn" data-dest-id="<?= $d['destination_id'] ?>">❤️</button>
        </div>
        <div class="dest-body">
          <h3><?= htmlspecialchars($d['name']) ?></h3>
          <div class="dest-loc">📍 <?= htmlspecialchars($d['district_name']) ?></div>
          <a href="/destination_details.php?id=<?= $d['destination_id'] ?>" class="btn btn-forest btn-block btn-sm">View details</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
