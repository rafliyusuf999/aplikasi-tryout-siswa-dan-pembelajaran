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

// Cek apakah ada completed attempt sebelumnya (first attempt)
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND user_id = ? AND is_completed = true ORDER BY finished_at ASC LIMIT 1");
$stmt->execute([$exam_id, $user['id']]);
$first_attempt = $stmt->fetch();

// Hitung jumlah attempt yang sudah completed
$stmt = $pdo->prepare("SELECT COUNT(*) FROM exam_attempts WHERE exam_id = ? AND user_id = ? AND is_completed = true");
$stmt->execute([$exam_id, $user['id']]);
$completed_attempts_count = (int)$stmt->fetchColumn();

// Jika sudah ada 2 attempt completed, redirect ke result page first attempt
if ($completed_attempts_count >= 2) {
    setFlash('Anda sudah menyelesaikan ujian ini 2 kali. Berikut adalah hasil pengerjaan pertama Anda.', 'info');
    redirect('student/result.php?id=' . $first_attempt['id']);
}

// Get current active attempt or create new one
$stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND user_id = ? ORDER BY started_at DESC LIMIT 1");
$stmt->execute([$exam_id, $user['id']]);
$attempt = $stmt->fetch();

if (!$attempt || $attempt['is_completed']) {
    $stmt = $pdo->prepare("INSERT INTO exam_attempts (exam_id, user_id, started_at) VALUES (?, ?, datetime('now'))");
    $stmt->execute([$exam_id, $user['id']]);
    $attempt_id = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT * FROM exam_attempts WHERE id = ?");
    $stmt->execute([$attempt_id]);
    $attempt = $stmt->fetch();
}

// Flag untuk pengerjaan kedua (review mode)
$is_second_attempt = ($completed_attempts_count === 1);

$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_order ASC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    setFlash('Try Out ini belum memiliki soal', 'warning');
    redirect('student/exams.php');
}

$answers = $attempt['answers'] ? json_decode($attempt['answers'], true) : [];
$essay_answers_raw = $attempt['essay_answers'] ? json_decode($attempt['essay_answers'], true) : [];

