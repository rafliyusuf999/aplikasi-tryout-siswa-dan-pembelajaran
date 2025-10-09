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

.rank-1 { 
    background: #FFD700; 
    color: #1a1a1a; 
    animation: pulseGold 2s infinite;
    border: 3px solid #FFA500;
}
.rank-2 { 
    background: #C0C0C0; 
    color: #1a1a1a;
    animation: pulseSilver 2s infinite;
    border: 3px solid #A8A8A8;
}
.rank-3 { 
    background: #CD7F32; 
    color: white;
    animation: pulseBronze 2s infinite;
    border: 3px solid #B8860B;
}
.rank-other { 
    background: var(--primary-color); 
    color: white; 
    border: 3px solid var(--secondary-color);
}

.celebration-badge {
    position: relative;
    box-shadow: 0 8px 30px rgba(255, 215, 0, 0.5);
}

@keyframes pulseGold {
    0%, 100% { box-shadow: 0 8px 30px rgba(255, 215, 0, 0.5); transform: scale(1); }
    50% { box-shadow: 0 12px 40px rgba(255, 215, 0, 0.8); transform: scale(1.05); }
}

@keyframes pulseSilver {
    0%, 100% { box-shadow: 0 8px 30px rgba(192, 192, 192, 0.5); transform: scale(1); }
    50% { box-shadow: 0 12px 40px rgba(192, 192, 192, 0.8); transform: scale(1.05); }
}

@keyframes pulseBronze {
    0%, 100% { box-shadow: 0 8px 30px rgba(205, 127, 50, 0.5); transform: scale(1); }
    50% { box-shadow: 0 12px 40px rgba(205, 127, 50, 0.8); transform: scale(1.05); }
}
</style>

<div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div class="result-card">
        <h1 style="text-align: center; margin-bottom: 2rem;">🎉 Hasil Try Out</h1>
        
        <div style="text-align: center;">
            <div style="margin-bottom: 1.5rem;">
                <?php if(!empty($user['profile_photo'])): ?>
                <img src="<?php echo url('storage/uploads/profiles/' . $user['profile_photo']); ?>" 
                     alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
                     style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); box-shadow: 0 4px 15px rgba(139, 21, 56, 0.3);">
                <?php else: ?>
                <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--primary-color); color: white; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; font-size: 2.5rem; border: 4px solid var(--primary-color); box-shadow: 0 4px 15px rgba(139, 21, 56, 0.3);">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <?php endif; ?>
                <div style="margin-top: 0.5rem; font-weight: 600; color: var(--text-dark);">
                    <?php echo htmlspecialchars($user['full_name']); ?>
                </div>
            </div>
            
            <h2><?php echo htmlspecialchars($attempt['exam_title']); ?></h2>
            
            <div class="score-display">
                <?php echo number_format($attempt['total_score'], 1); ?>
            </div>
            
            <div style="margin: 2rem 0;">
                <?php if ($rank == 1): ?>
                    <div class="rank-badge rank-1 celebration-badge">🥇 Peringkat 1</div>
                <?php elseif ($rank == 2): ?>
                    <div class="rank-badge rank-2 celebration-badge">🥈 Peringkat 2</div>
                <?php elseif ($rank == 3): ?>
                    <div class="rank-badge rank-3 celebration-badge">🥉 Peringkat 3</div>
                <?php else: ?>
                    <div class="rank-badge rank-other">Peringkat <?php echo $rank; ?></div>
                <?php endif; ?>
                
                <p style="margin-top: 1rem; color: #666;">
                    dari <?php echo $total_participants; ?> peserta
                </p>
            </div>
            
            <?php if ($rank <= 3): ?>
            <audio id="victorySound" autoplay>
                <source src="<?php echo url('static/audio/victory.mp3'); ?>" type="audio/mpeg">
            </audio>
            <?php endif; ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                <div class="card" style="text-align: center; padding: 1.5rem; background: #f8f9fa; border: 2px solid var(--primary-color);">
                    <div style="font-size: 2rem; color: var(--primary-color);">📝</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--text-dark);"><?php echo $attempt['total_questions']; ?></div>
                    <div style="color: #666;">Total Soal</div>
                </div>
                
                <div class="card" style="text-align: center; padding: 1.5rem; background: #f8f9fa; border: 2px solid var(--primary-color);">
                    <div style="font-size: 2rem; color: var(--primary-color);">▶️</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--text-dark);">
                        <?php 
                        $start_dt = new DateTime($attempt['started_at'], new DateTimeZone('Asia/Jakarta'));
                        echo $start_dt->format('H:i');
                        ?>
                    </div>
                    <div style="color: #666; font-size: 0.85rem;">
                        <?php echo $start_dt->format('d/m/Y'); ?>
                    </div>
                    <div style="color: #666; margin-top: 0.3rem;">Waktu Mulai</div>
                </div>
                
                <div class="card" style="text-align: center; padding: 1.5rem; background: #f8f9fa; border: 2px solid var(--primary-color);">
                    <div style="font-size: 2rem; color: var(--primary-color);">⏹️</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--text-dark);">
                        <?php 
                        if ($attempt['finished_at']) {
                            $end_dt = new DateTime($attempt['finished_at'], new DateTimeZone('Asia/Jakarta'));
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
                
                <div class="card" style="text-align: center; padding: 1.5rem; background: #f8f9fa; border: 2px solid var(--primary-color);">
                    <div style="font-size: 2rem; color: var(--primary-color);">⏱️</div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--text-dark);">
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
                
                <div class="card" style="text-align: center; padding: 1.5rem; background: #f8f9fa; border: 2px solid var(--primary-color);">
                    <div style="font-size: 2rem;">
                        <?php echo $attempt['cheating_warnings'] > 0 ? '⚠️' : '✅'; ?>
                    </div>
                    <div style="font-size: 1.5rem; font-weight: bold; color: var(--text-dark);">
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
