# Sistem Billing Warnet, Kasir Full Control & Antrian Dapur

Sistem manajemen warnet modern terintegrasi yang terdiri dari Server Kasir & Layar Antrian Dapur (KDS) berbasis Laravel, serta Aplikasi Client PC berbasis C++ Native (Win32 + GDI+ + WinHTTP).

---

## Arsitektur Proyek

1. **Aplikasi Client PC (client-cpp/)**
   - Bahasa: C++17 Native (Win32 API, GDI+, WinHTTP).
   - Tanpa runtime atau DLL pihak ketiga, menghasilkan file executable ringan (sekitar 230 KB).
   - Fitur:
     - Tampilan Lockscreen dengan form login member dan preview tarif paket.
     - Mode Fullscreen Lock (F11 untuk beralih mode).
     - Tambah paket billing langsung dari PC (potong saldo member atau bayar QRIS).
     - Pemesanan makanan dan minuman (F&B) dengan catatan dapur.
     - Live Tracking antrian dapur per akun member.
     - Pop-up alert pesan dari kasir.

2. **Server Kasir & Dapur (kasir-laravel/)**
   - Framework: Laravel (PHP 8.2+).
   - Frontend: Tailwind CSS + Alpine.js (komponen collapsible sidebar).
   - Database: SQLite (default) / MySQL.
   - Port default: 8088 (http://127.0.0.1:8088).
   - Fitur:
     - Monitoring status 10 terminal PC secara real-time (interval 2 detik).
     - Full kontrol PC: buka paket billing, tambah waktu (+30m, +1 jam), kunci PC, dan kirim pesan pop-up ke layar PC.
     - Layar Antrian Dapur / KDS (3 kolom: Pesanan Baru, Sedang Dimasak, Siap Diantar).
     - Kasir F&B & Konfirmasi pembayaran QRIS.
     - Manajemen akun member: isi saldo, tambah waktu simpanan, edit data member, dan registrasi member baru.
     - Manajemen katalog: tambah/edit menu makanan/minuman dan paket billing.

---

## Cara Menjalankan

### Cara Cepat (Windows)
Jalankan file batch berikut:
```cmd
JALANKAN_SEMUA.bat
```

### Cara Manual

1. **Jalankan Server Laravel Kasir:**
   ```bash
   cd kasir-laravel
   composer install
   cp .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan serve --host=127.0.0.1 --port=8088
   ```
   Buka di browser:
   - Dashboard Kasir: http://127.0.0.1:8088
   - Layar Antrian Dapur: http://127.0.0.1:8088/dapur

2. **Kompilasi & Jalankan Client C++:**
   ```cmd
   cd client-cpp
   build.bat
   WarnetClient.exe
   ```

---

## Akun Demo Bawaan
- Username: `sultan01` | Password: `password` (Saldo: Rp 150.000, Waktu: 2 Jam)
- Username: `gamer02` | Password: `password` (Saldo: Rp 55.000, Waktu: 45 Menit)
- Username: `budi99` | Password: `password` (Saldo: Rp 25.000, Waktu: 15 Menit)
