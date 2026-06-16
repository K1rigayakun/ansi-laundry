# SOP (Standard Operating Procedure) Penggunaan Aplikasi Laundry

Aplikasi ini merupakan sistem manajemen laundry berbasis web yang digunakan oleh Admin untuk mengelola pelanggan, transaksi, memantau *membership* (kuota kiloan), hingga mencetak laporan.

Berikut adalah panduan lengkap langkah demi langkah untuk menggunakan semua fitur yang ada.

---

## 1. Login ke Sistem
1. Buka URL aplikasi (`https://laundry-j23.vercel.app`).
2. Anda akan otomatis diarahkan ke halaman **Login**.
3. Masukkan kredensial bawaan berikut:
   - **Username:** `admin`
   - **Password:** `password`
4. Klik tombol **Login**. Jika berhasil, Anda akan masuk ke halaman **Dashboard**.

---

## 2. Dashboard (Halaman Utama)
Di halaman ini, Anda bisa melihat ringkasan bisnis Anda hari ini:
- **Statistik:** Total pelanggan, total transaksi, pemasukan hari ini, dan pemasukan bulan ini.
- **Peringatan Kuota:** Jika ada pelanggan *membership* yang sisa kuotanya 5 kg atau kurang, akan muncul peringatan warna kuning di bagian atas.
- **Grafik & Status:** Terdapat grafik pemasukan 7 hari terakhir dan ringkasan cucian berdasarkan statusnya (berapa yang sedang diproses, menunggu dijemput, dll).

---

## 3. Manajemen Pelanggan (`Menu: Pelanggan`)
Sebelum membuat transaksi, pelanggan harus didaftarkan terlebih dahulu.
1. Klik menu **Pelanggan** di *sidebar* kiri.
2. Klik tombol **+ Tambah Pelanggan**.
3. Masukkan **Nama**, **No. WhatsApp** (pastikan aktif karena akan digunakan untuk notifikasi), dan **Alamat**.
4. Klik **Simpan**.

---

## 4. Manajemen Membership (`Menu: Membership`)
Fitur ini digunakan jika Anda memiliki sistem paket (misalnya pelanggan bayar di awal untuk 30 kg).
1. Klik menu **Membership**.
2. Klik **+ Tambah Kuota**.
3. Pilih nama pelanggan dari *dropdown*, lalu masukkan jumlah kuota (misal: `30` untuk 30 kg).
4. Klik **Simpan**. 
5. Sistem akan melacak *Sisa Kuota* pelanggan secara otomatis setiap kali mereka melakukan transaksi dengan opsi "Gunakan Membership".

---

## 5. Membuat Transaksi Baru (`Menu: Transaksi -> Tambah`)
1. Klik menu **Transaksi**, lalu klik tombol **+ Transaksi Baru**.
2. **Pilih Pelanggan** dari daftar.
3. Masukkan **Berat** cucian (misal: `2.5` kg).
4. Pilih **Jenis Layanan**:
   - *Ambil Sendiri* (Pelanggan antar dan ambil ke toko)
   - *Antar* (Pelanggan antar ke toko, toko mengantar pulang)
   - *Jemput & Antar* (Toko menjemput dan mengantar pulang)
5. **Gunakan Membership**: Centang opsi ini jika pelanggan ingin memotong sisa kuota *membership*-nya. Jika dicentang, *Total Harga* akan otomatis menjadi **Rp 0** (karena sudah dibayar di awal).
6. Tentukan **Status** awal (misal: `Diproses` atau `Menunggu Dijemput`).
7. Isi **Alamat Pengiriman** (opsional, jika layanan antar) dan **Catatan** khusus.
8. Klik **Simpan**.

---

## 6. Update Status Cucian (`Menu: Status Cucian`)
Halaman ini dirancang khusus agar kasir bisa dengan sangat cepat memperbarui status cucian secara berurutan.
1. Klik menu **Status Cucian**.
2. Anda akan melihat daftar cucian yang sedang aktif beserta tombol aksi cepat berwarna.
3. Klik tombol aksi sesuai alur, misalnya:
   - **Tandai Sudah Dijemput** ➔ **Mulai Proses** ➔ **Tandai Selesai** ➔ **Mulai Antar** ➔ **Sudah Diantar**.
4. Jika Anda mengklik tombol **Update** (warna biru), Anda akan masuk ke detail. Di sana terdapat tombol **Notif Selesai** berlogo WhatsApp yang akan otomatis membuka WhatsApp Web dengan pesan siap kirim (lengkap dengan tagihan dan rincian) ke pelanggan.

---

## 7. Layanan Antar-Jemput (`Menu: Antar-Jemput`)
Halaman ini khusus memfilter transaksi yang butuh kurir (layanan *Antar* atau *Jemput & Antar*).
1. Buka menu **Antar-Jemput**.
2. Anda bisa langsung mengklik tombol **Jemput** (WhatsApp) untuk memberi tahu pelanggan bahwa kurir sedang menuju ke lokasi mereka.
3. Atau klik tombol **Antar** (WhatsApp) untuk memberi tahu bahwa cucian sudah selesai dan sedang dalam perjalanan ke rumah mereka.

---

## 8. Laporan Pemasukan (`Menu: Laporan`)
1. Klik menu **Laporan**.
2. Di sini Anda bisa melihat ringkasan pemasukan harian, mingguan (dalam tahun ini), dan bulanan.
3. Anda bisa memfilter bulan dan tahun spesifik di bagian atas untuk melihat histori laporan lama.
---

## 9. Logout
Setelah selesai bertugas, klik profil Admin di sudut kanan atas lalu pilih **Logout** untuk mengakhiri sesi dan mengamankan aplikasi.
