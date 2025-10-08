# Changelog - Perbaikan Sistem Inspiranet

## Tanggal: 8 Oktober 2025

### Perbaikan Kritis

#### 1. ✅ Sistem Pembayaran
- **Masalah**: Error saat menyetujui pembayaran karena penggunaan `NOW()` (PostgreSQL) pada database SQLite
- **Solusi**: Mengubah semua `NOW()` menjadi `datetime('now')` di `public/admin/payments.php`
- **File yang diubah**: 
  - `public/admin/payments.php` (3 lokasi)

#### 2. ✅ Validasi Waktu Try Out
- **Masalah**: Siswa bisa mulai try out sebelum waktu yang ditentukan (misal: jam 20:00 padahal seharusnya 23:00)
- **Solusi**: 
  - Menambahkan validasi `start_time` dan `end_time` di `public/student/exam_detail.php`
  - Menambahkan indikator visual waktu mulai/berakhir di `public/student/exams.php`
  - Tombol otomatis disabled untuk ujian yang belum dimulai atau sudah berakhir
- **File yang diubah**:
  - `public/student/exam_detail.php` - validasi server-side
  - `public/student/exams.php` - tampilan status waktu

#### 3. ✅ Duplikasi Data Siswa
- **Masalah**: Banyak siswa dengan nomor telepon yang sama
- **Solusi**:
  - Menambahkan constraint `UNIQUE` pada kolom `phone_number` di schema database
  - Membuat tool cleanup di `public/admin/cleanup_duplicates.php` untuk membersihkan data duplikat
  - Sebelum menerapkan constraint, admin harus membersihkan duplikat terlebih dahulu
- **File yang diubah**:
  - `config/schema_sqlite.sql` - tambah UNIQUE constraint
  - `public/admin/cleanup_duplicates.php` - tool baru untuk cleanup
  - `public/admin/students.php` - link ke tool cleanup

#### 4. ✅ Deteksi Kecurangan
- **Masalah**: Sistem deteksi kecurangan tidak berfungsi karena endpoint yang salah
- **Solusi**: Memperbaiki endpoint API di `public/static/js/main.js` agar sesuai dengan handler di `exam_detail.php`
- **File yang diubah**:
  - `public/static/js/main.js` - perbaikan endpoint mark_cheating

#### 5. ✅ Fitur Hapus Semua Siswa
- **Masalah**: Tidak ada cara untuk menghapus semua siswa sekaligus
- **Solusi**: Menambahkan tombol "Hapus Semua Siswa" dengan double confirmation
- **File yang diubah**:
  - `public/admin/students.php` - tambah action delete_all dan fungsi JavaScript

#### 6. ✅ Perbaikan CSS untuk iPhone
- **Masalah**: Tampilan tidak optimal di iPhone
- **Solusi**:
  - Menambahkan `-webkit-text-size-adjust` untuk mencegah auto-zoom
  - Memperbaiki ukuran minimum tombol (44px) untuk touch target
  - Menambahkan `-webkit-overflow-scrolling: touch` untuk smooth scrolling
  - Font size 16px untuk input fields (mencegah auto-zoom iOS)
  - Responsive layout improvements untuk mobile
- **File yang diubah**:
  - `public/static/css/style.css` - extensive iPhone fixes

#### 7. ✅ Deteksi Screenshot
- **Masalah**: Deteksi screenshot terbatas
- **Solusi**: Menambahkan deteksi untuk:
  - PrintScreen key (Windows/Linux)
  - Cmd+Shift+3/4/5 (macOS screenshot shortcuts)
  - Ctrl+Shift+S (Windows screenshot tool)
  - F12 dan Ctrl+Shift+I/J/C (Developer Tools)
  - Mouse middle button
  - Developer Tools detection via window size
- **File yang diubah**:
  - `public/static/js/main.js` - enhanced anti-cheat

#### 8. ✅ Optimasi dan Efisiensi
- **Masalah**: File tidak terorganisir dengan baik
- **Solusi**:
  - Menambahkan constraint UNIQUE pada phone_number
  - Membersihkan temporary files
  - Dokumentasi lengkap di CHANGELOG.md
  - Tool cleanup untuk maintenance
- **File yang diubah**:
  - `CHANGELOG.md` - file baru (dokumentasi ini)

### Catatan Penting untuk Admin

1. **Database**: Sistem menggunakan SQLite, bukan PostgreSQL. Gunakan `datetime('now')` bukan `NOW()`

2. **Cleanup Duplikat**: Sebelum sistem full berjalan dengan UNIQUE constraint, jalankan:
   - Akses `/admin/cleanup_duplicates.php`
   - Bersihkan semua nomor telepon duplikat
   - Constraint UNIQUE sudah ditambahkan di schema untuk database baru

3. **Backup**: Selalu backup database sebelum melakukan operasi bulk delete atau cleanup

4. **Screenshot Detection**: Meskipun sudah ditingkatkan, deteksi screenshot memiliki keterbatasan browser:
   - Mobile screenshot sulit dideteksi 100%
   - Beberapa tool screenshot pihak ketiga mungkin tidak terdeteksi
   - Sistem mencatat semua pelanggaran yang terdeteksi di kolom `cheating_warnings`

### Testing Checklist

- [x] Payment approval berfungsi dengan baik
- [x] Try out tidak bisa diakses sebelum start_time
- [x] Try out tidak bisa diakses setelah end_time
- [x] Cleanup duplicates tool bekerja
- [x] Delete all students dengan confirmation
- [x] Cheating detection mencatat pelanggaran
- [x] CSS responsive di iPhone
- [x] Screenshot detection improved

### File Baru yang Ditambahkan

- `public/admin/cleanup_duplicates.php` - Tool untuk membersihkan data duplikat
- `CHANGELOG.md` - Dokumentasi ini

### Rekomendasi Selanjutnya

1. **Backup Rutin**: Setup automated backup untuk database SQLite
2. **Monitoring**: Setup logging untuk track error dan suspicious activities
3. **Performance**: Consider database indexing jika data sudah banyak
4. **Security**: Regular security audit untuk anti-cheat system
5. **Mobile App**: Consider developing native mobile app untuk deteksi screenshot yang lebih baik
