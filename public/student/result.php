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

// Cek apakah ini pengerjaan kedua
$stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND user_id = ? AND is_completed = true AND finished_at <= ?");
$stmt->execute([$attempt['exam_id'], $user['id'], $attempt['finished_at']]);
$attempt_number = (int)$stmt->fetchColumn();
$is_second_attempt = ($attempt_number >= 2);

// Jika pengerjaan kedua, ambil soal dan jawaban
$questions_with_answers = [];
if ($is_second_attempt) {
    $stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_order ASC");
    $stmt->execute([$attempt['exam_id']]);
    $questions_with_answers = $stmt->fetchAll();
    
    $student_answers = $attempt['answers'] ? json_decode($attempt['answers'], true) : [];
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
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
}

.profile-section {
    text-align: center;
    margin-bottom: 1.5rem;
}

.profile-photo {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    border: 3px solid var(--primary-color);
    margin-bottom: 0.5rem;
}

.profile-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 2rem;
    border: 3px solid var(--primary-color);
    margin-bottom: 0.5rem;
}

.profile-name {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 1rem;
}

.exam-title {
    text-align: center;
    color: var(--text-dark);
    font-size: 1.5rem;
    margin: 1rem 0;
}

.score-display {
    font-size: 3.5rem;
    font-weight: bold;
    color: var(--primary-color);
    text-align: center;
    margin: 1.5rem 0;
}

.rank-section {
    text-align: center;
    margin: 1.5rem 0;
}

