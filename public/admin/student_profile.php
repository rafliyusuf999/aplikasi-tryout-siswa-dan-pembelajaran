<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Profil Siswa';

$student_id = (int)($_GET['id'] ?? 0);

if (!$student_id) {
    setFlash('ID siswa tidak valid', 'danger');
    redirect('admin/students.php');
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    setFlash('Siswa tidak ditemukan', 'danger');
    redirect('admin/students.php');
}

$stmt = $pdo->prepare("SELECT ea.*, e.title as exam_title 
                       FROM exam_attempts ea 
                       JOIN exams e ON ea.exam_id = e.id 
                       WHERE ea.user_id = ? 
                       ORDER BY ea.started_at DESC");
$stmt->execute([$student_id]);
$attempts = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(cheating_warnings), 0) as total_cheating FROM exam_attempts WHERE user_id = ?");
$stmt->execute([$student_id]);
$total_cheating = $stmt->fetch()['total_cheating'];

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div style="margin-bottom: 1rem;">
        <a href="<?php echo url('admin/students.php'); ?>" class="btn btn-secondary">← Kembali ke Kelola Siswa</a>
    </div>
    
    <div class="card">
        <h2 style="text-align: center; margin-bottom: 2rem;">👤 Profil Siswa</h2>
        
        <div style="text-align: center; margin-bottom: 2rem;">
            <?php if(!empty($student['profile_photo'])): ?>
            <img src="<?php echo url('storage/uploads/profiles/' . $student['profile_photo']); ?>" 
                 alt="<?php echo htmlspecialchars($student['full_name']); ?>" 
                 style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color);">
            <?php else: ?>
            <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 3rem; margin: 0 auto; border: 4px solid var(--primary-color);">
                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div>
                <label style="font-weight: bold; color: #666;">Nama Lengkap:</label>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($student['full_name']); ?></p>
            </div>
            
            <div>
                <label style="font-weight: bold; color: #666;">Email:</label>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($student['email']); ?></p>
            </div>
            
            <div>
                <label style="font-weight: bold; color: #666;">Cabang:</label>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($student['inspira_branch'] ?? '-'); ?></p>
            </div>
            
            <div>
                <label style="font-weight: bold; color: #666;">Kelas:</label>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($student['class_level'] ?? '-'); ?></p>
            </div>
            
            <div>
                <label style="font-weight: bold; color: #666;">Sekolah:</label>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($student['school_name'] ?? '-'); ?></p>
            </div>
            
            <div>
                <label style="font-weight: bold; color: #666;">No. HP:</label>
                <p style="margin-top: 0.5rem;"><?php echo htmlspecialchars($student['phone_number'] ?? '-'); ?></p>
            </div>
            
            <div>
                <label style="font-weight: bold; color: #666;">Total Peringatan Curang:</label>
                <p style="margin-top: 0.5rem;">
                    <?php if ($total_cheating > 0): ?>
                        <span class="badge badge-danger">⚠️ <?php echo $total_cheating; ?></span>
                    <?php else: ?>
                        <span class="badge badge-success">✓ Bersih</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div>
                <label style="font-weight: bold; color: #666;">Terdaftar Sejak:</label>
                <p style="margin-top: 0.5rem;"><?php echo date('d M Y', strtotime($student['created_at'])); ?></p>
            </div>
        </div>
    </div>
    
    <div class="card" style="margin-top: 2rem;">
        <h3>📊 Riwayat Ujian</h3>
        
        <?php if (count($attempts) > 0): ?>
        <div style="overflow-x: auto; margin-top: 1rem;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Try Out</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th>Waktu Pengerjaan</th>
                        <th>Peringatan</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attempts as $attempt): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                        <td><strong><?php echo number_format($attempt['total_score'], 1); ?></strong></td>
                        <td>
                            <span class="badge badge-<?php echo $attempt['is_completed'] ? 'success' : 'warning'; ?>">
                                <?php echo $attempt['is_completed'] ? 'Selesai' : 'Proses'; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($attempt['finished_at']) {
                                $start = new DateTime($attempt['started_at']);
                                $end = new DateTime($attempt['finished_at']);
                                $diff = $start->diff($end);
                                echo $diff->h . 'j ' . $diff->i . 'm';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($attempt['cheating_warnings'] > 0): ?>
                                <span class="badge badge-danger">⚠️ <?php echo $attempt['cheating_warnings']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-success">✓</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d M Y H:i', strtotime($attempt['started_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 2rem; color: #999;">
            <p>Siswa ini belum pernah mengikuti ujian</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
