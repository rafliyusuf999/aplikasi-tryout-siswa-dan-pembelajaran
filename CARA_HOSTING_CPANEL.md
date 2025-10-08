# Panduan Hosting di cPanel

## Cara Upload ke cPanel

### Opsi 1: Document Root ke Public Folder (RECOMMENDED)
1. Login ke cPanel
2. Masuk ke **File Manager**
3. Upload semua file project ke folder `public_html`
4. Di cPanel, ubah **Document Root** ke `public_html/public`
   - Buka **Domains** > Pilih domain Anda > **Manage** > Ubah **Document Root** ke `/public_html/public`
5. Selesai! Website akan langsung bisa diakses

### Opsi 2: Upload Langsung (Pakai File yang Sudah Disediakan)
1. Login ke cPanel
2. Masuk ke **File Manager**
3. Upload semua file project ke folder `public_html`
4. Website akan otomatis redirect ke folder `public/` karena sudah ada file:
   - `index.php` di root (redirect otomatis)
   - `.htaccess` di root (routing)
5. Selesai!

## Setup Database di cPanel

1. Buat database PostgreSQL atau MySQL di cPanel:
   - MySQL: **MySQL Databases** > Buat database baru
   - PostgreSQL: **PostgreSQL Databases** > Buat database baru

2. Catat informasi database:
   - Host: biasanya `localhost`
   - Username: sesuai yang dibuat
   - Password: sesuai yang dibuat
   - Database name: sesuai yang dibuat

3. Update file `config/database.php` dengan kredensial database cPanel

4. Import schema database:
   - Masuk ke **phpMyAdmin** (untuk MySQL) atau **phpPgAdmin** (untuk PostgreSQL)
   - Import file `config/schema.sql`

## Troubleshooting

### Masalah: Muncul "Index of /"
**Solusi:**
- Pastikan file `index.php` ada di root folder
- Pastikan file `.htaccess` ada dan aktif
- Atau ubah Document Root ke folder `public/`

### Masalah: Database Connection Failed
**Solusi:**
- Update `config/database.php` dengan kredensial database cPanel
- Pastikan database sudah dibuat
- Import file `config/schema.sql`

### Masalah: Error 500
**Solusi:**
- Cek file `.htaccess` - pastikan tidak ada error syntax
- Pastikan PHP versi minimal 7.4 atau lebih tinggi
- Cek error log di cPanel

### Masalah: Upload File Tidak Berfungsi
**Solusi:**
- Pastikan folder `storage/uploads/` ada dan writable (chmod 755 atau 777)
- Cek permission folder

## Login Default

Setelah import database, gunakan kredensial berikut:
- **Email:** admin@gmail.com
- **Password:** password

**PENTING:** Segera ganti password setelah login pertama kali!

## Struktur Folder

```
public_html/
├── index.php              (redirect ke public/)
├── .htaccess             (routing utama)
├── app/                  (aplikasi logic)
├── config/               (konfigurasi)
├── public/               (web root)
│   ├── index.php        (entry point)
│   ├── .htaccess        (routing public)
│   └── static/          (CSS, JS, images)
├── storage/             (upload files)
└── uploads/             (user uploads)
```

## Keamanan

1. ✅ Folder `config/` tidak bisa diakses langsung dari web (blocked via .htaccess)
2. ✅ File `.sql`, `.md`, `.env` tidak bisa diakses langsung
3. ✅ Directory listing dimatikan
4. ✅ Security headers sudah diset

---

**Selamat! Aplikasi Anda siap di-hosting di cPanel** 🎉
