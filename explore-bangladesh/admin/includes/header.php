<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — ' : '' ?>Admin — Explore Bangladesh</title>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/css/style.css">
<style>
  body{background:var(--paper-dim);}
  .admin-shell{display:flex; min-height:100vh;}
  .admin-sidebar{width:230px; background:var(--forest-dark); color:#fff; padding:26px 20px; flex-shrink:0;}
  .admin-sidebar a{display:block; padding:.6em .8em; border-radius:8px; color:rgba(255,255,255,.85); font-weight:600; font-size:.92rem; margin-bottom:4px;}
  .admin-sidebar a:hover, .admin-sidebar a.active{background:rgba(255,255,255,.12); color:#fff;}
  .admin-main{flex:1; padding:36px 40px;}
  .admin-topbar{display:flex; justify-content:space-between; align-items:center; margin-bottom:28px;}
</style>
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="brand" style="color:#fff; margin-bottom:30px;">
      <span class="brand-mark">EB</span> <span>Admin</span>
    </div>
    <a href="/admin/dashboard.php">📊 Dashboard</a>
    <a href="/admin/destinations.php">🗺️ Destinations</a>
    <a href="/admin/hotels.php">🏨 Hotels</a>
    <a href="/admin/reviews.php">⭐ Reviews</a>
    <a href="/admin/bookings.php">🎟️ Bookings</a>
    <a href="/index.php" style="margin-top:24px; border-top:1px solid rgba(255,255,255,.15); padding-top:16px;">← Back to site</a>
    <a href="/admin/logout.php">Log out</a>
  </aside>
  <main class="admin-main">
