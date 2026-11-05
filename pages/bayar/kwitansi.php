<?php
$id = (int) ($_GET['id'] ?? 0);
$t = q('SELECT t.*, s.nama, s.nis, k.nama kelas, ta.nama ta, u.nama petugas FROM transaksi t JOIN siswa s ON s.id = t.siswa_id
        LEFT JOIN kelas k ON k.id = s.kelas_id JOIN tahun_ajaran ta ON ta.id = t.tahun_ajaran_id LEFT JOIN users u ON u.id = t.user_id WHERE t.id = ?', [$id])->fetch();
if (!$t) redirect('bayar');
$detail = q('SELECT * FROM pembayaran WHERE transaksi_id = ? ORDER BY FIELD(bulan, ' . implode(',', BULAN_AJARAN) . ')', [$id])->fetchAll();
$set = setting();

$title = 'Kwitansi ' . $t['kode'];
$actions = '<a class="btn" href="' . url('bayar') . '">Pembayaran Baru</a><button class="btn btn-primary" onclick="window.print()">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card receipt">
    <div class="receipt-head">
        <div><h2><?= e($set['nama_sekolah']) ?></h2><div class="muted"><?= e($set['alamat']) ?> · <?= e($set['telepon']) ?></div></div>
        <div class="text-right"><span class="badge badge-yellow">KWITANSI SPP</span><div style="margin-top:8px;font-weight:900"><?= e($t['kode']) ?></div><div class="muted"><?= tgl($t['tgl_bayar']) ?></div></div>
    </div>
    <p style="margin:0 0 4px">Telah diterima dari <b><?= e($t['nama']) ?></b> (NIS <?= e($t['nis']) ?>, kelas <?= e($t['kelas']) ?>)</p>
    <p style="margin:0 0 16px">untuk pembayaran SPP tahun ajaran <b><?= e($t['ta']) ?></b> secara <b><?= e($t['metode']) ?></b>:</p>
    <table>
        <thead><tr><th>Bulan</th><th class="text-right">Nominal</th></tr></thead>
        <tbody><?php foreach ($detail as $d): ?><tr><td><?= NAMA_BULAN[$d['bulan']] ?></td><td class="text-right"><?= rupiah($d['nominal']) ?></td></tr><?php endforeach; ?></tbody>
    </table>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:18px">
        <span style="font-weight:800">Jumlah</span><span class="amount"><?= rupiah($t['total']) ?></span>
    </div>
    <div class="sign"><div><?= e(explode(',', $set['alamat'])[1] ?? '') ?> <?= tgl($t['tgl_bayar']) ?><br>Bendahara,<br><br><br><b><?= e($t['petugas'] ?? $set['bendahara']) ?></b></div></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
