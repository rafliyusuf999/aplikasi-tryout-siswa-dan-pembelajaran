<?php
require_once '../config/config.php';

if(isLoggedIn()) {
    $user = getCurrentUser();
    if($user['role'] == 'admin') {
        redirect('admin/dashboard.php');
    } elseif($user['role'] == 'teacher') {
        redirect('teacher/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$pdo = getDB();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM exams WHERE is_active = 1");
$total_exams = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT inspira_branch) FROM users WHERE inspira_branch IS NOT NULL");
$total_branches = $stmt->fetchColumn();

$active_students = $total_students;

$pageTitle = 'INSPIRANET OFFICIAL TO';
include '../app/Views/includes/header.php';
include '../app/Views/includes/navbar.php';
?>

<div class="hero">
    <h1>🎓 Selamat Datang di INSPIRANET</h1>
    <p>Platform Try Out Online Terpercaya untuk Persiapan Ujian Anda</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-number"><?php echo $total_students; ?></div>
        <div class="stat-label">Total Siswa</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">📝</div>
        <div class="stat-number"><?php echo $total_exams; ?></div>
        <div class="stat-label">Try Out Aktif</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">🏢</div>
        <div class="stat-number"><?php echo $total_branches; ?></div>
        <div class="stat-label">Cabang</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">✨</div>
        <div class="stat-number"><?php echo $active_students; ?></div>
        <div class="stat-label">Siswa Aktif</div>
    </div>
</div>

<div class="card" style="margin-top: 3rem;">
    <h2>Tentang Platform Kami</h2>
    <p>INSPIRANET adalah platform try out online yang dirancang khusus untuk membantu siswa mempersiapkan diri menghadapi ujian. Dengan sistem yang canggih dan anti-kecurangan, kami memastikan pengalaman ujian yang adil dan kompetitif.</p>
    
    <h3 style="margin-top: 2rem;">Fitur Unggulan:</h3>
    <ul style="line-height: 2;">
        <li>📊 Sistem Peringkat Cabang & Global</li>
        <li>🔒 Anti-Kecurangan Ketat</li>
        <li>💳 Pembayaran Try Out Premium</li>
        <li>📈 Analisis Hasil Detail</li>
        <li>👨‍🏫 Bimbingan Guru Profesional</li>
    </ul>
</div>

<?php include '../app/Views/includes/footer.php'; ?>
