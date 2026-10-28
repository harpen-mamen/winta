# Monitoring BOS SMP Sangihe

Penjelasan alur kerja, flowchart, pembagian peran, dan hasil audit tersedia di [`docs/AUDIT-ALUR-SISTEM.md`](docs/AUDIT-ALUR-SISTEM.md).
Panduan praktis per role tersedia di [`docs/PANDUAN-ADMIN-SINGKAT.md`](docs/PANDUAN-ADMIN-SINGKAT.md).

## Data demo dan tema

Dataset demo telah ditambahkan atas permintaan pengguna: 10 sekolah fiktif, 10 pagu, 16 penyaluran, 15 penerimaan, 192 realisasi, RKAS/rincian, saldo, dan contoh pengajuan/perbaikan. Nama sekolah/referensi memakai `[DEMO]`, jenis BOS terpisah `DEMO-BOS`, flag `is_demo` sekolah aktif, dan sumber menyatakan simulasi. Dokumen contoh juga memiliki judul DATA DEMO, bukan bukti resmi.

Gunakan **Lingkup Data → Data resmi saja** untuk mengecualikan demo dari dashboard, monitoring, saldo, dan laporan. Filter yang sama tersedia pada tabel sekolah/keuangan dan diikuti ekspornya. Laporan yang memuat demo diberi penanda pada judul, sumber, dan PDF. Banner demo akan muncul selama sekolah demo tersedia. Status terverifikasi pada demo hanya merupakan simulasi alur pemeriksaan, bukan verifikasi instansi.

Tema baru memakai sidebar navy, menu aktif teal, ikon tiap modul, kartu statistik beraksen warna, panel progres, dan tabel yang lebih ringkas. Muat ulang halaman dengan Ctrl+F5 setelah build.

Seeder demo terpisah dari instalasi resmi: `php artisan db:seed --class=DemoBosSeeder`. Hanya berjalan pada local/testing; dapat diulang tanpa menggandakan record. Tidak menghapus data lama. Dua akun penginput/pemeriksa demo memiliki kata sandi acak yang tidak dibagikan; gunakan akun admin yang sudah ada untuk meninjau data. Jangan menjalankan generator scaffold untuk mengisi demo.

## Data demo dan tema

Dataset demo telah ditambahkan atas permintaan pengguna: 10 sekolah fiktif, 10 pagu, 16 penyaluran, 15 penerimaan, 192 realisasi, RKAS/rincian, saldo, dan contoh pengajuan/perbaikan. Nama sekolah/referensi memakai `[DEMO]`, jenis BOS terpisah `DEMO-BOS`, flag `is_demo` sekolah aktif, dan sumber menyatakan simulasi. Dokumen contoh juga memiliki judul DATA DEMO, bukan bukti resmi.

Gunakan **Lingkup Data → Data resmi saja** untuk mengecualikan demo dari dashboard, monitoring, saldo, dan laporan. Filter yang sama tersedia pada tabel sekolah/keuangan dan diikuti ekspornya. Laporan yang memuat demo diberi penanda pada judul, sumber, dan PDF. Banner demo akan muncul selama sekolah demo tersedia. Status terverifikasi pada demo hanya merupakan simulasi alur pemeriksaan, bukan verifikasi instansi.

Tema baru memakai sidebar navy, menu aktif teal, ikon tiap modul, kartu statistik beraksen warna, panel progres, dan tabel yang lebih ringkas. Muat ulang halaman dengan Ctrl+F5 setelah build.

Seeder demo terpisah dari instalasi resmi: `php artisan db:seed --class=DemoBosSeeder`. Hanya berjalan pada local/testing; dapat diulang tanpa menggandakan record. Tidak menghapus data lama. Dua akun penginput/pemeriksa demo memiliki kata sandi acak yang tidak dibagikan; gunakan akun admin yang sudah ada untuk meninjau data. Jangan menjalankan generator scaffold untuk mengisi demo.

Aplikasi monitoring internal berbasis Laravel 13, Filament 5, Livewire 4, Tailwind 4, dan koneksi database `mysql`. Sistem tidak menggantikan ARKAS/MARKAS atau pengesahan resmi instansi.

## Menjalankan

```powershell
cd C:\winta\monitoring-bos-smp
php artisan serve
```

