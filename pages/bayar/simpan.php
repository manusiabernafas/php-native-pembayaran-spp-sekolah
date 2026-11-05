<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('bayar');
$ta = tahun_ajaran_aktif();
$siswa = q("SELECT s.*, k.tingkat FROM siswa s JOIN kelas k ON k.id = s.kelas_id WHERE s.id = ? AND s.status = 'aktif'", [(int) ($_POST['siswa_id'] ?? 0)])->fetch();
$bulan = array_values(array_intersect(BULAN_AJARAN, array_map('intval', $_POST['bulan'] ?? [])));
$tgl = ($_POST['tgl_bayar'] ?? '') ?: date('Y-m-d');
if ($tgl > date('Y-m-d')) $tgl = date('Y-m-d');

if (!$siswa || !$bulan) {
    flash('Pilih siswa dan minimal satu bulan.', 'error');
    redirect('bayar', $siswa ? ['siswa' => $siswa['id']] : []);
}
$kartu = kartu_spp($siswa, $ta);
if ($kartu['nominal'] <= 0) {
    flash('Siswa ini bebas SPP.', 'error');
    redirect('bayar', ['siswa' => $siswa['id']]);
}

try {
    db()->beginTransaction();
    q('INSERT INTO transaksi (kode, siswa_id, tahun_ajaran_id, total, tgl_bayar, metode, user_id) VALUES (?,?,?,?,?,?,?)', [
        'TMP-' . bin2hex(random_bytes(4)), $siswa['id'], $ta['id'], $kartu['nominal'] * count($bulan), $tgl,
        ($_POST['metode'] ?? '') === 'Transfer' ? 'Transfer' : 'Tunai', user()['id'],
    ]);
    $id = (int) db()->lastInsertId();
    foreach ($bulan as $b) {
        // UNIQUE (siswa, tahun ajaran, bulan) menolak bulan yang sudah lunas
        q('INSERT INTO pembayaran (transaksi_id, siswa_id, tahun_ajaran_id, bulan, nominal) VALUES (?,?,?,?,?)', [$id, $siswa['id'], $ta['id'], $b, $kartu['nominal']]);
    }
    $kode = 'KW-' . date('ym', strtotime($tgl)) . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    q('UPDATE transaksi SET kode = ? WHERE id = ?', [$kode, $id]);
    db()->commit();
    flash("Pembayaran $kode tersimpan.");
    redirect('kwitansi', ['id' => $id]);
} catch (PDOException $ex) {
    db()->rollBack();
    flash('Sebagian bulan yang dipilih ternyata sudah lunas. Muat ulang data siswa.', 'error');
    redirect('bayar', ['siswa' => $siswa['id']]);
}
