<?php
$menu = [
    ['dashboard', 'Dashboard', 'home'],
    ['bayar', 'Terima Pembayaran', 'wallet'],
    ['siswa', 'Data Siswa', 'users'],
    ['tunggakan', 'Tunggakan', 'alert'],
    ['laporan', 'Laporan', 'chart'],
];
if (user()['role'] === 'admin') $menu[] = ['master', 'Pengaturan', 'settings'];
$current = explode('.', $_GET['page'] ?? 'dashboard')[0];
if ($current === 'kwitansi') $current = 'bayar';
$flash = flash();
$ta = tahun_ajaran_aktif();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Dashboard') ?> · SPP <?= e(setting()['nama_sekolah']) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="layout">
    <aside class="sidebar" id="sidebar">
        <div class="brand">
            <span class="brand-mark"><?= icon('school', 22) ?></span>
            <div><strong><?= e(setting()['nama_sekolah']) ?></strong><small>SPP · TA <?= e($ta['nama'] ?? '-') ?></small></div>
        </div>
        <nav>
            <?php foreach ($menu as [$key, $label, $ic]): ?>
                <a href="<?= url($key) ?>" class="<?= $current === $key ? 'active' : '' ?>"><?= icon($ic) ?> <?= $label ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="grow"></div>
        <div class="user-card">
            <span class="avatar"><?= e(strtoupper(substr(user()['nama'], 0, 1))) ?></span>
            <div class="grow"><strong><?= e(user()['nama']) ?></strong><small><?= e(ucfirst(user()['role'])) ?></small></div>
            <a href="<?= url('logout') ?>" class="icon-btn" title="Keluar"><?= icon('logout', 16) ?></a>
        </div>
    </aside>
    <main class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><?= icon('menu') ?></button>
            <h1><?= $titleHtml ?? e($title ?? 'Dashboard') ?></h1>
            <div class="topbar-actions"><?= $actions ?? '' ?></div>
        </header>
        <div class="content">
            <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
