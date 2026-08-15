<?php
// admin/login.php
session_start();
require_once __DIR__ . '/../includes/dbh.inc.php';
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $pass = $_POST['password'] ?? '';
        if ($email && $pass) {
            $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $u = $stmt->fetch();
            if ($u && password_verify($pass, $u['password_hash'])) {
                $_SESSION['user_id'] = $u['id'];
                $_SESSION['role'] = $u['role'];
                header('Location: /admin/index.php'); exit;
            } else { $error = 'Invalid credentials.'; }
        } else { $error = 'Please enter email and password.'; }
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Login</title>
<link rel="stylesheet" href="/assets/css/site.css"></head><body>
<main class="container" style="max-width:600px;margin-top:40px">
<h2>Admin Login</h2>
<?php if ($error): ?><div class="card" style="background:#ffecec;color:#900;padding:12px"><?php echo htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card">
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
    <div class="form-row"><input name="email" placeholder="Email" required></div>
    <div class="form-row"><input type="password" name="password" placeholder="Password" required></div>
    <div class="form-row"><button class="primary">Login</button></div>
  </form>
</div>
</main>
</body></html>
