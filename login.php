<?php
require __DIR__ . '/includes/init.php';
if (!empty($_SESSION['user'])) { header('Location: index.php'); exit; }

$error = '';
$lock = $_SESSION['lock_until'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (time() < $lock) {
        $error = 'Terlalu banyak percobaan. Coba lagi dalam ' . ($lock - time()) . ' detik.';
    } else {
        $u = trim($_POST['username'] ?? '');
        $p = $_POST['password'] ?? '';
        $st = db()->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$u]);
        $user = $st->fetch();
        if ($user && password_verify($p, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id' => (int)$user['id'], 'nama' => $user['nama'], 'role' => $user['role']];
            unset($_SESSION['gagal'], $_SESSION['lock_until']);
            header('Location: index.php'); exit;
        }
        $_SESSION['gagal'] = ($_SESSION['gagal'] ?? 0) + 1;
        if ($_SESSION['gagal'] >= 5) { $_SESSION['lock_until'] = time() + 60; $_SESSION['gagal'] = 0; }
        $error = 'Username atau password salah.';
    }
}
?><!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - Pendaftaran RS</title><link rel="stylesheet" href="assets/style.css">
</head><body class="login">
<form method="post" class="card loginbox">
  <h1>Masuk petugas</h1>
  <p class="muted">Sistem pendaftaran pasien rumah sakit</p>
  <?php if ($error): ?><div class="msg err"><?= e($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <label for="username">Username</label>
  <input id="username" name="username" required autofocus autocomplete="username">
  <label for="password">Password</label>
  <input id="password" name="password" type="password" required autocomplete="current-password">
  <button class="btn" style="width:100%;margin-top:14px">Masuk</button>
</form>
</body></html>
