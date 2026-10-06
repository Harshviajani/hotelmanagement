<?php $title = $title ?? 'Hotel'; $f = flash(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= h($title) ?> - Hotel Booking</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<nav>
  <span class="brand">🏨 Hotel Booking</span>
  <a href="<?= BASE_URL ?>/index.php">Dashboard</a>
  <a href="<?= BASE_URL ?>/rooms/index.php">Rooms</a>
  <a href="<?= BASE_URL ?>/guests/index.php">Guests</a>
  <a href="<?= BASE_URL ?>/services/index.php">Services</a>
  <a href="<?= BASE_URL ?>/bookings/index.php">Bookings</a>
</nav>
<main>
<?php if ($f): ?><div class="alert <?= h($f['t']) ?>"><?= h($f['m']) ?></div><?php endif; ?>
