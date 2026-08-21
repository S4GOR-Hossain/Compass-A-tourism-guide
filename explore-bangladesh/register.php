<?php
require_once __DIR__ . '/config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Sign up';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name && $email && strlen($password) >= 6) {
        $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (full_name, email, password, phone) VALUES (?, ?, ?, ?)");
            $ins->execute([$name, $email, $hash, $phone]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            header('Location: /index.php');
            exit;
        }
    } else {
        $error = 'Please fill all fields — password must be at least 6 characters.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<section class="section-tight">
  <div class="container" style="max-width:440px;">
    <span class="eyebrow">Join Explore Bangladesh</span>
    <h2>Create your account</h2>
    <?php if ($error): ?><div class="advice-box warn"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="weather-form" style="margin-top:20px;">
      <div class="field"><label>Full name</label><input type="text" name="full_name" required></div>
      <div class="field"><label>Email</label><input type="email" name="email" required></div>
      <div class="field"><label>Phone</label><input type="text" name="phone"></div>
      <div class="field"><label>Password</label><input type="password" name="password" required minlength="6"></div>
      <button type="submit" class="btn btn-primary btn-block">Sign up</button>
    </form>
    <p style="margin-top:16px; font-size:.9rem;">Already have an account? <a href="/login.php" style="color:var(--river-dark); font-weight:600;">Log in</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
