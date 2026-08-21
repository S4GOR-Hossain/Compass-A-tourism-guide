<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Explore Bangladesh</title>
<script>
  // Apply saved theme before first paint, to avoid a light-mode flash.
  (function () {
    try {
      var saved = localStorage.getItem('theme');
      var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (saved === 'dark' || (!saved && prefersDark)) {
        document.documentElement.setAttribute('data-theme', 'dark');
      }
    } catch (e) {}
  })();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/style.css">
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <a href="/index.php" class="brand">
      <span class="brand-mark">EB</span>
      <span class="brand-text">Explore<em>Bangladesh</em></span>
    </a>

    <nav class="main-nav" id="mainNav">
      <a href="/index.php">Home</a>
      <a href="/destinations.php">Destinations</a>
      <a href="/weather_suggestion.php">Weather Planner</a>
      <a href="/hotels.php">Stays</a>
      <a href="/shared_rides.php">Share a Ride</a>
      <a href="/favourites.php">Favourites</a>
      <?php if (!empty($_SESSION['user_id'])): ?><a href="/booking_history.php">My Bookings</a><?php endif; ?>
    </nav>

    <div class="header-actions">
      <button type="button" class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" aria-pressed="false">
        <span class="theme-toggle-icon" aria-hidden="true">🌙</span>
      </button>
      <?php if (!empty($_SESSION['user_id'])): ?>
        <span class="user-pill">👋 <?= htmlspecialchars($_SESSION['user_name'] ?? 'Traveler') ?></span>
        <a href="/logout.php" class="btn btn-ghost">Log out</a>
      <?php else: ?>
        <a href="/login.php" class="btn btn-ghost">Log in</a>
        <a href="/register.php" class="btn btn-primary">Sign up</a>
      <?php endif; ?>
    </div>

    <button class="nav-toggle" id="navToggle" aria-label="Toggle menu">☰</button>
  </div>
</header>