<?php
require __DIR__ . '/includes/bootstrap.php';

$routes = [
    'login'        => 'pages/login.php',
    'logout'       => 'pages/logout.php',
    'cek'          => 'pages/cek.php',
    'dashboard'    => 'pages/dashboard.php',
    'bayar'        => 'pages/bayar/index.php',
    'bayar.simpan' => 'pages/bayar/simpan.php',
    'bayar.batal'  => 'pages/bayar/batal.php',
    'kwitansi'     => 'pages/bayar/kwitansi.php',
    'siswa'        => 'pages/siswa/index.php',
    'siswa.form'   => 'pages/siswa/form.php',
    'siswa.kartu'  => 'pages/siswa/kartu.php',
    'tunggakan'    => 'pages/tunggakan.php',
    'laporan'      => 'pages/laporan.php',
    'master'       => 'pages/master.php',
];

$page = $_GET['page'] ?? 'dashboard';
if (!isset($routes[$page])) {
    http_response_code(404);
    $page = 'dashboard';
}
if (!in_array($page, ['login', 'cek'], true)) require_login();
verify_csrf();

require __DIR__ . '/' . $routes[$page];
