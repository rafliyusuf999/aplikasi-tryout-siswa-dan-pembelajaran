# 🚀 INSPIRANET - Platform TO Online (PHP Version)

## ✅ APLIKASI SUDAH DIKONVERSI KE PHP!

Aplikasi ini **SUDAH 100% PHP**, tidak ada lagi bahasa Python.

### 📁 Struktur Aplikasi

```
├── config/              # Konfigurasi
│   ├── database.php    # Koneksi MySQL
│   ├── auth.php        # Session & authentication
│   ├── helpers.php     # Helper functions
│   ├── config.php      # Main config
│   └── schema.sql      # Database schema
│
├── app/Views/includes/  # Template components
│   ├── header.php      # HTML header
│   ├── navbar.php      # Navigation bar
│   └── footer.php      # Footer
│
├── public/              # Document root (web accessible)
│   ├── index.php       # Homepage
│   ├── login.php       # Login page
│   ├── register.php    # Register page  
│   ├── logout.php      # Logout
│   ├── profile.php     # User profile
│   ├── admin/          # Admin pages
│   ├── teacher/        # Teacher pages
│   ├── student/        # Student pages
│   ├── api/            # API endpoints
│   ├── static/         # CSS, JS, images
│   └── .htaccess       # Apache config
│
└── storage/uploads/     # File uploads
    ├── profiles/       # Foto profil
    ├── payments/       # Bukti bayar
    ├── answers/        # Foto jawaban essay
    └── payment/        # QRIS image
```

---

## 🔧 SETUP CEPAT

### 1️⃣ Setup Database

```bash
# Buat database MySQL
mysql -u root -p

# Import schema
mysql -u root -p < config/schema.sql
```

### 2️⃣ Edit Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');         // Sesuaikan
define('DB_PASS', '');             // Sesuaikan
define('DB_NAME', 'inspiranet_db');
```

### 3️⃣ Set Permission Folder Upload

```bash
chmod -R 755 storage/uploads/
# Atau kalau perlu:
chmod -R 777 storage/uploads/
```

### 4️⃣ Login Admin

- **Email:** admin@gmail.com
- **Password:** inspiranetgacor25

---

## 🎯 YANG SUDAH SELESAI

✅ **Database Schema MySQL** - Lengkap dengan semua tabel  
✅ **Authentication System** - Session dengan security hardening  
✅ **CSRF Protection** - Di semua form  
✅ **Password Security** - BCrypt cost 12  
✅ **File Upload System** - Dengan validasi  
✅ **Halaman Login & Register** - Dengan CSRF  
✅ **Halaman Profile** - Update data & foto  
✅ **Admin Dashboard** - Stats & quick access  
✅ **Student Dashboard** - Riwayat TO & stats  
✅ **Layout Components** - Header, navbar, footer  
✅ **Static Files** - CSS, JS, images  
✅ **.htaccess** - Apache routing & security  

---

## 📋 YANG PERLU DILANJUTKAN

Lihat file **STATUS_KONVERSI.md** untuk detail lengkap halaman yang perlu dibuat.

### Prioritas Tinggi:
1. `admin/students.php` - Kelola siswa
2. `admin/teachers.php` - Kelola guru  
3. `admin/exams.php` - Kelola TO
4. `admin/questions.php` - Kelola soal
5. `admin/payments.php` - Konfirmasi pembayaran
6. `student/exams.php` - Daftar TO
7. `student/exam.php` - Mengerjakan TO
8. Dan halaman lainnya...

---

## 📖 DOKUMENTASI

- **README.md** - Setup & deployment guide lengkap
- **INSTRUKSI_KONVERSI.md** - Pola konversi dengan contoh kode
- **STATUS_KONVERSI.md** - Checklist halaman yang perlu dibuat
- **replit.md** - Arsitektur & technical documentation

---

## 🔒 SECURITY FEATURES

✅ **Session Hardening:**
- Session regeneration saat login
- Session token untuk prevent fixation
- IP validation untuk detect hijacking
- Secure cookie settings

✅ **CSRF Protection:**
- Token di semua form
- Verification setiap POST request

✅ **Input Security:**
- Sanitization semua input
- PDO prepared statements
- Output escaping dengan htmlspecialchars()

✅ **Password:**
- BCrypt hashing cost 12
- Minimum 6 karakter

---

## 🚀 DEPLOYMENT

### Shared Hosting (cPanel):

1. Upload semua file ke `public_html/`
2. Set document root ke folder `public/`
3. Import database via phpMyAdmin
4. Edit `config/database.php`
5. Set permission folder `storage/` ke 755/777

### VPS (Apache/Nginx):

Lihat **README.md** untuk config lengkap Apache/Nginx.

---

## ⚠️ CATATAN PENTING

1. ⚠️ **WAJIB pakai CSRF di semua form POST!**
2. ⚠️ **Semua fungsi dari versi Flask HARUS ada**
3. ⚠️ **Test setiap halaman sebelum deploy**
4. ⚠️ **Backup database berkala**

---

## 🆘 BUTUH BANTUAN?

Baca dokumentasi lengkap:
- `README.md` - Cara setup
- `INSTRUKSI_KONVERSI.md` - Cara lanjutkan development
- `STATUS_KONVERSI.md` - Checklist halaman

**Semua sudah PHP, HTML, JS, CSS! Tidak ada Python lagi! 🎉**
