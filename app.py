from flask import Flask, render_template, request, redirect, url_for, flash, jsonify, session
from flask_login import LoginManager, login_user, logout_user, login_required, current_user
from werkzeug.utils import secure_filename
from models import db, User, Exam, Question, ExamAttempt, Payment, Leaderboard
from datetime import datetime
import os
import json
from sqlalchemy import func

app = Flask(__name__)
app.config['SECRET_KEY'] = os.environ.get('SESSION_SECRET', 'inspiranet-secret-key-2025')
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///inspiranet.db'
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
app.config['UPLOAD_FOLDER'] = 'uploads'
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024

db.init_app(app)
login_manager = LoginManager()
login_manager.init_app(app)
login_manager.login_view = 'login'

@app.template_filter('from_json')
def from_json_filter(value):
    if value:
        return json.loads(value)
    return {}

BRANCHES = [
    'Inspiranet_Cakrawala 1',
    'Inspiranet_Cakrawala 2',
    'Inspiranet_Cakrawala 3',
    'Inspiranet_Cakrawala 4'
]

CLASS_LEVELS = ['10', '11', '12', 'Alumni']

@login_manager.user_loader
def load_user(user_id):
    return User.query.get(int(user_id))

def init_database():
    with app.app_context():
        db.create_all()
        
        import sqlite3
        from sqlalchemy.engine.url import make_url
        
        db_uri = app.config['SQLALCHEMY_DATABASE_URI']
        if db_uri.startswith('sqlite:///'):
            db_url = make_url(db_uri)
            db_path = db_url.database
            
            if not os.path.isabs(db_path):
                db_path = os.path.join(app.instance_path, db_path)
            
            if os.path.exists(db_path):
                conn = sqlite3.connect(db_path)
                cursor = conn.cursor()
                try:
                    cursor.execute("PRAGMA table_info(users)")
                    columns = [col[1] for col in cursor.fetchall()]
                    
                    if 'profile_photo' not in columns:
                        cursor.execute("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255)")
                        conn.commit()
                        print("✓ Added profile_photo column")
                    
                    cursor.execute("PRAGMA table_info(exam_attempts)")
                    columns = [col[1] for col in cursor.fetchall()]
                    
                    if 'essay_answers' not in columns:
                        cursor.execute("ALTER TABLE exam_attempts ADD COLUMN essay_answers TEXT")
                        conn.commit()
                        print("✓ Added essay_answers column")
                except Exception as e:
                    print(f"Migration warning: {e}")
                finally:
                    conn.close()
        
        admin = User.query.filter_by(email='admin@gmail.com').first()
        if not admin:
            admin_password = os.environ.get('ADMIN_PASSWORD', 'inspiranetgacor25')
            admin = User(
                email='admin@gmail.com',
                full_name='Administrator',
                role='admin'
            )
            admin.set_password(admin_password)
            db.session.add(admin)
            db.session.commit()
            print(f"Admin user created with email: admin@gmail.com")

@app.before_request
def ensure_db():
    if not hasattr(app, '_db_initialized'):
        app._db_initialized = True
        init_database()

