<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Kelola Soal';

$exam_id = (int)($_GET['exam_id'] ?? 0);

if (!$exam_id) {
    setFlash('ID Try Out tidak valid', 'danger');
    redirect('admin/exams.php');
}

$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    setFlash('Try Out tidak ditemukan', 'danger');
    redirect('admin/exams.php');
}

$stmt = $pdo->prepare("SELECT * FROM questions WHERE exam_id = ? ORDER BY question_order ASC");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $question_type = sanitize($_POST['question_type']);
            $category = sanitize($_POST['category'] ?? '');
            $question_text = sanitize($_POST['question_text']);
            $option_a = sanitize($_POST['option_a'] ?? '');
            $option_b = sanitize($_POST['option_b'] ?? '');
            $option_c = sanitize($_POST['option_c'] ?? '');
            $option_d = sanitize($_POST['option_d'] ?? '');
            $option_e = sanitize($_POST['option_e'] ?? '');
            $correct_answer = sanitize($_POST['correct_answer'] ?? '');
            $points = (int)($_POST['points'] ?? 1);
            
            $question_image = null;
            if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === 0) {
                $upload_dir = '../../storage/uploads/questions/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
                $question_image = 'question_' . time() . '_' . uniqid() . '.' . $file_extension;
                move_uploaded_file($_FILES['question_image']['tmp_name'], $upload_dir . $question_image);
            }
            
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM questions WHERE exam_id = ?");
            $stmt->execute([$exam_id]);
            $question_order = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("INSERT INTO questions (exam_id, question_type, category, question_text, question_image, option_a, option_b, option_c, option_d, option_e, correct_answer, question_order, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$exam_id, $question_type, $category, $question_text, $question_image, $option_a, $option_b, $option_c, $option_d, $option_e, $correct_answer, $question_order, $points]);
            
            $stmt = $pdo->prepare("UPDATE exams SET total_questions = (SELECT COUNT(*) FROM questions WHERE exam_id = ?) WHERE id = ?");
            $stmt->execute([$exam_id, $exam_id]);
            
            setFlash('Soal berhasil ditambahkan', 'success');
            redirect('admin/questions.php?exam_id=' . $exam_id);
        }
        
        if ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $question_type = sanitize($_POST['question_type']);
            $category = sanitize($_POST['category'] ?? '');
            $question_text = sanitize($_POST['question_text']);
            $option_a = sanitize($_POST['option_a'] ?? '');
            $option_b = sanitize($_POST['option_b'] ?? '');
            $option_c = sanitize($_POST['option_c'] ?? '');
            $option_d = sanitize($_POST['option_d'] ?? '');
            $option_e = sanitize($_POST['option_e'] ?? '');
            $correct_answer = sanitize($_POST['correct_answer'] ?? '');
            $points = (int)($_POST['points'] ?? 1);
            
            $question_image = $_POST['existing_image'] ?? null;
            if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === 0) {
                $upload_dir = '../../storage/uploads/questions/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if ($question_image && file_exists($upload_dir . $question_image)) {
                    unlink($upload_dir . $question_image);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['question_image']['name'], PATHINFO_EXTENSION));
                $question_image = 'question_' . time() . '_' . uniqid() . '.' . $file_extension;
                move_uploaded_file($_FILES['question_image']['tmp_name'], $upload_dir . $question_image);
            }
            
            $stmt = $pdo->prepare("UPDATE questions SET question_type = ?, category = ?, question_text = ?, question_image = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, option_e = ?, correct_answer = ?, points = ? WHERE id = ?");
            $stmt->execute([$question_type, $category, $question_text, $question_image, $option_a, $option_b, $option_c, $option_d, $option_e, $correct_answer, $points, $id]);
            
            setFlash('Soal berhasil diupdate', 'success');
            redirect('admin/questions.php?exam_id=' . $exam_id);
        }
        
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
            $stmt->execute([$id]);
            
            $stmt = $pdo->prepare("UPDATE exams SET total_questions = (SELECT COUNT(*) FROM questions WHERE exam_id = ?) WHERE id = ?");
            $stmt->execute([$exam_id, $exam_id]);
            
            setFlash('Soal berhasil dihapus', 'success');
            redirect('admin/questions.php?exam_id=' . $exam_id);
        }
    }
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1>Kelola Soal</h1>
            <p class="text-muted"><?php echo htmlspecialchars($exam['title']); ?> (<?php echo count($questions); ?> soal)</p>
        </div>
        <a href="<?php echo url('admin/exams.php'); ?>" class="btn btn-secondary">← Kembali</a>
    </div>
    
    <div class="card">
        <button onclick="showAddModal()" class="btn btn-primary" style="margin-bottom: 1.5rem;">+ Tambah Soal</button>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tipe</th>
                        <th>Kategori</th>
                        <th>Soal</th>
                        <th>Jawaban</th>
                        <th>Poin</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $index => $question): ?>
                    <tr>
                        <td><?php echo $index + 1; ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($question['question_type']); ?></span></td>
                        <td><?php echo htmlspecialchars($question['category'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars(substr($question['question_text'], 0, 100)) . (strlen($question['question_text']) > 100 ? '...' : ''); ?></td>
                        <td><?php echo htmlspecialchars($question['correct_answer'] ?? '-'); ?></td>
                        <td><?php echo $question['points']; ?></td>
                        <td>
                            <button onclick='editQuestion(<?php echo json_encode($question); ?>)' class="btn btn-sm btn-primary">Edit</button>
                            <form method="POST" style="display: inline;">
                                <?php echo csrf(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $question['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus soal ini?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="addModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2>Tambah Soal</h2>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Tipe Soal</label>
                <select name="question_type" id="add_question_type" required class="form-control" onchange="toggleOptions('add')">
                    <option value="multiple_choice">Pilihan Ganda</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <input type="text" name="category" class="form-control" placeholder="Contoh: Matematika, Bahasa Indonesia">
            </div>
            <div class="form-group">
                <label>Pertanyaan</label>
                <div style="background: #e7f3ff; padding: 0.75rem; border-radius: 4px; margin-bottom: 0.5rem; font-size: 0.9rem;">
                    <strong>💡 Tips LaTeX:</strong> Gunakan $ untuk inline: <code>$x^2 + y^2$</code> atau $$ untuk display: <code>$$\sqrt{(x_2 - x_1)^2 + (y_2 - y_1)^2}$$</code>
                </div>
                <textarea name="question_text" required class="form-control" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>Gambar Soal (opsional)</label>
                <input type="file" name="question_image" accept="image/*" class="form-control">
            </div>
            <div id="add_options_group">
                <div class="form-group">
                    <label>Opsi A</label>
                    <input type="text" name="option_a" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi B</label>
                    <input type="text" name="option_b" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi C</label>
                    <input type="text" name="option_c" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi D</label>
                    <input type="text" name="option_d" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi E</label>
                    <input type="text" name="option_e" class="form-control">
                </div>
                <div class="form-group">
                    <label>Jawaban Benar</label>
                    <select name="correct_answer" class="form-control">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Poin</label>
                <input type="number" name="points" value="1" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Soal</h2>
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <input type="hidden" name="existing_image" id="edit_existing_image">
            <div class="form-group">
                <label>Tipe Soal</label>
                <select name="question_type" id="edit_question_type" required class="form-control" onchange="toggleOptions('edit')">
                    <option value="multiple_choice">Pilihan Ganda</option>
                    <option value="essay">Essay</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kategori</label>
                <input type="text" name="category" id="edit_category" class="form-control">
            </div>
            <div class="form-group">
                <label>Pertanyaan</label>
                <div style="background: #e7f3ff; padding: 0.75rem; border-radius: 4px; margin-bottom: 0.5rem; font-size: 0.9rem;">
                    <strong>💡 Tips LaTeX:</strong> Gunakan $ untuk inline: <code>$x^2 + y^2$</code> atau $$ untuk display: <code>$$\sqrt{(x_2 - x_1)^2 + (y_2 - y_1)^2}$$</code>
                </div>
                <textarea name="question_text" id="edit_question_text" required class="form-control" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label>Gambar Soal (opsional)</label>
                <div id="edit_current_image" style="margin-bottom: 0.5rem;"></div>
                <input type="file" name="question_image" accept="image/*" class="form-control">
            </div>
            <div id="edit_options_group">
                <div class="form-group">
                    <label>Opsi A</label>
                    <input type="text" name="option_a" id="edit_option_a" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi B</label>
                    <input type="text" name="option_b" id="edit_option_b" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi C</label>
                    <input type="text" name="option_c" id="edit_option_c" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi D</label>
                    <input type="text" name="option_d" id="edit_option_d" class="form-control">
                </div>
                <div class="form-group">
                    <label>Opsi E</label>
                    <input type="text" name="option_e" id="edit_option_e" class="form-control">
                </div>
                <div class="form-group">
                    <label>Jawaban Benar</label>
                    <select name="correct_answer" id="edit_correct_answer" class="form-control">
                        <option value="A">A</option>
                        <option value="B">B</option>
                        <option value="C">C</option>
                        <option value="D">D</option>
                        <option value="E">E</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Poin</label>
                <input type="number" name="points" id="edit_points" value="1" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function editQuestion(question) {
    document.getElementById('edit_id').value = question.id;
    document.getElementById('edit_question_type').value = question.question_type;
    document.getElementById('edit_category').value = question.category || '';
    document.getElementById('edit_question_text').value = question.question_text;
    document.getElementById('edit_option_a').value = question.option_a || '';
    document.getElementById('edit_option_b').value = question.option_b || '';
    document.getElementById('edit_option_c').value = question.option_c || '';
    document.getElementById('edit_option_d').value = question.option_d || '';
    document.getElementById('edit_option_e').value = question.option_e || '';
    document.getElementById('edit_correct_answer').value = question.correct_answer || 'A';
    document.getElementById('edit_points').value = question.points;
    document.getElementById('edit_existing_image').value = question.question_image || '';
    
    const currentImageDiv = document.getElementById('edit_current_image');
    if (question.question_image) {
        currentImageDiv.innerHTML = '<img src="<?php echo url('storage/uploads/questions/'); ?>' + question.question_image + '" style="max-width: 200px; border-radius: 4px;"><br><small>Gambar saat ini (upload baru untuk mengganti)</small>';
    } else {
        currentImageDiv.innerHTML = '';
    }
    
    toggleOptions('edit');
    document.getElementById('editModal').style.display = 'block';
}

function toggleOptions(prefix) {
    const questionType = document.getElementById(prefix + '_question_type').value;
    const optionsGroup = document.getElementById(prefix + '_options_group');
    optionsGroup.style.display = questionType === 'multiple_choice' ? 'block' : 'none';
}

function closeModal(id) {
    document.getElementById(id).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.className === 'modal') {
        event.target.style.display = 'none';
    }
}
</script>

<?php include '../../app/Views/includes/footer.php'; ?>
