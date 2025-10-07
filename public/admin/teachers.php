<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Kelola Mentor';

$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users WHERE role = 'teacher'";
if ($search) {
    $query .= " AND (full_name ILIKE :search OR email ILIKE :search)";
}
$query .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$teachers = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $full_name = sanitize($_POST['full_name']);
            $phone_number = sanitize($_POST['phone_number'] ?? '');
            
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, phone_number) VALUES (?, ?, ?, 'teacher', ?)");
            $stmt->execute([$email, $password_hash, $full_name, $phone_number]);
            
            setFlash('Mentor berhasil ditambahkan', 'success');
            redirect('admin/teachers.php');
        }
        
        if ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $email = sanitize($_POST['email']);
            $full_name = sanitize($_POST['full_name']);
            $phone_number = sanitize($_POST['phone_number'] ?? '');
            
            $stmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ?, phone_number = ? WHERE id = ? AND role = 'teacher'");
            $stmt->execute([$email, $full_name, $phone_number, $id]);
            
            setFlash('Mentor berhasil diupdate', 'success');
            redirect('admin/teachers.php');
        }
        
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
            $stmt->execute([$id]);
            
            setFlash('Mentor berhasil dihapus', 'success');
            redirect('admin/teachers.php');
        }
        
        if ($_POST['action'] === 'reset_password' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $new_password = $_POST['new_password'];
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'teacher'");
            $stmt->execute([$password_hash, $id]);
            
            setFlash('Password berhasil direset', 'success');
            redirect('admin/teachers.php');
        }
    }
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Kelola Mentor</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari mentor..." class="form-control" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?php echo url('admin/teachers.php'); ?>" class="btn btn-secondary">Reset</a>
        </form>
        
        <button onclick="showAddModal()" class="btn btn-primary" style="margin-bottom: 1.5rem;">+ Tambah Mentor</button>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>No. Telepon</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($teachers as $teacher): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($teacher['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['email']); ?></td>
                        <td><?php echo htmlspecialchars($teacher['phone_number'] ?? '-'); ?></td>
                        <td>
                            <button onclick='editTeacher(<?php echo json_encode($teacher); ?>)' class="btn btn-sm btn-primary">Edit</button>
                            <button onclick='resetPassword(<?php echo $teacher['id']; ?>)' class="btn btn-sm btn-secondary">Reset Password</button>
                            <form method="POST" style="display: inline;">
                                <?php echo csrf(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $teacher['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus mentor ini?')">Hapus</button>
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
        <h2>Tambah Mentor</h2>
        <form method="POST">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required class="form-control">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required class="form-control">
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="full_name" required class="form-control">
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="phone_number" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>

<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('editModal')">&times;</span>
        <h2>Edit Mentor</h2>
        <form method="POST" id="editForm">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" id="edit_email" required class="form-control">
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="full_name" id="edit_full_name" required class="form-control">
            </div>
            <div class="form-group">
                <label>No. Telepon</label>
                <input type="text" name="phone_number" id="edit_phone_number" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>

<div id="resetPasswordModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('resetPasswordModal')">&times;</span>
        <h2>Reset Password</h2>
        <form method="POST">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" id="reset_id">
            <div class="form-group">
                <label>Password Baru</label>
                <input type="password" name="new_password" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Reset Password</button>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function editTeacher(teacher) {
    document.getElementById('edit_id').value = teacher.id;
    document.getElementById('edit_email').value = teacher.email;
    document.getElementById('edit_full_name').value = teacher.full_name;
    document.getElementById('edit_phone_number').value = teacher.phone_number || '';
    document.getElementById('editModal').style.display = 'block';
}

function resetPassword(id) {
    document.getElementById('reset_id').value = id;
    document.getElementById('resetPasswordModal').style.display = 'block';
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
