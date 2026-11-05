<?php
$ta = tahun_ajaran_aktif();
$s = q('SELECT s.*, k.nama kelas, k.tingkat, k.wali_kelas FROM siswa s LEFT JOIN kelas k ON k.id = s.kelas_id WHERE s.id = ?', [(int) ($_GET['id'] ?? 0)])->fetch();
if (!$s) redirect('siswa');
$kartu = kartu_spp($s, $ta);
$riwayat = q('SELECT t.*, ta.nama ta, GROUP_CONCAT(p.bulan ORDER BY FIELD(p.bulan,' . implode(',', BULAN_AJARAN) . ')) bulan FROM transaksi t
              JOIN tahun_ajaran ta ON ta.id = t.tahun_ajaran_id JOIN pembayaran p ON p.transaksi_id = t.id
              WHERE t.siswa_id = ? GROUP BY t.id ORDER BY t.tgl_bayar DESC', [$s['id']])->fetchAll();

$title = 'Kartu SPP';
$titleHtml = 'Kartu SPP <mark>' . e($s['nama']) . '</mark>';
$actions = '<a class="btn btn-yellow" href="' . url('bayar', ['siswa' => $s['id']]) . '">' . icon('wallet', 16) . ' Bayar</a><button class="btn" onclick="window.print()">' . icon('printer', 16) . '</button>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="stats">
    <div class="stat bg-white"><small>NIS · Kelas</small><strong><?= e($s['nis']) ?></strong><span>Kelas <?= e($s['kelas']) ?> · wali kelas <?= e($s['wali_kelas']) ?></span></div>
    <div class="stat bg-blue"><small>SPP per bulan</small><strong><?= rupiah($kartu['nominal']) ?></strong><span><?= $s['potongan_persen'] ? 'potongan ' . $s['potongan_persen'] . '% dari ' . rupiah($kartu['tarif']) : 'tarif normal' ?></span></div>
    <div class="stat bg-green"><small>Bulan lunas</small><strong><?= count(array_filter($kartu['bulan'], fn ($b) => $b['status'] === 'lunas')) ?> / 12</strong><span>TA <?= e($ta['nama']) ?></span></div>
    <div class="stat <?= $kartu['jml_tunggak'] ? 'bg-red' : 'bg-yellow' ?>"><small>Tunggakan</small><strong><?= rupiah($kartu['total_tunggak']) ?></strong><span><?= $kartu['jml_tunggak'] ?> bulan lewat jatuh tempo</span></div>
</div>
<div class="card">
    <div class="card-head"><h2>Tahun Ajaran <?= e($ta['nama']) ?></h2><span class="muted">Jatuh tempo setiap tanggal <?= (int) setting()['tanggal_jatuh_tempo'] ?></span></div>
    <div class="card-body"><div class="months">
        <?php foreach ($kartu['bulan'] as $b => $i): ?>
            <div class="month <?= $i['status'] ?>"><b><?= NAMA_BULAN[$b] ?></b>
                <small><?= ['lunas' => 'Lunas', 'tunggak' => 'Menunggak', 'belum' => 'Belum jatuh tempo', 'gratis' => 'Bebas SPP'][$i['status']] ?></small></div>
        <?php endforeach; ?>
    </div></div>
</div>
<div class="card mt">
    <div class="card-head"><h2>Riwayat Pembayaran</h2></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Kwitansi</th><th>Tanggal</th><th>Bulan</th><th>Metode</th><th class="text-right">Total</th></tr></thead>
        <tbody>
        <?php foreach ($riwayat as $r): ?>
            <tr><td><a href="<?= url('kwitansi', ['id' => $r['id']]) ?>"><?= e($r['kode']) ?></a></td><td><?= tgl($r['tgl_bayar']) ?></td>
                <td><?= implode(', ', array_map(fn ($b) => substr(NAMA_BULAN[(int) $b], 0, 3), explode(',', $r['bulan']))) ?> <small><?= e($r['ta']) ?></small></td>
                <td><?= e($r['metode']) ?></td><td class="text-right"><strong><?= rupiah($r['total']) ?></strong></td></tr>
        <?php endforeach; ?>
        <?php if (!$riwayat): ?><tr><td colspan="5" class="empty">Belum ada pembayaran.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