@app.route('/')
def index():
    if current_user.is_authenticated:
        if current_user.role == 'admin':
            return redirect(url_for('admin_dashboard'))
        elif current_user.role == 'teacher':
            return redirect(url_for('teacher_dashboard'))
        else:
            return redirect(url_for('student_dashboard'))
    return render_template('index.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    if current_user.is_authenticated:
        return redirect(url_for('index'))
    
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        user = User.query.filter_by(email=email).first()
        
        if user and user.check_password(password):
            login_user(user)
            flash('Login berhasil!', 'success')
            return redirect(url_for('index'))
        else:
            flash('Email atau password salah!', 'danger')
    
    return render_template('login.html')

@app.route('/register', methods=['GET', 'POST'])
def register():
    if current_user.is_authenticated:
        return redirect(url_for('index'))
    
    if request.method == 'POST':
        email = request.form.get('email')
        password = request.form.get('password')
        full_name = request.form.get('full_name')
        inspira_branch = request.form.get('inspira_branch')
        class_level = request.form.get('class_level')
        school_name = request.form.get('school_name')
        phone_number = request.form.get('phone_number')
        
        if User.query.filter_by(email=email).first():
            flash('Email sudah terdaftar!', 'danger')
            return redirect(url_for('register'))
        
        if User.query.filter(func.lower(User.full_name) == func.lower(full_name)).first():
            flash('Nama sudah terdaftar! Gunakan nama yang berbeda.', 'danger')
            return redirect(url_for('register'))
        
        profile_photo = None
        photo = request.files.get('profile_photo')
        if photo and photo.filename:
            os.makedirs(os.path.join(app.config['UPLOAD_FOLDER'], 'profiles'), exist_ok=True)
            filename = secure_filename(f"profile_{email}_{datetime.now().timestamp()}.jpg")
            filepath = os.path.join(app.config['UPLOAD_FOLDER'], 'profiles', filename)
            photo.save(filepath)
            profile_photo = filename
        
        user = User(
            email=email,
            full_name=full_name,
            role='student',
            inspira_branch=inspira_branch,
            class_level=class_level,
            school_name=school_name,
            phone_number=phone_number,
            profile_photo=profile_photo
        )
        user.set_password(password)
        db.session.add(user)
        db.session.commit()
        
        flash('Pendaftaran berhasil! Silakan login.', 'success')
        return redirect(url_for('login'))
    
    return render_template('register.html', branches=BRANCHES, class_levels=CLASS_LEVELS)

@app.route('/logout')
@login_required
def logout():
    logout_user()
    flash('Anda telah logout.', 'info')
    return redirect(url_for('index'))

@app.route('/admin/dashboard')
@login_required
def admin_dashboard():
    if current_user.role != 'admin':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    total_students = User.query.filter_by(role='student').count()
    total_teachers = User.query.filter_by(role='teacher').count()
    total_exams = Exam.query.filter_by(is_active=True).count()
    active_students = db.session.query(ExamAttempt.user_id).distinct().count()
    pending_payments = Payment.query.filter_by(status='pending').count()
    
    return render_template('admin_dashboard.html',
                         total_students=total_students,
                         active_students=active_students,
                         total_teachers=total_teachers,
                         total_exams=total_exams,
                         pending_payments=pending_payments)

@app.route('/admin/students')
@login_required
def admin_students():
    if current_user.role != 'admin':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    search_query = request.args.get('search', '')
    if search_query:
        students = User.query.filter(
            User.role == 'student',
            User.full_name.ilike(f'%{search_query}%')
        ).all()
    else:
        students = User.query.filter_by(role='student').all()
    
    return render_template('admin_students.html', students=students, branches=BRANCHES, search_query=search_query)

@app.route('/admin/students/add', methods=['POST'])
@login_required
def admin_add_student():
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    email = request.form.get('email')
    password = request.form.get('password')
    full_name = request.form.get('full_name')
    inspira_branch = request.form.get('inspira_branch')
    class_level = request.form.get('class_level')
    school_name = request.form.get('school_name')
    phone_number = request.form.get('phone_number')
    
    if User.query.filter_by(email=email).first():
        return jsonify({'success': False, 'message': 'Email sudah terdaftar'}), 400
    
    if User.query.filter(func.lower(User.full_name) == func.lower(full_name)).first():
        return jsonify({'success': False, 'message': 'Nama sudah terdaftar! Gunakan nama yang berbeda'}), 400
    
    user = User(
        email=email,
        full_name=full_name,
        role='student',
        inspira_branch=inspira_branch,
        class_level=class_level,
        school_name=school_name,
        phone_number=phone_number
    )
    user.set_password(password)
    db.session.add(user)
    db.session.commit()
    
    flash('Siswa berhasil ditambahkan!', 'success')
    return redirect(url_for('admin_students'))

@app.route('/admin/students/delete/<int:id>', methods=['POST'])
@login_required
def admin_delete_student(id):
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    user = User.query.get_or_404(id)
    if user.role != 'student':
        return jsonify({'success': False, 'message': 'Hanya bisa menghapus siswa'}), 400
    
    db.session.delete(user)
    db.session.commit()
    
    flash('Siswa berhasil dihapus!', 'success')
    return redirect(url_for('admin_students'))

@app.route('/admin/teachers')
@login_required
def admin_teachers():
    if current_user.role != 'admin':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    teachers = User.query.filter_by(role='teacher').all()
    return render_template('admin_teachers.html', teachers=teachers)

@app.route('/admin/teachers/add', methods=['POST'])
@login_required
def admin_add_teacher():
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    email = request.form.get('email')
    password = request.form.get('password')
    full_name = request.form.get('full_name')
    phone_number = request.form.get('phone_number')
    
    if User.query.filter_by(email=email).first():
        return jsonify({'success': False, 'message': 'Email sudah terdaftar'}), 400
    
    user = User(
        email=email,
        full_name=full_name,
        role='teacher',
        phone_number=phone_number
    )
    user.set_password(password)
    db.session.add(user)
    db.session.commit()
    
    flash('Guru berhasil ditambahkan!', 'success')
    return redirect(url_for('admin_teachers'))

@app.route('/admin/teachers/delete/<int:id>', methods=['POST'])
@login_required
def admin_delete_teacher(id):
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    user = User.query.get_or_404(id)
    if user.role != 'teacher':
        return jsonify({'success': False, 'message': 'Hanya bisa menghapus guru'}), 400
    
    db.session.delete(user)
    db.session.commit()
    
    flash('Guru berhasil dihapus!', 'success')
    return redirect(url_for('admin_teachers'))

@app.route('/admin/exams')
@login_required
def admin_exams():
    if current_user.role != 'admin':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exams = Exam.query.all()
    return render_template('admin_exams.html', exams=exams)

@app.route('/admin/exams/add', methods=['POST'])
@login_required
def admin_add_exam():
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    title = request.form.get('title')
    description = request.form.get('description')
    duration_minutes = request.form.get('duration_minutes')
    is_premium = request.form.get('is_premium') == 'true'
    price = request.form.get('price', '0')
    
    exam = Exam(
        title=title,
        description=description,
        duration_minutes=int(duration_minutes) if duration_minutes else 120,
        is_premium=is_premium,
        price=int(price) if price else 0,
        created_by=current_user.id
    )
    db.session.add(exam)
    db.session.commit()
    
    flash('TO berhasil ditambahkan!', 'success')
    return redirect(url_for('admin_exams'))

@app.route('/admin/exams/<int:id>/delete', methods=['POST'])
@login_required
def admin_delete_exam(id):
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    exam = Exam.query.get_or_404(id)
    db.session.delete(exam)
    db.session.commit()
    
    flash('TO berhasil dihapus!', 'success')
    return redirect(url_for('admin_exams'))

@app.route('/admin/payments')
@login_required
def admin_payments():
    if current_user.role not in ['admin', 'teacher']:
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    payments = Payment.query.order_by(Payment.created_at.desc()).all()
    pending_count = Payment.query.filter_by(status='pending').count()
    return render_template('admin_payments.html', payments=payments, pending_count=pending_count)

@app.route('/admin/payments/<int:id>/approve', methods=['POST'])
@login_required
def admin_approve_payment(id):
    if current_user.role not in ['admin', 'teacher']:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    payment = Payment.query.get_or_404(id)
    payment.status = 'approved'
    payment.approved_at = datetime.utcnow()
    payment.approved_by = current_user.id
    db.session.commit()
    
    flash('Pembayaran berhasil disetujui!', 'success')
    return redirect(url_for('admin_payments'))

@app.route('/admin/payments/approve_all', methods=['POST'])
@login_required
def admin_approve_all_payments():
    if current_user.role not in ['admin', 'teacher']:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    pending_payments = Payment.query.filter_by(status='pending').all()
    for payment in pending_payments:
        payment.status = 'approved'
        payment.approved_at = datetime.utcnow()
        payment.approved_by = current_user.id
    
    db.session.commit()
    
    flash(f'{len(pending_payments)} pembayaran berhasil disetujui!', 'success')
    return redirect(url_for('admin_payments'))

@app.route('/admin/payments/<int:id>/reject', methods=['POST'])
@login_required
def admin_reject_payment(id):
    if current_user.role not in ['admin', 'teacher']:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    payment = Payment.query.get_or_404(id)
    payment.status = 'rejected'
    db.session.commit()
    
    flash('Pembayaran ditolak!', 'info')
    return redirect(url_for('admin_payments'))

@app.route('/admin/exams/<int:id>/questions')
@login_required
def admin_questions(id):
    if current_user.role != 'admin':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exam = Exam.query.get_or_404(id)
    questions = Question.query.filter_by(exam_id=id).order_by(Question.question_order).all()
    return render_template('admin_questions.html', exam=exam, questions=questions)

@app.route('/admin/exams/<int:id>/questions/add', methods=['POST'])
@login_required
def admin_add_question(id):
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    exam = Exam.query.get_or_404(id)
    
    question_type = request.form.get('question_type')
    category = request.form.get('category')
    question_text = request.form.get('question_text')
    option_a = request.form.get('option_a')
    option_b = request.form.get('option_b')
    option_c = request.form.get('option_c')
    option_d = request.form.get('option_d')
    option_e = request.form.get('option_e')
    correct_answer = request.form.get('correct_answer')
    points = request.form.get('points', 1)
    
    max_order = db.session.query(func.max(Question.question_order)).filter_by(exam_id=id).scalar() or 0
    
    question = Question(
        exam_id=id,
        question_type=question_type,
        category=category,
        question_text=question_text,
        option_a=option_a,
        option_b=option_b,
        option_c=option_c,
        option_d=option_d,
        option_e=option_e,
        correct_answer=correct_answer,
        question_order=max_order + 1,
        points=int(points)
    )
    db.session.add(question)
    exam.total_questions = Question.query.filter_by(exam_id=id).count() + 1
    db.session.commit()
    
    flash('Soal berhasil ditambahkan!', 'success')
    return redirect(url_for('admin_questions', id=id))

@app.route('/admin/questions/<int:id>/edit', methods=['POST'])
@login_required
def admin_edit_question(id):
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    question = Question.query.get_or_404(id)
    
    question.question_type = request.form.get('question_type')
    question.category = request.form.get('category')
    question.question_text = request.form.get('question_text')
    question.option_a = request.form.get('option_a')
    question.option_b = request.form.get('option_b')
    question.option_c = request.form.get('option_c')
    question.option_d = request.form.get('option_d')
    question.option_e = request.form.get('option_e')
    question.correct_answer = request.form.get('correct_answer')
    question.points = int(request.form.get('points', 1))
    
    db.session.commit()
    
    flash('Soal berhasil diperbarui!', 'success')
    return redirect(url_for('admin_questions', id=question.exam_id))

@app.route('/admin/questions/<int:id>/delete', methods=['POST'])
@login_required
def admin_delete_question(id):
    if current_user.role != 'admin':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    question = Question.query.get_or_404(id)
    exam_id = question.exam_id
    exam = Exam.query.get(exam_id)
    
    db.session.delete(question)
    exam.total_questions = Question.query.filter_by(exam_id=exam_id).count() - 1
    db.session.commit()
    
    flash('Soal berhasil dihapus!', 'success')
    return redirect(url_for('admin_questions', id=exam_id))

@app.route('/teacher/dashboard')
@login_required
def teacher_dashboard():
    if current_user.role != 'teacher':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    my_exams = Exam.query.filter_by(created_by=current_user.id).count()
    total_questions = db.session.query(func.sum(Exam.total_questions)).filter_by(created_by=current_user.id).scalar() or 0
    total_attempts = db.session.query(ExamAttempt).join(Exam).filter(Exam.created_by == current_user.id).count()
    
    return render_template('teacher_dashboard.html',
                         my_exams=my_exams,
                         total_questions=total_questions,
                         total_attempts=total_attempts)

@app.route('/teacher/exams')
@login_required
def teacher_exams():
    if current_user.role != 'teacher':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exams = Exam.query.filter_by(created_by=current_user.id).all()
    return render_template('teacher_exams.html', exams=exams)

@app.route('/teacher/exams/add', methods=['POST'])
@login_required
def teacher_add_exam():
    if current_user.role != 'teacher':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    title = request.form.get('title')
    description = request.form.get('description')
    duration_minutes = request.form.get('duration_minutes')
    is_premium = request.form.get('is_premium') == 'true'
    price = request.form.get('price', '0')
    
    exam = Exam(
        title=title,
        description=description,
        duration_minutes=int(duration_minutes) if duration_minutes else 120,
        is_premium=is_premium,
        price=int(price) if price else 0,
        created_by=current_user.id
    )
    db.session.add(exam)
    db.session.commit()
    
    flash('TO berhasil ditambahkan!', 'success')
    return redirect(url_for('teacher_exams'))

@app.route('/teacher/exams/<int:id>/questions')
@login_required
def teacher_questions(id):
    if current_user.role != 'teacher':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exam = Exam.query.get_or_404(id)
    if exam.created_by != current_user.id:
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('teacher_exams'))
    
    questions = Question.query.filter_by(exam_id=id).order_by(Question.question_order).all()
    return render_template('teacher_questions.html', exam=exam, questions=questions)

