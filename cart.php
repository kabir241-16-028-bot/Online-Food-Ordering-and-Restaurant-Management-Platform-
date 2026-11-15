<?php
$base = ''; require 'includes/db.php';
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];
$cart = &$_SESSION['cart'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'update') {
        foreach ($_POST['qty'] ?? [] as $id => $q) {
            $q = (int)$q;
            if ($q <= 0) unset($cart[(int)$id]); else $cart[(int)$id] = $q;
        }
        flash('Cart updated.');
    } elseif ($a === 'remove') {
        unset($cart[(int)$_POST['id']]);
        flash('Item removed.');
    } elseif ($a === 'checkout') {
        require_login();
        $address = trim($_POST['address'] ?? ''); $phone = trim($_POST['phone'] ?? '');
        if (!$cart) { flash('Your cart is empty.', 'err'); go('cart.php'); }
        if ($address === '' || $phone === '') { flash('Address and phone are required.', 'err'); go('cart.php'); }
        $total = 0; $items = [];
        foreach ($cart as $id => $qty) {
            $s = $pdo->prepare("SELECT * FROM foods WHERE id=? AND available=1"); $s->execute([$id]);
            if ($f = $s->fetch()) { $items[] = [$f, $qty]; $total += $f['price'] * $qty; }
        }
        if (!$items) { flash('No available items in cart.', 'err'); go('cart.php'); }
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO orders(user_id,total,address,phone,payment,payment_status) VALUES(?,?,?,?,?,?)")
            ->execute([user()['id'], $total, $address, $phone, '', 'Unpaid']);
        $oid = $pdo->lastInsertId();
        $ins = $pdo->prepare("INSERT INTO order_items(order_id,food_id,name,price,qty) VALUES(?,?,?,?,?)");
        foreach ($items as [$f, $qty]) $ins->execute([$oid, $f['id'], $f['name'], $f['price'], $qty]);
        $pdo->commit();
        $cart = [];
        flash("Order #$oid created. Now choose a payment method.");
        go('pay.php?id=' . $oid);
    }
    go('cart.php');
}

$rows = []; $total = 0;
foreach ($cart as $id => $qty) {
    $s = $pdo->prepare("SELECT * FROM foods WHERE id=?"); $s->execute([$id]);
    if ($f = $s->fetch()) { $f['qty'] = $qty; $rows[] = $f; $total += $f['price'] * $qty; }
}
$title = 'Cart';
include 'includes/header.php';
?>
<h2>🛒 Your Cart</h2>
<?php if (!$rows): ?>
  <p>Cart is empty. <a href="index.php">Browse the menu</a></p>
<?php else: ?>
<form method="post">
  <input type="hidden" name="action" value="update">
  <table>
    <tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th><th></th></tr>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['icon'] . ' ' . $r['name']) ?></td>
      <td><?= taka($r['price']) ?></td>
      <td><input type="number" name="qty[<?= $r['id'] ?>]" value="<?= $r['qty'] ?>" min="0" class="qty"></td>
      <td><?= taka($r['price'] * $r['qty']) ?></td>
      <td><button class="btn small danger" form="rm<?= $r['id'] ?>">Remove</button></td>
    </tr>
    <?php endforeach; ?>
    <tr><td colspan="3" align="right"><strong>Total</strong></td><td colspan="2"><strong><?= taka($total) ?></strong></td></tr>
  </table>
  <button class="btn">Update Cart</button>
</form>
<?php foreach ($rows as $r): ?>
  <form method="post" id="rm<?= $r['id'] ?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="id" value="<?= $r['id'] ?>"></form>
<?php endforeach; ?>

<h3>Delivery Details</h3>
<?php if (!user()): ?>
  <p>Please <a href="login.php">login</a> or <a href="register.php">register</a> to place your order.</p>
<?php else: ?>
<form method="post" class="form">
  <input type="hidden" name="action" value="checkout">
  <label>Delivery Address<textarea name="address" required><?= e(user()['address'] ?? '') ?></textarea></label>
  <label>Phone<input type="text" name="phone" value="<?= e(user()['phone'] ?? '') ?>" required></label>
  <button class="btn">Proceed to Payment →</button>
</form>
<?php endif; endif; ?>
<?php include 'includes/footer.php'; ?>
