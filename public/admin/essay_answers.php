<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Jawaban Essay Siswa';

$exam_id = (int)($_GET['exam_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    setFlash('Try Out tidak ditemukan', 'danger');
    redirect('admin/exams.php');
}

$stmt = $pdo->prepare("
    SELECT ea.*, u.full_name, u.email, u.inspira_branch
    FROM exam_attempts ea
    JOIN users u ON ea.user_id = u.id
    WHERE ea.exam_id = ? AND ea.is_completed = 1 AND ea.essay_answers IS NOT NULL AND ea.essay_answers != '{}'
    ORDER BY u.full_name ASC
");
$stmt->execute([$exam_id]);
$attempts = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? AND question_type = 'essay' ORDER BY question_order ASC");
$stmt->execute([$exam_id]);
$essay_questions = $stmt->fetchAll();

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>Jawaban Essay Siswa</h1>
            <p class="text-muted"><?php echo htmlspecialchars($exam['title']); ?></p>
        </div>
        <a href="<?php echo url('admin/exams.php'); ?>" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <?php if (empty($essay_questions)): ?>
        <div class="card">
            <p style="text-align: center; color: #666;">ℹ️ Try Out ini tidak memiliki soal essay</p>
        </div>
    <?php elseif (empty($attempts)): ?>
        <div class="card">
            <p style="text-align: center; color: #666;">ℹ️ Belum ada siswa yang mengerjakan soal essay di Try Out ini</p>
        </div>
    <?php else: ?>
        <?php foreach ($attempts as $attempt): ?>
            <?php
            $essay_answers = $attempt['essay_answers'] ? json_decode($attempt['essay_answers'], true) : [];
            if (empty($essay_answers)) continue;
            ?>
            <div class="card" style="margin-bottom: 2rem;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px 8px 0 0; margin: -1.5rem -1.5rem 1.5rem -1.5rem;">
                    <h3 style="margin: 0; color: white;"><?php echo htmlspecialchars($attempt['full_name']); ?></h3>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">
                        <?php echo htmlspecialchars($attempt['email']); ?> 
                        <?php if ($attempt['inspira_branch']): ?>
                            | <?php echo htmlspecialchars($attempt['inspira_branch']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php foreach ($essay_questions as $index => $question): ?>
                    <?php 
                    $q_id = $question['id'];
                    $answer = $essay_answers[$q_id] ?? '';
                    
                    preg_match_all('/\[File: ([^\]]+)\]/', $answer, $matches);
                    $files = $matches[1] ?? [];
                    $answer_text = preg_replace('/\[File: [^\]]+\]\n?/', '', $answer);
                    ?>
                    
                    <div style="margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px solid #e0e0e0;">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                            <span style="background: var(--primary-color); color: white; padding: 0.5rem 1rem; border-radius: 6px; font-weight: bold;">
                                Soal Essay <?php echo $index + 1; ?>
                            </span>
                            <?php if ($question['category']): ?>
                                <span style="background: #e7f3ff; color: #0056b3; padding: 0.5rem 1rem; border-radius: 6px;">
                                    <?php echo htmlspecialchars($question['category']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="question-text" style="background: #f8f9fa; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                            <strong>Pertanyaan:</strong><br>
                            <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                        </div>
                        
                        <?php if ($question['question_image']): ?>
                            <div style="margin-bottom: 1rem;">
                                <img src="<?php echo url('storage/uploads/questions/' . $question['question_image']); ?>" 
                                     alt="Gambar Soal" 
                                     style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            </div>
                        <?php endif; ?>
                        
                        <div style="background: #ffffff; border: 2px solid #28a745; border-radius: 6px; padding: 1rem;">
                            <strong style="color: #28a745;">📝 Jawaban Siswa:</strong><br>
                            <?php if ($answer_text): ?>
                                <div style="margin-top: 0.5rem; white-space: pre-wrap; line-height: 1.6;">
                                    <?php echo htmlspecialchars($answer_text); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($files)): ?>
                                <div style="margin-top: 1rem;">
                                    <strong>📎 File Lampiran:</strong><br>
                                    <?php foreach ($files as $file): ?>
                                        <div style="margin-top: 0.5rem;">
                                            <a href="<?php echo url('storage/uploads/answers/' . $file); ?>" 
                                               target="_blank" 
                                               class="btn btn-sm btn-info">
                                                📥 Lihat File: <?php echo htmlspecialchars($file); ?>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$answer_text && empty($files)): ?>
                                <div style="margin-top: 0.5rem; color: #999; font-style: italic;">
                                    (Tidak dijawab)
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
window.addEventListener('load', function() {
    if (window.MathJax) {
        MathJax.typesetPromise().catch(err => console.log('MathJax error:', err));
    }
});
</script>

<?php include '../../app/Views/includes/footer.php'; ?>
