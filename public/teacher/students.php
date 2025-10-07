<?php
require_once '../../config/config.php';

requireAuth('teacher');

$pdo = getDB();
$user = getCurrentUser();
$pageTitle = 'Hasil Siswa';

$exam_id = $_GET['exam_id'] ?? null;
$search = $_GET['search'] ?? '';

$stmt = $pdo->prepare("SELECT id, title FROM exams WHERE created_by = ? ORDER BY created_at DESC");
$stmt->execute([$user['id']]);
$exams = $stmt->fetchAll();

if ($exam_id) {
    $query = "SELECT ea.*, u.full_name as student_name, u.email as student_email, 
              u.inspira_branch, e.title as exam_title
              FROM exam_attempts ea
              JOIN users u ON ea.user_id = u.id
              JOIN exams e ON ea.exam_id = e.id
              WHERE e.created_by = :created_by AND ea.exam_id = :exam_id";
    
    if ($search) {
        $query .= " AND (u.full_name ILIKE :search OR u.email ILIKE :search)";
    }
    
    $query .= " ORDER BY ea.total_score DESC, ea.finished_at ASC";
    
    $stmt = $pdo->prepare($query);
    $params = ['created_by' => $user['id'], 'exam_id' => $exam_id];
    if ($search) {
        $params['search'] = "%$search%";
    }
    $stmt->execute($params);
    $attempts = $stmt->fetchAll();
} else {
    $attempts = [];
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Hasil Siswa</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <div class="form-group">
            <label>Pilih Try Out</label>
            <select id="exam_select" class="form-control" onchange="location.href='?exam_id=' + this.value">
                <option value="">-- Pilih Try Out --</option>
                <?php foreach ($exams as $exam): ?>
                <option value="<?php echo $exam['id']; ?>" <?php echo $exam_id == $exam['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($exam['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($exam_id): ?>
        <form method="GET" style="display: flex; gap: 1rem; margin-top: 1.5rem; margin-bottom: 1.5rem;">
            <input type="hidden" name="exam_id" value="<?php echo htmlspecialchars($exam_id); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari siswa..." class="form-control" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?php echo url('teacher/students.php?exam_id=' . $exam_id); ?>" class="btn btn-secondary">Reset</a>
        </form>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Nama Siswa</th>
                        <th>Cabang</th>
                        <th>Nilai</th>
                        <th>Status</th>
                        <th>Waktu</th>
                        <th>Peringatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($attempts as $attempt): 
                    ?>
                    <tr>
                        <td>
                            <?php if ($rank <= 3): ?>
                                <?php if ($rank == 1): ?>🥇
                                <?php elseif ($rank == 2): ?>🥈
                                <?php else: ?>🥉
                                <?php endif; ?>
                            <?php else: ?>
                                #<?php echo $rank; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($attempt['student_name']); ?><br>
                            <small><?php echo htmlspecialchars($attempt['student_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($attempt['inspira_branch'] ?? '-'); ?></td>
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
                                <span class="badge badge-success">✓ Bersih</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #999;">
            <p>Silakan pilih Try Out untuk melihat hasil siswa</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