@app.route('/teacher/exams/<int:id>/questions/add', methods=['POST'])
@login_required
def teacher_add_question(id):
    if current_user.role != 'teacher':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    exam = Exam.query.get_or_404(id)
    if exam.created_by != current_user.id:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    question_type = request.form.get('question_type')
    category = request.form.get('category')
    question_text = request.form.get('question_text')
    option_a = request.form.get('option_a')
    option_b = request.form.get('option_b')
    option_c = request.form.get('option_c')
    option_d = request.form.get('option_d')
    option_e = request.form.get('option_e')
    correct_answer = request.form.get('correct_answer')
    points = request.form.get('points', 1)
    
    max_order = db.session.query(func.max(Question.question_order)).filter_by(exam_id=id).scalar() or 0
    
    question = Question(
        exam_id=id,
        question_type=question_type,
        category=category,
        question_text=question_text,
        option_a=option_a,
        option_b=option_b,
        option_c=option_c,
        option_d=option_d,
        option_e=option_e,
        correct_answer=correct_answer,
        question_order=max_order + 1,
        points=int(points)
    )
    db.session.add(question)
    exam.total_questions = Question.query.filter_by(exam_id=id).count() + 1
    db.session.commit()
    
    flash('Soal berhasil ditambahkan!', 'success')
    return redirect(url_for('teacher_questions', id=id))

