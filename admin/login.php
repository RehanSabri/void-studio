<?php
// ─────────────────────────────────────────────────
//  admin/login.php — Admin login page
// ─────────────────────────────────────────────────

session_start();
require_once __DIR__ . '/../config/db.php';

// Already logged in → redirect
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $pdo  = get_db();
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['username'];

            // Record last login
            $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);

            header('Location: /admin/index.php');
            exit;
        }
    }
    $error = 'Invalid username or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>VØID Admin — Login</title>
<style>
  *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
  :root {
    --bg:#0a0906; --bg2:#111009; --cream:#e8e0d0; --gold:#c9a96e;
    --muted:#4a4540; --border:rgba(201,169,110,0.15);
  }
  body { background:var(--bg); color:var(--cream); font-family:Georgia,serif;
         min-height:100vh; display:flex; align-items:center; justify-content:center; }
  .box { width:100%; max-width:400px; padding:2.5rem; border:1px solid var(--border); }
  .logo { font-family:sans-serif; font-weight:800; font-size:1.5rem;
          letter-spacing:.08em; margin-bottom:2rem; color:var(--cream); }
  .logo span { color:var(--gold); }
  label { display:block; font-size:.65rem; letter-spacing:.12em; text-transform:uppercase;
          color:var(--muted); font-family:monospace; margin-bottom:.4rem; }
  input { width:100%; background:transparent; border:none; border-bottom:1px solid var(--border);
          padding:.6rem 0; color:var(--cream); font-size:1rem; font-family:Georgia,serif;
          outline:none; margin-bottom:1.5rem; }
  input:focus { border-color:var(--gold); }
  button { width:100%; background:var(--gold); border:none; padding:.85rem;
           color:var(--bg); font-family:monospace; font-size:.72rem; letter-spacing:.12em;
           text-transform:uppercase; cursor:pointer; transition:background .3s; }
  button:hover { background:var(--cream); }
  .error { color:#e57373; font-family:monospace; font-size:.7rem; margin-bottom:1rem;
           letter-spacing:.05em; }
</style>
</head>
<body>
<div class="box">
  <div class="logo">VØ<span>I</span>D <span style="color:var(--muted);font-size:.7rem;">Admin</span></div>
  <?php if ($error): ?>
    <p class="error">⚠ <?= htmlspecialchars($error) ?></p>
  <?php endif; ?>
  <form method="POST">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required autocomplete="username">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required autocomplete="current-password">
    <button type="submit">Sign In</button>
  </form>
</div>
</body>
</html>
