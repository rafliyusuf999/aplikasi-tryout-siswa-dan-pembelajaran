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
