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
  - `User` - Multi-role system (admin, teacher, student) with branch assignment and profile photo support
  - `Exam` - Exam metadata including premium status and pricing
  - `Question` - Supports multiple-choice and essay question types with photo upload capability
  - `ExamAttempt` - Tracks student submissions, cheating warnings, and essay answers with photos (stored as JSON)
  - `Payment` - Premium exam payment processing with approval workflow
  - `Leaderboard` - Dual ranking system (branch-specific and global)

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
- Payment workflow:
  1. Student uploads payment proof (image)
  2. Admin reviews and approves/rejects
  3. Access granted upon approval
- File upload handling with Werkzeug's secure_filename

**Design Rationale:** Manual approval workflow provides payment verification without third-party payment gateway integration, reducing deployment complexity.

### Frontend Architecture
- **Jinja2 Templates** - Server-side rendering
- **Custom CSS** with CSS variables for theming and improved modal scrolling
- **Vanilla JavaScript** for interactivity, anti-cheat enforcement, and async file uploads
- **Fully Responsive Design** using CSS Grid and Flexbox with breakpoints:
  - Desktop: 1024px and above
  - Tablet: 768px - 1023px
  - Mobile: Below 768px
- **Modal Improvements:** Scrollable content with overflow handling to prevent content being cut off

**Design Rationale:** Server-side rendering reduces client complexity. No frontend framework dependencies enable lightweight, fast-loading pages suitable for Replit hosting. Responsive design ensures optimal user experience across all devices. Modals with proper scrolling ensure forms are accessible on all screen sizes.

### File Structure
- `app.py` - Main application entry with route definitions, automatic database migration, and question management endpoints
- `models.py` - SQLAlchemy database models with essay and profile photo support
- `templates/` - Jinja2 HTML templates organized by user role
  - `admin_questions.html` - Admin interface for managing all exam questions (add, edit, delete)
  - `teacher_questions.html` - Teacher interface for managing own exam questions (add, edit, delete)
  - `student_exam.html` - Exam interface with essay photo upload and comprehensive error handling
  - `essay_answers.html` - Admin/teacher dashboard for viewing essay submissions
  - `register.html` - Student registration with profile photo upload
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
  - Payment proof images: `uploads/` directory (max 16MB per file)
  - Student profile photos: `uploads/profiles/` directory
  - Essay answer photos: `uploads/answers/` directory
- **Automatic Migration** - Database schema automatically updated on startup using SQLAlchemy URL parsing and instance path resolution

### Environment Configuration
- `SESSION_SECRET` - Flask session encryption key (defaults to 'inspiranet-secret-key-2025')
- `ADMIN_PASSWORD` - Initial admin account password (defaults to 'inspiragacor25')
- Admin account email: 'admin'

### No External Services Required
The application is designed to run completely standalone without external APIs, databases, or third-party services, making it ideal for Replit deployment.