.rank-badge {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-size: 1.2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.rank-1 { 
    background: #FFD700; 
    color: #1a1a1a; 
}
.rank-2 { 
    background: #C0C0C0; 
    color: #1a1a1a;
}
.rank-3 { 
    background: #CD7F32; 
    color: white;
}
.rank-other { 
    background: var(--primary-color); 
    color: white; 
}

.participants-count {
    color: #1a1a1a;
    font-size: 0.9rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.stat-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.stat-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.stat-value {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--text-dark);
    margin: 0.3rem 0;
}

.stat-label {
    color: #1a1a1a;
    font-size: 0.85rem;
}

.stat-date {
    color: #1a1a1a;
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .result-card {
        padding: 1.5rem;
    }
    
    .score-display {
        font-size: 2.5rem;
    }
    
    .exam-title {
        font-size: 1.2rem;
    }
    
    .rank-badge {
        font-size: 1rem;
        padding: 0.6rem 1.2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 0.75rem;
    }
    
    .stat-value {
        font-size: 1.1rem;
    }
}

@media (max-width: 480px) {
    .result-card {
        padding: 1rem;
    }
    
    .profile-photo, .profile-placeholder {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .score-display {
        font-size: 2rem;
    }
}
</style>

<div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div class="result-card">
        <h1 style="text-align: center; margin-bottom: 1.5rem; color: #1a1a1a;">🎉 Hasil Try Out</h1>
        
        <div class="profile-section">
            <?php if(!empty($user['profile_photo'])): ?>
                <img src="<?php echo url('storage/uploads/profiles/' . $user['profile_photo']); ?>" 
                     alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
                     class="profile-photo">
            <?php else: ?>
                <div class="profile-placeholder">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
            <?php endif; ?>
            <div class="profile-name">
                <?php echo htmlspecialchars($user['full_name']); ?>
            </div>
        </div>
        
        <h2 class="exam-title"><?php echo htmlspecialchars($attempt['exam_title']); ?></h2>
        
        <div class="score-display">
            <?php echo number_format($attempt['total_score'], 1); ?>
        </div>
        
        <div class="rank-section">
            <?php if ($rank == 1): ?>
                <div class="rank-badge rank-1">🥇 Peringkat 1</div>
            <?php elseif ($rank == 2): ?>
                <div class="rank-badge rank-2">🥈 Peringkat 2</div>
            <?php elseif ($rank == 3): ?>
                <div class="rank-badge rank-3">🥉 Peringkat 3</div>
            <?php else: ?>
                <div class="rank-badge rank-other">Peringkat <?php echo $rank; ?></div>
            <?php endif; ?>
            
            <div class="participants-count">
                dari <?php echo $total_participants; ?> peserta
            </div>
        </div>
            
        <?php if ($rank <= 3): ?>
        <audio id="victorySound" autoplay>
            <source src="<?php echo url('static/audio/victory.mp3'); ?>" type="audio/mpeg">
        </audio>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📝</div>
                <div class="stat-value"><?php echo $attempt['total_questions']; ?></div>
                <div class="stat-label">Total Soal</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">▶️</div>
                <div class="stat-value">
                    <?php 
                    $start_dt = new DateTime($attempt['started_at'], new DateTimeZone('UTC'));
                    $start_dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
                    echo $start_dt->format('H:i');
                    ?>
                </div>
                <div class="stat-date"><?php echo $start_dt->format('d/m/Y'); ?></div>
                <div class="stat-label">Waktu Mulai</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏹️</div>
                <div class="stat-value">
                    <?php 
                    if ($attempt['finished_at']) {
                        $end_dt = new DateTime($attempt['finished_at'], new DateTimeZone('UTC'));
                        $end_dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
                        echo $end_dt->format('H:i');
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="stat-date"><?php echo $attempt['finished_at'] ? $end_dt->format('d/m/Y') : ''; ?></div>
                <div class="stat-label">Waktu Selesai</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-value">
                    <?php 
                    if ($attempt['finished_at']) {
                        $start = new DateTime($attempt['started_at'], new DateTimeZone('UTC'));
                        $end = new DateTime($attempt['finished_at'], new DateTimeZone('UTC'));
                        $diff = $start->diff($end);
                        echo $diff->h . 'j ' . $diff->i . 'm';
                    } else {
                        echo '-';
                    }
                    ?>
                </div>
                <div class="stat-label">Durasi</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <?php echo $attempt['cheating_warnings'] > 0 ? '⚠️' : '✅'; ?>
                </div>
                <div class="stat-value"><?php echo $attempt['cheating_warnings']; ?></div>
                <div class="stat-label">Peringatan</div>
            </div>
        </div>
            
        <?php if ($attempt['cheating_warnings'] > 0): ?>
        <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 1rem; margin: 1rem 0; text-align: center;">
            <strong>⚠️ Perhatian:</strong> Terdeteksi <?php echo $attempt['cheating_warnings']; ?> peringatan kecurangan selama ujian.
        </div>
        <?php endif; ?>
            
            <?php if ($is_second_attempt && !empty($questions_with_answers)): ?>
            <div style="margin-top: 2rem;">
                <div style="background: #e7f3ff; border: 2px solid #0056b3; border-radius: 8px; padding: 1rem; margin-bottom: 1.5rem;">
                    <strong>ℹ️ Mode Review:</strong> Ini adalah pengerjaan kedua Anda. Berikut adalah pembahasan soal dan jawaban yang benar.
                </div>
                
                <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">📋 Pembahasan Soal</h3>
                
                <?php foreach ($questions_with_answers as $index => $question): 
                    $q_id = $question['id'];
                    $student_answer = $student_answers[$q_id] ?? '';
                    $is_correct = ($student_answer === $question['correct_answer']);
                ?>
                <div class="card" style="margin-bottom: 1.5rem; padding: 1.5rem; <?php echo $is_correct ? 'border-left: 4px solid #28a745;' : 'border-left: 4px solid #dc3545;'; ?>">
                    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                        <span style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: 6px; font-weight: bold;">
                            Soal <?php echo $index + 1; ?>
                        </span>
                        <?php if ($question['question_type'] === 'multiple_choice'): ?>
                            <?php if ($is_correct): ?>
                                <span style="background: #28a745; color: white; padding: 0.5rem 1rem; border-radius: 6px;">✓ Benar</span>
                            <?php else: ?>
                                <span style="background: #dc3545; color: white; padding: 0.5rem 1rem; border-radius: 6px;">✗ Salah</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-bottom: 1rem;">
                        <strong>Pertanyaan:</strong><br>
                        <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                    </div>
                    
                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                        <div style="margin-bottom: 1rem;">
                            <strong>Jawaban Anda:</strong> 
                            <span style="padding: 0.3rem 0.8rem; border-radius: 4px; <?php echo $is_correct ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;'; ?>">
                                <?php echo $student_answer ? htmlspecialchars($student_answer) : 'Tidak dijawab'; ?>
                            </span>
                        </div>
                        
                        <?php if (!$is_correct): ?>
                        <div style="margin-bottom: 1rem;">
                            <strong>Jawaban Benar:</strong> 
                            <span style="padding: 0.3rem 0.8rem; border-radius: 4px; background: #d4edda; color: #155724;">
                                <?php echo htmlspecialchars($question['correct_answer']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                            <strong>Pilihan:</strong><br>
                            <?php if ($question['option_a']): ?>
                                <div style="margin: 0.5rem 0;">A. <?php echo htmlspecialchars($question['option_a']); ?></div>
                            <?php endif; ?>
                            <?php if ($question['option_b']): ?>
                                <div style="margin: 0.5rem 0;">B. <?php echo htmlspecialchars($question['option_b']); ?></div>
                            <?php endif; ?>
                            <?php if ($question['option_c']): ?>
                                <div style="margin: 0.5rem 0;">C. <?php echo htmlspecialchars($question['option_c']); ?></div>
                            <?php endif; ?>
                            <?php if ($question['option_d']): ?>
                                <div style="margin: 0.5rem 0;">D. <?php echo htmlspecialchars($question['option_d']); ?></div>
                            <?php endif; ?>
                            <?php if ($question['option_e']): ?>
                                <div style="margin: 0.5rem 0;">E. <?php echo htmlspecialchars($question['option_e']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 6px;">
                            <strong>Jawaban Essay:</strong><br>
                            <?php echo $student_answer ? nl2br(htmlspecialchars($student_answer)) : '<em>Tidak dijawab</em>'; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 2rem; text-align: center; display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
            <a href="<?php echo url('student/exams.php'); ?>" class="btn btn-primary">
                📚 Kembali ke Daftar TO
            </a>
            <a href="<?php echo url('leaderboards.php'); ?>" class="btn btn-secondary">
                🏆 Lihat Peringkat
            </a>
        </div>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