@app.route('/teacher/questions/<int:id>/edit', methods=['POST'])
@login_required
def teacher_edit_question(id):
    if current_user.role != 'teacher':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    question = Question.query.get_or_404(id)
    exam = Exam.query.get(question.exam_id)
    
    if exam.created_by != current_user.id:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    question.question_type = request.form.get('question_type')
    question.category = request.form.get('category')
    question.question_text = request.form.get('question_text')
    question.option_a = request.form.get('option_a')
    question.option_b = request.form.get('option_b')
    question.option_c = request.form.get('option_c')
    question.option_d = request.form.get('option_d')
    question.option_e = request.form.get('option_e')
    question.correct_answer = request.form.get('correct_answer')
    question.points = int(request.form.get('points', 1))
    
    db.session.commit()
    
    flash('Soal berhasil diperbarui!', 'success')
    return redirect(url_for('teacher_questions', id=question.exam_id))

@app.route('/teacher/questions/<int:id>/delete', methods=['POST'])
@login_required
def teacher_delete_question(id):
    if current_user.role != 'teacher':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    question = Question.query.get_or_404(id)
    exam_id = question.exam_id
    exam = Exam.query.get(exam_id)
    
    if exam.created_by != current_user.id:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    db.session.delete(question)
    exam.total_questions = Question.query.filter_by(exam_id=exam_id).count() - 1
    db.session.commit()
    
    flash('Soal berhasil dihapus!', 'success')
    return redirect(url_for('teacher_questions', id=exam_id))

