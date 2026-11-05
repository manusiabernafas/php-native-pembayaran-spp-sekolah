<?php
$ta = tahun_ajaran_aktif();
$kelasList = q('SELECT * FROM kelas ORDER BY tingkat, nama')->fetchAll();
$kelasId = (int) ($_GET['kelas'] ?? ($kelasList[0]['id'] ?? 0));
$cari = trim($_GET['cari'] ?? '');

$params = [];
$sql = 'SELECT s.*, k.nama kelas, k.tingkat FROM siswa s LEFT JOIN kelas k ON k.id = s.kelas_id WHERE ';
if ($cari !== '') { $sql .= '(s.nis LIKE ? OR s.nama LIKE ?)'; $params = ["%$cari%", "%$cari%"]; }
else { $sql .= 's.kelas_id = ?'; $params = [$kelasId]; }
$rows = q($sql . ' ORDER BY s.nama', $params)->fetchAll();

$title = 'Data Siswa';
$actions = '<a class="btn btn-primary" href="' . url('siswa.form') . '">' . icon('plus', 16) . ' Tambah Siswa</a>';
require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-head">
        <form class="filters" method="get">
            <input type="hidden" name="page" value="siswa">
            <select name="kelas" onchange="this.form.submit()">
                <?php foreach ($kelasList as $k): ?><option value="<?= $k['id'] ?>" <?= $kelasId === (int) $k['id'] && $cari === '' ? 'selected' : '' ?>>Kelas <?= e($k['nama']) ?></option><?php endforeach; ?>
            </select>
            <input type="search" name="cari" value="<?= e($cari) ?>" placeholder="atau cari NIS / nama">
            <button class="btn btn-sm"><?= icon('search', 16) ?></button>
        </form>
        <span class="muted" style="font-weight:700"><?= count($rows) ?> siswa</span>
    </div>
    <div class="table-wrap"><table>
        <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Status SPP <?= e($ta['nama']) ?> (Jul–Jun)</th><th>Tunggakan</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($rows as $s): $kartu = kartu_spp($s, $ta); ?>
            <tr>
                <td><?= e($s['nis']) ?></td>
                <td><strong><?= e($s['nama']) ?></strong><small><?= $s['jk'] === 'L' ? 'Laki-laki' : 'Perempuan' ?><?= $s['potongan_persen'] ? ' · potongan ' . $s['potongan_persen'] . '%' : '' ?><?= $s['status'] !== 'aktif' ? ' · ' . $s['status'] : '' ?></small></td>
                <td><?= e($s['kelas']) ?></td>
                <td><div class="mini-months"><?php foreach ($kartu['bulan'] as $b => $i): ?><span class="<?= $i['status'] ?>" title="<?= NAMA_BULAN[$b] ?>"><?= substr(NAMA_BULAN[$b], 0, 1) ?></span><?php endforeach; ?></div></td>
                <td><?= $kartu['jml_tunggak'] ? '<strong style="color:#c0183d">' . rupiah($kartu['total_tunggak']) . '</strong><small>' . $kartu['jml_tunggak'] . ' bulan</small>' : status_badge($kartu['nominal'] ? 'lunas' : 'gratis') ?></td>
                <td><div class="actions">
                    <a class="btn btn-sm btn-yellow" href="<?= url('bayar', ['siswa' => $s['id']]) ?>">Bayar</a>
                    <a class="icon-btn" title="Kartu SPP" href="<?= url('siswa.kartu', ['id' => $s['id']]) ?>"><?= icon('eye', 16) ?></a>
                    <a class="icon-btn" title="Ubah" href="<?= url('siswa.form', ['id' => $s['id']]) ?>"><?= icon('edit', 16) ?></a>
                </div></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="6" class="empty">Tidak ada siswa.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
