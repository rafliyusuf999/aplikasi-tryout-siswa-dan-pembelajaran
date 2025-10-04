from flask_sqlalchemy import SQLAlchemy
from flask_login import UserMixin
from werkzeug.security import generate_password_hash, check_password_hash
from datetime import datetime

db = SQLAlchemy()

class User(UserMixin, db.Model):
    __tablename__ = 'users'
    
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password_hash = db.Column(db.String(255), nullable=False)
    full_name = db.Column(db.String(100), nullable=False)
    role = db.Column(db.String(20), nullable=False, default='student')
    inspira_branch = db.Column(db.String(50), nullable=True)
    class_level = db.Column(db.String(20), nullable=True)
    school_name = db.Column(db.String(150), nullable=True)
    phone_number = db.Column(db.String(20), nullable=True)
    profile_photo = db.Column(db.String(255), nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    exam_attempts = db.relationship('ExamAttempt', backref='user', lazy=True, cascade='all, delete-orphan')
    payments = db.relationship('Payment', backref='user', lazy=True, cascade='all, delete-orphan')
    leaderboard_entries = db.relationship('Leaderboard', backref='user', lazy=True, cascade='all, delete-orphan')
    
    def set_password(self, password):
        self.password_hash = generate_password_hash(password)
    
    def check_password(self, password):
        return check_password_hash(self.password_hash, password)

class Exam(db.Model):
    __tablename__ = 'exams'
    
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(200), nullable=False)
    description = db.Column(db.Text, nullable=True)
    duration_minutes = db.Column(db.Integer, nullable=False, default=120)
    total_questions = db.Column(db.Integer, nullable=False, default=0)
    is_premium = db.Column(db.Boolean, default=False)
    price = db.Column(db.Integer, default=0)
    is_active = db.Column(db.Boolean, default=True)
    created_by = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    
    questions = db.relationship('Question', backref='exam', lazy=True, cascade='all, delete-orphan')
    attempts = db.relationship('ExamAttempt', backref='exam', lazy=True, cascade='all, delete-orphan')
    payments = db.relationship('Payment', backref='exam', lazy=True, cascade='all, delete-orphan')
    leaderboard_entries = db.relationship('Leaderboard', backref='exam', lazy=True, cascade='all, delete-orphan')

class Question(db.Model):
    __tablename__ = 'questions'
    
    id = db.Column(db.Integer, primary_key=True)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    question_type = db.Column(db.String(20), nullable=False)
    category = db.Column(db.String(100), nullable=True)
    question_text = db.Column(db.Text, nullable=False)
    option_a = db.Column(db.Text, nullable=True)
    option_b = db.Column(db.Text, nullable=True)
    option_c = db.Column(db.Text, nullable=True)
    option_d = db.Column(db.Text, nullable=True)
    option_e = db.Column(db.Text, nullable=True)
    correct_answer = db.Column(db.String(10), nullable=True)
    question_order = db.Column(db.Integer, nullable=False)
    points = db.Column(db.Integer, default=1)

class ExamAttempt(db.Model):
    __tablename__ = 'exam_attempts'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    started_at = db.Column(db.DateTime, default=datetime.utcnow)
    finished_at = db.Column(db.DateTime, nullable=True)
    total_score = db.Column(db.Float, default=0)
    is_completed = db.Column(db.Boolean, default=False)
    answers = db.Column(db.Text, nullable=True)
    essay_answers = db.Column(db.Text, nullable=True)
    cheating_warnings = db.Column(db.Integer, default=0)

class Payment(db.Model):
    __tablename__ = 'payments'
    
    id = db.Column(db.Integer, primary_key=True)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    amount = db.Column(db.Integer, nullable=False)
    payment_proof = db.Column(db.String(255), nullable=True)
    status = db.Column(db.String(20), default='pending')
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    approved_at = db.Column(db.DateTime, nullable=True)
    approved_by = db.Column(db.Integer, nullable=True)

class Leaderboard(db.Model):
    __tablename__ = 'leaderboards'
    
    id = db.Column(db.Integer, primary_key=True)
    exam_id = db.Column(db.Integer, db.ForeignKey('exams.id'), nullable=False)
    user_id = db.Column(db.Integer, db.ForeignKey('users.id'), nullable=False)
    rank_in_branch = db.Column(db.Integer, nullable=True)
    rank_global = db.Column(db.Integer, nullable=True)
    total_score = db.Column(db.Float, nullable=False)
    completion_time = db.Column(db.Integer, nullable=True)
    percentile = db.Column(db.Float, nullable=True)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
