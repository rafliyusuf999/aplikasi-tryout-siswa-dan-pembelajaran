<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Kelola Try Out';

$search = $_GET['search'] ?? '';
$query = "SELECT e.*, u.full_name as creator_name FROM exams e 
          LEFT JOIN users u ON e.created_by = u.id WHERE 1=1";
if ($search) {
    $query .= " AND (e.title LIKE :search OR e.description LIKE :search)";
}
$query .= " ORDER BY e.created_at DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$exams = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role IN ('admin', 'teacher') ORDER BY full_name");
$creators = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $title = sanitize($_POST['title']);
            $description = sanitize($_POST['description']);
            $duration_minutes = (int)$_POST['duration_minutes'];
            $is_premium = isset($_POST['is_premium']) ? 1 : 0;
            $price = (int)($_POST['price'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $start_time = $_POST['start_time'] ? $_POST['start_time'] : null;
            $end_time = $_POST['end_time'] ? $_POST['end_time'] : null;
            $created_by = (int)$_POST['created_by'];
            
            $stmt = $pdo->prepare("INSERT INTO exams (title, description, duration_minutes, is_premium, price, is_active, start_time, end_time, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $duration_minutes, $is_premium, $price, $is_active, $start_time, $end_time, $created_by]);
            
            setFlash('Try Out berhasil ditambahkan', 'success');
            redirect('admin/exams.php');
        }
        
        if ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $title = sanitize($_POST['title']);
            $description = sanitize($_POST['description']);
            $duration_minutes = (int)$_POST['duration_minutes'];
            $is_premium = isset($_POST['is_premium']) ? 1 : 0;
            $price = (int)($_POST['price'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $start_time = $_POST['start_time'] ? $_POST['start_time'] : null;
            $end_time = $_POST['end_time'] ? $_POST['end_time'] : null;
            
            $stmt = $pdo->prepare("UPDATE exams SET title = ?, description = ?, duration_minutes = ?, is_premium = ?, price = ?, is_active = ?, start_time = ?, end_time = ? WHERE id = ?");
            $stmt->execute([$title, $description, $duration_minutes, $is_premium, $price, $is_active, $start_time, $end_time, $id]);
            
            setFlash('Try Out berhasil diupdate', 'success');
            redirect('admin/exams.php');
        }
        
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlash('Try Out berhasil dihapus', 'success');
            redirect('admin/exams.php');
        }
    }
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Kelola Try Out</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari TO..." class="form-control" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?php echo url('admin/exams.php'); ?>" class="btn btn-secondary">Reset</a>
        </form>
        
        <button onclick="showAddModal()" class="btn btn-primary" style="margin-bottom: 1.5rem;">+ Tambah Try Out</button>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Tipe</th>
                        <th>Harga</th>
                        <th>Pembuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exams as $exam): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                        <td><?php echo $exam['duration_minutes']; ?> menit</td>
                        <td>
                            <span class="badge badge-<?php echo $exam['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $exam['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $exam['is_premium'] ? 'warning' : 'info'; ?>">
                                <?php echo $exam['is_premium'] ? 'Premium' : 'Gratis'; ?>
                            </span>
                        </td>
                        <td><?php echo $exam['is_premium'] ? 'Rp ' . number_format($exam['price'], 0, ',', '.') : '-'; ?></td>
                        <td><?php echo htmlspecialchars($exam['creator_name'] ?? '-'); ?></td>
                        <td>
                            <button onclick='editExam(<?php echo json_encode($exam); ?>)' class="btn btn-sm btn-primary">Edit</button>
                            <a href="<?php echo url('admin/questions.php?exam_id=' . $exam['id']); ?>" class="btn btn-sm btn-secondary">Soal</a>
                            <form method="POST" style="display: inline;">
                                <?php echo csrf(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus TO ini?')">Hapus</button>
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
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2>Tambah Try Out</h2>
        <form method="POST">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Judul Try Out</label>
                <input type="text" name="title" required class="form-control">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Durasi (menit)</label>
                <input type="number" name="duration_minutes" value="120" required class="form-control">
            </div>
            <div class="form-group">
                <label>Pembuat</label>
                <select name="created_by" required class="form-control">
                    <?php foreach ($creators as $creator): ?>
                    <option value="<?php echo $creator['id']; ?>"><?php echo htmlspecialchars($creator['full_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_premium" id="add_is_premium" onchange="togglePrice('add')"> Premium (Berbayar)
                </label>
            </div>
            <div class="form-group" id="add_price_group" style="display: none;">
                <label>Harga (Rp)</label>
                <input type="number" name="price" value="0" class="form-control">
            </div>
            <div class="form-group">
                <label>Waktu Mulai</label>
                <input type="datetime-local" name="start_time" class="form-control">
            </div>
            <div class="form-group">
                <label>Waktu Selesai</label>
                <input type="datetime-local" name="end_time" class="form-control">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" checked> Aktif
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Try Out</h2>
        <form method="POST" id="editForm">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Judul Try Out</label>
                <input type="text" name="title" id="edit_title" required class="form-control">
            </div>
            <div class="form-group">
                <label>Deskripsi</label>
                <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Durasi (menit)</label>
                <input type="number" name="duration_minutes" id="edit_duration_minutes" required class="form-control">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_premium" id="edit_is_premium" onchange="togglePrice('edit')"> Premium (Berbayar)
                </label>
            </div>
            <div class="form-group" id="edit_price_group" style="display: none;">
                <label>Harga (Rp)</label>
                <input type="number" name="price" id="edit_price" value="0" class="form-control">
            </div>
            <div class="form-group">
                <label>Waktu Mulai</label>
                <input type="datetime-local" name="start_time" id="edit_start_time" class="form-control">
            </div>
            <div class="form-group">
                <label>Waktu Selesai</label>
                <input type="datetime-local" name="end_time" id="edit_end_time" class="form-control">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_active" id="edit_is_active"> Aktif
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function editExam(exam) {
    document.getElementById('edit_id').value = exam.id;
    document.getElementById('edit_title').value = exam.title;
    document.getElementById('edit_description').value = exam.description || '';
    document.getElementById('edit_duration_minutes').value = exam.duration_minutes;
    document.getElementById('edit_is_premium').checked = exam.is_premium;
    document.getElementById('edit_price').value = exam.price || 0;
    document.getElementById('edit_start_time').value = exam.start_time ? exam.start_time.substring(0, 16) : '';
    document.getElementById('edit_end_time').value = exam.end_time ? exam.end_time.substring(0, 16) : '';
    document.getElementById('edit_is_active').checked = exam.is_active;
    togglePrice('edit');
    document.getElementById('editModal').style.display = 'block';
}

function togglePrice(prefix) {
    const checkbox = document.getElementById(prefix + '_is_premium');
    const priceGroup = document.getElementById(prefix + '_price_group');
    priceGroup.style.display = checkbox.checked ? 'block' : 'none';
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
