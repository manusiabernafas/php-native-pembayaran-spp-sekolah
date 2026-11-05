<?php
// Halaman publik untuk orang tua: cek status SPP cukup dengan NIS + 4 digit terakhir no. WA wali (mencegah orang lain mengintip)
$ta = tahun_ajaran_aktif();
$siswa = null;
$error = null;
if (isset($_GET['nis'])) {
    $s = q("SELECT s.*, k.nama kelas, k.tingkat FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.nis = ? AND s.status = 'aktif'", [trim($_GET['nis'])])->fetch();
    if ($s && strlen($_GET['pin'] ?? '') === 4 && substr(preg_replace('/\D/', '', $s['telp_wali']), -4) === $_GET['pin']) $siswa = $s;
    else $error = 'Data tidak cocok. Periksa NIS dan 4 digit terakhir nomor WhatsApp wali yang terdaftar di sekolah.';
}
$kartu = $siswa ? kartu_spp($siswa, $ta) : null;
$set = setting();
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cek Tagihan SPP · <?= e($set['nama_sekolah']) ?></title><link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<div class="center-page" style="align-items:start;padding-top:48px">
    <div style="width:100%;max-width:760px">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:22px">
            <span class="brand-mark" style="width:52px;height:52px;border:var(--b);border-radius:12px;background:var(--blue);color:#fff;display:grid;place-items:center"><?= icon('school', 26) ?></span>
            <div><h1 style="font-size:28px">Cek Tagihan SPP</h1><span class="muted"><?= e($set['nama_sekolah']) ?> · TA <?= e($ta['nama']) ?></span></div>
        </div>
        <form class="card" method="get" style="margin-bottom:24px"><div class="card-body filters">
            <input type="hidden" name="page" value="cek">
            <input type="text" name="nis" placeholder="NIS siswa" value="<?= e($_GET['nis'] ?? '') ?>" required style="flex:1">
            <input type="text" name="pin" placeholder="4 digit akhir no. WA wali" maxlength="4" inputmode="numeric" required style="width:210px">
            <button class="btn btn-primary"><?= icon('search', 16) ?> Cek</button>
        </div></form>
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <?php if ($siswa): ?>
            <div class="card">
                <div class="card-head"><div><h2><?= e($siswa['nama']) ?></h2><span class="muted">Kelas <?= e($siswa['kelas']) ?> · SPP <?= rupiah($kartu['nominal']) ?>/bulan</span></div>
                    <?= $kartu['jml_tunggak'] ? '<span class="badge badge-red">Tunggakan ' . rupiah($kartu['total_tunggak']) . '</span>' : '<span class="badge badge-green">Tidak ada tunggakan</span>' ?></div>
                <div class="card-body"><div class="months">
                    <?php foreach ($kartu['bulan'] as $b => $i): ?>
                        <div class="month <?= $i['status'] ?>"><b><?= substr(NAMA_BULAN[$b], 0, 3) ?></b><small><?= ['lunas' => 'Lunas', 'tunggak' => 'Belum bayar', 'belum' => 'Belum tempo', 'gratis' => 'Bebas'][$i['status']] ?></small></div>
                    <?php endforeach; ?>
                </div>
                <p class="muted" style="margin:18px 0 0">Pembayaran dapat dilakukan di ruang Tata Usaha. Informasi: <?= e($set['telepon']) ?>.</p></div>
            </div>
        <?php endif; ?>
        <p style="text-align:center;margin-top:26px"><a href="<?= url('login') ?>">Login petugas</a></p>
    </div>
</div>
</body>
</html>