$essay_answers = [];
foreach ($essay_answers_raw as $q_id => $essay_text) {
    $essay_answers[$q_id] = preg_replace('/\[File: [^\]]+\]\n?/', '', $essay_text);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action']) && $_POST['action'] === 'submit') {
        $new_answers = [];
        $new_essay_answers = [];
        $total_score = 0;
        
        $stmt = $pdo->prepare("SELECT essay_answers FROM exam_attempts WHERE id = ?");
        $stmt->execute([$attempt['id']]);
        $existing_essay = $stmt->fetchColumn();
        $existing_essay_data = $existing_essay ? json_decode($existing_essay, true) : [];
        
        foreach ($questions as $question) {
            $q_id = $question['id'];
            
            if ($question['question_type'] === 'multiple_choice') {
                $answer = $_POST["answer_$q_id"] ?? '';
                $new_answers[$q_id] = $answer;
                
                // Hitung skor HANYA pada percobaan pertama
                if ($completed_attempts_count === 0 && $answer === $question['correct_answer']) {
                    $total_score += $question['points'];
                }
            } else {
                $essay_answer = $_POST["essay_$q_id"] ?? '';
                
                if (isset($existing_essay_data[$q_id])) {
                    preg_match_all('/\[File: ([^\]]+)\]/', $existing_essay_data[$q_id], $matches);
                    if (!empty($matches[0])) {
                        $file_markers = implode("\n", $matches[0]);
                        $essay_answer = trim($essay_answer) . "\n" . $file_markers;
                    }
                }
                
                $new_essay_answers[$q_id] = $essay_answer;
            }
        }
        
        // Pada percobaan kedua, ambil skor dari percobaan pertama
        if ($completed_attempts_count === 1 && $first_attempt) {
            $total_score = $first_attempt['total_score'];
        }
        
        $stmt = $pdo->prepare("UPDATE exam_attempts SET answers = ?, essay_answers = ?, total_score = ?, finished_at = datetime('now'), is_completed = 1 WHERE id = ?");
        $stmt->execute([json_encode($new_answers), json_encode($new_essay_answers), $total_score, $attempt['id']]);
        
        // Update leaderboard dan nilai HANYA jika ini adalah first completed attempt
        if ($completed_attempts_count === 0) {
            // Hitung total poin dari semua exam
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(total_score), 0) as combined_score
                FROM exam_attempts
                WHERE user_id = ? AND is_completed = true
            ");
            $stmt->execute([$user['id']]);
            $combined = $stmt->fetch();
            $combined_score = (int)$combined['combined_score'];
            
            // Delete old leaderboard entry untuk exam ini (jika ada)
            $stmt = $pdo->prepare("DELETE FROM leaderboards WHERE exam_id = ? AND user_id = ?");
            $stmt->execute([$exam_id, $user['id']]);
            
            // Hitung ranking berdasarkan branch
            $stmt = $pdo->prepare("
                SELECT COUNT(*) + 1 as rank
                FROM (
                    SELECT DISTINCT user_id, MAX(total_score) as max_score
                    FROM exam_attempts
                    WHERE exam_id = ? AND is_completed = true
                    GROUP BY user_id
                ) as scores
                JOIN users ON scores.user_id = users.id
                WHERE users.inspira_branch = ? AND scores.max_score > ?
            ");
            $stmt->execute([$exam_id, $user['inspira_branch'], $total_score]);
            $rank_in_branch = (int)$stmt->fetchColumn();
            
            // Hitung ranking global
            $stmt = $pdo->prepare("
                SELECT COUNT(*) + 1 as rank
                FROM (
                    SELECT DISTINCT user_id, MAX(total_score) as max_score
                    FROM exam_attempts
                    WHERE exam_id = ? AND is_completed = true
                    GROUP BY user_id
                ) as scores
                WHERE scores.max_score > ?
            ");
            $stmt->execute([$exam_id, $total_score]);
            $rank_global = (int)$stmt->fetchColumn();
            
            // Insert leaderboard entry
            $stmt = $pdo->prepare("
                INSERT INTO leaderboards (exam_id, user_id, total_score, rank_in_branch, rank_global, achieved_at)
                VALUES (?, ?, ?, ?, ?, datetime('now'))
            ");
            $stmt->execute([$exam_id, $user['id'], $total_score, $rank_in_branch, $rank_global]);
        }
        
        setFlash('Try Out berhasil diselesaikan!', 'success');
        redirect('student/result.php?id=' . $attempt['id']);
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'save_progress') {
        $new_answers = [];
        $new_essay_answers = [];
        
        $stmt = $pdo->prepare("SELECT essay_answers FROM exam_attempts WHERE id = ?");
        $stmt->execute([$attempt['id']]);
        $existing_essay = $stmt->fetchColumn();
        $existing_essay_data = $existing_essay ? json_decode($existing_essay, true) : [];
        
        foreach ($questions as $question) {
            $q_id = $question['id'];
            
            if ($question['question_type'] === 'multiple_choice') {
                $answer = $_POST["answer_$q_id"] ?? '';
                $new_answers[$q_id] = $answer;
            } else {
                $essay_answer = $_POST["essay_$q_id"] ?? '';
                
                if (isset($existing_essay_data[$q_id])) {
                    preg_match_all('/\[File: ([^\]]+)\]/', $existing_essay_data[$q_id], $matches);
                    if (!empty($matches[0])) {
                        $file_markers = implode("\n", $matches[0]);
                        $essay_answer = trim($essay_answer) . "\n" . $file_markers;
                    }
                }
                
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
    
    if (isset($_POST['action']) && $_POST['action'] === 'upload_essay_file') {
        header('Content-Type: application/json');
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== 0) {
            echo json_encode(['success' => false, 'error' => 'File tidak ditemukan atau error upload']);
            exit;
        }
        
        $file = $_FILES['file'];
        $question_id = (int)$_POST['question_id'];
        $attempt_id = (int)$_POST['attempt_id'];
        
        if ($attempt_id !== $attempt['id']) {
            echo json_encode(['success' => false, 'error' => 'Invalid attempt ID']);
            exit;
        }
        
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'Hanya file gambar (JPG, PNG, GIF) yang diperbolehkan']);
            exit;
        }
        
        $max_size = 5 * 1024 * 1024;
        if ($file['size'] > $max_size) {
            echo json_encode(['success' => false, 'error' => 'File terlalu besar. Maksimal 5MB']);
            exit;
        }
        
        $upload_dir = '../../storage/uploads/answers/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_filename = 'essay_' . $attempt_id . '_' . $question_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $stmt = $pdo->prepare("SELECT essay_answers FROM exam_attempts WHERE id = ?");
            $stmt->execute([$attempt_id]);
            $current_essay = $stmt->fetchColumn();
            $essay_data = $current_essay ? json_decode($current_essay, true) : [];
            
            $essay_data[$question_id] = ($essay_data[$question_id] ?? '') . "\n[File: $new_filename]";
            
            $stmt = $pdo->prepare("UPDATE exam_attempts SET essay_answers = ? WHERE id = ?");
            $stmt->execute([json_encode($essay_data), $attempt_id]);
            
            echo json_encode(['success' => true, 'filename' => $new_filename]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Gagal menyimpan file']);
        }
        exit;
    }
}

