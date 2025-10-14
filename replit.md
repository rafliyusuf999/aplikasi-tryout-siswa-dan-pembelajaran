# INSPIRANET OFFICIAL TO

## Overview

INSPIRANET OFFICIAL TO is an online exam platform for high school students (grades 10-12 and alumni) preparing for Indonesia's UTBK university entrance exam. It provides secure, fair online testing with comprehensive anti-cheating measures and branch-based leaderboards, aiming to deliver a trustworthy platform with fair competition and efficient exam management.

## Recent Changes

### 2025-10-14: Tryout System Enhancements
- **Correct Answer Tracking:** Added `correct_count` column to track number of correct answers in exam attempts
  - Schema updated to include correct_count in exam_attempts table
  - Exam submission now calculates and saves number of correct answers
  - Result page displays "Soal Benar" (correct answers count) as primary metric
  - Kecepatan Pengerjaan (exam completion speed/duration) prominently displayed
- **Leaderboard Improvements:** Enhanced leaderboard to show complete performance metrics
  - Added "Soal Benar" column showing number of correct answers for each participant
  - Speed/duration calculation properly displayed for all attempts
  - Improved data visibility with color-coded correct answers (green)
- **Dashboard Enhancement:** Added dedicated Premium and Free tryout cards
  - Separate "Try Out Gratis" section with green gradient styling
  - Separate "Try Out Premium" section with gold gradient styling
  - Each section shows up to 3 latest tryouts with quick access buttons
  - Payment status and attempt count properly displayed
  - Direct links to "Lihat Semua" for complete tryout lists
- **Scoring Fix:** Fixed scoring system to be case-insensitive
  - Students answering 'a', 'A', ' a ', ' A ' all get points if correct answer is 'A'
  - Prevents scoring issues caused by letter case differences
  - Trims whitespace to ensure accurate comparison

