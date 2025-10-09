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

$stmt = $pdo->query("SELECT COUNT(*) FROM exams WHERE is_active = true");
$total_exams = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT inspira_branch) FROM users WHERE inspira_branch IS NOT NULL");
$total_branches = $stmt->fetchColumn();

$active_students = $total_students;

$pageTitle = 'INSPIRANET OFFICIAL TO';
include '../app/Views/includes/header.php';
include '../app/Views/includes/navbar.php';
?>

<canvas id="particles-bg"></canvas>

<div class="hero">
    <h1>🎓 Selamat Datang di INSPIRANET</h1>
    <p>Platform Try Out Online Terpercaya untuk Persiapan Ujian Anda</p>
</div>

<div class="stats-grid">
    <div class="stat-card" data-animate="slide-up">
        <div class="stat-icon">👥</div>
        <div class="stat-number" data-count="<?php echo $total_students; ?>">0</div>
        <div class="stat-label">Total Siswa</div>
    </div>
    <div class="stat-card" data-animate="slide-up" style="animation-delay: 0.1s;">
        <div class="stat-icon">📝</div>
        <div class="stat-number" data-count="<?php echo $total_exams; ?>">0</div>
        <div class="stat-label">Try Out Aktif</div>
    </div>
    <div class="stat-card" data-animate="slide-up" style="animation-delay: 0.2s;">
        <div class="stat-icon">🏢</div>
        <div class="stat-number" data-count="<?php echo $total_branches; ?>">0</div>
        <div class="stat-label">Cabang</div>
    </div>
    <div class="stat-card" data-animate="slide-up" style="animation-delay: 0.3s;">
        <div class="stat-icon">✨</div>
        <div class="stat-number" data-count="<?php echo $active_students; ?>">0</div>
        <div class="stat-label">Siswa Aktif</div>
    </div>
</div>

<div class="card" style="margin-top: 3rem;" data-animate="fade-in">
    <h2 style="text-align: center;">Tentang Platform Kami</h2>
    <p style="color: #1a1a1a; line-height: 1.8; text-align: center; max-width: 800px; margin: 0 auto;">INSPIRANET adalah platform try out online yang dirancang khusus untuk membantu siswa mempersiapkan diri menghadapi ujian. Dengan sistem yang canggih dan anti-kecurangan, kami memastikan pengalaman ujian yang adil dan kompetitif.</p>
</div>

<div class="card" style="margin-top: 2rem;" data-animate="fade-in">
    <h2 style="text-align: center; margin-bottom: 2rem;">✨ Fitur Unggulan</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; max-width: 900px; margin: 0 auto;">
        <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary-color); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
            <div style="font-size: 2.5rem; text-align: center; margin-bottom: 0.75rem;">📊</div>
            <h3 style="color: #1a1a1a; text-align: center; font-size: 1.1rem; margin-bottom: 0.5rem;">Peringkat Cabang & Global</h3>
            <p style="color: #2d2d2d; text-align: center; font-size: 0.9rem; margin: 0; line-height: 1.6;">Sistem kompetisi yang adil antar cabang dan global</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary-color); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
            <div style="font-size: 2.5rem; text-align: center; margin-bottom: 0.75rem;">🔒</div>
            <h3 style="color: #1a1a1a; text-align: center; font-size: 1.1rem; margin-bottom: 0.5rem;">Anti-Kecurangan Ketat</h3>
            <p style="color: #2d2d2d; text-align: center; font-size: 0.9rem; margin: 0; line-height: 1.6;">Proteksi copy-paste, screenshot, dan pindah tab</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary-color); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
            <div style="font-size: 2.5rem; text-align: center; margin-bottom: 0.75rem;">💳</div>
            <h3 style="color: #1a1a1a; text-align: center; font-size: 1.1rem; margin-bottom: 0.5rem;">Pembayaran Premium</h3>
            <p style="color: #2d2d2d; text-align: center; font-size: 0.9rem; margin: 0; line-height: 1.6;">Akses try out premium dengan sistem pembayaran mudah</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary-color); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
            <div style="font-size: 2.5rem; text-align: center; margin-bottom: 0.75rem;">📈</div>
            <h3 style="color: #1a1a1a; text-align: center; font-size: 1.1rem; margin-bottom: 0.5rem;">Analisis Hasil Detail</h3>
            <p style="color: #2d2d2d; text-align: center; font-size: 0.9rem; margin: 0; line-height: 1.6;">Lihat hasil lengkap dengan pembahasan soal</p>
        </div>
        
        <div style="background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%); padding: 1.5rem; border-radius: 12px; border-left: 4px solid var(--primary-color); transition: transform 0.3s ease, box-shadow 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 8px 25px rgba(59, 130, 246, 0.25)';" onmouseout="this.style.transform=''; this.style.boxShadow='';">
            <div style="font-size: 2.5rem; text-align: center; margin-bottom: 0.75rem;">👨‍🏫</div>
            <h3 style="color: #1a1a1a; text-align: center; font-size: 1.1rem; margin-bottom: 0.5rem;">Bimbingan Mentor</h3>
            <p style="color: #2d2d2d; text-align: center; font-size: 0.9rem; margin: 0; line-height: 1.6;">Didampingi mentor berpengalaman</p>
        </div>
    </div>
</div>

<?php include '../app/Views/includes/footer.php'; ?>
