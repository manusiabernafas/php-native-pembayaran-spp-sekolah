<?php
require_admin();
$id = (int) ($_POST['id'] ?? 0);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id) {
    $kode = q('SELECT kode FROM transaksi WHERE id = ?', [$id])->fetchColumn();
    q('DELETE FROM transaksi WHERE id = ?', [$id]); // detail bulan ikut terhapus (ON DELETE CASCADE)
    flash("Transaksi $kode dibatalkan, bulan-bulannya kembali belum lunas.");
}
redirect('laporan');
