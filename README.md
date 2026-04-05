# SIMPRO - Sistem Manajemen Proyek & Purchase Order

SIMPRO adalah aplikasi berbasis web yang dirancang untuk mengelola operasional proyek secara efisien, mulai dari permintaan material hingga pembayaran upah tenaga kerja harian. Sistem ini membantu sinkronisasi antara kebutuhan lapangan (proyek), departemen pengadaan (purchasing), dan manajemen.

## 🚀 Fitur Utama

### 1. Dashboard Operasional (Real-time)
- Rekapitulasi statistik: Total Proyek Aktif, Permintaan Bahan, Purchase Order, Penerimaan Barang, dan Total Upah.
- Widget Status Realisasi & Workflow yang interaktif (menampilkan MR menunggu approval, MR terpenuhi sebagian, dll).
- Ringkasan aktivitas operasional harian (jumlah transaksi dan total nominal).

### 2. Manajemen Proyek & Supplier
- Master data proyek (kode, nama, lokasi, status).
- Master data supplier untuk rantai pasokan bahan baku.

### 3. Modul Permintaan Bahan (Material Request - MR)
- Pengajuan kebutuhan bahan baku oleh staf proyek.
- Alur approval berjenjang oleh Manajer/Admin.
- Tracking status: Draft, Diajukan, Disetujui, Ditolak, Terpenuhi Sebagian, atau Selesai (Diproses ke PO).

### 4. Modul Purchase Order (PO)
- Konversi Permintaan Bahan (MR) yang disetujui menjadi Purchase Order.
- Pemilihan supplier dan kalkulasi total otomatis.
- Cetak dokumen PO untuk dikirim ke supplier.

### 5. Modul Penerimaan Barang (Goods Receipt)
- Pencatatan barang masuk berdasarkan dokumen PO.
- Validasi jumlah barang yang dipesan vs yang diterima.
- Update stok bahan baku otomatis.

### 6. Modul Pembayaran Upah Harian
- Pencatatan upah tenaga kerja proyek berdasarkan rentang tanggal kerja (periode fleksibel).
- Kalkulasi otomatis: (Jumlah Hari x Upah) + Lembur + Tambahan - Potongan.
- Fitur **Revisi Upah** dengan audit trail (log perubahan nominal, alasan, dan user pengubah).

### 7. Monitoring & Laporan
- Filter laporan operasional berdasarkan rentang tanggal, proyek, dan status dokumen.
- Laporan monitoring pengeluaran operasional terintegrasi.

---

## 🔄 Alur Kerja (Workflows)

### Alur Pengadaan Barang
1. **Permintaan (MR)**: Staf proyek membuat permintaan bahan.
2. **Approval**: Manajer meninjau dan menyetujui/menolak MR tersebut.
3. **Pemesanan (PO)**: Purchasing membuat Purchase Order berdasarkan MR yang telah disetujui.
4. **Penerimaan (GR)**: Barang diterima di lokasi proyek dan dicatat dalam sistem sebagai bukti barang masuk.

### Alur Pembayaran Upah
1. **Pencatatan**: Admin menginput data absensi/kerja pekerja untuk periode tertentu.
2. **Kalkulasi**: Sistem menghitung total upah bersih secara otomatis.
3. **Revisi (Jika Perlu)**: Jika ada kesalahan input, dilakukan revisi yang akan tercatat dalam log audit.
4. **Pembayaran**: Status diubah menjadi 'Dibayar' setelah dana diserahkan.

---

## 🛠️ Stack Teknologi

- **Bahasa Pemrograman**: PHP Native (v7.4+)
- **Database**: MySQL / MariaDB
- **Frontend**: Bootstrap 5, FontAwesome 6, Inter Google Fonts
- **Kalkulasi**: JavaScript (Vanilla JS) untuk interaksi real-time di form
- **Autentikasi**: Session-based login dengan Role-Based Access Control (Admin, Manajer, Purchasing, Staf Proyek)

---

## ⚙️ Instalasi

1. Clone repository ini ke folder `htdocs` atau `www` Anda.
2. Import database menggunakan file SQL yang tersedia (`database.sql` atau `database_mr.sql`).
3. Sesuaikan konfigurasi database pada file `config/koneksi.php`.
4. Login menggunakan akun default yang ada di tabel `users`.

---
*Dikembangkan untuk efisiensi operasional proyek dan transparansi aliran kerja.*