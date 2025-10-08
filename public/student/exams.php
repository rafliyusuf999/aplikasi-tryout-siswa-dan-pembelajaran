<?php
require_once '../../config/config.php';

requireAuth('student');

$pdo = getDB();
$user = getCurrentUser();
$pageTitle = 'Daftar Try Out';

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? 'all';

$query = "SELECT e.*, u.full_name as creator_name,
          (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND user_id = :user_id1) as attempt_count,
          (SELECT status FROM payments WHERE exam_id = e.id AND user_id = :user_id2 ORDER BY created_at DESC LIMIT 1) as payment_status
          FROM exams e
          LEFT JOIN users u ON e.created_by = u.id
          WHERE e.is_active = true";

if ($filter === 'free') {
    $query .= " AND e.is_premium = false";
} elseif ($filter === 'premium') {
    $query .= " AND e.is_premium = true";
}

if ($search) {
    $query .= " AND (e.title LIKE :search OR e.description LIKE :search)";
}

$query .= " ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($query);
$params = ['user_id1' => $user['id'], 'user_id2' => $user['id']];
if ($search) {
    $params['search'] = "%$search%";
}
$stmt->execute($params);
$exams = $stmt->fetchAll();

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Daftar Try Out</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">
                Semua
            </a>
            <a href="?filter=free" class="btn <?php echo $filter === 'free' ? 'btn-primary' : 'btn-secondary'; ?>">
                Gratis
            </a>
            <a href="?filter=premium" class="btn <?php echo $filter === 'premium' ? 'btn-primary' : 'btn-secondary'; ?>">
                Premium
            </a>
        </div>
        
        <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari TO..." class="form-control" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?php echo url('student/exams.php?filter=' . $filter); ?>" class="btn btn-secondary">Reset</a>
        </form>
        
        <div class="exam-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php foreach ($exams as $exam): ?>
            <div class="exam-card card" style="padding: 1.5rem;">
                <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($exam['title']); ?></h3>
                <?php if ($exam['description']): ?>
                <p class="text-muted" style="margin-bottom: 1rem; font-size: 0.9rem;">
                    <?php echo htmlspecialchars(substr($exam['description'], 0, 100)); ?>
                    <?php echo strlen($exam['description']) > 100 ? '...' : ''; ?>
                </p>
                <?php endif; ?>
                
                <div style="margin-bottom: 1rem;">
                    <small class="text-muted">
                        ⏱️ <?php echo $exam['duration_minutes']; ?> menit
                        <?php if ($exam['creator_name']): ?>
                        | 👨‍🏫 <?php echo htmlspecialchars($exam['creator_name']); ?>
                        <?php endif; ?>
                    </small>
                    <?php 
                    $current_time = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
                    $exam_status = 'available';
                    $status_message = '';
                    
                    if ($exam['start_time']) {
                        $start_time = new DateTime($exam['start_time'], new DateTimeZone('Asia/Jakarta'));
                        if ($current_time < $start_time) {
                            $exam_status = 'not_started';
                            $status_message = '🕐 Mulai: ' . $start_time->format('d/m/Y H:i') . ' WIB';
                        }
                    }
                    
                    if ($exam['end_time']) {
                        $end_time = new DateTime($exam['end_time'], new DateTimeZone('Asia/Jakarta'));
                        if ($current_time > $end_time) {
                            $exam_status = 'ended';
                            $status_message = '⏰ Berakhir: ' . $end_time->format('d/m/Y H:i') . ' WIB';
                        } else if ($exam_status === 'available') {
                            $status_message = '⏰ Berakhir: ' . $end_time->format('d/m/Y H:i') . ' WIB';
                        }
                    }
                    
                    if ($status_message): ?>
                        <br><small class="text-muted" style="<?php echo $exam_status === 'not_started' ? 'color: #ff9800 !important;' : ($exam_status === 'ended' ? 'color: #f44336 !important;' : ''); ?>"><?php echo $status_message; ?></small>
                    <?php endif; ?>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <?php if ($exam['is_premium']): ?>
                    <span class="badge badge-warning">💎 Premium - Rp <?php echo number_format($exam['price'], 0, ',', '.'); ?></span>
                    <?php else: ?>
                    <span class="badge badge-info">🆓 Gratis</span>
                    <?php endif; ?>
                    
                    <?php if ($exam['attempt_count'] > 0): ?>
                    <span class="badge badge-success">✓ Sudah Dikerjakan <?php echo $exam['attempt_count']; ?>x</span>
                    <?php endif; ?>
                </div>
                
                <div>
                    <?php 
                    $can_access = true;
                    $button_text = 'Mulai TO';
                    $button_class = 'btn-primary';
                    
                    if ($exam['is_premium']) {
                        if (!$exam['payment_status'] || $exam['payment_status'] !== 'approved') {
                            $can_access = false;
                            if (!$exam['payment_status'] || $exam['payment_status'] === 'rejected') {
                                $button_text = 'Bayar Sekarang';
                                $button_class = 'btn-warning';
                            } elseif ($exam['payment_status'] === 'pending') {
                                $button_text = 'Menunggu Verifikasi';
                                $button_class = 'btn-secondary';
                            }
                        }
                    }
                    ?>
                    
                    <?php if ($exam_status === 'not_started'): ?>
                        <button class="btn btn-secondary" style="width: 100%;" disabled>Belum Dimulai</button>
                    <?php elseif ($exam_status === 'ended'): ?>
                        <button class="btn btn-danger" style="width: 100%;" disabled>Sudah Berakhir</button>
                    <?php elseif ($can_access): ?>
                        <a href="<?php echo url('student/exam_detail.php?id=' . $exam['id']); ?>" class="btn <?php echo $button_class; ?>" style="width: 100%;">
                            <?php echo $button_text; ?>
                        </a>
                    <?php elseif ($exam['payment_status'] === 'pending'): ?>
                        <button class="btn <?php echo $button_class; ?>" style="width: 100%;" disabled>
                            <?php echo $button_text; ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo url('student/pay.php?exam_id=' . $exam['id']); ?>" class="btn <?php echo $button_class; ?>" style="width: 100%;">
                            <?php echo $button_text; ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (count($exams) === 0): ?>
        <div style="text-align: center; padding: 3rem; color: #999;">
            <p>Tidak ada Try Out tersedia</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
