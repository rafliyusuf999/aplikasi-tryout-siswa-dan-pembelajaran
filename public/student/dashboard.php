<?php
require_once '../../config/config.php';

requireRole('student');

$user = getCurrentUser();
$pdo = getDB();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts WHERE user_id = ? AND is_completed = true");
$stmt->execute([$user['id']]);
$total_attempts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT AVG(total_score) FROM exam_attempts WHERE user_id = ? AND is_completed = true");
$stmt->execute([$user['id']]);
$avg_score = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("
    SELECT MIN(rank_in_branch) as best_rank_branch, MIN(rank_global) as best_rank_global 
    FROM leaderboards 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$rankings = $stmt->fetch();
$best_rank_branch = $rankings['best_rank_branch'] ?? '-';
$best_rank_global = $rankings['best_rank_global'] ?? '-';

$stmt = $pdo->prepare("SELECT MAX(total_score) FROM exam_attempts WHERE user_id = ? AND is_completed = true");
$stmt->execute([$user['id']]);
$best_score = $stmt->fetchColumn() ?? 0;

$stmt = $pdo->prepare("
    SELECT ea.*, e.title, e.duration_minutes, e.is_premium
    FROM exam_attempts ea
    JOIN exams e ON ea.exam_id = e.id
    WHERE ea.user_id = ? AND ea.is_completed = true
    ORDER BY ea.finished_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$recent_attempts = $stmt->fetchAll();

// Get free tryouts
$stmt = $pdo->prepare("
    SELECT e.*, 
    (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND user_id = ?) as attempt_count
    FROM exams e
    WHERE e.is_active = true AND e.is_premium = false
    ORDER BY e.created_at DESC
    LIMIT 3
");
$stmt->execute([$user['id']]);
$free_tryouts = $stmt->fetchAll();

// Get premium tryouts
$stmt = $pdo->prepare("
    SELECT e.*, 
    (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND user_id = ?) as attempt_count,
    (SELECT status FROM payments WHERE exam_id = e.id AND user_id = ? ORDER BY created_at DESC LIMIT 1) as payment_status
    FROM exams e
    WHERE e.is_active = true AND e.is_premium = true
    ORDER BY e.created_at DESC
    LIMIT 3
");
$stmt->execute([$user['id'], $user['id']]);
$premium_tryouts = $stmt->fetchAll();

$pageTitle = 'Dashboard Siswa - INSPIRANET';
include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<h1 style="color: #1a1a1a; margin-bottom: 2rem; text-align: center;">📊 Dashboard Siswa</h1>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?php echo $total_attempts; ?></h3>
        <p>Total TO Dikerjakan</p>
    </div>
    <div class="stat-card">
        <h3><?php echo number_format($avg_score, 1); ?></h3>
        <p>Rata-rata Nilai</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $best_rank_branch != '-' ? '#' . $best_rank_branch : '-'; ?></h3>
        <p>Peringkat Terbaik (Cabang)</p>
    </div>
    <div class="stat-card">
        <h3><?php echo number_format($best_score, 1); ?></h3>
        <p>Nilai Terbaik</p>
    </div>
</div>

<div class="card" style="margin-top: 2rem;">
    <h2>🚀 Quick Access</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
        <a href="<?php echo url('student/exams.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">📝 Lihat Daftar TO</a>
        <a href="<?php echo url('leaderboards.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">🏆 Lihat Peringkat</a>
        <a href="<?php echo url('profile.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">👤 Edit Profil</a>
    </div>
</div>

<!-- Free Tryouts Section -->
<div class="card" style="margin-top: 2rem; background: linear-gradient(135deg, #E8F5E9 0%, #C8E6C9 100%); border: 2px solid #4CAF50;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h2 style="color: #2E7D32; margin: 0;">🆓 Try Out Gratis</h2>
        <a href="<?php echo url('student/exams.php?filter=free'); ?>" class="btn btn-secondary" style="font-size: 0.9rem;">Lihat Semua</a>
    </div>
    
    <?php if(count($free_tryouts) > 0): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
        <?php foreach($free_tryouts as $tryout): ?>
        <div style="background: white; border-radius: 8px; padding: 1.25rem; border: 1px solid #A5D6A7;">
            <h3 style="color: #1a1a1a; font-size: 1.1rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($tryout['title']); ?></h3>
            <p style="color: #2d2d2d; font-size: 0.85rem; margin-bottom: 0.75rem;">
                ⏱️ <?php echo $tryout['duration_minutes']; ?> menit | 📝 <?php echo $tryout['total_questions']; ?> soal
            </p>
            <?php if($tryout['attempt_count'] > 0): ?>
            <span class="badge badge-success" style="margin-bottom: 0.75rem;">✓ Sudah Dikerjakan <?php echo $tryout['attempt_count']; ?>x</span>
            <?php endif; ?>
            <a href="<?php echo url('student/exam_detail.php?id=' . $tryout['id']); ?>" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                <?php echo $tryout['attempt_count'] > 0 ? 'Kerjakan Lagi' : 'Mulai TO'; ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="text-align: center; color: #2d2d2d; padding: 2rem;">Belum ada Try Out gratis tersedia</p>
    <?php endif; ?>
</div>

<!-- Premium Tryouts Section -->
<div class="card" style="margin-top: 2rem; background: linear-gradient(135deg, #FFF9C4 0%, #FFE082 100%); border: 2px solid #FFA000;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h2 style="color: #F57C00; margin: 0;">💎 Try Out Premium</h2>
        <a href="<?php echo url('student/exams.php?filter=premium'); ?>" class="btn btn-secondary" style="font-size: 0.9rem;">Lihat Semua</a>
    </div>
    
    <?php if(count($premium_tryouts) > 0): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
        <?php foreach($premium_tryouts as $tryout): 
            $can_access = ($tryout['payment_status'] === 'approved');
        ?>
        <div style="background: white; border-radius: 8px; padding: 1.25rem; border: 1px solid #FFB74D;">
            <h3 style="color: #1a1a1a; font-size: 1.1rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($tryout['title']); ?></h3>
            <p style="color: #2d2d2d; font-size: 0.85rem; margin-bottom: 0.75rem;">
                ⏱️ <?php echo $tryout['duration_minutes']; ?> menit | 📝 <?php echo $tryout['total_questions']; ?> soal
            </p>
            <span class="badge badge-warning" style="margin-bottom: 0.75rem;">💎 Rp <?php echo number_format($tryout['price'], 0, ',', '.'); ?></span>
            <?php if($tryout['attempt_count'] > 0): ?>
            <span class="badge badge-success">✓ Sudah Dikerjakan <?php echo $tryout['attempt_count']; ?>x</span>
            <?php endif; ?>
            
            <?php if($can_access): ?>
            <a href="<?php echo url('student/exam_detail.php?id=' . $tryout['id']); ?>" class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;">
                <?php echo $tryout['attempt_count'] > 0 ? 'Kerjakan Lagi' : 'Mulai TO'; ?>
            </a>
            <?php elseif($tryout['payment_status'] === 'pending'): ?>
            <button class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;" disabled>Menunggu Verifikasi</button>
            <?php else: ?>
            <a href="<?php echo url('student/pay.php?exam_id=' . $tryout['id']); ?>" class="btn btn-warning" style="width: 100%; margin-top: 0.5rem;">Bayar Sekarang</a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p style="text-align: center; color: #2d2d2d; padding: 2rem;">Belum ada Try Out premium tersedia</p>
    <?php endif; ?>
</div>

<?php if(count($recent_attempts) > 0): ?>
<div class="card" style="margin-top: 2rem;">
    <h2>📚 Riwayat TO Terakhir</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama TO</th>
                    <th>Nilai</th>
                    <th>Durasi</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($recent_attempts as $attempt): ?>
                <tr>
                    <td>
                        <?php echo htmlspecialchars($attempt['title']); ?>
                        <?php if($attempt['is_premium']): ?>
                        <span style="background: gold; color: #333; padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.75rem; font-weight: bold; margin-left: 0.5rem;">
                            ⭐ PREMIUM
                        </span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo number_format($attempt['total_score'], 1); ?></strong></td>
                    <td><?php echo $attempt['duration_minutes']; ?> menit</td>
                    <td><?php echo date('d/m/Y H:i', strtotime($attempt['finished_at'])); ?></td>
                    <td>
                        <a href="<?php echo url('student/result.php?id=' . $attempt['id']); ?>" class="btn btn-primary" style="padding: 0.5rem 1rem;">Lihat Detail</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-top: 2rem; text-align: center; padding: 3rem;">
    <h3>Belum ada riwayat TO</h3>
    <p>Ayo mulai kerjakan TO pertamamu!</p>
    <a href="<?php echo url('student/exams.php'); ?>" class="btn btn-primary" style="margin-top: 1rem;">Lihat Daftar TO</a>
</div>
<?php endif; ?>

<?php include '../../app/Views/includes/footer.php'; ?>
