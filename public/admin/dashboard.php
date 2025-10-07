<?php
require_once '../../config/config.php';

requireRole('admin');

$pdo = getDB();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'");
$total_teachers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM exams WHERE is_active = TRUE");
$total_exams = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM exam_attempts");
$active_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
$pending_payments = $stmt->fetchColumn();

$pageTitle = 'Admin Dashboard - INSPIRANET';
include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<h1 style="color: var(--primary-color); margin-bottom: 2rem;">👨‍💼 Admin Dashboard</h1>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $total_students; ?></h3>
        <p>Total Siswa Terdaftar</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $active_students; ?></h3>
        <p>Siswa Aktif (Mengerjakan TO)</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $total_exams; ?></h3>
        <p>Tes Online Aktif</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $pending_payments; ?></h3>
        <p>Pembayaran Pending</p>
    </div>
</div>

<div class="card">
    <h2>⚡ Quick Access</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <a href="<?php echo url('admin/students.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">Kelola Siswa</a>
        <a href="<?php echo url('admin/teachers.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">Kelola Guru</a>
        <a href="<?php echo url('admin/exams.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">Kelola TO</a>
        <a href="<?php echo url('admin/payments.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">Konfirmasi Pembayaran</a>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
