# Panduan Singkat Admin Monitoring BOS SMP

## Gambaran paling sederhana

Sistem ini dipakai untuk mencatat dana BOS dari awal sampai laporan:

```text
Data sekolah → Pagu → Penyaluran/Penerimaan → RKAS → Realisasi → Saldo → Laporan
```

Setiap data keuangan dibuat oleh operator, diperiksa oleh verifikator, lalu baru dihitung ke dashboard setelah disetujui.

## 1. Super Admin

Tugas Super Admin adalah menyiapkan sistem dan akun pengguna.

Urutan kerja:

1. Masuk ke menu **Pengaturan**.
2. Isi **Kecamatan**, **Jenis BOS**, dan **Komponen Belanja**.
3. Isi **Data Sekolah** dan **Tahun Anggaran**.
4. Isi **Tahap Penyaluran** bila diperlukan.
5. Buka menu **Pengguna** dan buat akun Operator, Verifikator, Kepala Bidang, atau Pimpinan.
6. Atur role sesuai tugas masing-masing.
7. Periksa **Audit Log** jika ingin melihat aktivitas pengguna.

Super Admin tidak perlu menghapus data keuangan. Data yang sudah terverifikasi dikunci untuk menjaga riwayat.

## 2. Operator BOS

Tugas Operator adalah memasukkan data dan mengirimkannya untuk diperiksa.

Urutan kerja setiap sekolah:

1. Buka **Pagu Anggaran**, pilih sekolah, tahun, dan jenis BOS.
2. Masukkan nominal pagu sesuai dokumen resmi.
3. Buka aksi **Unggah Bukti** untuk mengunggah SK atau dokumen sumber.
4. Klik **Ajukan**.
5. Setelah pagu disetujui, masukkan **Penyaluran Dana** dan **Penerimaan Dana**.
6. Buat **RKAS**, lalu tambahkan **Rincian RKAS**.
7. Unggah dokumen RKAS dan klik **Ajukan**.
8. Setelah RKAS disetujui, masukkan **Realisasi Belanja**.
9. Masukkan **Saldo Awal**, **Penyesuaian Saldo**, dan **Saldo Dilaporkan** bila tersedia.
10. Jika ada catatan perbaikan, buka data berstatus **Perlu Perbaikan**, perbaiki, lalu ajukan ulang.

Operator tidak dapat mengubah data yang sudah terverifikasi. Gunakan transaksi koreksi jika ada kesalahan.

## 3. Verifikator

Tugas Verifikator adalah memastikan data benar dan dokumennya lengkap.

Urutan kerja:

1. Buka menu **Verifikasi** atau tabel modul yang memiliki status **Diajukan**.
2. Klik **Detail** dan periksa sekolah, tahun, nominal, tanggal, referensi, dan sumber.
3. Buka menu **Dokumen** dan periksa bukti pendukung.
4. Klik **Verifikasi** jika data benar.
5. Klik **Minta Perbaikan** jika ada kesalahan, lalu tulis catatan yang jelas.

Verifikator tidak boleh memverifikasi data yang dibuat oleh dirinya sendiri.

## 4. Kepala Bidang SMP

Tugas Kepala Bidang adalah memantau dan memberi tindak lanjut.

1. Buka **Dashboard Monitoring**.
2. Pilih filter **Data resmi saja** saat melihat angka resmi.
3. Pilih tahun, kecamatan, sekolah, dan periode.
4. Periksa kartu Pagu, Penerimaan, Realisasi, Saldo, dan Temuan.
5. Buka menu **Evaluasi dan Tindak Lanjut** untuk menulis catatan pembinaan.
6. Gunakan **Laporan** untuk membaca atau mengunduh rekap.

## 5. Pimpinan Dinas

Tugas Pimpinan adalah membaca hasil akhir.

1. Buka **Dashboard Monitoring**.
2. Gunakan **Data resmi saja**.
3. Pilih periode atau sekolah yang ingin dilihat.
4. Periksa ringkasan dana, grafik, saldo, dan temuan.
5. Buka **Laporan** dan ekspor PDF/XLSX bila diperlukan.

Pimpinan hanya melihat data yang sudah terverifikasi sehingga angka yang tampil adalah data hasil pemeriksaan.

## Arti warna status

- **Draft**: data masih dikerjakan operator.
- **Diajukan**: menunggu pemeriksaan.
- **Perlu Perbaikan**: ada catatan dari verifikator.
- **Terverifikasi**: data disetujui, dihitung dashboard, dan terkunci.

## Jika ada kesalahan data

- Data masih **Draft**: gunakan **Ubah**.
- Data **Perlu Perbaikan**: ikuti catatan verifikator lalu ajukan ulang.
- Data sudah **Terverifikasi**: jangan dihapus atau diedit; buat transaksi koreksi dan unggah bukti.

## Urutan menu yang disarankan untuk operator

```text
Data Sekolah
→ Tahun Anggaran / Jenis BOS
→ Pagu Anggaran
→ Penyaluran Dana dan Penerimaan Dana
→ RKAS
→ Rincian RKAS
→ Realisasi Belanja
→ Saldo
→ Laporan
```

Data dengan label `[DEMO]` hanya untuk latihan. Untuk rapat atau laporan resmi, selalu pilih **Lingkup Data → Data resmi saja**.

