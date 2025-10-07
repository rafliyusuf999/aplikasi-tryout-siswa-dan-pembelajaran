# Instruksi Konversi Flask ke PHP - INSPIRANET

## Status Konversi

### ✅ SELESAI:
1. **Database Schema MySQL** - `config/schema.sql`
2. **File Konfigurasi:**
   - `config/database.php` - Koneksi database
   - `config/auth.php` - Sistem autentikasi
   - `config/helpers.php` - Helper functions
   - `config/config.php` - Config utama

3. **Layout & Includes:**
   - `app/Views/includes/header.php`
   - `app/Views/includes/navbar.php`
   - `app/Views/includes/footer.php`

4. **Halaman Utama:**
   - `public/index.php` - Homepage
   - `public/login.php` - Login
   - `public/register.php` - Register dengan upload foto
   - `public/logout.php` - Logout

5. **Admin:**
   - `public/admin/dashboard.php` - Admin dashboard

### 🔄 PERLU DIKONVERSI:

#### A. Admin Pages (dari `app.py` route `/admin/...`)
- `admin/students.php` - Kelola siswa (view, add, edit, delete, reset password, clear cheating)
- `admin/teachers.php` - Kelola guru
- `admin/exams.php` - Kelola TO
- `admin/questions.php` - Kelola soal
- `admin/payments.php` - Konfirmasi pembayaran
- `admin/payment_settings.php` - Setting QRIS, rekening
- `admin/essay_answers.php` - Penilaian essay

#### B. Teacher Pages (dari `app.py` route `/teacher/...`)
- `teacher/dashboard.php`
- `teacher/exams.php`
- `teacher/questions.php`
- `teacher/students.php`

#### C. Student Pages (dari `app.py` route `/student/...`)
- `student/dashboard.php`
- `student/exams.php` - List TO
- `student/exam_detail.php` - Detail TO
- `student/pay.php` - Pembayaran
- `student/exam.php` - Mengerjakan TO
- `student/result.php` - Hasil TO

#### D. Other Pages
- `profile.php` - Update profile
- `leaderboards.php` - Semua peringkat

#### E. API Endpoints (folder `public/api/`)
Buat file PHP untuk handle AJAX request:
- `api/submit_exam.php`
- `api/upload_essay.php`
- `api/check_payment.php`
- dll.

## Pola Konversi

### 1. Konversi Route Flask → File PHP

**FLASK:**
```python
@app.route('/admin/students')
@login_required
def admin_students():
    if current_user.role != 'admin':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    students = User.query.filter_by(role='student').all()
    return render_template('admin_students.html', students=students)
```

**PHP:**
```php
<?php
require_once '../../config/config.php';

requireRole('admin');

$pdo = getDB();
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'student'");
$students = $stmt->fetchAll();

$pageTitle = 'Kelola Siswa - Admin';
include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<!-- HTML content here -->

<?php include '../../app/Views/includes/footer.php'; ?>
```

### 2. Konversi SQLAlchemy → PDO

**FLASK (SQLAlchemy):**
```python
user = User.query.filter_by(email=email).first()
User.query.get(id)
db.session.add(user)
db.session.delete(user)
db.session.commit()
```

**PHP (PDO):**
```php
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("INSERT INTO users (...) VALUES (...)");
$stmt->execute([...]);

$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$id]);
```

### 3. Konversi Template Jinja2 → PHP

**JINJA2:**
```jinja
{% for student in students %}
    <tr>
        <td>{{ student.full_name }}</td>
        <td>{{ student.email }}</td>
    </tr>
{% endfor %}

{% if current_user.is_authenticated %}
    <p>Hello {{ current_user.full_name }}</p>
{% endif %}
```

**PHP:**
```php
<?php foreach($students as $student): ?>
    <tr>
        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
        <td><?php echo htmlspecialchars($student['email']); ?></td>
    </tr>
<?php endforeach; ?>

<?php if(isLoggedIn()): 
    $user = getCurrentUser(); ?>
    <p>Hello <?php echo htmlspecialchars($user['full_name']); ?></p>
<?php endif; ?>
```

