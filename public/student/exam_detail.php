<?php
require_once '../../config/config.php';

requireAuth('student');

$pdo = getDB();
$user = getCurrentUser();
$pageTitle = 'Detail Try Out';

$exam_id = (int)($_GET['id'] ?? 0);

if (!$exam_id) {
    setFlash('ID Try Out tidak valid', 'danger');
    redirect('student/exams.php');
}

$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND is_active = true");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    setFlash('Try Out tidak ditemukan atau sudah tidak aktif', 'danger');
    redirect('student/exams.php');
}

if ($exam['is_premium']) {
    $stmt = $pdo->prepare("SELECT status FROM payments WHERE exam_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$exam_id, $user['id']]);
    $payment = $stmt->fetch();
    
    if (!$payment || $payment['status'] !== 'approved') {
        setFlash('Anda belum membayar untuk Try Out ini', 'warning');
        redirect('student/pay.php?exam_id=' . $exam_id);
    }
}

$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$exam_id, $user['id']]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['is_completed']) {
    $stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, user_id, started_at) VALUES (?, ?, NOW()) RETURNING id");
    $stmt->execute([$exam_id, $user['id']]);
    $attempt = $stmt->fetch();
    $attempt_id = $attempt['id'];
    
    $stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ?");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
}

$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_order ASC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    setFlash('Try Out ini belum memiliki soal', 'warning');
    redirect('student/exams.php');
}

