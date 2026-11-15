<?php $cartCount = array_sum($_SESSION['cart'] ?? []); $u = user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'FoodHub') ?> - FoodHub</title>
<link rel="stylesheet" href="<?= $base ?>assets/style.css">
</head>
<body>
<header class="nav">
  <a class="brand" href="<?= $base ?>index.php">🍔 FoodHub</a>
  <nav>
    <a href="<?= $base ?>index.php">Menu</a>
    <a href="<?= $base ?>cart.php">Cart (<?= $cartCount ?>)</a>
    <?php if ($u): ?>
      <a href="<?= $base ?>orders.php">My Orders</a>
      <?php if ($u['role'] === 'admin'): ?>
        <a href="<?= $base ?>admin/index.php">Admin Orders</a>
        <a href="<?= $base ?>admin/menu.php">Manage Menu</a>
      <?php endif; ?>
      <a href="<?= $base ?>logout.php">Logout (<?= e($u['name']) ?>)</a>
    <?php else: ?>
      <a href="<?= $base ?>login.php">Login</a>
      <a href="<?= $base ?>register.php">Register</a>
    <?php endif; ?>
  </nav>
</header>
<main class="container">
<?php if (!empty($_SESSION['flash'])): [$m, $t] = $_SESSION['flash']; unset($_SESSION['flash']); ?>
  <div class="flash <?= $t ?>"><?= e($m) ?></div>
<?php endif; ?>
