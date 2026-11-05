<?php
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($_POST['aksi'] ?? '') {
            case 'sekolah':
                q('UPDATE pengaturan SET nama_sekolah=?, alamat=?, telepon=?, kepala_sekolah=?, bendahara=?, tanggal_jatuh_tempo=? WHERE id=1', [
                    trim($_POST['nama_sekolah']), trim($_POST['alamat']), trim($_POST['telepon']), trim($_POST['kepala_sekolah']),
                    trim($_POST['bendahara']), min(28, max(1, (int) $_POST['tanggal_jatuh_tempo'])),
                ]);
                break;
            case 'tahun':
                $mulai = (int) $_POST['tahun_mulai'];
                if ($mulai < 2000) throw new RuntimeException('Tahun tidak valid.');
                db()->beginTransaction();
                q('INSERT INTO tahun_ajaran (nama, tahun_mulai) VALUES (?,?)', [$mulai . '/' . ($mulai + 1), $mulai]);
                $baru = (int) db()->lastInsertId();
                // Salin tarif dari tahun ajaran aktif supaya admin tinggal menyesuaikan
                q('INSERT INTO tarif (tahun_ajaran_id, tingkat, nominal) SELECT ?, tingkat, nominal FROM tarif WHERE tahun_ajaran_id = ?', [$baru, tahun_ajaran_aktif()['id']]);
                db()->commit();
                break;
            case 'aktifkan':
                db()->beginTransaction();
                q('UPDATE tahun_ajaran SET aktif = 0');
                q('UPDATE tahun_ajaran SET aktif = 1 WHERE id = ?', [(int) $_POST['id']]);
                db()->commit();
                break;
            case 'tarif':
                foreach ($_POST['tarif'] ?? [] as $tingkat => $nominal) {
                    q('INSERT INTO tarif (tahun_ajaran_id, tingkat, nominal) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nominal = VALUES(nominal)',
                        [tahun_ajaran_aktif()['id'], (int) $tingkat, max(0, (int) $nominal)]);
                }
                break;
            case 'kelas':
                if (trim($_POST['nama']) === '') throw new RuntimeException('Nama kelas wajib diisi.');
                q('INSERT INTO kelas (nama, tingkat, wali_kelas) VALUES (?,?,?)', [trim($_POST['nama']), (int) $_POST['tingkat'], trim($_POST['wali_kelas'])]);
                break;
            case 'user':
                if (strlen($_POST['password']) < 6) throw new RuntimeException('Password minimal 6 karakter.');
                q('INSERT INTO users (nama, username, password, role) VALUES (?,?,?,?)', [trim($_POST['nama']), trim($_POST['username']),
                    password_hash($_POST['password'], PASSWORD_DEFAULT), $_POST['role'] === 'admin' ? 'admin' : 'bendahara']);
                break;
        }
        flash('Perubahan disimpan.');
    } catch (PDOException $ex) {
        if (db()->inTransaction()) db()->rollBack();
        flash('Gagal: data sudah ada.', 'error');
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'error');
    }
    redirect('master');
}

$set = setting();
$ta = tahun_ajaran_aktif();
$tahun = q('SELECT * FROM tahun_ajaran ORDER BY tahun_mulai DESC')->fetchAll();
$tingkat = q('SELECT DISTINCT tingkat FROM kelas ORDER BY tingkat')->fetchAll(PDO::FETCH_COLUMN);
$tarif = q('SELECT tingkat, nominal FROM tarif WHERE tahun_ajaran_id = ?', [$ta['id']])->fetchAll(PDO::FETCH_KEY_PAIR);
$kelas = q('SELECT k.*, COUNT(s.id) jml FROM kelas k LEFT JOIN siswa s ON s.kelas_id = k.id AND s.status = "aktif" GROUP BY k.id ORDER BY k.tingkat, k.nama')->fetchAll();
$users = q('SELECT * FROM users ORDER BY id')->fetchAll();

