# INSPIRANET - Versi PHP

## 🎓 Tentang Aplikasi

INSPIRANET adalah platform Try Out (TO) online yang telah dikonversi dari Python Flask ke PHP murni untuk kemudahan hosting di shared hosting.

## ✅ Status Konversi

### Yang Sudah Dibuat:
1. **Database & Konfigurasi**
   - Schema MySQL lengkap (`config/schema.sql`)
   - Sistem autentikasi PHP native session
   - Helper functions untuk upload file, flash messages, dll

2. **Halaman Utama**
   - Homepage (`public/index.php`)
   - Login & Register (`public/login.php`, `public/register.php`)
   - Logout (`public/logout.php`)
   - Profile (`public/profile.php`)

3. **Admin**
   - Dashboard (`public/admin/dashboard.php`)

4. **Layout & Static Files**
   - Header, Navbar, Footer includes
   - CSS, JS, images sudah di-copy
   - JavaScript anti-cheat masih berfungsi

### Yang Perlu Dilanjutkan:
Lihat file `INSTRUKSI_KONVERSI.md` untuk detail lengkap cara melanjutkan konversi halaman-halaman lainnya.

## 🚀 Cara Setup

### 1. Setup Database

```bash
# Login ke MySQL
mysql -u root -p

# Import schema
mysql -u root -p < config/schema.sql
```

### 2. Konfigurasi Database

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'inspiranet_db');
```

### 3. Set Permission Folder Upload

```bash
chmod -R 755 storage/uploads/
# Atau jika perlu:
chmod -R 777 storage/uploads/
```

### 4. Akun Admin Default

- **Email:** admin@gmail.com
- **Password:** inspiranetgacor25

## 📁 Struktur Folder

```
php-version/
├── config/
│   ├── database.php       # Koneksi database
│   ├── auth.php          # Autentikasi & session
│   ├── helpers.php       # Helper functions
│   ├── config.php        # Config utama
│   └── schema.sql        # Database schema
├── app/
│   └── Views/
│       └── includes/
│           ├── header.php
│           ├── navbar.php
│           └── footer.php
├── public/               # Document root
│   ├── index.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── profile.php
│   ├── admin/           # Halaman admin
│   ├── teacher/         # Halaman guru
│   ├── student/         # Halaman siswa
│   ├── api/            # API endpoints
│   └── static/         # CSS, JS, images
└── storage/
    └── uploads/         # File uploads
        ├── profiles/
        ├── payments/
        ├── answers/
        └── payment/
```

## 🔧 Deployment ke Hosting

### Untuk Apache (cPanel, dll):

1. Upload semua file ke `public_html/` atau `www/`

2. Buat file `.htaccess` di folder `public/`:

```apache
RewriteEngine On

# Handle uploads
RewriteRule ^storage/uploads/(.*)$ ../storage/uploads/$1 [L]

# Handle static files
RewriteRule ^static/(.*)$ static/$1 [L]

# Hide PHP extensions (optional)
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^([^\.]+)$ $1.php [NC,L]
```

3. Point document root ke folder `public/` (di cPanel: Domains > Document Root)

4. Import database via phpMyAdmin

5. Edit `config/database.php` dengan kredensial database hosting

### Untuk Nginx:

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/php-version/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /storage/uploads {
        alias /var/www/html/php-version/storage/uploads;
    }
}
```

## 📋 Fitur Utama (Semua Sama dengan Versi Flask)

1. **Multi-Role System:**
   - Admin: Kelola siswa, guru, TO, pembayaran
   - Teacher: Buat TO & soal, lihat hasil siswa
   - Student: Kerjakan TO, bayar premium, lihat ranking

2. **Exam Management:**
   - MCQ (Multiple Choice Questions)
   - Essay dengan upload foto jawaban
   - Timer otomatis
   - Anti-cheat system

3. **Payment System:**
   - Upload bukti bayar
   - Admin approval
   - QRIS & transfer bank

4. **Leaderboard:**
   - Ranking per cabang
   - Ranking global

5. **Anti-Cheat:**
   - Detect copy/paste
   - Tab switch detection
   - Screenshot blocking
   - Right-click prevention

## 🛠️ Melanjutkan Konversi

Baca file **`INSTRUKSI_KONVERSI.md`** untuk:
- Pola konversi Flask → PHP
- Contoh konversi SQLAlchemy → PDO
- Contoh konversi Jinja2 → PHP
- Checklist halaman yang perlu dibuat

## 🔒 Security Notes

- Semua input di-sanitize
- Password di-hash dengan `password_hash()` (bcrypt)
- SQL menggunakan prepared statements (PDO)
- File upload divalidasi type & size
- Session management dengan PHP native

## 📞 Support

Jika ada pertanyaan atau butuh bantuan melanjutkan konversi, hubungi developer atau baca `INSTRUKSI_KONVERSI.md`.

---

**PENTING:** Semua fungsi dari versi Flask harus tetap ada dan bekerja sama di versi PHP ini!
