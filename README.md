# MSRA — Money Smart Room Access with AiYO Bills & IoT Door Access

Sistem Pemesanan & Akses Ruangan Komersial Cerdas dengan Integrasi Gateway Pembayaran **AiYO Bills** (QRIS & Virtual Account) dan Kunci Pintu Otomatis **IoT ESP32 Solenoid Door Lock**.

---

## 📌 Identitas Proyek
- **Nama Proyek:** MSRA (Money Smart Room Access)
- **Nama Kelompok:** Money Talks
- **Penyusun / Mahasiswa:** Catherine Wong (`cthrnwong11@gmail.com`)
- **Mata Kuliah:** Teknologi Finansial (Fintech) / Smart City
- **Program Studi:** Sistem Informasi & Rekayasa Perangkat Lunak (SIEGA)
- **Universitas:** Universitas Katolik Soegijapranata (SCU) Semarang
- **Dosen Pengampu:** Prof. Dr. Ir. Ridwan Sanjaya, S.E., S.Kom., MS.IEC.
- **URL Live Production:** [https://app.mesinbayar.com/msra/index.html](https://app.mesinbayar.com/msra/index.html)
- **URL Admin Live:** [https://app.mesinbayar.com/msra/admin.html](https://app.mesinbayar.com/msra/admin.html)

---

## 🚀 Fitur Utama

1. **Mobile-First Responsive PWA (Android / iOS / Desktop):**
   - Katalog ruangan komersial interaktif (*Silent Focus Pod Solo, Acoustic Studio Master, Executive Boardroom Alpha, Innovation Sandbox*).
   - Bottom navigation bar ala aplikasi Android native.
   - PWA ready: dapat di-install langsung ke homescreen smartphone.

2. **Fintech Payment Gateway (AiYO Bills):**
   - Pembayaran **QRIS Nasional** dinamis via Bank CIMB Niaga (022).
   - Pembayaran **Virtual Account (VA)** 4 Bank Besar: Mandiri (008), BNI (009), CIMB Niaga (022), BTN (200).
   - Otentikasi aman OAuth 2.0 dan penandatanganan digital kriptografis HMAC-SHA256 (`apiKey + signRelativeURL + rawBody`, `apiSecret`).
   - Webhook callback instan (`/api/payment/aiyo-webhook`) dan sinkronisasi otomatis ke status `PAID`.

3. **Smart IoT Door Access (ESP32 & 12V Solenoid Lock):**
   - Tiket digital otomatis terbit setelah pembayaran lunas (`SPK-PASS-xxxx`).
   - Scanner kamera smartphone berbasis WebRTC untuk memindai plakat QR fisik pintu.
   - Mikrokontroler ESP32 melakukan polling HTTP (`GET /api/door/poll?room_code=xxx`) setiap 1–2 detik.
   - Relay 1-Channel dengan optocoupler menarik kunci solenoid 12V selama 5 detik, active buzzer berbunyi, dan LCD menampilkan *"SILAKAN MASUK"*.

4. **Konsol Administrator Gedung (`admin.html`):**
   - Dashboard KPI transaksi dan total omzet real-time.
   - Monitoring live ruangan aktif & tombol darurat buka kunci (*Emergency Door Unlock*).
   - Manajemen katalog ruangan & tarif sewa per jam.
   - Kelola hak akses administrator gedung.
   - Audit log akses pintu IoT secara transparan (*Granted / Denied*).

---

## 🛠️ Panduan Menjalankan di Device Lain

### Persyaratan Sistem:
- **Web Server:** Apache (via Laragon atau XAMPP)
- **PHP:** Versi 8.1 / 8.2 / 8.3 dengan ekstensi `pdo_sqlite`, `curl`, `json`, `mbstring` aktif.
- **Database:** SQLite (otomatis aktif di `backend/database/database.sqlite`) dan MySQL (opsional jika menggunakan `fintek` database).

### Langkah Instalasi:

1. **Clone Repositori:**
   ```bash
   # Masuk ke folder web server (contoh Laragon):
   cd C:\laragon\www
   git clone <URL_REPO_GITHUB> aiyobills
   cd aiyobills
   ```

2. **Jalankan Apache di Laragon / XAMPP:**
   - Buka Laragon / XAMPP lalu klik **Start All**.

3. **Akses Sistem di Browser:**
   - **Katalog Publik:** `http://localhost/aiyobills/index.html` (atau `http://aiyobills.test/index.html`)
   - **Konsol Admin:** `http://localhost/aiyobills/admin.html` (atau `http://aiyobills.test/admin.html`)

---

## 🔑 Cara Login Sebagai Administrator

### Di Localhost:
- Di pojok kanan atas `index.html` klik tombol **"⚡ Dev Bypass (Localhost)"**.
- Pilih akun **Catherine Wong (Admin)** (`cthrnwong11@gmail.com`).
- Klik tombol **"Konsol Admin"** yang muncul di navbar.

### Di Server Live Hosting (`https://app.mesinbayar.com/msra/`):
- Buka `https://app.mesinbayar.com/msra/admin.html`.
- Tekan **F12** ➔ buka tab **Console** ➔ ketik perintah ini lalu tekan Enter:
  ```javascript
  localStorage.setItem('msra_user', JSON.stringify({ email: 'cthrnwong11@gmail.com', name: 'Catherine Wong', role: 'admin' })); location.reload();
  ```
- Panel Admin akan langsung terbuka penuh dengan identitas resmi Anda!

---

## 📄 Dokumen Laporan Tugas Pribadi

File laporan tugas pribadi telah disusun lengkap dalam format Word dan Markdown:
- 📁 **File Word Siap Cetak/Submit:** [`Laporan_Individu_MSRA_CatherineWong_Final.docx`](./Laporan_Individu_MSRA_CatherineWong_Final.docx)
- 📝 **File Dokumen Markdown:** [`docs/laporan_tugas_pribadi_msra.md`](./docs/laporan_tugas_pribadi_msra.md)
- 📸 **Seluruh Screenshot Android Live:** [`docs/screenshots/`](./docs/screenshots/)
