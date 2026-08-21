<?php
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$pageTitle = 'Admin Login';
$error = '';

if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: /admin/dashboard.php');
        exit;
    }
    $error = 'Invalid admin credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Explore Bangladesh</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/style.css">
</head>
<body style="background:var(--forest-dark); min-height:100vh; display:flex; align-items:center; justify-content:center;">
  <div style="width:100%; max-width:380px; background:#fff; border-radius:var(--radius-lg); padding:36px; box-shadow:var(--shadow-soft);">
    <div class="brand" style="justify-content:center; margin-bottom:20px;">
      <span class="brand-mark">EB</span>
      <span class="brand-text">Explore<em>Bangladesh</em></span>
    </div>
    <h2 style="text-align:center; font-size:1.3rem;">Admin Panel</h2>
    <?php if ($error): ?><div class="advice-box warn"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" class="weather-form">
      <div class="field"><label>Username</label><input type="text" name="username" required autofocus></div>
      <div class="field"><label>Password</label><input type="password" name="password" required></div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <p style="font-size:.78rem; color:var(--ink-soft); text-align:center; margin-top:16px;">
      Default seed user: <code>admin</code> — set a real password hash in <code>database.sql</code> before deploying.
    </p>
  </div>
</body>
</html>
