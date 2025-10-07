# INSPIRANET OFFICIAL TO

## Overview

INSPIRANET OFFICIAL TO is an online exam platform designed for high school students (grades 10-12 and alumni) preparing for UTBK (Indonesia's university entrance exam). The platform provides secure, fair online testing across multiple branches with comprehensive anti-cheating measures and branch-based leaderboards.

**Core Purpose:** Deliver a trustworthy online testing platform with strict anti-cheating protections, fair competition through branch-separated leaderboards, and comprehensive exam management for administrators and teachers.

**Target Users:** 
- Students (grades 10, 11, 12, and alumni)
- Teachers (exam creators)
- Administrators (system managers)

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Web Framework & Backend
- **PHP** - Native PHP untuk backend dan server-side logic
- **MySQL/PDO** - Database dengan prepared statements untuk security
- **PHP Session** - Native session management dengan security hardening
- **BCrypt** - Password hashing dengan cost 12

**Design Rationale:** PHP native cocok untuk shared hosting, mudah di-deploy, dan mendukung semua fitur yang dibutuhkan tanpa dependency kompleks.

### Database Schema
- **MySQL** - Production-ready database dengan InnoDB engine
- **Core Tables:**
  - `users` - Multi-role system (admin, teacher, student) dengan branch assignment dan profile photo
  - `exams` - Metadata TO termasuk premium status dan pricing
  - `questions` - Soal multiple-choice dan essay dengan foto
  - `exam_attempts` - Tracking submissions, cheating warnings, dan essay answers (JSON)
  - `payments` - Payment processing dengan admin approval
  - `leaderboards` - Dual ranking (branch-specific dan global)
  - `payment_settings` - QRIS image, payment instructions, bank account details

**Design Rationale:** MySQL cocok untuk shared hosting, mendukung concurrent access, dan memiliki foreign key constraints dengan cascade delete. PDO prepared statements memastikan security dari SQL injection.

### Authentication & Authorization
- Role-based access control (RBAC) with three user types:
  - **Admin:** Full system control, user management, payment approval, question management
  - **Teacher:** Exam creation, question management untuk TO sendiri, view hasil siswa
  - **Student:** Exam participation, payment submission, leaderboard access, profile management
- **PHP Session Management dengan Security Hardening:**
  - Session regeneration saat login (prevent session fixation)
  - Session token untuk extra security
  - IP address validation (detect session hijacking)
  - Secure cookie settings (httponly, samesite=Lax, secure on HTTPS)
- **CSRF Protection:**
  - CSRF token di semua form POST
  - Verification sebelum proses data
- **Password Security:**
  - BCrypt hashing dengan cost 12
  - password_hash() dan password_verify()

**Design Rationale:** PHP native session dengan hardening lengkap, CSRF protection di semua form, dan strong password hashing memastikan aplikasi aman dari common web vulnerabilities.

### Branch Competition System
- **4 Branches:** Inspiranet_Cakrawala 1-4
- **Dual Leaderboard Architecture:**
  - Branch-specific rankings for fair local competition
  - Global rankings across all branches
- Students assigned to specific branches during registration

**Design Rationale:** Separate branch leaderboards ensure fair competition within smaller groups while global leaderboards provide overall achievement visibility. This increases motivation through achievable local rankings.

### Anti-Cheating Architecture
- **Enhanced Security Measures:**
  - Auto-logout on copy attempt (immediate session termination)
  - Auto-restart exam on tab/window switching (all answers cleared)
  - Security warning modal before exam start (explains rules)
  - Screenshot blocking and right-click prevention
  - Real-time timer enforcement
  - Cheating warnings tracked per exam attempt

**Design Rationale:** Multi-layered client-side protection with strict enforcement (auto-logout and auto-restart) creates strong barriers to common cheating methods. Security modal ensures students are aware of consequences before starting.

### Payment & Premium Content
- Two-tier exam system: Free and Premium
- **Dual Payment Workflows:**
  1. **Student-Initiated:** Student uploads payment proof → Admin reviews and approves/rejects → Access granted
  2. **Admin-Initiated:** Admin directly creates approved payment for any student and premium exam
- **Payment Settings Management:**
  - Admin can configure QRIS payment image
  - Customizable payment instructions and bank account details
  - Settings displayed dynamically on student payment page
- File upload handling with Werkzeug's secure_filename

**Design Rationale:** Flexible payment system supports both student uploads and direct admin management for scholarships or special cases. Configurable payment settings eliminate hardcoded payment details and allow easy updates via admin interface.

### Frontend Architecture
- **PHP Templates** - Server-side rendering dengan include system
- **Custom CSS** dengan CSS variables untuk theming dan responsive design
- **Vanilla JavaScript** untuk interactivity, anti-cheat enforcement, dan async file uploads
- **Fully Responsive Design** using CSS Grid dan Flexbox:
  - Desktop: 1024px and above
  - Tablet: 768px - 1023px
  - Mobile: Below 768px
- **Enhanced User Interface:**
  - Navbar dengan profile photo dan user name
  - Profile management page untuk semua user
  - Countdown timer dengan format HH:MM:SS
  - Visual question status indicators (grey=unanswered, green=answered, yellow=doubtful)
  - Leaderboard dengan trophy icons untuk top 3
  - Combined highest score dari premium dan free exams

**Design Rationale:** PHP includes untuk reusable components (header, navbar, footer). Vanilla JS mengurangi dependency. Responsive design optimal untuk semua device.

### File Structure
- `config/` - Konfigurasi aplikasi
  - `database.php` - PDO database connection
  - `auth.php` - Session management & authentication helpers
  - `helpers.php` - Utility functions (flash, upload, sanitize, dll)
  - `config.php` - Main config dengan session hardening
  - `schema.sql` - MySQL database schema
- `app/Views/includes/` - Reusable template components
  - `header.php` - HTML head dan opening tags
  - `navbar.php` - Navigation bar dengan profile photo
  - `footer.php` - Footer dan closing tags
- `public/` - Document root (web accessible)
  - `index.php` - Homepage
  - `login.php`, `register.php`, `logout.php` - Auth pages
  - `profile.php` - User profile management
  - `admin/` - Admin pages (dashboard, students, teachers, exams, dll)
  - `teacher/` - Teacher pages (dashboard, exams, questions, dll)
  - `student/` - Student pages (dashboard, exams, payment, dll)
  - `api/` - API endpoints untuk AJAX
  - `static/` - CSS, JS, images
  - `.htaccess` - Apache routing & security
- `storage/uploads/` - File uploads (profiles, payments, answers, payment)

## Recent Changes (October 2025)

### Database Migration
- **Migrated from MySQL to PostgreSQL** - Using Replit's managed PostgreSQL (Neon)
- **Schema converted** - All tables migrated with proper data types
- **Boolean fields fixed** - `is_premium`, `is_active`, `is_completed` now use BOOLEAN type
- **Data permanency** - All data stored in cloud database (persistent across sessions)

### Payment System Updates
- **Removed pending tab** - Default view is now "approved" payments
- **Added Payment Settings page** - `/admin/payment_settings.php` for QRIS & bank config
- **QRIS upload** - Admin can upload/update QRIS payment image
- **Bank account configuration** - Admin can set bank name, account number, and account holder

### Questions Management
- **Added Questions page** - `/admin/questions.php` for managing exam questions
- **Multiple choice & essay** - Support for both question types
- **Category tagging** - Questions can be categorized (Math, Science, etc.)

### Bug Fixes
- **Boolean type errors fixed** - SQL queries updated for PostgreSQL boolean compatibility
- **Redirect function improved** - Absolute path handling to prevent double path issues
- **Mobile responsive** - iPhone/Safari specific CSS fixes for animations

## External Dependencies

### PHP Requirements
- **PHP 8.2+** - Core language
- **PDO PostgreSQL Extension** - Database connectivity (changed from MySQL)
- **GD atau Imagick** - Image processing (optional)
- **Apache/Nginx** - Web server

### Storage
- **PostgreSQL Database** - Replit managed database (Neon-backed)
- **File Uploads:**
  - Payment proof images: `storage/uploads/payments/` (max 16MB)
  - QRIS payment image: `storage/uploads/payment/` (admin-configurable)
  - Student profile photos: `storage/uploads/profiles/` (displayed in navbar)
  - Essay answer photos: `storage/uploads/answers/`

### Environment Configuration
- **Database credentials** di `config/database.php`:
  - DB_HOST (default: localhost)
  - DB_USER (default: root)
  - DB_PASS (default: '')
  - DB_NAME (default: inspiranet_db)
- **Admin account default:**
  - Email: admin@gmail.com
  - Password: inspiranetgacor25

### Deployment
- Cocok untuk **shared hosting** (cPanel, DirectAdmin, dll)
- Cocok untuk **VPS** dengan Apache/Nginx
- Mudah di-deploy tanpa complex dependencies
- Database import via phpMyAdmin atau MySQL CLI