$started_at = new DateTime($attempt['started_at']);
$now = new DateTime();
$elapsed = $now->getTimestamp() - $started_at->getTimestamp();
$remaining = max(0, ($exam['duration_minutes'] * 60) - $elapsed);

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<style>
body {
    overflow-x: hidden;
}

.exam-container {
    display: flex;
    gap: 1.5rem;
    margin-top: 2rem;
    margin-bottom: 2rem;
}

.questions-section {
    flex: 1;
    min-width: 0;
}

.sidebar {
    width: 280px;
    position: sticky;
    top: 80px;
    align-self: flex-start;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
}

.question-card {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    scroll-margin-top: 100px;
}

.question-card.active {
    border: 3px solid var(--primary-color);
}

.question-number {
    display: inline-block;
    background: var(--primary-color);
    color: white;
    padding: 0.5rem 1.2rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    font-weight: bold;
    font-size: 1.1rem;
}

.option-label {
    display: block;
    padding: 1rem;
    margin: 0.5rem 0;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    background: white;
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
    background: linear-gradient(135deg, #FF6B6B 0%, #EE5A6F 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 10px rgba(255, 107, 107, 0.3);
}

#timer-display {
    font-size: 2.5rem;
    font-weight: bold;
    font-family: 'Courier New', monospace;
}

.timer-label {
    font-size: 0.9rem;
    margin-top: 0.5rem;
    opacity: 0.9;
}

.question-nav {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.question-nav h3 {
    margin-bottom: 1rem;
    font-size: 1.1rem;
    color: #333;
}

.nav-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 0.5rem;
}

.nav-item {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #ddd;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    background: white;
    color: #666;
}

.nav-item:hover {
    border-color: var(--primary-color);
    background: #f5f5f5;
}

.nav-item.answered {
    background: #4CAF50;
    color: white;
    border-color: #4CAF50;
}

.nav-item.active {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
}

