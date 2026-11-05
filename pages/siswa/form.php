<?php
$id = (int) ($_GET['id'] ?? 0);
$s = $id ? q('SELECT * FROM siswa WHERE id = ?', [$id])->fetch() : null;
if ($id && !$s) redirect('siswa');
$kelasList = q('SELECT * FROM kelas ORDER BY tingkat, nama')->fetchAll();
$data = $s ?: ['nis' => '', 'nama' => '', 'jk' => 'L', 'kelas_id' => $kelasList[0]['id'] ?? '', 'nama_wali' => '', 'telp_wali' => '', 'potongan_persen' => 0, 'status' => 'aktif'];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nis', 'nama', 'jk', 'kelas_id', 'nama_wali', 'telp_wali', 'potongan_persen', 'status'] as $f) $data[$f] = trim($_POST[$f] ?? '');
    $data['potongan_persen'] = min(100, max(0, (int) $data['potongan_persen']));
    if (!preg_match('/^\d{4,20}$/', $data['nis']) || $data['nama'] === '') {
        $error = 'NIS (angka) dan nama wajib diisi.';
    } elseif (q('SELECT id FROM siswa WHERE nis = ? AND id <> ?', [$data['nis'], $id])->fetch()) {
        $error = 'NIS sudah dipakai siswa lain.';
    } else {
        $vals = [$data['nis'], $data['nama'], $data['jk'] === 'P' ? 'P' : 'L', (int) $data['kelas_id'] ?: null, $data['nama_wali'], $data['telp_wali'], $data['potongan_persen'],
            in_array($data['status'], ['aktif', 'lulus', 'keluar'], true) ? $data['status'] : 'aktif'];
        if ($id) q('UPDATE siswa SET nis=?, nama=?, jk=?, kelas_id=?, nama_wali=?, telp_wali=?, potongan_persen=?, status=? WHERE id=?', [...$vals, $id]);
        else q('INSERT INTO siswa (nis, nama, jk, kelas_id, nama_wali, telp_wali, potongan_persen, status) VALUES (?,?,?,?,?,?,?,?)', $vals);
        flash('Data siswa disimpan.');
        redirect('siswa', ['kelas' => $data['kelas_id']]);
    }
}
$title = $id ? 'Ubah Siswa' : 'Tambah Siswa';
require __DIR__ . '/../../includes/header.php';
?>
<form method="post" class="card" style="max-width:820px">
    <?= csrf_field() ?>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
        <div class="form-grid">
            <div><label>NIS</label><input type="text" name="nis" value="<?= e($data['nis']) ?>" inputmode="numeric" required></div>
            <div><label>Nama Lengkap</label><input type="text" name="nama" value="<?= e($data['nama']) ?>" required></div>
            <div><label>Jenis Kelamin</label><select name="jk"><option value="L">Laki-laki</option><option value="P" <?= $data['jk'] === 'P' ? 'selected' : '' ?>>Perempuan</option></select></div>
            <div><label>Kelas</label><select name="kelas_id"><?php foreach ($kelasList as $k): ?><option value="<?= $k['id'] ?>" <?= $data['kelas_id'] == $k['id'] ? 'selected' : '' ?>><?= e($k['nama']) ?></option><?php endforeach; ?></select></div>
            <div><label>Nama Orang Tua / Wali</label><input type="text" name="nama_wali" value="<?= e($data['nama_wali']) ?>"></div>
            <div><label>No. WhatsApp Wali</label><input type="text" name="telp_wali" value="<?= e($data['telp_wali']) ?>"></div>
            <div><label>Potongan / Beasiswa (%)</label><input type="number" name="potongan_persen" value="<?= (int) $data['potongan_persen'] ?>" min="0" max="100"></div>
            <div><label>Status</label><select name="status"><?php foreach (['aktif', 'lulus', 'keluar'] as $o): ?><option value="<?= $o ?>" <?= $data['status'] === $o ? 'selected' : '' ?>><?= ucfirst($o) ?></option><?php endforeach; ?></select></div>
        </div>
        <div class="form-actions"><button class="btn btn-primary">Simpan</button><a class="btn" href="<?= url('siswa') ?>">Batal</a></div>
    </div>
</form>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
