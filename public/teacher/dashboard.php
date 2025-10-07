<?php
require_once '../../config/config.php';

requireAuth('teacher');

$pdo = getDB();
$user = getCurrentUser();
$pageTitle = 'Dashboard Teacher';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM exams WHERE created_by = ?");
$stmt->execute([$user['id']]);
$total_exams = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT ea.user_id) FROM exam_attempts ea 
                       JOIN exams e ON ea.exam_id = e.id 
                       WHERE e.created_by = ?");
$stmt->execute([$user['id']]);
$total_students = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts ea 
                       JOIN exams e ON ea.exam_id = e.id 
                       WHERE e.created_by = ? AND ea.is_completed = TRUE");
$stmt->execute([$user['id']]);
$completed_attempts = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT e.*, COUNT(ea.id) as attempt_count 
                       FROM exams e 
                       LEFT JOIN exam_attempts ea ON e.id = ea.exam_id 
                       WHERE e.created_by = ? 
                       GROUP BY e.id 
                       ORDER BY e.created_at DESC 
                       LIMIT 5");
$stmt->execute([$user['id']]);
$recent_exams = $stmt->fetchAll();

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1 style="color: var(--primary-color); margin-bottom: 2rem;">👨‍🏫 Dashboard Teacher</h1>

    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $total_exams; ?></h3>
            <p>Try Out Dibuat</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $total_students; ?></h3>
            <p>Siswa Mengerjakan</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $completed_attempts; ?></h3>
            <p>Total Pengerjaan</p>
        </div>
        <div class="stat-card">
            <h3><?php echo count($recent_exams); ?></h3>
            <p>TO Terbaru</p>
        </div>
    </div>

    <div class="card" style="margin-top: 2rem;">
        <h2>⚡ Quick Access</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1rem;">
            <a href="<?php echo url('teacher/exams.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">📝 Kelola Try Out</a>
            <a href="<?php echo url('teacher/students.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">👥 Hasil Siswa</a>
            <a href="<?php echo url('admin/payments.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center;">💰 Pembayaran</a>
        </div>
    </div>

    <?php if (count($recent_exams) > 0): ?>
    <div class="card" style="margin-top: 2rem;">
        <h2>📊 Try Out Terbaru</h2>
        <div style="overflow-x: auto; margin-top: 1rem;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Tipe</th>
                        <th>Pengerjaan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_exams as $exam): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $exam['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $exam['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $exam['is_premium'] ? 'warning' : 'info'; ?>">
                                <?php echo $exam['is_premium'] ? 'Premium' : 'Gratis'; ?>
                            </span>
                        </td>
                        <td><?php echo $exam['attempt_count']; ?> kali</td>
                        <td>
                            <a href="<?php echo url('admin/questions.php?exam_id=' . $exam['id']); ?>" class="btn btn-sm btn-primary">Kelola Soal</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