.submit-section {
    position: sticky;
    bottom: 20px;
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    margin-top: 2rem;
    display: flex;
    justify-content: space-between;
    gap: 1rem;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

@media (max-width: 768px) {
    .exam-container {
        flex-direction: column;
    }
    .sidebar {
        position: relative;
        width: 100%;
        max-height: none;
        order: -1;
    }
}
</style>

<div class="container">
    <div class="exam-container">
        <div class="questions-section">
            <h1><?php echo htmlspecialchars($exam['title']); ?></h1>
            <p style="color: rgba(255,255,255,0.9); margin-bottom: 1.5rem;"><?php echo htmlspecialchars($exam['description']); ?></p>
            
            <form method="POST" id="examForm">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="submit">
                
                <?php foreach ($questions as $index => $question): ?>
                <div class="question-card" id="question-<?php echo ($index + 1); ?>" data-question="<?php echo ($index + 1); ?>">
                    <div class="question-number">Soal #<?php echo ($index + 1); ?></div>
                    <?php if ($question['category']): ?>
                        <div style="color: #666; margin-bottom: 0.5rem;"><em>Kategori: <?php echo htmlspecialchars($question['category']); ?></em></div>
                    <?php endif; ?>
                    <div style="font-size: 1.1rem; margin-bottom: 1.5rem; line-height: 1.6;">
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
                                       data-question-num="<?php echo ($index + 1); ?>"
                                       <?php echo (isset($answers[$question['id']]) && $answers[$question['id']] === $key) ? 'checked' : ''; ?>
                                       onchange="updateNavigation()">
                                <span class="option-text"><?php echo strtoupper($key); ?>. <?php echo htmlspecialchars($option); ?></span>
                            </label>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <textarea name="essay_<?php echo $question['id']; ?>" class="form-control allow-file-upload" rows="6" placeholder="Tulis jawaban Anda di sini..." data-question-num="<?php echo ($index + 1); ?>" oninput="updateNavigation()"><?php echo htmlspecialchars($essay_answers[$question['id']] ?? ''); ?></textarea>
                        <div style="margin-top: 0.5rem; color: #666; font-size: 0.9rem;">💡 Atau upload file gambar jawaban Anda di bawah</div>
                        <div style="margin-top: 0.5rem;">
                            <input type="file" 
                                   id="file_<?php echo $question['id']; ?>" 
                                   class="form-control" 
                                   accept="image/jpeg,image/jpg,image/png,image/gif" 
                                   onchange="uploadEssayFile(<?php echo $question['id']; ?>, <?php echo $attempt['id']; ?>)"
                                   style="padding: 0.5rem;">
                            <div id="upload_status_<?php echo $question['id']; ?>" style="margin-top: 0.3rem; font-size: 0.85rem;">
                                <?php 
                                if (isset($essay_answers_raw[$question['id']])) {
                                    preg_match_all('/\[File: ([^\]]+)\]/', $essay_answers_raw[$question['id']], $matches);
                                    if (!empty($matches[1])) {
                                        echo '<div style="color: #28a745; margin-top: 0.3rem;">';
                                        echo '📎 File terupload: ';
                                        foreach ($matches[1] as $idx => $filename) {
                                            if ($idx > 0) echo ', ';
                                            echo '<a href="' . url('storage/uploads/answers/' . htmlspecialchars($filename)) . '" target="_blank" style="color: #007bff;">' . htmlspecialchars($filename) . '</a>';
                                        }
                                        echo '</div>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                
                <div class="submit-section">
                    <button type="button" onclick="saveProgress()" class="btn btn-secondary">💾 Simpan Progress</button>
                    <button type="submit" onclick="return confirmSubmit()" class="btn btn-success">✅ Selesaikan Ujian</button>
                </div>
            </form>
        </div>
        
        <div class="sidebar">
            <div id="timer-box">
                <div id="timer-display"></div>
                <div class="timer-label">Waktu Tersisa</div>
            </div>
            
            <?php if ($first_attempt && $completed_attempts_count > 0): 
                $first_answers = $first_attempt['answers'] ? json_decode($first_attempt['answers'], true) : [];
            ?>
            <div class="previous-attempt-box" style="
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 1.5rem;
                border-radius: 15px;
                margin-top: 1rem;
                box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
            ">
                <h3 style="margin: 0 0 1rem 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <span>📊</span> Attempt Pertama
                </h3>
                <div style="background: rgba(255,255,255,0.1); padding: 1rem; border-radius: 10px;">
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">Nilai yang Dinilai:</div>
                    <div style="font-size: 2rem; font-weight: bold;">
                        <?php echo number_format($first_attempt['total_score'], 0); ?>
                    </div>
                    <div style="font-size: 0.85rem; opacity: 0.8; margin-top: 0.5rem;">
                        Tanggal: <?php echo date('d/m/Y H:i', strtotime($first_attempt['finished_at'])); ?>
                    </div>
                </div>
                <div style="margin-top: 1rem; font-size: 0.85rem; opacity: 0.9; line-height: 1.5;">
                    ℹ️ Ini adalah attempt ke-<?php echo $completed_attempts_count + 1; ?>. 
                    Nilai dari attempt pertama yang akan masuk ke leaderboard.
                </div>
            </div>
            <?php endif; ?>
            
            <div class="question-nav">
                <h3>📋 Navigasi Soal</h3>
                <div class="nav-grid">
                    <?php foreach ($questions as $index => $question): ?>
                    <div class="nav-item" data-question="<?php echo ($index + 1); ?>" onclick="scrollToQuestion(<?php echo ($index + 1); ?>)">
                        <?php echo ($index + 1); ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 1rem; font-size: 0.85rem; color: #666;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.3rem;">
                        <div style="width: 20px; height: 20px; background: #4CAF50; border-radius: 3px;"></div>
                        <span>Terjawab</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <div style="width: 20px; height: 20px; border: 2px solid #ddd; border-radius: 3px;"></div>
                        <span>Belum Dijawab</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let examTimer;
let antiCheat;
let isUploadingFile = false;

document.addEventListener('DOMContentLoaded', function() {
    updateNavigation();
    setupScrollTracking();
    
    showSecurityWarningModal(() => {
        examTimer = new ExamTimer(<?php echo max(1, round($remaining / 60)); ?>, handleTimeUp);
        examTimer.start('timer-display');
        
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

function scrollToQuestion(questionNum) {
    const element = document.getElementById('question-' + questionNum);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        element.classList.add('active');
        setTimeout(() => element.classList.remove('active'), 2000);
    }
}

function updateNavigation() {
    document.querySelectorAll('.nav-item').forEach(item => {
        const questionNum = item.getAttribute('data-question');
        const questionCard = document.getElementById('question-' + questionNum);
        
        if (questionCard) {
            const radio = questionCard.querySelector('input[type="radio"]:checked');
            const textarea = questionCard.querySelector('textarea');
            
            if (radio || (textarea && textarea.value.trim().length > 0)) {
                item.classList.add('answered');
            } else {
                item.classList.remove('answered');
            }
        }
    });
}

function setupScrollTracking() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const questionNum = entry.target.getAttribute('data-question');
                document.querySelectorAll('.nav-item').forEach(nav => {
                    nav.classList.remove('active');
                });
                const activeNav = document.querySelector('.nav-item[data-question="' + questionNum + '"]');
                if (activeNav) {
                    activeNav.classList.add('active');
                }
            }
        });
    }, { threshold: 0.5 });
    
    document.querySelectorAll('.question-card').forEach(card => {
        observer.observe(card);
    });
}

