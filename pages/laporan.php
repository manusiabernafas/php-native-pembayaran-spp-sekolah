<?php
$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$rows = q('SELECT t.*, s.nama, s.nis, k.nama kelas, u.nama petugas, (SELECT COUNT(*) FROM pembayaran p WHERE p.transaksi_id = t.id) jml_bulan
           FROM transaksi t JOIN siswa s ON s.id = t.siswa_id LEFT JOIN kelas k ON k.id = s.kelas_id LEFT JOIN users u ON u.id = t.user_id
           WHERE t.tgl_bayar BETWEEN ? AND ? ORDER BY t.tgl_bayar DESC, t.id DESC', [$dari, $sampai])->fetchAll();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"penerimaan-spp-$dari-sd-$sampai.csv\"");
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Kwitansi', 'Tanggal', 'NIS', 'Nama', 'Kelas', 'Jumlah Bulan', 'Metode', 'Total', 'Petugas'], ';', '"', '');
    foreach ($rows as $r) fputcsv($out, [$r['kode'], $r['tgl_bayar'], $r['nis'], $r['nama'], $r['kelas'], $r['jml_bulan'], $r['metode'], $r['total'], $r['petugas']], ';', '"', '');
    exit;
}

$total = array_sum(array_column($rows, 'total'));
$tunai = array_sum(array_map(fn ($r) => $r['metode'] === 'Tunai' ? $r['total'] : 0, $rows));
$perKelas = q('SELECT k.nama, SUM(t.total) t, COUNT(*) n FROM transaksi t JOIN siswa s ON s.id = t.siswa_id JOIN kelas k ON k.id = s.kelas_id
               WHERE t.tgl_bayar BETWEEN ? AND ? GROUP BY k.id ORDER BY k.tingkat, k.nama', [$dari, $sampai])->fetchAll();

$title = 'Laporan Penerimaan';
$actions = '<a class="btn" href="' . url('laporan', ['dari' => $dari, 'sampai' => $sampai, 'export' => 'csv']) . '">Ekspor CSV</a><button class="btn btn-primary" onclick="window.print()">' . icon('printer', 16) . ' Cetak</button>';
require __DIR__ . '/../includes/header.php';
?>
<form class="card no-print" method="get" style="margin-bottom:24px"><div class="card-body filters">
    <input type="hidden" name="page" value="laporan">
    <label style="margin:0">Dari</label><input type="date" name="dari" value="<?= e($dari) ?>">
    <label style="margin:0">Sampai</label><input type="date" name="sampai" value="<?= e($sampai) ?>">
    <button class="btn btn-primary">Tampilkan</button>
</div></form>
<div class="stats">
    <div class="stat bg-green"><small>Total penerimaan</small><strong><?= rupiah($total) ?></strong><span><?= tgl($dari) ?> – <?= tgl($sampai) ?></span></div>
    <div class="stat bg-yellow"><small>Tunai</small><strong><?= rupiah($tunai) ?></strong></div>
    <div class="stat bg-blue"><small>Transfer</small><strong><?= rupiah($total - $tunai) ?></strong></div>
    <div class="stat bg-white"><small>Transaksi</small><strong><?= count($rows) ?></strong><span><?= array_sum(array_column($rows, 'jml_bulan')) ?> bulan SPP</span></div>
</div>
<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Daftar Transaksi</h2></div>
        <div class="table-wrap" style="max-height:560px;overflow:auto"><table>
            <thead><tr><th>Kwitansi</th><th>Siswa</th><th>Bulan</th><th class="text-right">Total</th><?php if (user()['role'] === 'admin'): ?><th class="no-print"></th><?php endif; ?></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr><td><a href="<?= url('kwitansi', ['id' => $r['id']]) ?>"><?= e($r['kode']) ?></a><small><?= tgl($r['tgl_bayar']) ?> · <?= e($r['metode']) ?></small></td>
                    <td><strong><?= e($r['nama']) ?></strong><small><?= e($r['kelas']) ?></small></td><td><?= $r['jml_bulan'] ?></td>
                    <td class="text-right"><strong><?= rupiah($r['total']) ?></strong></td>
                    <?php if (user()['role'] === 'admin'): ?><td class="no-print"><form method="post" action="<?= url('bayar.batal') ?>" data-confirm="Batalkan transaksi <?= e($r['kode']) ?>?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="icon-btn" title="Batalkan"><?= icon('trash', 14) ?></button></form></td><?php endif; ?></tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?><tr><td colspan="5" class="empty">Tidak ada transaksi.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
    <div class="card" style="align-self:start">
        <div class="card-head"><h2>Per Kelas</h2></div>
        <div class="table-wrap"><table>
            <tbody><?php foreach ($perKelas as $k): ?><tr><td><strong><?= e($k['nama']) ?></strong><small><?= $k['n'] ?> transaksi</small></td><td class="text-right"><strong><?= rupiah($k['t']) ?></strong></td></tr><?php endforeach; ?></tbody>
        </table></div>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