Buka http://127.0.0.1:8000/admin. Gunakan akun lokal yang telah diberikan di chat. Kata sandi tidak disimpan di panduan atau source code. Ubah kata sandi melalui menu profil di kanan atas. Koneksi database mengikuti `.env`; server lokal yang terukur saat audit adalah MariaDB 10.4.32 pada 127.0.0.1:3306. Jangan mengubah database yang sudah digunakan. Document root Apache/XAMPP harus menuju direktori `public` proyek ini.

Jika source dipindahkan ke komputer lain, jalankan `composer install`, `npm install`, `npm run build`, siapkan `.env` dan database, kemudian `php artisan key:generate` hanya pada instalasi baru, `php artisan migrate`, dan `php artisan db:seed`. Jangan menjalankan `migrate:fresh`. Seeder menetapkan ulang permission lima role dasar; gunakan saat instalasi awal/pengembangan, bukan sembarang setelah kebijakan role diedit.

## Urutan input

1. Masuk sebagai Super Admin. Lengkapi Kecamatan, Tahun Anggaran, Jenis BOS, Komponen Belanja, dan Tahap Penyaluran berdasarkan sumber resmi. Tahun 2026 dan referensi awal BOS Reguler tersedia; konfirmasikan klasifikasi sebelum penggunaan resmi. Tidak ada daftar sekolah atau komponen aturan yang dikarang.
2. Buat akun **Operator BOS** dan **Verifikator** melalui Pengaturan → Pengguna. Gunakan pengguna yang berbeda untuk input dan pemeriksaan. Super Admin juga bisa menginput, tetapi tidak bisa memverifikasi data yang dibuatnya sendiri.
3. Masukkan Data Sekolah dengan NPSN delapan digit. Masukkan Data Murid secara terpisah per sekolah/tahun. Informasi murid tidak digunakan untuk menentukan pagu resmi.
4. Input Pagu Anggaran dari dokumen penetapan. Unggah bukti melalui aksi **Unggah Bukti**, lalu **Ajukan**. Verifikator memeriksa **Detail** dan **Dokumen**, kemudian **Verifikasi** atau **Minta Perbaikan**. Bukti wajib ada sebelum pengajuan.
5. Setelah pagu terverifikasi, input Penyaluran Dana dan Penerimaan Dana secara terpisah. Penerimaan dapat bertahap atau tanpa tautan penyaluran bila sumbernya belum dipasangkan. Referensi transaksi harus unik.
6. Buat header RKAS, tambahkan Rincian RKAS per komponen, unggah bukti pada header, lalu ajukan. Versi terverifikasi dikunci; gunakan versi baru untuk revisi.
7. Catat realisasi terhadap rincian RKAS terverifikasi. Mode **transaksi** menggunakan satu tanggal (awal = akhir = tanggal transaksi); mode **rekap** mencakup rentang periode. Rekap dan transaksi yang tumpang tindih pada komponen sama ditolak agar tidak dihitung ganda.
8. Input Saldo Awal dan, jika sah, Penyesuaian Saldo beserta catatan/bukti. Gunakan Saldo Dilaporkan untuk membandingkan saldo rekening/laporan dengan hasil sistem. Semua data keuangan perlu verifikasi.

## Perubahan dan koreksi

Pagu terverifikasi direvisi melalui **Perubahan Pagu**. Pagu awal dipertahankan dan pagu berlaku berubah setelah revisi disetujui. Usulan yang berbasis nilai lama yang sudah berubah akan ditolak saat verifikasi; buat usulan berdasarkan keadaan terbaru.

Untuk membatalkan transaksi penyaluran, penerimaan, atau realisasi terverifikasi, buat transaksi pembalik dengan **Transaksi Asli yang Dikoreksi**, nominal tepat negatif nilai asli, referensi baru, dan bukti. Pembalik harus diverifikasi. Bila perlu, buat transaksi pengganti terpisah. Satu transaksi asli hanya dapat memiliki satu pembalik. Jangan menggunakan nominal negatif pada transaksi biasa. Dokumen dan transaksi lama dipertahankan sebagai riwayat.

Tahun anggaran yang dikunci menolak perubahan keuangan. Master dinonaktifkan untuk menjaga relasi; penghapusan keuangan tidak disediakan.

## Dashboard, monitoring, dan laporan