@app.route('/teacher/students')
@login_required
def teacher_students():
    if current_user.role != 'teacher':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    attempts = db.session.query(ExamAttempt).join(Exam).filter(
        Exam.created_by == current_user.id,
        ExamAttempt.is_completed == True
    ).all()
    
    return render_template('teacher_students.html', attempts=attempts)

@app.route('/student/dashboard')
@login_required
def student_dashboard():
    if current_user.role != 'student':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    total_exams_taken = ExamAttempt.query.filter_by(user_id=current_user.id, is_completed=True).count()
    avg_score = db.session.query(func.avg(ExamAttempt.total_score)).filter_by(
        user_id=current_user.id,
        is_completed=True
    ).scalar() or 0
    
    best_rank = db.session.query(func.min(Leaderboard.rank_in_branch)).filter_by(
        user_id=current_user.id
    ).scalar() or 0
    
    best_premium_score = db.session.query(func.max(ExamAttempt.total_score)).join(Exam).filter(
        ExamAttempt.user_id == current_user.id,
        ExamAttempt.is_completed == True,
        Exam.is_premium == True
    ).scalar() or 0
    
    best_free_score = db.session.query(func.max(ExamAttempt.total_score)).join(Exam).filter(
        ExamAttempt.user_id == current_user.id,
        ExamAttempt.is_completed == True,
        Exam.is_premium == False
    ).scalar() or 0
    
    combined_best_score = best_premium_score + best_free_score
    
    return render_template('student_dashboard.html',
                         total_exams_taken=total_exams_taken,
                         avg_score=round(avg_score, 2),
                         best_rank=best_rank,
                         combined_best_score=round(combined_best_score, 2),
                         best_premium_score=round(best_premium_score, 2),
                         best_free_score=round(best_free_score, 2))

