# Sistem Pembayaran SPP Sekolah (PHP Native + MySQL)

Sistem Pembayaran SPP Sekolah berbasis PHP Native dan MySQL untuk bendahara SD, SMP, SMA, SMK, madrasah, maupun pesantren. Bendahara cukup mencari siswa dengan NIS atau nama, mencentang bulan yang dibayar (bisa beberapa bulan sekaligus), lalu kwitansi langsung tercetak. Setiap siswa punya kartu SPP 12 bulan per tahun ajaran (Juli sampai Juni) yang menunjukkan bulan lunas, menunggak, atau belum jatuh tempo.

Tunggakan dihitung otomatis berdasarkan tanggal jatuh tempo, lengkap dengan tombol pengingat WhatsApp ke orang tua yang pesannya sudah terisi rinci. Mendukung tarif berbeda per tingkat kelas, potongan atau beasiswa per siswa, pergantian tahun ajaran, laporan penerimaan tunai dan transfer, serta halaman cek tagihan untuk orang tua tanpa login. Tampilannya bergaya neobrutalism yang segar dan mudah dibaca, cocok untuk dipakai langsung atau sebagai bahan tugas kuliah dan skripsi.

## Fitur

- Terima pembayaran: cari siswa via NIS/nama, centang beberapa bulan sekaligus, total otomatis
- Kwitansi SPP siap cetak dengan rincian bulan
- Kartu SPP 12 bulan per siswa (Juli s.d. Juni): Lunas, Menunggak, Belum jatuh tempo, Bebas SPP
- Perhitungan tunggakan otomatis berdasarkan tanggal jatuh tempo yang bisa diatur
- Anti bayar dobel: bulan yang sama tidak bisa dibayar dua kali (UNIQUE key)
- Tarif SPP berbeda per tingkat kelas dan per tahun ajaran
- Potongan / beasiswa per siswa dalam persen (100% = bebas SPP)
- Daftar tunggakan per kelas + tombol pengingat WhatsApp ke orang tua (pesan otomatis)
- Halaman cek tagihan untuk orang tua tanpa login (NIS + 4 digit akhir nomor WA wali)
- Dashboard: penerimaan hari ini dan bulan ini, total tunggakan, persentase siswa tertib bayar
- Grafik penerimaan per bulan dan grafik tunggakan per kelas
- Laporan penerimaan per periode: total, tunai, transfer, rekap per kelas
- Ekspor laporan penerimaan dan daftar tunggakan ke CSV (Excel)
- Pembatalan transaksi oleh admin (bulan otomatis kembali belum lunas)
- Manajemen tahun ajaran (salin tarif otomatis), kelas, siswa, dan pengguna
- 2 peran: Admin dan Bendahara
- Keamanan: PDO prepared statement, password_hash, proteksi CSRF
- Data contoh 180 siswa, 9 kelas, dan 400+ transaksi yang menyesuaikan tanggal import
- Tampilan neobrutalism modern dan responsif
- Tanpa framework dan tanpa Composer, langsung jalan di XAMPP

## Instalasi

**Kebutuhan:** PHP 8.0+ dan MySQL/MariaDB (XAMPP atau Laragon).

1. Ekstrak ZIP, lalu pindahkan folder `spp-sekolah` ke:
   - XAMPP: `C:\xampp\htdocs\spp-sekolah`
   - Laragon: `C:\laragon\www\spp-sekolah`

2. Jalankan **Apache** dan **MySQL**.

3. Buka `http://localhost/phpmyadmin` → **Import** → pilih `database/spp_sekolah.sql` → **Import**.

   Atau lewat terminal:
   ```bash
   mysql -u root -p < database/spp_sekolah.sql
   ```
   Data contoh menyesuaikan tanggal saat import: tahun ajaran aktif mengikuti tahun berjalan.

4. Sesuaikan `config/database.php` jika user/password MySQL kamu berbeda:
   ```php
   return [
       'host'     => 'localhost',
       'port'     => 3306,
       'database' => 'spp_sekolah',
       'username' => 'root',
       'password' => '',
   ];
   ```

5. Buka `http://localhost/spp-sekolah` dan login:
   - Admin: `admin` / `admin123`
   - Bendahara: `bendahara` / `admin123`

6. Halaman cek tagihan untuk orang tua (tanpa login):
   ```
   http://localhost/spp-sekolah/index.php?page=cek
   ```

7. Buka menu **Pengaturan** untuk mengganti nama sekolah, tanggal jatuh tempo, tarif SPP, kelas, dan tahun ajaran.

---
Source code ini dibagikan gratis oleh **Papicode** — https://papicode.id
