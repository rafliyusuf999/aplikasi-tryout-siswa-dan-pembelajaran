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
- **Flask** - Python web framework serving the entire application
- **SQLAlchemy** - ORM for database interactions
- **Flask-Login** - User session and authentication management
- **Werkzeug Security** - Password hashing and security utilities

**Design Rationale:** Flask provides a lightweight, flexible framework suitable for rapid deployment on Replit while maintaining scalability for thousands of users.

### Database Schema
- **SQLite** - Local file-based database (`inspiranet.db`)
- **Core Models:**
  - `User` - Multi-role system (admin, teacher, student) with branch assignment and profile photo support (displayed in navbar)
  - `Exam` - Exam metadata including premium status and pricing
  - `Question` - Supports multiple-choice and essay question types with photo upload capability
  - `ExamAttempt` - Tracks student submissions, cheating warnings, and essay answers with photos (stored as JSON)
  - `Payment` - Premium exam payment processing with manual admin approval or admin-initiated payment
  - `Leaderboard` - Dual ranking system (branch-specific and global)
  - `PaymentSettings` - Stores QRIS image, payment instructions, and bank account details for student payment display

**Design Rationale:** SQLite eliminates external database dependencies for easy Replit deployment. The schema supports the branch-based competition model with separate leaderboards while maintaining referential integrity through foreign keys and cascade deletes. Automatic migration system ensures existing databases are updated with new columns on startup.

### Authentication & Authorization
- Role-based access control (RBAC) with three user types:
  - **Admin:** Full system control, user management, payment approval, complete question management for all exams (add, edit, delete)
  - **Teacher:** Exam creation, question management (add, edit, delete for own exams), student result viewing
  - **Student:** Exam participation, payment submission, leaderboard access
- Session-based authentication via Flask-Login
- Password hashing with Werkzeug's generate_password_hash/check_password_hash

**Design Rationale:** Role-based system ensures proper access segregation. Session-based auth provides security without external dependencies. Admin has full control while teachers can only manage their own exam questions.

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
- **Jinja2 Templates** - Server-side rendering
- **Custom CSS** with CSS variables for theming and improved modal scrolling
- **Vanilla JavaScript** for interactivity, anti-cheat enforcement, and async file uploads
- **Fully Responsive Design** using CSS Grid and Flexbox with breakpoints:
  - Desktop: 1024px and above
  - Tablet: 768px - 1023px
  - Mobile: Below 768px
- **Modal Improvements:** Scrollable content with overflow handling to prevent content being cut off
- **Enhanced User Interface:**
  - **Navbar Profile Display:** User profile photo and name shown in navigation bar with responsive design
  - **User Profile Management:** Dedicated profile page where all users can view and update their personal information, including email, name, password, photo, and role-specific details (branch, class, school for students)
  - Complete countdown timer with HH:MM:SS format updating every second
  - Visual question status indicators with color coding (grey=unanswered, green=answered, yellow=doubtful)
  - Question navigation buttons with scale effect for current question while preserving status colors
  - Leaderboard with trophy icons (🥇🥈🥉) for top 3 ranks and user highlighting
  - Combined highest score display from premium and free exams in student dashboard
  - Secure admin student management with password reset capability (without exposing password hashes)
  - Admin payment management with manual payment creation and settings configuration

**Design Rationale:** Server-side rendering reduces client complexity. No frontend framework dependencies enable lightweight, fast-loading pages suitable for Replit hosting. Responsive design ensures optimal user experience across all devices. Modals with proper scrolling ensure forms are accessible on all screen sizes. Visual indicators and countdown timer improve user experience and exam time management.

### File Structure
- `app.py` - Main application entry with route definitions, automatic database migration, and question management endpoints
- `models.py` - SQLAlchemy database models with essay and profile photo support
- `templates/` - Jinja2 HTML templates organized by user role
  - `admin_questions.html` - Admin interface for managing all exam questions (add, edit, delete)
  - `admin_payments.html` - Payment management with manual payment creation
  - `admin_payment_settings.html` - Configure QRIS, payment instructions, and bank details
  - `teacher_questions.html` - Teacher interface for managing own exam questions (add, edit, delete)
  - `student_exam.html` - Exam interface with essay photo upload and comprehensive error handling
  - `student_pay.html` - Payment page displaying QRIS and dynamic payment instructions
  - `essay_answers.html` - Admin/teacher dashboard for viewing essay submissions
  - `register.html` - Student registration with profile photo upload
  - `profile.html` - User profile management page for viewing and updating personal information
- `static/css/` - Responsive stylesheets with custom theme and improved modal scrolling
- `static/js/` - Client-side JavaScript with anti-cheat enforcement
- `uploads/` - File upload directories (profiles, answers, payment proofs)

## External Dependencies

### Python Packages
- **Flask** - Web framework
- **Flask-SQLAlchemy** - Database ORM
- **Flask-Login** - Authentication management
- **Werkzeug** - Security utilities and file handling

### Storage
- **SQLite Database** - `inspiranet.db` file stored in `instance/` directory
- **File Uploads:**
  - Payment proof images: `uploads/payments/` directory (max 16MB per file)
  - QRIS payment image: `uploads/payment/` directory (admin-configurable)
  - Student profile photos: `uploads/profiles/` directory (displayed in navbar)
  - Essay answer photos: `uploads/answers/` directory
- **Automatic Migration** - Database schema automatically updated on startup using SQLAlchemy URL parsing and instance path resolution

### Environment Configuration
- `SESSION_SECRET` - Flask session encryption key (defaults to 'inspiranet-secret-key-2025')
- `ADMIN_PASSWORD` - Initial admin account password (defaults to 'inspiragacor25')
- Admin account email: 'admin'

### No External Services Required
The application is designed to run completely standalone without external APIs, databases, or third-party services, making it ideal for Replit deployment.