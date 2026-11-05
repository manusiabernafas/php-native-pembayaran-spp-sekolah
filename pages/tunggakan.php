<?php
$ta = tahun_ajaran_aktif();
$kelasList = q('SELECT * FROM kelas ORDER BY tingkat, nama')->fetchAll();
$kelasId = (int) ($_GET['kelas'] ?? 0);
$siswa = q("SELECT s.*, k.nama kelas, k.tingkat FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.status = 'aktif'"
    . ($kelasId ? ' AND s.kelas_id = ' . $kelasId : '') . ' ORDER BY k.tingkat, k.nama, s.nama')->fetchAll();

$rows = [];
$total = 0;
foreach ($siswa as $s) {
    $k = kartu_spp($s, $ta);
    if (!$k['jml_tunggak']) continue;
    $s['kartu'] = $k;
    $rows[] = $s;
    $total += $k['total_tunggak'];
}
usort($rows, fn ($a, $b) => $b['kartu']['total_tunggak'] <=> $a['kartu']['total_tunggak']);

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="tunggakan-spp-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['NIS', 'Nama', 'Kelas', 'Wali', 'WA Wali', 'Bulan Menunggak', 'Jumlah Bulan', 'Total'], ';', '"', '');
    foreach ($rows as $r) {
        $bln = array_keys(array_filter($r['kartu']['bulan'], fn ($b) => $b['status'] === 'tunggak'));
        fputcsv($out, [$r['nis'], $r['nama'], $r['kelas'], $r['nama_wali'], $r['telp_wali'], implode(', ', array_map(fn ($b) => NAMA_BULAN[$b], $bln)), $r['kartu']['jml_tunggak'], $r['kartu']['total_tunggak']], ';', '"', '');
    }
    exit;
}

$title = 'Tunggakan SPP';
$actions = '<a class="btn" href="' . url('tunggakan', ['kelas' => $kelasId, 'export' => 'csv']) . '">Ekspor CSV</a>';
require __DIR__ . '/../includes/header.php';
?>
<div class="stats" style="grid-template-columns:repeat(3,1fr)">
    <div class="stat bg-red"><small>Total tunggakan</small><strong><?= rupiah($total) ?></strong><span>sampai <?= tgl(date('Y-m-d')) ?></span></div>
    <div class="stat bg-yellow"><small>Siswa menunggak</small><strong><?= count($rows) ?></strong><span>dari <?= count($siswa) ?> siswa aktif</span></div>
    <div class="stat bg-white"><small>Filter kelas</small>
        <form method="get" style="margin-top:8px"><input type="hidden" name="page" value="tunggakan">
            <select name="kelas" onchange="this.form.submit()"><option value="0">Semua kelas</option>
                <?php foreach ($kelasList as $k): ?><option value="<?= $k['id'] ?>" <?= $kelasId === (int) $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?>
            </select></form></div>
</div>
<div class="card">
    <div class="table-wrap"><table>
        <thead><tr><th>Siswa</th><th>Kelas</th><th>Bulan Menunggak</th><th>Total</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r):
            $bln = array_keys(array_filter($r['kartu']['bulan'], fn ($b) => $b['status'] === 'tunggak'));
            $pesan = "Assalamualaikum {$r['nama_wali']}, kami informasikan SPP ananda {$r['nama']} (kelas {$r['kelas']}) bulan "
                . implode(', ', array_map(fn ($b) => NAMA_BULAN[$b], $bln)) . ' belum dibayarkan, total ' . rupiah($r['kartu']['total_tunggak'])
                . '. Mohon dapat segera diselesaikan. Terima kasih. - Bendahara ' . setting()['nama_sekolah']; ?>
            <tr>
                <td><strong><?= e($r['nama']) ?></strong><small>NIS <?= e($r['nis']) ?> · <?= e($r['nama_wali']) ?></small></td>
                <td><?= e($r['kelas']) ?></td>
                <td><?php foreach ($bln as $b): ?><span class="badge badge-red" style="margin:2px"><?= substr(NAMA_BULAN[$b], 0, 3) ?></span><?php endforeach; ?></td>
                <td><strong><?= rupiah($r['kartu']['total_tunggak']) ?></strong><small><?= $r['kartu']['jml_tunggak'] ?> bulan</small></td>
                <td><div class="actions">
                    <a class="btn btn-sm btn-green" target="_blank" href="<?= wa_link($r['telp_wali'], $pesan) ?>"><?= icon('message', 14) ?> Ingatkan</a>
                    <a class="btn btn-sm btn-yellow" href="<?= url('bayar', ['siswa' => $r['id']]) ?>">Bayar</a>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="5" class="empty">Tidak ada tunggakan. Mantap!</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