function handleTimeUp() {
    if (antiCheat) antiCheat.disable();
    alert('⏰ Waktu habis! Ujian akan diselesaikan secara otomatis.');
    document.getElementById('examForm').submit();
}

function handleCheatingWarning(message) {
    alert(message);
}

function confirmSubmit() {
    if (antiCheat) antiCheat.disable();
    return confirm('Yakin ingin menyelesaikan ujian?\n\nPastikan semua jawaban sudah benar!\nAnda tidak dapat mengubah jawaban setelah submit.');
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
    })
    .catch(() => {
        alert('⚠️ Gagal menyimpan progress. Coba lagi.');
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
    if (!document.getElementById('examForm').submitted) {
        autoSave();
        e.preventDefault();
        e.returnValue = '';
    }
});

document.getElementById('examForm').addEventListener('submit', function() {
    this.submitted = true;
});

async function uploadEssayFile(questionId, attemptId) {
    const fileInput = document.getElementById('file_' + questionId);
    const statusDiv = document.getElementById('upload_status_' + questionId);
    const file = fileInput.files[0];
    
    if (!file) {
        return;
    }
    
    const maxSize = 5 * 1024 * 1024;
    if (file.size > maxSize) {
        statusDiv.innerHTML = '<span style="color: #dc3545;">❌ File terlalu besar! Maksimal 5MB</span>';
        fileInput.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('question_id', questionId);
    formData.append('attempt_id', attemptId);
    formData.append('action', 'upload_essay_file');
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    statusDiv.innerHTML = '<span style="color: #007bff;">📤 Uploading...</span>';
    
    if (antiCheat) {
        antiCheat.setUploadingState(true);
    }
    isUploadingFile = true;
    
    try {
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            const fileUrl = '/storage/uploads/answers/' + data.filename;
            statusDiv.innerHTML = '<div style="color: #28a745; margin-top: 0.3rem;">📎 File terupload: <a href="' + fileUrl + '" target="_blank" style="color: #007bff;">' + data.filename + '</a></div>';
            fileInput.value = '';
            updateNavigation();
        } else {
            statusDiv.innerHTML = '<span style="color: #dc3545;">❌ ' + (data.error || 'Upload gagal') + '</span>';
            fileInput.value = '';
        }
    } catch (error) {
        statusDiv.innerHTML = '<span style="color: #dc3545;">❌ Terjadi kesalahan saat upload</span>';
        fileInput.value = '';
    } finally {
        if (antiCheat) {
            antiCheat.setUploadingState(false);
        }
        isUploadingFile = false;
    }
}
</script>

<?php include '../../app/Views/includes/footer.php'; ?>
