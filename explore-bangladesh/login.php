<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Log in';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        header('Location: /index.php');
        exit;
    }
    $error = 'Invalid email or password.';
}

include __DIR__ . '/includes/header.php';
?>
<section class="section-tight">
  <div class="container" style="max-width:440px;">
    <span class="eyebrow">Welcome back</span>
    <h2>Log in</h2>
    <?php if ($error): ?><div class="advice-box warn"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="weather-form" style="margin-top:20px;">
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Password</label><input type="password" name="password" required></div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <p style="margin-top:16px; font-size:.9rem;">No account? <a href="/register.php" style="color:var(--river-dark); font-weight:600;">Sign up</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
