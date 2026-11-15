<?php
$base = ''; require 'includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $s = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $s->execute([strtolower(trim($_POST['email'] ?? ''))]);
    $u = $s->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;
        flash('Welcome, ' . $u['name'] . '!');
        go($u['role'] === 'admin' ? 'admin/index.php' : 'index.php');
    }
    flash('Wrong email or password.', 'err');
}
$title = 'Login';
include 'includes/header.php';
?>
<h2>Login</h2>
<form method="post" class="form">
  <label>Email<input type="email" name="email" required></label>
  <label>Password<input type="password" name="password" required></label>
  <button class="btn">Login</button>
</form>
<p class="muted">Demo → Admin: admin@food.com / admin123 &nbsp;|&nbsp; Customer: user@food.com / user123</p>
<?php include 'includes/footer.php'; ?>