### 2025-10-09: UI/UX Improvements & Text Color Optimization
- **Text Color Standardization:** All text colors throughout the application updated to black (#1a1a1a, #2d2d2d) for maximum readability and consistency
  - Updated all gray colors (#666, #999, #4a4a4a) to black in PHP files and CSS
  - Maintained white text only on dark backgrounds (navbar, stat-cards, modals) for proper contrast
  - Button text colors standardized to white (#FFFFFF !important) with centered alignment
  - All headings (H1, H2) changed from var(--primary-color) to black (#1a1a1a) across all pages
- **Featured Section Redesign:** "Fitur Unggulan" section restructured with individual feature cards in responsive grid layout
  - Centered layout with max-width 900px
  - Each feature has own card with hover effects
  - Light blue gradient backgrounds (#EFF6FF → #DBEAFE)
  - Payment feature card updated to "Try Out Gratis & Premium" to show both options
- **Dashboard Improvements:** All dashboard headings centered and colored black for better readability
  - Admin Dashboard: "👨‍💼 Admin Dashboard" centered and black
  - Teacher Dashboard: "👨‍🏫 Dashboard Teacher" centered and black
  - Student Dashboard: "📊 Dashboard Siswa" centered and black
  - Quick Access buttons: White text on blue gradient with proper contrast
- **Leaderboard Page Enhancements:** 
  - Main heading "🏆 Peringkat Try Out" centered with black color
  - Labels and sub-headings updated to black for consistency
- **iOS Anti-Cheat Fix:** Developer Tools detection threshold increased from 160px to 300px for iOS devices to prevent false positives on iPhone
- **Payment Photo Display Fix:** QRIS image path corrected from `uploads/payment/` to `storage/uploads/payment/`
- **Footer Enhancement:** Copyright text styling improved with black color, bold font weight, and centered alignment

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Web Framework & Backend
- **PHP** for server-side logic.
- **PDO** for database interaction with prepared statements.
- **PHP Session** for native session management with security hardening.
- **BCrypt** for password hashing (cost 12).
- **Design Rationale:** Native PHP is chosen for ease of deployment on shared hosting and minimal dependencies.

### Database Schema
- **PostgreSQL** as the primary database.
- **Core Tables:** `users` (multi-role), `exams` (metadata, premium status), `questions` (multiple-choice, essay), `exam_attempts` (submissions, cheating warnings), `payments` (processing, admin approval), `leaderboards` (branch-specific and global), `payment_settings` (QRIS, instructions).
- **Design Rationale:** PostgreSQL for production readiness, concurrent access, and foreign key constraints.

### Authentication & Authorization
- **Role-Based Access Control (RBAC)** with Admin, Teacher, and Student roles.
- **PHP Native Session Management:** Includes session regeneration, token validation, IP address validation, and secure cookie settings.
- **CSRF Protection:** CSRF tokens on all POST forms.
- **Password Security:** BCrypt hashing using `password_hash()` and `password_verify()`.
- **Design Rationale:** Comprehensive native PHP security features protect against common web vulnerabilities.

### Branch Competition System
- Supports **4 distinct branches** (Inspiranet_Cakrawala 1-4).
- **Dual Leaderboards:** Branch-specific for local competition and global for overall ranking.
- **Design Rationale:** Fosters motivation through fair competition within smaller groups and visible overall achievement.

### Anti-Cheating Architecture
- **Client-Side Protections:** Auto-logout on copy attempt, auto-restart exam on tab/window switching (clears answers), security warning modal, screenshot blocking, right-click prevention.
- **Real-time Timer Enforcement:** Strict time limits.
- **Tracking:** Cheating warnings logged per exam attempt.
- **Design Rationale:** Multi-layered client-side enforcement creates strong barriers against common cheating methods.

### Payment & Premium Content
- Supports **Free and Premium exams**.
- **Dual Payment Workflows:** Student-initiated (upload proof, admin approval) and Admin-initiated (direct approval).
- **Configurable Payment Settings:** Admin can upload QRIS images and set bank details.
- **File Upload Handling:** Secure filename processing for payment proofs.
- **Design Rationale:** Flexible system accommodates various payment scenarios and customizable payment information.

### Frontend Architecture
- **PHP Templates:** Server-side rendering with an include system.
- **Custom CSS:** With CSS variables for theming and responsive design (light blue theme).
- **Vanilla JavaScript:** For interactivity, anti-cheat enforcement, and async operations.
- **Fully Responsive Design:** Optimized for desktop, tablet, and mobile using CSS Grid and Flexbox.
- **Enhanced UI Elements:** Profile photo in navbar, countdown timer (HH:MM:SS), visual question status (grey=unanswered, green=answered, yellow=doubtful), leaderboard with trophy icons, combined highest scores.
- **Typography & Accessibility:** Dark text (#1a1a1a, #2d2d2d) on light backgrounds for maximum readability, white text on dark components (navbar, stat-cards, modals). Bold font weights (600-800) for headings and important text. All times displayed in WIB (Asia/Jakarta) timezone.
- **Design Rationale:** PHP includes for modularity, vanilla JS for performance, and responsive design for broad device compatibility. A consistent light blue theme with high-contrast text ensures excellent readability across all devices.

### File Structure
- `config/`: Application configuration (database, auth, helpers, main config, schema).
- `app/Views/includes/`: Reusable template components (header, navbar, footer).
- `public/`: Document root with entry points for various user roles (`admin/`, `teacher/`, `student/`), authentication pages, API endpoints, and static assets.
- `storage/uploads/`: For file uploads (profiles, payments, answers, QRIS).

## External Dependencies

### PHP Requirements
- **PHP 8.2+**
- **PDO PostgreSQL Extension**
- **GD atau Imagick** (optional, for image processing)
- **Apache/Nginx** (web server)

### Storage
- **PostgreSQL Database** (Replit managed, Neon-backed).
- **File Uploads:** Stored locally in `storage/uploads/` for payment proofs, QRIS images, student profile photos, and essay answer photos.

### Environment Configuration
- **Database credentials** in `config/database.php` (DB_HOST, DB_USER, DB_PASS, DB_NAME).
- **Default Admin Account:** Email: admin@gmail.com, Password: inspiranetgacor25.

### Deployment
- Designed for **shared hosting** (cPanel, DirectAdmin) or **VPS** with Apache/Nginx.
- Easy to deploy with minimal dependencies.