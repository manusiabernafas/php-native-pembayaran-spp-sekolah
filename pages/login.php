<?php
if (user()) redirect('dashboard');
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = q('SELECT * FROM users WHERE username = ?', [trim($_POST['username'] ?? '')])->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        unset($u['password']);
        $_SESSION['user'] = $u;
        redirect('dashboard');
    }
    $error = 'Username atau password salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk · SPP <?= e(setting()['nama_sekolah']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="center-page">
    <form method="post" class="card login-box">
        <?= csrf_field() ?>
        <div class="brand-mark"><?= icon('school', 26) ?></div>
        <h1>Halo, Bendahara!</h1>
        <p class="muted" style="margin:0 0 22px">Sistem Pembayaran SPP <?= e(setting()['nama_sekolah']) ?></p>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <div class="field"><label>Username</label><input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>"></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button class="btn btn-primary btn-block">Masuk</button>
        <a class="btn btn-yellow btn-block" style="margin-top:12px" href="<?= url('cek') ?>">Orang tua? Cek tagihan SPP di sini</a>
        <div class="note">Akun demo: <b>admin</b> / <b>admin123</b> atau <b>bendahara</b> / <b>admin123</b></div>
    </form>
</div>
</body>
</html>
