<?php
$ta = tahun_ajaran_aktif();
$hariIni = (int) q('SELECT COALESCE(SUM(total),0) FROM transaksi WHERE tgl_bayar = CURDATE()')->fetchColumn();
$bulanIni = (int) q("SELECT COALESCE(SUM(total),0) FROM transaksi WHERE DATE_FORMAT(tgl_bayar,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn();
$siswaAktif = q("SELECT s.*, k.nama kelas, k.tingkat FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.status = 'aktif'")->fetchAll();

// Rekap tunggakan per kelas & persentase siswa yang lunas sampai bulan berjalan
$perKelas = [];
$totalTunggak = 0;
$lunas = 0;
foreach ($siswaAktif as $s) {
    $k = kartu_spp($s, $ta);
    $perKelas[$s['kelas']] = ($perKelas[$s['kelas']] ?? 0) + $k['total_tunggak'];
    $totalTunggak += $k['total_tunggak'];
    if (!$k['jml_tunggak']) $lunas++;
}
$maxKelas = max(1, max($perKelas ?: [0]));
$persenLunas = $siswaAktif ? round($lunas / count($siswaAktif) * 100) : 0;

// Penerimaan per bulan selama tahun ajaran aktif
$grafik = [];
foreach (BULAN_AJARAN as $b) {
    $th = $b >= 7 ? $ta['tahun_mulai'] : $ta['tahun_mulai'] + 1;
    $grafik[$b] = (int) q("SELECT COALESCE(SUM(total),0) FROM transaksi WHERE YEAR(tgl_bayar) = ? AND MONTH(tgl_bayar) = ?", [$th, $b])->fetchColumn();
}
$maxG = max(1, max($grafik));
$terbaru = q('SELECT t.*, s.nama, k.nama kelas FROM transaksi t JOIN siswa s ON s.id = t.siswa_id LEFT JOIN kelas k ON k.id = s.kelas_id ORDER BY t.tgl_bayar DESC, t.id DESC LIMIT 6')->fetchAll();

$title = 'Dashboard';
$titleHtml = 'Halo, <mark>' . e(explode(' ', user()['nama'])[0]) . '</mark>';
$actions = '<a class="btn btn-primary" href="' . url('bayar') . '">' . icon('wallet', 16) . ' Terima Pembayaran</a>';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats">
    <div class="stat bg-yellow"><small>Masuk hari ini</small><strong><?= rupiah($hariIni) ?></strong><span><?= tgl(date('Y-m-d')) ?></span></div>
    <div class="stat bg-blue"><small>Masuk bulan ini</small><strong><?= rupiah($bulanIni) ?></strong><span><?= NAMA_BULAN[(int) date('n')] ?> <?= date('Y') ?></span></div>
    <div class="stat bg-red"><small>Total tunggakan</small><strong><?= rupiah($totalTunggak) ?></strong><span><?= count($siswaAktif) - $lunas ?> siswa menunggak</span></div>
    <div class="stat bg-green"><small>Siswa tertib bayar</small><strong><?= $persenLunas ?>%</strong><span><?= $lunas ?> dari <?= count($siswaAktif) ?> siswa</span></div>
</div>
<div class="grid-2">
    <div class="card">
        <div class="card-head"><h2>Penerimaan TA <?= e($ta['nama']) ?></h2></div>
        <div class="card-body"><div class="bars">
            <?php foreach ($grafik as $b => $v): ?>
                <div class="bar-col" title="<?= NAMA_BULAN[$b] ?>: <?= rupiah($v) ?>">
                    <small><?= $v ? number_format($v / 1000000, 1, ',', '.') . 'jt' : '' ?></small>
                    <div class="bar" style="height:<?= max(2, round($v / $maxG * 100)) ?>%"></div>
                    <small><?= substr(NAMA_BULAN[$b], 0, 3) ?></small>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
    <div class="card">
        <div class="card-head"><h2>Tunggakan per Kelas</h2><a href="<?= url('tunggakan') ?>">Detail</a></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
            <?php foreach ($perKelas as $kelas => $v): ?>
                <div style="display:flex;align-items:center;gap:12px"><b style="width:52px"><?= e($kelas) ?></b>
                    <div class="hbar"><span style="width:<?= round($v / $maxKelas * 100) ?>%"></span></div>
                    <span style="width:110px;text-align:right;font-weight:800"><?= rupiah($v) ?></span></div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<div class="card mt">
    <div class="card-head"><h2>Pembayaran Terakhir</h2><a href="<?= url('laporan') ?>">Laporan</a></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Kwitansi</th><th>Siswa</th><th>Tanggal</th><th>Metode</th><th class="text-right">Total</th></tr></thead>
        <tbody><?php foreach ($terbaru as $t): ?>
            <tr><td><a href="<?= url('kwitansi', ['id' => $t['id']]) ?>"><?= e($t['kode']) ?></a></td><td><strong><?= e($t['nama']) ?></strong><small><?= e($t['kelas']) ?></small></td>
                <td><?= tgl($t['tgl_bayar']) ?></td><td><span class="badge <?= $t['metode'] === 'Tunai' ? 'badge-yellow' : 'badge-blue' ?>"><?= e($t['metode']) ?></span></td><td class="text-right"><strong><?= rupiah($t['total']) ?></strong></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