$title = 'Pengaturan';
require __DIR__ . '/../includes/header.php';
?>
<div class="grid-2">
    <form method="post" class="card">
        <?= csrf_field() ?><input type="hidden" name="aksi" value="sekolah">
        <div class="card-head"><h2>Profil Sekolah</h2></div>
        <div class="card-body"><div class="form-grid">
            <div class="full"><label>Nama Sekolah</label><input type="text" name="nama_sekolah" value="<?= e($set['nama_sekolah']) ?>" required></div>
            <div class="full"><label>Alamat</label><input type="text" name="alamat" value="<?= e($set['alamat']) ?>"></div>
            <div><label>Telepon</label><input type="text" name="telepon" value="<?= e($set['telepon']) ?>"></div>
            <div><label>Jatuh Tempo (tanggal)</label><input type="number" name="tanggal_jatuh_tempo" value="<?= (int) $set['tanggal_jatuh_tempo'] ?>" min="1" max="28"></div>
            <div><label>Kepala Sekolah</label><input type="text" name="kepala_sekolah" value="<?= e($set['kepala_sekolah']) ?>"></div>
            <div><label>Bendahara</label><input type="text" name="bendahara" value="<?= e($set['bendahara']) ?>"></div>
        </div><div class="form-actions"><button class="btn btn-primary">Simpan</button></div></div>
    </form>
    <div>
        <form method="post" class="card">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="tarif">
            <div class="card-head"><h2>Tarif SPP TA <?= e($ta['nama']) ?></h2></div>
            <div class="card-body">
                <?php foreach ($tingkat as $t): ?>
                    <div style="display:flex;gap:12px;align-items:center;margin-bottom:10px"><b style="width:90px">Kelas <?= (int) $t ?></b>
                        <input type="number" name="tarif[<?= (int) $t ?>]" value="<?= (int) ($tarif[$t] ?? 0) ?>" min="0" step="5000"></div>
                <?php endforeach; ?>
                <button class="btn btn-primary">Simpan Tarif</button>
            </div>
        </form>
        <div class="card mt">
            <div class="card-head"><h2>Tahun Ajaran</h2></div>
            <div class="table-wrap"><table><tbody>
                <?php foreach ($tahun as $t): ?>
                    <tr><td><strong><?= e($t['nama']) ?></strong></td><td class="text-right">
                        <?php if ($t['aktif']): ?><span class="badge badge-green">Aktif</span>
                        <?php else: ?><form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="aksi" value="aktifkan"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm">Aktifkan</button></form><?php endif; ?>
                    </td></tr>
                <?php endforeach; ?>
            </tbody></table></div>
            <form method="post" class="card-body filters" style="border-top:var(--b)">
                <?= csrf_field() ?><input type="hidden" name="aksi" value="tahun">
                <input type="number" name="tahun_mulai" value="<?= (int) $ta['tahun_mulai'] + 1 ?>" style="width:120px">
                <button class="btn btn-yellow"><?= icon('plus', 16) ?> Tahun Ajaran Baru</button>
            </form>
        </div>
    </div>
</div>
<div class="grid-2 mt">
    <div class="card">
        <div class="card-head"><h2>Kelas</h2></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Kelas</th><th>Tingkat</th><th>Wali Kelas</th><th>Siswa</th></tr></thead>
            <tbody><?php foreach ($kelas as $k): ?><tr><td><strong><?= e($k['nama']) ?></strong></td><td><?= (int) $k['tingkat'] ?></td><td><?= e($k['wali_kelas']) ?></td><td><?= $k['jml'] ?></td></tr><?php endforeach; ?></tbody>
        </table></div>
        <form method="post" class="card-body filters" style="border-top:var(--b)">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="kelas">
            <input type="text" name="nama" placeholder="VII D" style="width:90px" required>
            <input type="number" name="tingkat" value="7" min="1" max="12" style="width:80px">
            <input type="text" name="wali_kelas" placeholder="Wali kelas">
            <button class="btn"><?= icon('plus', 16) ?></button>
        </form>
    </div>
    <div class="card" style="align-self:start">
        <div class="card-head"><h2>Pengguna</h2></div>
        <div class="table-wrap"><table><tbody><?php foreach ($users as $x): ?><tr><td><strong><?= e($x['nama']) ?></strong><small><?= e($x['username']) ?></small></td><td class="text-right"><span class="badge <?= $x['role'] === 'admin' ? 'badge-blue' : 'badge-yellow' ?>"><?= e(ucfirst($x['role'])) ?></span></td></tr><?php endforeach; ?></tbody></table></div>
        <form method="post" class="card-body" style="border-top:var(--b)">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="user">
            <div class="form-grid">
                <div><label>Nama</label><input type="text" name="nama" required></div>
                <div><label>Username</label><input type="text" name="username" required></div>
                <div><label>Password</label><input type="password" name="password" minlength="6" required></div>
                <div><label>Peran</label><select name="role"><option value="bendahara">Bendahara</option><option value="admin">Admin</option></select></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary"><?= icon('plus', 16) ?> Tambah</button></div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
