# DigitalForensik — Dokumentasi & Panduan Penggunaan

Dokumentasi singkat untuk project "DigitalForensik" — aplikasi monitoring website dan deteksi defacement/malware berbasis Laravel.

Catatan: instruksi di sini disusun dalam Bahasa Indonesia.

## Ringkasan Proyek
- Aplikasi Laravel untuk memonitor situs web, mendeteksi defacement (perubahan tampilan) dan potensi injeksi/script berbahaya.
- Fitur utama:
  - Monitoring berkala via `php artisan monitor:run` (juga dijadwalkan via Task Scheduler di Windows).
  - Deteksi berbasis DOM (zona statis & dinamis) plus scan blacklist/malware heuristik.
  - Penyimpanan bukti (`BuktiDigital`) dan pembuatan kasus (`Kasus`) otomatis.
  - Modul `Announcements` (vulnerable-by-design untuk demonstrasi lab).

## Persyaratan & Setup

- PHP (sesuai requirement composer.json), Composer, dan web-server (Laragon / Apache / Nginx).
- Database (MySQL / MariaDB / SQLite sesuai `config/database.php`).

Langkah cepat men-setup project secara lokal:

1. Clone repo ke mesin Anda.
2. Masuk ke folder project:

```powershell
cd C:\\laragon\\www\\DigitalForensik
```

3. Install dependensi PHP:

```powershell
composer install
```

4. Buat file `.env` (salin dari `.env.example` jika tersedia), atur DB dan konfigurasi lain.

5. Generate app key:

```powershell
php artisan key:generate
```

6. Migrasi database & seeder (jika diperlukan):

```powershell
php artisan migrate --seed
```

7. (Opsional) Link storage untuk akses public:

```powershell
php artisan storage:link
```

## Menjalankan Monitor Manual

Untuk menjalankan pemeriksaan sekali jalan (mis. debug):

```powershell
php artisan monitor:run
```

Output dan logging akan muncul di `storage/logs/` (tergantung konfigurasi logging Anda). Jika Anda menjalankan monitor lewat Task Scheduler di Windows, saya merekomendasikan mengarahkan stdout/stderr ke file log, contohnya konfigurasi `schtasks` yang disediakan dalam dokumentasi internal proyek.

## Menjadwalkan di Windows (Task Scheduler)

Contoh perintah `schtasks` untuk membuat task mengeksekusi monitor setiap menit (sesuaikan path PHP/Artisan):

```powershell
# Sesuaikan path php.exe dan path project
schtasks /Create /SC MINUTE /MO 1 /TN "LaravelMonitorRun" /TR "C:\\path\\to\\php.exe C:\\laragon\\www\\DigitalForensik\\artisan monitor:run >> C:\\laragon\\www\\DigitalForensik\\storage\\logs\\monitor.log 2>&1" /F
```

Periksa hasil di `storage/logs/monitor.log` dan Task Scheduler history.

## Konfigurasi `MonitoredSite` dan Selektor

- `MonitoredSite` menyimpan konfigurasi site yang dimonitor, termasuk kolom `selector_static` dan `selector_dynamic`.
- Smart Defaults:
  - Jika `selector_static` kosong → default: `nav, header, footer, aside, .navbar, .footer`.
  - Jika `selector_dynamic` kosong → sistem mencoba auto-discovery urutan `['main','article','#content','#main','.content','body']` dan memakai elemen pertama yang ditemukan.
  - Jika selector tidak menemukan elemen apapun, sistem tidak melempar error — akan mencatat warning dan menjalankan fallback "global blacklist scan" (memindai seluruh body untuk tag berbahaya seperti `<script>`, `<iframe>`, inline style suspicious, form/input, dsb.).

Aturan ini diterapkan di `app/Services/DefacementDetectionService.php`.

## Perilaku Deteksi (Ringkas)

1. Ambil baseline (snapshot) HTML jika belum ada.
2. Bandingkan zona statis (static selectors) dengan baseline berdasarkan hash.
3. Periksa zona dinamis untuk injeksi berbahaya (forbidden tags, inline style abuse, form input injection).
4. Deteksi link/button hijack pada zona dinamis bila anchor dengan kelas button mengarah ke domain tidak-whitelisted.
5. Jika selectors gagal menemukan elemen, jalankan global blacklist scan di seluruh `body`.
6. Jika terdeteksi, buat `Kasus`, simpan `BuktiDigital` (snapshot/headers), dan catat `ActivityLog`.

## Modul Announcements (Catatan Keamanan)

- Modul `Announcements` sengaja disusun agar rentan untuk skenario lab (Stored XSS) — konten ditampilkan dengan unescaped HTML (`{!! $announcement->content !!}`) dan controller menyimpan input mentah.
- Jangan gunakan modul ini di produksi tanpa sanitasi input yang baik.

## Pengujian

- Jalankan test unit/feature (jika tersedia):

```powershell
vendor\\bin\\phpunit
```

Tambahkan test untuk `DefacementDetectionService` jika Anda ingin memverifikasi pengurangan false positives pada halaman dinamis.

## Debugging & Troubleshooting

- Periksa `storage/logs/laravel.log` dan `storage/logs/monitor.log` untuk pesan dari scheduler dan service monitoring.
- Jika deteksi meng-flag false positive: periksa `selector_static` dan `selector_dynamic` pada record `monitored_sites`, atur custom selector yang lebih presisi.
- Untuk melihat bukti yang disimpan, buka `storage/app/public/bukti_digital/monitored_site_{id}/...` (atau sesuai disk `public` Anda).

## Kontribusi

- Fork repository, buat branch fitur/bugfix, lalu kirim PR. Sertakan test untuk perubahan logika deteksi bila relevan.

## Kontak / Dokumentasi Lanjutan

- Untuk pertanyaan teknis, hubungi pemilik repo atau periksa file terkait berikut:
  - `app/Services/DefacementDetectionService.php` — logika deteksi utama.
  - `app/Models/MonitoredSite.php` — model konfigurasi site.
  - `routes/web.php` & `app/Http/Controllers/AnnouncementController.php` — pengaturan routes & announcements.

---
Dokumentasi ini dimaksudkan sebagai pengantar dan panduan operasional. Jika Anda ingin, saya bisa menambahkan contoh screenshot, diagram alur monitoring, atau menulis test integration untuk `DefacementDetectionService`.
