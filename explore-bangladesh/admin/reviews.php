<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Manage Reviews';
$message = '';

if (isset($_GET['verify'])) {
    $pdo->prepare("UPDATE reviews SET is_verified = 1 WHERE review_id = ?")->execute([(int) $_GET['verify']]);
    $message = 'Review marked as verified.';
}
if (isset($_GET['unverify'])) {
    $pdo->prepare("UPDATE reviews SET is_verified = 0 WHERE review_id = ?")->execute([(int) $_GET['unverify']]);
    $message = 'Review marked as unverified.';
}
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM reviews WHERE review_id = ?")->execute([(int) $_GET['delete']]);
    $message = 'Review deleted.';
}

$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT rv.*, u.full_name, u.email, d.name AS destination_name,
               (SELECT stars FROM ratings WHERE user_id = rv.user_id AND destination_id = rv.destination_id) AS stars
        FROM reviews rv
        JOIN users u ON u.user_id = rv.user_id
        JOIN destinations d ON d.destination_id = rv.destination_id";
if ($filter === 'pending') $sql .= " WHERE rv.is_verified = 0";
if ($filter === 'verified') $sql .= " WHERE rv.is_verified = 1";
$sql .= " ORDER BY rv.created_at DESC";

$reviews = $pdo->query($sql)->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<div class="admin-topbar">
  <div><span class="eyebrow">Moderation</span><h2 style="margin:0;">Reviews</h2></div>
</div>

<?php if ($message): ?><div class="advice-box"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="filter-bar">
  <a href="/admin/reviews.php?filter=all" class="filter-chip <?= $filter === 'all' ? 'active' : '' ?>">All</a>
  <a href="/admin/reviews.php?filter=pending" class="filter-chip <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
  <a href="/admin/reviews.php?filter=verified" class="filter-chip <?= $filter === 'verified' ? 'active' : '' ?>">Verified</a>
</div>

<?php if (empty($reviews)): ?>
  <div class="info-note">No reviews in this view.</div>
<?php else: ?>
<table class="data-table">
  <thead><tr><th>Traveler</th><th>Destination</th><th>Rating</th><th>Review</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach ($reviews as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['full_name']) ?><br><span style="font-size:.75rem; color:var(--ink-soft);"><?= htmlspecialchars($r['email']) ?></span></td>
      <td><?= htmlspecialchars($r['destination_name']) ?></td>
      <td><?= $r['stars'] ? str_repeat('⭐', (int)$r['stars']) : '—' ?></td>
      <td style="max-width:260px;"><?= htmlspecialchars($r['review_text']) ?></td>
      <td><?= $r['is_verified'] ? '✅ Verified' : '🕓 Pending' ?></td>
      <td>
        <?php if ($r['is_verified']): ?>
          <a href="/admin/reviews.php?unverify=<?= $r['review_id'] ?>&filter=<?= $filter ?>" class="btn btn-ghost btn-sm">Unverify</a>
        <?php else: ?>
          <a href="/admin/reviews.php?verify=<?= $r['review_id'] ?>&filter=<?= $filter ?>" class="btn btn-forest btn-sm">Verify</a>
        <?php endif; ?>
        <a href="/admin/reviews.php?delete=<?= $r['review_id'] ?>&filter=<?= $filter ?>" class="btn btn-ghost btn-sm" onclick="return confirm('Delete this review?')">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
