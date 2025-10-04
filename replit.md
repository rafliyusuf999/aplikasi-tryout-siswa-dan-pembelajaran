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
  - `User` - Multi-role system (admin, teacher, student) with branch assignment
  - `Exam` - Exam metadata including premium status and pricing
  - `Question` - Supports multiple-choice and essay question types
  - `ExamAttempt` - Tracks student submissions and cheating warnings
  - `Payment` - Premium exam payment processing with approval workflow
  - `Leaderboard` - Dual ranking system (branch-specific and global)

**Design Rationale:** SQLite eliminates external database dependencies for easy Replit deployment. The schema supports the branch-based competition model with separate leaderboards while maintaining referential integrity through foreign keys and cascade deletes.

### Authentication & Authorization
- Role-based access control (RBAC) with three user types:
  - **Admin:** Full system control, user management, payment approval
  - **Teacher:** Exam creation, question management, student result viewing
  - **Student:** Exam participation, payment submission, leaderboard access
- Session-based authentication via Flask-Login
- Password hashing with Werkzeug's generate_password_hash/check_password_hash

**Design Rationale:** Role-based system ensures proper access segregation. Session-based auth provides security without external dependencies.

### Branch Competition System
- **4 Branches:** Inspiranet_Cakrawala 1-4
- **Dual Leaderboard Architecture:**
  - Branch-specific rankings for fair local competition
  - Global rankings across all branches
- Students assigned to specific branches during registration

**Design Rationale:** Separate branch leaderboards ensure fair competition within smaller groups while global leaderboards provide overall achievement visibility. This increases motivation through achievable local rankings.

### Anti-Cheating Architecture
- Cheating warnings tracked per exam attempt
- Frontend monitoring for:
  - Copy-paste prevention
  - Screenshot blocking
  - Tab switching detection
  - Real-time timer enforcement

**Design Rationale:** Multi-layered client-side protection creates barriers to common cheating methods while tracking violations for review.

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
- **Custom CSS** with CSS variables for theming
- **Vanilla JavaScript** for interactivity
- Responsive design using CSS Grid and Flexbox

**Design Rationale:** Server-side rendering reduces client complexity. No frontend framework dependencies enable lightweight, fast-loading pages suitable for Replit hosting.

### File Structure
- `app.py` - Main application entry with route definitions
- `models.py` - SQLAlchemy database models
- `templates/` - Jinja2 HTML templates organized by user role
- `static/css/` - Stylesheets with custom theme
- `static/js/` - Client-side JavaScript including animated logo

## External Dependencies

### Python Packages
- **Flask** - Web framework
- **Flask-SQLAlchemy** - Database ORM
- **Flask-Login** - Authentication management
- **Werkzeug** - Security utilities and file handling

### Storage
- **SQLite Database** - `inspiranet.db` file stored locally
- **File Uploads** - Payment proof images stored in `uploads/` directory (max 16MB per file)

### Environment Configuration
- `SESSION_SECRET` - Flask session encryption key (defaults to 'inspiranet-secret-key-2025')
- `ADMIN_PASSWORD` - Initial admin account password (defaults to 'inspiragacor25')
- Admin account email: 'admin'

### No External Services Required
The application is designed to run completely standalone without external APIs, databases, or third-party services, making it ideal for Replit deployment.