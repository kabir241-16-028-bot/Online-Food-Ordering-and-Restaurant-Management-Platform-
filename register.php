<?php
$base = ''; require 'includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? ''; $phone = trim($_POST['phone'] ?? ''); $addr = trim($_POST['address'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
        flash('Enter a name, a valid email and a password of at least 6 characters.', 'err');
    } else {
        $s = $pdo->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$email]);
        if ($s->fetch()) flash('Email already registered.', 'err');
        else {
            $pdo->prepare("INSERT INTO users(name,email,password,phone,address) VALUES(?,?,?,?,?)")
                ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT), $phone, $addr]);
            flash('Registration successful. Please login.');
            go('login.php');
        }
    }
}
$title = 'Register';
include 'includes/header.php';
?>
<h2>Create Account</h2>
<form method="post" class="form">
  <label>Name<input name="name" required></label>
  <label>Email<input type="email" name="email" required></label>
  <label>Password<input type="password" name="password" minlength="6" required></label>
  <label>Phone<input name="phone"></label>
  <label>Address<textarea name="address"></textarea></label>
  <button class="btn">Register</button>
</form>
<?php include 'includes/footer.php'; ?>
