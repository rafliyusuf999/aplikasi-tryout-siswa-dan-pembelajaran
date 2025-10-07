-- PostgreSQL Schema for INSPIRANET

-- Drop existing tables if needed (careful in production!)
DROP TABLE IF EXISTS payment_settings CASCADE;
DROP TABLE IF EXISTS leaderboards CASCADE;
DROP TABLE IF EXISTS payments CASCADE;
DROP TABLE IF EXISTS exam_attempts CASCADE;
DROP TABLE IF EXISTS questions CASCADE;
DROP TABLE IF EXISTS exams CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(120) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'student',
    inspira_branch VARCHAR(50),
    class_level VARCHAR(20),
    school_name VARCHAR(150),
    phone_number VARCHAR(20),
    profile_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_full_name ON users(full_name);

-- Exams table
CREATE TABLE exams (
    id SERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    duration_minutes INTEGER NOT NULL DEFAULT 120,
    total_questions INTEGER NOT NULL DEFAULT 0,
    is_premium BOOLEAN DEFAULT FALSE,
    price INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    start_time TIMESTAMP,
    end_time TIMESTAMP,
    created_by INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_exams_is_active ON exams(is_active);
CREATE INDEX idx_exams_created_by ON exams(created_by);

-- Questions table
CREATE TABLE questions (
    id SERIAL PRIMARY KEY,
    exam_id INTEGER NOT NULL,
    question_type VARCHAR(20) NOT NULL,
    category VARCHAR(100),
    question_text TEXT NOT NULL,
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    option_e TEXT,
    correct_answer VARCHAR(10),
    question_order INTEGER NOT NULL,
    points INTEGER DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE INDEX idx_questions_exam_id ON questions(exam_id);
CREATE INDEX idx_questions_question_order ON questions(question_order);

-- Exam attempts table
CREATE TABLE exam_attempts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    exam_id INTEGER NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP,
    total_score REAL DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    answers TEXT,
    essay_answers TEXT,
    essay_scores TEXT,
    cheating_warnings INTEGER DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE INDEX idx_exam_attempts_user_id ON exam_attempts(user_id);
CREATE INDEX idx_exam_attempts_exam_id ON exam_attempts(exam_id);
CREATE INDEX idx_exam_attempts_is_completed ON exam_attempts(is_completed);

-- Payments table
CREATE TABLE payments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    exam_id INTEGER NOT NULL,
    amount INTEGER NOT NULL,
    payment_proof VARCHAR(255),
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP,
    approved_by INTEGER,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

CREATE INDEX idx_payments_user_id ON payments(user_id);
CREATE INDEX idx_payments_exam_id ON payments(exam_id);
CREATE INDEX idx_payments_status ON payments(status);

-- Leaderboards table
CREATE TABLE leaderboards (
    id SERIAL PRIMARY KEY,
    exam_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rank_in_branch INTEGER,
    rank_global INTEGER,
    total_score REAL NOT NULL,
    completion_time INTEGER,
    percentile REAL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_leaderboards_exam_id ON leaderboards(exam_id);
CREATE INDEX idx_leaderboards_user_id ON leaderboards(user_id);
CREATE INDEX idx_leaderboards_total_score ON leaderboards(total_score);

-- Payment settings table
CREATE TABLE payment_settings (
    id SERIAL PRIMARY KEY,
    qris_image VARCHAR(255),
    payment_instructions TEXT,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    account_name VARCHAR(100),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Function to update timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger for payment_settings
CREATE TRIGGER update_payment_settings_updated_at BEFORE UPDATE
    ON payment_settings FOR EACH ROW
    EXECUTE PROCEDURE update_updated_at_column();

-- Insert default admin user
-- Password: inspiranetgacor25
INSERT INTO users (email, password_hash, full_name, role) 
VALUES ('admin@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');