@app.route('/student/exams')
@login_required
def student_exams():
    if current_user.role != 'student':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exams = Exam.query.filter_by(is_active=True).all()
    
    exam_access = {}
    for exam in exams:
        if exam.is_premium:
            payment = Payment.query.filter_by(
                user_id=current_user.id,
                exam_id=exam.id,
                status='approved'
            ).first()
            exam_access[exam.id] = payment is not None
        else:
            exam_access[exam.id] = True
    
    return render_template('student_exams.html', exams=exams, exam_access=exam_access)

@app.route('/student/exams/<int:id>/pay', methods=['GET', 'POST'])
@login_required
def student_pay_exam(id):
    if current_user.role != 'student':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exam = Exam.query.get_or_404(id)
    
    if request.method == 'POST':
        file = request.files.get('payment_proof')
        if file and file.filename:
            filename = secure_filename(f"{current_user.id}_{exam.id}_{datetime.now().timestamp()}.jpg")
            filepath = os.path.join(app.config['UPLOAD_FOLDER'], 'payments', filename)
            file.save(filepath)
            
            payment = Payment(
                user_id=current_user.id,
                exam_id=exam.id,
                amount=exam.price,
                payment_proof=filename,
                status='pending'
            )
            db.session.add(payment)
            db.session.commit()
            
            flash('Bukti pembayaran berhasil diupload! Menunggu konfirmasi admin.', 'success')
            return redirect(url_for('student_exams'))
    
    return render_template('student_pay.html', exam=exam)

@app.route('/student/exams/<int:id>/start')
@login_required
def student_start_exam(id):
    if current_user.role != 'student':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exam = Exam.query.get_or_404(id)
    
    if exam.is_premium:
        payment = Payment.query.filter_by(
            user_id=current_user.id,
            exam_id=exam.id,
            status='approved'
        ).first()
        if not payment:
            flash('Anda belum membayar TO ini!', 'danger')
            return redirect(url_for('student_exams'))
    
    attempt = ExamAttempt.query.filter_by(
        user_id=current_user.id,
        exam_id=exam.id,
        is_completed=False
    ).first()
    
    if not attempt:
        attempt = ExamAttempt(
            user_id=current_user.id,
            exam_id=exam.id,
            started_at=datetime.utcnow()
        )
        db.session.add(attempt)
        db.session.commit()
    
    questions = Question.query.filter_by(exam_id=exam.id).order_by(Question.question_order).all()
    return render_template('student_exam.html', exam=exam, attempt=attempt, questions=questions)

@app.route('/student/exams/<int:attempt_id>/upload_essay', methods=['POST'])
@login_required
def student_upload_essay(attempt_id):
    if current_user.role != 'student':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    attempt = ExamAttempt.query.get_or_404(attempt_id)
    if attempt.user_id != current_user.id:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    essay_file = request.files.get('essay_file')
    question_id = request.form.get('question_id')
    
    if essay_file and essay_file.filename:
        os.makedirs(os.path.join(app.config['UPLOAD_FOLDER'], 'answers'), exist_ok=True)
        filename = secure_filename(f"essay_{attempt_id}_{question_id}_{datetime.now().timestamp()}.jpg")
        filepath = os.path.join(app.config['UPLOAD_FOLDER'], 'answers', filename)
        essay_file.save(filepath)
        
        essay_answers = json.loads(attempt.essay_answers) if attempt.essay_answers else {}
        essay_answers[question_id] = filename
        attempt.essay_answers = json.dumps(essay_answers)
        db.session.commit()
        
        return jsonify({'success': True, 'filename': filename})
    
    return jsonify({'success': False, 'message': 'No file uploaded'}), 400