### 4. Upload File

**FLASK:**
```python
photo = request.files.get('profile_photo')
filename = secure_filename(f"profile_{email}_{datetime.now().timestamp()}.jpg")
filepath = os.path.join(app.config['UPLOAD_FOLDER'], 'profiles', filename)
photo.save(filepath)
```

**PHP:**
```php
$filename = uploadFile($_FILES['profile_photo'], 'profiles', 'profile_' . str_replace(['@', '.'], '', $email) . '_');
```

### 5. Flash Messages

**FLASK:**
```python
flash('Login berhasil!', 'success')
```

**PHP:**
```php
setFlash('Login berhasil!', 'success');
```

### 5b. CSRF Protection (WAJIB untuk semua form!)

**Setiap form POST harus pakai CSRF token:**

```php
<?php
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // WAJIB: Verify CSRF token terlebih dahulu
    if(!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request!', 'danger');
        redirect('back-to-form.php');
    }
    
    // Proses form di sini...
}
?>

<!-- Di form HTML, tambahkan hidden input CSRF token -->
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <!-- form fields lainnya -->
</form>
```

### 6. Password Hashing

**FLASK:**
```python
user.set_password(password)  # Werkzeug
user.check_password(password)
```

**PHP:**
```php
$password_hash = hashPassword($password);  # password_hash()
verifyPassword($password, $hash);  # password_verify()
```

### 7. JSON Response (untuk API)

**FLASK:**
```python
return jsonify({'success': True, 'message': 'Berhasil'}), 200
```

**PHP:**
```php
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Berhasil']);
exit;
```

## Setup Database

1. Buat database MySQL:
```bash
mysql -u root -p
```

2. Import schema:
```bash
mysql -u root -p < config/schema.sql
```

3. Edit `config/database.php` sesuaikan dengan kredensial MySQL Anda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_NAME', 'inspiranet_db');
```

4. Password admin default:
   - Email: `admin@gmail.com`
   - Password: `inspiranetgacor25`

## Static Files & Uploads

- CSS: `public/static/css/`
- JS: `public/static/js/`
- Images: `public/static/img/`
- Uploads: `storage/uploads/`
  - profiles/
  - payments/
  - answers/
  - payment/

## .htaccess (untuk Apache)

Buat file `.htaccess` di folder `public/`:

```apache
RewriteEngine On

# Handle images and assets
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Route uploads
RewriteRule ^storage/uploads/(.*)$ ../storage/uploads/$1 [L]

# Route static files
RewriteRule ^static/(.*)$ static/$1 [L]
```

## Catatan Penting

1. **Semua fungsi harus sama persis** dengan versi Flask
2. **Anti-cheat JavaScript** di `static/js/main.js` sudah compatible, tinggal adjust endpoint API
3. **Leaderboard** perlu logic perhitungan ranking yang sama
4. **Payment** sistem approval sama seperti Flask
5. **Essay grading** dengan upload foto sama
6. **Time zone** sudah diset ke Asia/Jakarta di config.php

## Testing Checklist

- [ ] Login/Register/Logout
- [ ] Admin: CRUD siswa, guru, TO
- [ ] Admin: Approve payment
- [ ] Admin: Grade essay
- [ ] Teacher: Create TO & questions
- [ ] Student: View TO, bayar, kerjakan
- [ ] Leaderboard: branch & global
- [ ] Anti-cheat: detect copy, tab switch
- [ ] File upload: profile, payment proof, essay

## Deployment ke Hosting

1. Upload semua file ke folder `public_html/` atau `www/`
2. Point document root ke folder `public/`
3. Import database
4. Set permission folder `storage/` ke 755 atau 777
5. Edit `config/database.php` dengan kredensial hosting

---

**PENTING:** Ikuti pola konversi di atas untuk semua halaman yang tersisa. Pastikan setiap fungsi dari versi Flask tetap ada dan bekerja sama.