Dashboard menyediakan filter tahun, jenis BOS, kecamatan, sekolah, tanggal, dan tahap. Seluruh widget membaca konteks filter yang sama. Penyaluran/penerimaan/realisasi pada periode merupakan arus pada periode; saldo dan penerimaan untuk sisa pagu dihitung kumulatif sampai akhir periode. Pagu akhir periode memakai perubahan terverifikasi yang berlaku sampai tanggal tersebut. Persentase realisasi dibandingkan RKAS versi terakhir sampai akhir periode; transaksi tetap menyimpan rincian versi RKAS rujukannya.

Saldo = saldo awal + penerimaan − pengeluaran + penyesuaian sah. Sisa pagu = pagu berlaku − penerimaan. Keduanya berbeda. Jika filter tahap dipilih, saldo dan realisasi tidak tersedia per tahap karena belanja tidak memiliki atribusi tahap; jangan membaca tanda `—` sebagai nol.

Temuan dan Selisih memuat data belum dikirim/diverifikasi, dokumen kosong, selisih penyaluran-penerimaan, realisasi di atas anggaran, saldo berbeda, dan dugaan transaksi dengan tanggal/nominal sama. Temuan adalah indikator pemeriksaan, bukan kesimpulan pelanggaran. Untuk rekening saldo dilaporkan, pembanding dihitung pada tanggal laporan itu, bukan tanggal terbaru yang berbeda.

Menu Laporan menyediakan sepuluh jenis rekap dengan pratinjau dan ekspor XLSX/PDF beserta pembuat, waktu WITA, sumber, dan filter. Rekap laporan memakai data terverifikasi. Ekspor dari tabel modul mengikuti filter tabel dan mencantumkan status tiap baris; Pimpinan Dinas tetap hanya melihat data terverifikasi.

## Impor Excel

Menu **Impor Data Excel** menyediakan template tiap modul sesuai hak akses. Gunakan header persis. ID referensi dapat dilihat pada kolom **ID** tabel modul. NPSN dan uang di Excel harus berformat **Text**, tanpa pemisah ribuan (contoh `1250000.00`). Tanggal `YYYY-MM-DD`, boolean `1`/`0`. Kolom opsional boleh kosong.

Unggah XLSX → baca pratinjau dan kesalahan per baris → **Simpan Batch**. Validasi pratinjau dibatalkan dari database; tidak ada baris disimpan sebelum commit. Satu batch disimpan atomik, referensi duplikat ditolak, dan checksum mencegah file yang sama diimpor ulang. Semua transaksi impor menjadi draft. Batas awal 5 MB dan 1.000 baris dapat diubah di `config/bos.php`. Pratinjau berlaku satu jam. File staging tetap privat.

OpenSpout dipakai untuk XLSX karena pemasangan Laravel Excel modern membutuhkan ekstensi GD yang tidak tersedia pada PHP aktif. Tidak ada platform requirement yang diabaikan.

## Hak akses dan berkas

Lima role dasar: Super Admin, Operator BOS, Verifikator, Kepala Bidang SMP, dan Pimpinan Dinas. Admin mengelola pengguna/role/referensi/audit; operator menginput dan mengimpor; verifikator memeriksa pengajuan; Kabid melihat monitoring dan mencatat evaluasi; pimpinan melihat data terverifikasi dan laporan. Permission role selain Super Admin dapat disesuaikan. Akun aktif yang terakhir sebagai Super Admin dan akun admin yang sedang digunakan dilindungi dari penonaktifan melalui panel.

Bukti disimpan di `storage/app/private`, bukan `public/storage`. Unduh dilakukan melalui endpoint berautentikasi dan policy target. PDF, XLSX, JPG, PNG diizinkan sesuai MIME dan batas ukuran. Jangan menjalankan `storage:link` untuk membuka dokumen BOS. Audit menyimpan perubahan keuangan, pengelolaan pengguna/role, ekspor, dan login/logout tanpa password/token.

Pengesahan laporan oleh Kabid belum diaktifkan karena memerlukan kebijakan tertulis instansi. Tahap/jadwal, komponen, sumber penetapan, dan referensi instansi harus diisi/disepakati sebelum penggunaan operasional.

## Pengujian

Database test **monitoring_bos_smp_test** terpisah dari database aplikasi. Persiapkan sekali dengan `php C:\winta\tools\prepare-bos-test-db.php`, kemudian `php artisan test`. Pengujian modul menolak target database selain nama tersebut dan memakai rollback transaksi. Data sekolah/keuangan uji berpenanda uji tidak disimpan ke database aplikasi. Build aset: `npm run build`.
