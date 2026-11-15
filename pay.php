<?php
$base = ''; require 'includes/db.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$s = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
$s->execute([$id, user()['id']]);
$o = $s->fetch();
if (!$o) { flash('Order not found.', 'err'); go('orders.php'); }
if ($o['payment_status'] !== 'Unpaid') { flash('Payment for this order is already submitted.'); go('orders.php'); }

$method = $_POST['method'] ?? 'Cash on Delivery';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($method === 'Cash on Delivery') {
        $pdo->prepare("UPDATE orders SET payment='Cash on Delivery', payment_status='Pay on Delivery' WHERE id=?")->execute([$id]);
        flash("Order #$id confirmed! Pay cash when the food arrives. 🎉");
        go('orders.php');

    } elseif ($method === 'bKash') {
        $sender = preg_replace('/\D/', '', $_POST['sender'] ?? '');
        $trx = strtoupper(trim($_POST['trx'] ?? ''));
        if (strlen($sender) !== 11) $errors[] = 'Enter your 11 digit bKash number.';
        if (!preg_match('/^[A-Z0-9]{8,12}$/', $trx)) $errors[] = 'Enter a valid Transaction ID (8-12 letters/numbers).';
        if (!$errors) {
            $pdo->prepare("UPDATE orders SET payment='bKash', payment_status='Verification Pending', trx_id=? WHERE id=?")->execute([$trx, $id]);
            flash("bKash payment submitted for order #$id. The restaurant will verify your TrxID soon.");
            go('orders.php');
        }

    } elseif ($method === 'Card') {
        // DEMO ONLY: no real money moves. We never store the full card number or CVV.
        $num = preg_replace('/\D/', '', $_POST['card'] ?? '');
        $name = trim($_POST['holder'] ?? '');
        $exp = trim($_POST['exp'] ?? '');
        $cvv = trim($_POST['cvv'] ?? '');
        if ($name === '') $errors[] = 'Enter the card holder name.';
        if (strlen($num) < 13 || strlen($num) > 19 || !luhn_ok($num)) $errors[] = 'Card number is not valid.';
        if (!preg_match('/^(0[1-9]|1[0-2])\s*\/\s*(\d{2})$/', $exp, $m)) $errors[] = 'Expiry must be MM/YY.';
        elseif ((int)('20' . $m[2]) < (int)date('Y') || ((int)('20' . $m[2]) === (int)date('Y') && (int)$m[1] < (int)date('n'))) $errors[] = 'Card has expired.';
        if (!preg_match('/^\d{3,4}$/', $cvv)) $errors[] = 'CVV must be 3 or 4 digits.';
        if (!$errors) {
            $ref = 'CARD-' . strtoupper(bin2hex(random_bytes(4)));
            $pdo->prepare("UPDATE orders SET payment=?, payment_status='Paid', trx_id=? WHERE id=?")
                ->execute(['Card (**** ' . substr($num, -4) . ')', $ref, $id]);
            flash("Payment successful! Reference: $ref 🎉");
            go('orders.php');
        }
    }
}

$title = 'Payment';
include 'includes/header.php';
?>
<h2>💳 Payment – Order #<?= $o['id'] ?></h2>
<div class="two">
  <div class="card">
    <h3>Order Summary</h3>
    <ul>
    <?php $i = $pdo->prepare("SELECT * FROM order_items WHERE order_id=?"); $i->execute([$id]);
      foreach ($i->fetchAll() as $it): ?>
      <li><?= e($it['name']) ?> × <?= $it['qty'] ?> — <?= taka($it['price'] * $it['qty']) ?></li>
    <?php endforeach; ?>
    </ul>
    <p class="big">Amount to pay: <strong><?= taka($o['total']) ?></strong></p>
    <small class="muted">Deliver to: <?= e($o['address']) ?></small>
  </div>

  <form method="post" class="form" id="payform">
    <?php foreach ($errors as $er): ?><div class="flash err"><?= e($er) ?></div><?php endforeach; ?>
    <h3>Choose Payment Method</h3>
    <label class="opt"><input type="radio" name="method" value="Cash on Delivery" <?= $method === 'Cash on Delivery' ? 'checked' : '' ?>> 💵 Cash on Delivery</label>
    <label class="opt"><input type="radio" name="method" value="bKash" <?= $method === 'bKash' ? 'checked' : '' ?>> 📱 bKash</label>
    <label class="opt"><input type="radio" name="method" value="Card" <?= $method === 'Card' ? 'checked' : '' ?>> 💳 Credit / Debit Card (demo)</label>

    <div class="pm" id="pm-Cash on Delivery">
      <p class="muted">Pay the delivery person in cash when your food arrives.</p>
    </div>

    <div class="pm" id="pm-bKash">
      <p>1. Open bKash app → <strong>Send Money</strong> to <strong><?= e(BKASH_NUMBER) ?></strong><br>
         2. Amount: <strong><?= taka($o['total']) ?></strong><br>
         3. Enter your number and the Transaction ID (TrxID) below.</p>
      <label>Your bKash number<input name="sender" placeholder="01XXXXXXXXX" value="<?= e($_POST['sender'] ?? '') ?>"></label>
      <label>Transaction ID<input name="trx" placeholder="e.g. 9BK3A7X2QP" value="<?= e($_POST['trx'] ?? '') ?>"></label>
    </div>

    <div class="pm" id="pm-Card">
      <p class="muted">Demo mode – no real charge. Test card: <code>4242 4242 4242 4242</code>, any future expiry, any CVV.</p>
      <label>Card holder<input name="holder" value="<?= e($_POST['holder'] ?? '') ?>"></label>
      <label>Card number<input name="card" inputmode="numeric" placeholder="4242 4242 4242 4242" autocomplete="off"></label>
      <div class="row2">
        <label>Expiry (MM/YY)<input name="exp" placeholder="12/30" value="<?= e($_POST['exp'] ?? '') ?>"></label>
        <label>CVV<input name="cvv" inputmode="numeric" maxlength="4" autocomplete="off"></label>
      </div>
    </div>

    <button class="btn">Confirm Payment</button>
  </form>
</div>

<script>
function showPm() {
  var m = document.querySelector('input[name=method]:checked').value;
  document.querySelectorAll('.pm').forEach(function (d) { d.style.display = (d.id === 'pm-' + m) ? 'block' : 'none'; });
}
document.querySelectorAll('input[name=method]').forEach(function (r) { r.addEventListener('change', showPm); });
showPm();
</script>
<?php include 'includes/footer.php'; ?>
