<?php
require_once '../../config/config.php';

requireAuth('student');

$pdo = getDB();
$user = getCurrentUser();
$pageTitle = 'Hasil Try Out';

$attempt_id = (int)($_GET['id'] ?? 0);

if (!$attempt_id) {
    setFlash('ID attempt tidak valid', 'danger');
    redirect('student/exams.php');
}

$stmt = $pdo->prepare("SELECT ea.*, e.title as exam_title, e.total_questions 
                       FROM exam_attempts ea 
                       JOIN exams e ON ea.exam_id = e.id 
                       WHERE ea.id = ? AND ea.user_id = ?");
$stmt->execute([$attempt_id, $user['id']]);
$attempt = $stmt->fetch();

if (!$attempt) {
    setFlash('Hasil tidak ditemukan', 'danger');
    redirect('student/exams.php');
}

$stmt = $pdo->prepare("SELECT COUNT(*) as rank FROM exam_attempts 
                       WHERE exam_id = ? AND total_score > ? AND is_completed = true");
$stmt->execute([$attempt['exam_id'], $attempt['total_score']]);
$rank_result = $stmt->fetch();
$rank = $rank_result['rank'] + 1;

$stmt = $pdo->prepare("SELECT COUNT(*) as total_participants FROM exam_attempts 
                       WHERE exam_id = ? AND is_completed = true");
$stmt->execute([$attempt['exam_id']]);
$total_participants = $stmt->fetch()['total_participants'];

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<style>
.result-card {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.score-display {
    font-size: 4rem;
    font-weight: bold;
    color: var(--primary-color);
    text-align: center;
    margin: 2rem 0;
}

.rank-badge {
    display: inline-block;
    padding: 1rem 2rem;
    border-radius: 50px;
    font-size: 1.5rem;
    font-weight: bold;
    margin: 1rem 0;
}

.rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
.rank-2 { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: white; }
.rank-3 { background: linear-gradient(135deg, #CD7F32, #B8860B); color: white; }
.rank-other { background: linear-gradient(135deg, #6c757d, #5a6268); color: white; }
</style>

<div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div class="result-card">
        <h1 style="text-align: center; margin-bottom: 2rem;">🎉 Hasil Try Out</h1>
        
        <div style="text-align: center;">
            <h2><?php echo htmlspecialchars($attempt['exam_title']); ?></h2>
            
            <div class="score-display">
                <?php echo number_format($attempt['total_score'], 1); ?>
            </div>
            
            <div style="margin: 2rem 0;">
                <?php if ($rank == 1): ?>
                    <div class="rank-badge rank-1">🥇 Peringkat 1</div>
                <?php elseif ($rank == 2): ?>
                    <div class="rank-badge rank-2">🥈 Peringkat 2</div>
                <?php elseif ($rank == 3): ?>
                    <div class="rank-badge rank-3">🥉 Peringkat 3</div>
                <?php else: ?>
                    <div class="rank-badge rank-other">Peringkat <?php echo $rank; ?></div>
                <?php endif; ?>
                
                <p style="margin-top: 1rem; color: #666;">
                    dari <?php echo $total_participants; ?> peserta
                </p>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; color: var(--primary-color);">📝</div>
                    <div style="font-size: 1.5rem; font-weight: bold;"><?php echo $attempt['total_questions']; ?></div>
                    <div style="color: #666;">Total Soal</div>
                </div>
                
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; color: var(--primary-color);">▶️</div>
                    <div style="font-size: 1.2rem; font-weight: bold;">
                        <?php 
                        $start_dt = new DateTime($attempt['started_at']);
                        echo $start_dt->format('H:i');
                        ?>
                    </div>
                    <div style="color: #666; font-size: 0.85rem;">
                        <?php echo $start_dt->format('d/m/Y'); ?>
                    </div>
                    <div style="color: #666; margin-top: 0.3rem;">Waktu Mulai</div>
                </div>
                
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; color: var(--primary-color);">⏹️</div>
                    <div style="font-size: 1.2rem; font-weight: bold;">
                        <?php 
                        if ($attempt['finished_at']) {
                            $end_dt = new DateTime($attempt['finished_at']);
                            echo $end_dt->format('H:i');
                        } else {
                            echo '-';
                        }
                        ?>
                    </div>
                    <div style="color: #666; font-size: 0.85rem;">
                        <?php echo $attempt['finished_at'] ? $end_dt->format('d/m/Y') : ''; ?>
                    </div>
                    <div style="color: #666; margin-top: 0.3rem;">Waktu Selesai</div>
                </div>
                
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem; color: var(--primary-color);">⏱️</div>
                    <div style="font-size: 1.5rem; font-weight: bold;">
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
                    </div>
                    <div style="color: #666;">Durasi Pengerjaan</div>
                </div>
                
                <div class="card" style="text-align: center; padding: 1.5rem;">
                    <div style="font-size: 2rem;">
                        <?php echo $attempt['cheating_warnings'] > 0 ? '⚠️' : '✅'; ?>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: bold;">
                        <?php echo $attempt['cheating_warnings']; ?>
                    </div>
                    <div style="color: #666;">Peringatan</div>
                </div>
            </div>
            
            <?php if ($attempt['cheating_warnings'] > 0): ?>
            <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 1rem; margin: 1rem 0;">
                <strong>⚠️ Perhatian:</strong> Terdeteksi <?php echo $attempt['cheating_warnings']; ?> peringatan kecurangan selama ujian.
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 2rem;">
                <a href="<?php echo url('student/exams.php'); ?>" class="btn btn-primary">
                    📚 Kembali ke Daftar TO
                </a>
                <a href="<?php echo url('leaderboards.php'); ?>" class="btn btn-secondary">
                    🏆 Lihat Peringkat
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
