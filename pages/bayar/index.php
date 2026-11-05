<?php
$ta = tahun_ajaran_aktif();
$cari = trim($_GET['cari'] ?? '');
$siswa = null;
$hasil = [];

if (!empty($_GET['siswa'])) {
    $siswa = q('SELECT s.*, k.nama kelas, k.tingkat FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.id = ?', [(int) $_GET['siswa']])->fetch();
} elseif ($cari !== '') {
    $hasil = q("SELECT s.*, k.nama kelas, k.tingkat FROM siswa s JOIN kelas k ON k.id = s.kelas_id
                WHERE s.status = 'aktif' AND (s.nis = ? OR s.nama LIKE ?) ORDER BY s.nama LIMIT 20", [$cari, "%$cari%"])->fetchAll();
    if (count($hasil) === 1) $siswa = $hasil[0];
}
$kartu = $siswa ? kartu_spp($siswa, $ta) : null;

$title = 'Terima Pembayaran';
require __DIR__ . '/../../includes/header.php';
?>
<form class="card" method="get" style="margin-bottom:24px">
    <div class="card-body filters">
        <input type="hidden" name="page" value="bayar">
        <input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Ketik NIS atau nama siswa..." style="flex:1;min-width:240px;font-size:16px" autofocus>
        <button class="btn btn-primary"><?= icon('search', 16) ?> Cari Siswa</button>
    </div>
</form>

<?php if ($hasil && !$siswa): ?>
    <div class="card">
        <div class="card-head"><h2><?= count($hasil) ?> siswa ditemukan</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th></th></tr></thead>
            <tbody><?php foreach ($hasil as $h): ?>
                <tr><td><?= e($h['nis']) ?></td><td><strong><?= e($h['nama']) ?></strong></td><td><?= e($h['kelas']) ?></td>
                    <td class="text-right"><a class="btn btn-sm btn-yellow" href="<?= url('bayar', ['siswa' => $h['id']]) ?>">Pilih</a></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    </div>
<?php elseif ($cari !== '' && !$siswa): ?>
    <div class="alert alert-error">Siswa aktif dengan NIS atau nama "<?= e($cari) ?>" tidak ditemukan.</div>
<?php endif; ?>

<?php if ($siswa): ?>
    <form method="post" action="<?= url('bayar.simpan') ?>" id="form-bayar" data-nominal="<?= $kartu['nominal'] ?>">
        <?= csrf_field() ?><input type="hidden" name="siswa_id" value="<?= $siswa['id'] ?>">
        <div class="grid-2">
            <div class="card">
                <div class="card-head">
                    <div><h2><?= e($siswa['nama']) ?></h2><span class="muted">NIS <?= e($siswa['nis']) ?> · Kelas <?= e($siswa['kelas']) ?> · TA <?= e($ta['nama']) ?></span></div>
                    <a class="btn btn-sm" href="<?= url('siswa.kartu', ['id' => $siswa['id']]) ?>">Kartu SPP</a>
                </div>
                <div class="card-body">
                    <p style="margin:0 0 14px;font-weight:700">Centang bulan yang dibayar:</p>
                    <div class="months">
                        <?php foreach ($kartu['bulan'] as $b => $info): ?>
                            <?php if (in_array($info['status'], ['tunggak', 'belum'], true)): ?>
                                <label class="month <?= $info['status'] ?>">
                                    <input type="checkbox" name="bulan[]" value="<?= $b ?>" <?= $info['status'] === 'tunggak' ? 'checked' : '' ?>>
                                    <b><?= substr(NAMA_BULAN[$b], 0, 3) ?></b><small><?= $info['status'] === 'tunggak' ? 'Menunggak' : 'Belum jatuh tempo' ?></small>
                                </label>
                            <?php else: ?>
                                <div class="month <?= $info['status'] ?>"><b><?= substr(NAMA_BULAN[$b], 0, 3) ?></b><small><?= $info['status'] === 'lunas' ? 'Lunas' : 'Bebas SPP' ?></small></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="card" style="align-self:start">
                <div class="card-head"><h2>Rincian</h2></div>
                <div class="card-body">
                    <p style="margin:0 0 6px">Tarif kelas <?= (int) $siswa['tingkat'] ?>: <b><?= rupiah($kartu['tarif']) ?></b>/bulan</p>
                    <?php if ($siswa['potongan_persen'] > 0): ?><p style="margin:0 0 6px">Potongan beasiswa: <span class="badge badge-purple"><?= (int) $siswa['potongan_persen'] ?>%</span></p><?php endif; ?>
                    <p style="margin:0 0 16px">Dibayar per bulan: <b><?= rupiah($kartu['nominal']) ?></b></p>
                    <div class="stat bg-yellow" style="box-shadow:var(--shadow-sm)"><small>Total bayar (<span id="jml">0</span> bulan)</small><strong id="total">Rp 0</strong></div>
                    <div class="form-grid mt" style="margin-top:18px">
                        <div><label>Tanggal</label><input type="date" name="tgl_bayar" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"></div>
                        <div><label>Metode</label><select name="metode"><option>Tunai</option><option>Transfer</option></select></div>
                    </div>
                    <button class="btn btn-green btn-block mt" <?= $kartu['nominal'] ? '' : 'disabled' ?>><?= icon('check', 16) ?> Simpan & Cetak Kwitansi</button>
                    <?php if ($kartu['jml_tunggak']): ?>
                        <a class="btn btn-block" style="margin-top:12px" target="_blank" href="<?= wa_link($siswa['telp_wali'], "Assalamualaikum {$siswa['nama_wali']}, kami informasikan SPP ananda {$siswa['nama']} (kelas {$siswa['kelas']}) masih ada tunggakan {$kartu['jml_tunggak']} bulan sebesar " . rupiah($kartu['total_tunggak']) . '. Terima kasih. - Bendahara ' . setting()['nama_sekolah']) ?>"><?= icon('message', 16) ?> Ingatkan Wali via WA</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
    <script>
    (function () {
        var f = document.getElementById('form-bayar'), n = +f.dataset.nominal;
        function hitung() {
            var c = f.querySelectorAll('input[name="bulan[]"]:checked').length;
            document.getElementById('jml').textContent = c;
            document.getElementById('total').textContent = 'Rp ' + (c * n).toLocaleString('id-ID');
        }
        f.addEventListener('change', hitung); hitung();
    })();
    </script>
<?php endif; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