$answers = $attempt['answers'] ? json_decode($attempt['answers'], true) : [];
$essay_answers = $attempt['essay_answers'] ? json_decode($attempt['essay_answers'], true) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action']) && $_POST['action'] === 'submit') {
        $new_answers = [];
        $new_essay_answers = [];
        $total_score = 0;
        
        foreach ($questions as $question) {
            $q_id = $question['id'];
            
            if ($question['question_type'] === 'multiple_choice') {
                $answer = $_POST["answer_$q_id"] ?? '';
                $new_answers[$q_id] = $answer;
                
                if ($answer === $question['correct_answer']) {
                    $total_score += $question['points'];
                }
            } else {
                $essay_answer = $_POST["essay_$q_id"] ?? '';
                $new_essay_answers[$q_id] = $essay_answer;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE exam_attempts SET answers = ?, essay_answers = ?, total_score = ?, finished_at = NOW(), is_completed = true WHERE id = ?");
        $stmt->execute([json_encode($new_answers), json_encode($new_essay_answers), $total_score, $attempt['id']]);
        
        setFlash('Try Out berhasil diselesaikan!', 'success');
        redirect('student/result.php?id=' . $attempt['id']);
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'save_progress') {
        $new_answers = [];
        $new_essay_answers = [];
        
        foreach ($questions as $question) {
            $q_id = $question['id'];
            
            if ($question['question_type'] === 'multiple_choice') {
                $answer = $_POST["answer_$q_id"] ?? '';
                $new_answers[$q_id] = $answer;
            } else {
                $essay_answer = $_POST["essay_$q_id"] ?? '';
                $new_essay_answers[$q_id] = $essay_answer;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE exam_attempts SET answers = ?, essay_answers = ? WHERE id = ?");
        $stmt->execute([json_encode($new_answers), json_encode($new_essay_answers), $attempt['id']]);
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'mark_cheating') {
        $stmt = $pdo->prepare("UPDATE exam_attempts SET cheating_warnings = cheating_warnings + 1 WHERE id = ?");
        $stmt->execute([$attempt['id']]);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

$started_at = new DateTime($attempt['started_at']);
$now = new DateTime();
$elapsed = $now->getTimestamp() - $started_at->getTimestamp();
$remaining = max(0, ($exam['duration'] * 60) - $elapsed);

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<style>
.question-card {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.question-number {
    display: inline-block;
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    font-weight: bold;
}

.option-label {
    display: block;
    padding: 1rem;
    margin: 0.5rem 0;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.option-label:hover {
    background: #f5f5f5;
    border-color: var(--primary-color);
}

.option-label input[type="radio"]:checked + .option-text {
    font-weight: bold;
}

.option-label input[type="radio"]:checked {
    accent-color: var(--primary-color);
}

#timer-box {
    position: sticky;
    top: 80px;
    background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 1.5rem;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.submit-section {
    position: sticky;
    bottom: 20px;
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    margin-top: 2rem;
}
</style>

<div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div style="display: grid; grid-template-columns: 1fr 300px; gap: 2rem;">
        <div>
            <h1><?php echo htmlspecialchars($exam['title']); ?></h1>
            <p><?php echo htmlspecialchars($exam['description']); ?></p>
            
            <form method="POST" id="examForm">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="submit">
                
                <?php foreach ($questions as $index => $question): ?>
                <div class="question-card">
                    <div class="question-number">Soal #<?php echo ($index + 1); ?></div>
                    <?php if ($question['category']): ?>
                        <div style="color: #666; margin-bottom: 0.5rem;">Kategori: <?php echo htmlspecialchars($question['category']); ?></div>
                    <?php endif; ?>
                    <div style="font-size: 1.1rem; margin-bottom: 1rem;">
                        <?php echo nl2br(htmlspecialchars($question['question_text'])); ?>
                    </div>
                    
                    <?php if ($question['question_type'] === 'multiple_choice'): ?>
                        <?php 
                        $options = ['a' => $question['option_a'], 'b' => $question['option_b'], 
                                    'c' => $question['option_c'], 'd' => $question['option_d'], 
                                    'e' => $question['option_e']];
                        ?>
                        <?php foreach ($options as $key => $option): ?>
                            <?php if ($option): ?>
                            <label class="option-label">
                                <input type="radio" name="answer_<?php echo $question['id']; ?>" value="<?php echo $key; ?>" 
                                       <?php echo (isset($answers[$question['id']]) && $answers[$question['id']] === $key) ? 'checked' : ''; ?>>
                                <span class="option-text"><?php echo strtoupper($key); ?>. <?php echo htmlspecialchars($option); ?></span>
                            </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <textarea name="essay_<?php echo $question['id']; ?>" class="form-control" rows="6" placeholder="Tulis jawaban Anda di sini..."><?php echo htmlspecialchars($essay_answers[$question['id']] ?? ''); ?></textarea>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="submit-section">
                    <button type="button" onclick="saveProgress()" class="btn btn-secondary">💾 Simpan Progress</button>
                    <button type="submit" onclick="return confirmSubmit()" class="btn btn-success" style="float: right;">✅ Selesaikan Ujian</button>
                </div>
            </form>
        </div>
        
        <div>
            <div id="timer-box">
                <div id="timer-display" style="font-size: 2rem; font-weight: bold;"></div>
            </div>
            
            <div style="background: white; padding: 1.5rem; border-radius: 8px;">
                <h3>Info Ujian</h3>
                <p><strong>Total Soal:</strong> <?php echo count($questions); ?></p>
                <p><strong>Durasi:</strong> <?php echo $exam['duration']; ?> menit</p>
                <p><strong>Poin Total:</strong> <?php echo array_sum(array_column($questions, 'points')); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
let examTimer;
let antiCheat;

document.addEventListener('DOMContentLoaded', function() {
    showSecurityWarningModal(() => {
        examTimer = new ExamTimer(<?php echo max(1, round($remaining / 60)); ?>, handleTimeUp);
        examTimer.start('timer-box');
        
        antiCheat = new AntiCheat(
            handleCheatingWarning,
            '<?php echo url('logout.php'); ?>',
            null,
            <?php echo $attempt['id']; ?>
        );
        antiCheat.enable();
        
        setInterval(autoSave, 60000);
    });
});

function handleTimeUp() {
    antiCheat.disable();
    document.getElementById('examForm').submit();
}

function handleCheatingWarning(message) {
    alert(message);
}

function confirmSubmit() {
    return confirm('Yakin ingin menyelesaikan ujian? Pastikan semua jawaban sudah benar!');
}

function saveProgress() {
    const formData = new FormData(document.getElementById('examForm'));
    formData.set('action', 'save_progress');
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Progress berhasil disimpan!');
        }
    });
}

function autoSave() {
    const formData = new FormData(document.getElementById('examForm'));
    formData.set('action', 'save_progress');
    
    fetch('', {
        method: 'POST',
        body: formData
    });
}

window.addEventListener('beforeunload', function(e) {
    autoSave();
});
</script>

<?php include '../../app/Views/includes/footer.php'; ?>