@app.route('/student/exams/<int:attempt_id>/submit', methods=['POST'])
@login_required
def student_submit_exam(attempt_id):
    if current_user.role != 'student':
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    attempt = ExamAttempt.query.get_or_404(attempt_id)
    if attempt.user_id != current_user.id:
        return jsonify({'success': False, 'message': 'Akses ditolak'}), 403
    
    answers = request.form.get('answers')
    attempt.answers = answers
    attempt.is_completed = True
    attempt.finished_at = datetime.utcnow()
    
    answers_dict = json.loads(answers)
    total_score = 0
    
    for question_id, answer in answers_dict.items():
        question = Question.query.get(int(question_id))
        if question and question.question_type == 'multiple_choice':
            if answer == question.correct_answer:
                total_score += question.points
    
    attempt.total_score = total_score
    db.session.commit()
    
    update_leaderboard(attempt)
    
    flash('Ujian berhasil diselesaikan!', 'success')
    return jsonify({'success': True, 'redirect': url_for('student_result', id=attempt_id)})

@app.route('/student/exams/<int:id>/result')
@login_required
def student_result(id):
    if current_user.role != 'student':
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    attempt = ExamAttempt.query.get_or_404(id)
    if attempt.user_id != current_user.id:
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('student_dashboard'))
    
    leaderboard_entry = Leaderboard.query.filter_by(
        exam_id=attempt.exam_id,
        user_id=current_user.id
    ).first()
    
    return render_template('student_result.html', attempt=attempt, leaderboard=leaderboard_entry)

@app.route("/peringkat")
@login_required
def all_leaderboards():
    exams = Exam.query.filter_by(is_active=True).all()
    return render_template("all_leaderboards.html", exams=exams)

@app.route('/leaderboard/<int:exam_id>')
@login_required
def leaderboard(exam_id):
    exam = Exam.query.get_or_404(exam_id)
    
    branch_leaderboard = Leaderboard.query.join(User).filter(
        Leaderboard.exam_id == exam_id,
        User.inspira_branch == current_user.inspira_branch
    ).order_by(Leaderboard.rank_in_branch).limit(50).all()
    
    global_leaderboard = Leaderboard.query.filter_by(
        exam_id=exam_id
    ).order_by(Leaderboard.rank_global).limit(50).all()
    
    return render_template('leaderboard.html',
                         exam=exam,
                         branch_leaderboard=branch_leaderboard,
                         global_leaderboard=global_leaderboard)

def update_leaderboard(attempt):
    exam = attempt.exam
    user = attempt.user
    
    leaderboard_entry = Leaderboard.query.filter_by(
        exam_id=exam.id,
        user_id=user.id
    ).first()
    
    if not leaderboard_entry:
        leaderboard_entry = Leaderboard(
            exam_id=exam.id,
            user_id=user.id,
            total_score=attempt.total_score,
            completion_time=(attempt.finished_at - attempt.started_at).seconds
        )
        db.session.add(leaderboard_entry)
    else:
        if attempt.total_score > leaderboard_entry.total_score:
            leaderboard_entry.total_score = attempt.total_score
            leaderboard_entry.completion_time = (attempt.finished_at - attempt.started_at).seconds
    
    db.session.commit()
    
    if user.role == 'student':
        branch_entries = Leaderboard.query.join(User).filter(
            Leaderboard.exam_id == exam.id,
            User.inspira_branch == user.inspira_branch
        ).order_by(Leaderboard.total_score.desc(), Leaderboard.completion_time.asc()).all()
        
        for rank, entry in enumerate(branch_entries, 1):
            entry.rank_in_branch = rank
    
    global_entries = Leaderboard.query.filter_by(
        exam_id=exam.id
    ).order_by(Leaderboard.total_score.desc(), Leaderboard.completion_time.asc()).all()
    
    for rank, entry in enumerate(global_entries, 1):
        entry.rank_global = rank
    
    db.session.commit()

@app.route('/admin/exams/<int:exam_id>/essay_answers')
@login_required
def admin_view_essay_answers(exam_id):
    if current_user.role not in ['admin', 'teacher']:
        flash('Akses ditolak!', 'danger')
        return redirect(url_for('index'))
    
    exam = Exam.query.get_or_404(exam_id)
    attempts = ExamAttempt.query.filter_by(exam_id=exam_id, is_completed=True).all()
    
    essay_questions = Question.query.filter_by(exam_id=exam_id, question_type='essay').all()
    
    return render_template('essay_answers.html', 
                         exam=exam, 
                         attempts=attempts, 
                         essay_questions=essay_questions)

@app.route('/uploads/<path:filename>')
def uploaded_file(filename):
    from flask import send_from_directory
    return send_from_directory(app.config['UPLOAD_FOLDER'], filename)

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
