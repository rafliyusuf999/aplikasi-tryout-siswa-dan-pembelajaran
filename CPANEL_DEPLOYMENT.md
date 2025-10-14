# Panduan Deployment ke cPanel

## File-File yang Perlu Diupdate/Upload Saat Deploy

Jika Anda sudah punya aplikasi yang berjalan di cPanel dan hanya ingin update fitur-fitur baru, berikut adalah file-file yang perlu diubah:

### 1. File JavaScript (Frontend)
```
public/static/js/main.js
```
- **Mengapa:** Berisi fitur deteksi kecurangan yang sudah diperbaiki (tambah CSRF token)

### 2. File Header (MathJax)
```
app/Views/includes/header.php
```
- **Mengapa:** Sudah ditambahkan library MathJax untuk render LaTeX formula matematika

### 3. File Soal/Questions
```
public/admin/questions.php
```
- **Mengapa:** 
  - Sudah ditambahkan fitur upload gambar soal
  - Sudah ditambahkan panduan LaTeX untuk input rumus
  - Handle upload dan edit gambar soal

### 4. File Exam Detail (Siswa)
```
public/student/exam_detail.php
```
- **Mengapa:** 
  - Menampilkan gambar soal
  - Render MathJax untuk LaTeX

### 5. File Admin Siswa
```
public/admin/students.php
```
- **Mengapa:** 
  - Fitur export CSV siswa per cabang sudah ditambahkan

### 6. File Admin Ujian/Exams
```
public/admin/exams.php
```
- **Mengapa:** 
  - Tambahan link ke halaman essay answers

### 7. File Baru - Jawaban Essay Admin
```
public/admin/essay_answers.php
```
- **File BARU:** Upload file ini, halaman untuk admin lihat jawaban essay siswa

### 8. File Baru - Migrasi Database
```
public/admin/migrate_add_question_image.php
```
- **File BARU:** Upload file ini dan akses sekali untuk menambah kolom `question_image` ke database

### 9. File Schema Database (Opsional untuk reference)
```
config/schema.sql
config/schema_sqlite.sql
```
- **Mengapa:** Schema sudah diupdate dengan kolom `question_image`
- **Catatan:** Tidak perlu dijalankan jika database sudah ada, cukup jalankan migrate_add_question_image.php

## Langkah-Langkah Deployment ke cPanel:

### A. Upload File
1. **Login ke cPanel** → File Manager
2. **Backup dulu** file-file lama (download atau rename dengan suffix `_backup`)
3. **Upload** semua file di atas ke lokasi yang sama di server
4. **Buat folder baru** untuk upload gambar soal: 
   ```
   public/uploads/questions/
   ```
   Set permission ke 777 (atau 755)

### B. Migrasi Database
1. **Akses URL:** `https://domain-anda.com/admin/migrate_add_question_image.php`
2. Akan muncul pesan konfirmasi bahwa kolom berhasil ditambah
3. Selesai! Database sudah siap

### C. Verifikasi
1. **Login sebagai admin**
2. **Cek fitur baru:**
   - ✅ Tambah soal → ada field upload gambar
   - ✅ Tambah soal → ada panduan LaTeX di textarea
   - ✅ Kelola siswa → ada tombol "Export CSV"
   - ✅ Kelola Try Out → ada tombol "📝 Essay" untuk lihat jawaban essay
   - ✅ Siswa mengerjakan ujian → deteksi kecurangan berfungsi

## File Permission yang Perlu Diset:

```
public/uploads/questions/      → 777 (atau 755)
storage/uploads/answers/       → 777 (atau 755)
storage/uploads/profiles/      → 777 (atau 755)
storage/uploads/payments/      → 777 (atau 755)
storage/                       → 777 (atau 755)
```

## Tips Penting:

1. **Backup dulu** sebelum upload file apapun
2. **Test di local/development** dulu sebelum deploy ke production
3. **Database backup** juga penting sebelum migrasi
4. Jika ada error, cek **error log** di cPanel → Error Log

## File yang TIDAK Perlu Diubah:

❌ `.htaccess` - Tidak ada perubahan
❌ `config/config.php` - Tidak ada perubahan  
❌ `config/database.php` - Tidak ada perubahan
❌ `index.php` - Tidak ada perubahan
❌ File-file lain yang tidak disebutkan di atas

## Troubleshooting:

### Jika gambar soal tidak muncul:
1. Cek permission folder `storage/uploads/questions/`
2. Pastikan path di `config/helpers.php` function `url()` sudah benar

### Jika LaTeX/MathJax tidak render:
1. Cek koneksi internet server (MathJax load dari CDN)
2. Pastikan `app/Views/includes/header.php` sudah terupdate

### Jika deteksi kecurangan tidak jalan:
1. Cek `public/static/js/main.js` sudah terupdate
2. Clear browser cache

### Jika export CSV error:
1. Pastikan `public/admin/students.php` sudah terupdate
2. Cek permission folder write di server

---

**Selesai!** Aplikasi Anda sudah diupdate dengan fitur-fitur baru tanpa perlu reset dari awal.
