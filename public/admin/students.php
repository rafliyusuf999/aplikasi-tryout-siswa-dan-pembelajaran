<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Kelola Siswa';

$search = $_GET['search'] ?? '';
$query = "SELECT u.*, 
          COALESCE(SUM(ea.cheating_warnings), 0) as total_cheating 
          FROM users u
          LEFT JOIN exam_attempts ea ON u.id = ea.user_id
          WHERE u.role = 'student'";
if ($search) {
    $query .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR u.inspira_branch LIKE :search)";
}
$query .= " GROUP BY u.id ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->execute(['search' => "%$search%"]);
} else {
    $stmt->execute();
}
$students = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add') {
            $email = sanitize($_POST['email']);
            $password = $_POST['password'];
            $full_name = sanitize($_POST['full_name']);
            $inspira_branch = sanitize($_POST['inspira_branch']);
            $class_level = sanitize($_POST['class_level']);
            $school_name = sanitize($_POST['school_name']);
            $phone_number = sanitize($_POST['phone_number']);
            
            $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, inspira_branch, class_level, school_name, phone_number) VALUES (?, ?, ?, 'student', ?, ?, ?, ?)");
            $stmt->execute([$email, $password_hash, $full_name, $inspira_branch, $class_level, $school_name, $phone_number]);
            
            setFlash('Siswa berhasil ditambahkan', 'success');
            redirect('admin/students.php');
        }
        
        if ($_POST['action'] === 'edit' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $email = sanitize($_POST['email']);
            $full_name = sanitize($_POST['full_name']);
            $inspira_branch = sanitize($_POST['inspira_branch']);
            $class_level = sanitize($_POST['class_level']);
            $school_name = sanitize($_POST['school_name']);
            $phone_number = sanitize($_POST['phone_number']);
            
            $stmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ?, inspira_branch = ?, class_level = ?, school_name = ?, phone_number = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$email, $full_name, $inspira_branch, $class_level, $school_name, $phone_number, $id]);
            
            setFlash('Siswa berhasil diupdate', 'success');
            redirect('admin/students.php');
        }
        
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$id]);
            
            setFlash('Siswa berhasil dihapus', 'success');
            redirect('admin/students.php');
        }
        
        if ($_POST['action'] === 'reset_password' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $new_password = $_POST['new_password'];
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$password_hash, $id]);
            
            setFlash('Password berhasil direset', 'success');
            redirect('admin/students.php');
        }
        
        if ($_POST['action'] === 'clear_cheating' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("UPDATE exam_attempts SET cheating_warnings = 0 WHERE user_id = ?");
            $stmt->execute([$id]);
            
            setFlash('Status curang berhasil dibersihkan', 'success');
            redirect('admin/students.php');
        }
        
        if ($_POST['action'] === 'clear_all_cheating') {
            $stmt = $pdo->prepare("UPDATE exam_attempts SET cheating_warnings = 0");
            $stmt->execute();
            
            setFlash('Status curang semua siswa berhasil dibersihkan', 'success');
            redirect('admin/students.php');
        }
        
        if ($_POST['action'] === 'delete_all') {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE role = 'student'");
            $stmt->execute();
            
            setFlash("Berhasil menghapus {$count} siswa", 'success');
            redirect('admin/students.php');
        }
    }
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Kelola Siswa</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari siswa..." class="form-control" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?php echo url('admin/students.php'); ?>" class="btn btn-secondary">Reset</a>
        </form>
        
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <button onclick="showAddModal()" class="btn btn-primary">+ Tambah Siswa</button>
            <button onclick="clearAllCheating()" class="btn btn-warning">🚫 Clear Curang All</button>
            <a href="<?php echo url('admin/cleanup_duplicates.php'); ?>" class="btn btn-info">🧹 Cleanup Duplikat</a>
            <a href="<?php echo url('admin/migrate_phone_unique.php'); ?>" class="btn btn-success">🚀 Migrasi Database</a>
            <button onclick="deleteAllStudents()" class="btn btn-danger" style="margin-left: auto;">🗑️ Hapus Semua Siswa</button>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Cabang</th>
                        <th>Kelas</th>
                        <th>Sekolah</th>
                        <th>Status Curang</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['inspira_branch'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($student['class_level'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($student['school_name'] ?? '-'); ?></td>
                        <td>
                            <?php if ($student['total_cheating'] > 0): ?>
                                <span class="badge badge-danger">⚠️ <?php echo $student['total_cheating']; ?></span>
                            <?php else: ?>
                                <span class="badge badge-success">✓ Bersih</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button onclick='editStudent(<?php echo json_encode($student); ?>)' class="btn btn-sm btn-primary">Edit</button>
                            <button onclick='viewProfile(<?php echo $student['id']; ?>)' class="btn btn-sm btn-info">Lihat Profil</button>
                            <button onclick='resetPassword(<?php echo $student['id']; ?>)' class="btn btn-sm btn-secondary">Reset Password</button>
                            <?php if ($student['total_cheating'] > 0): ?>
                                <button onclick='clearCheating(<?php echo $student['id']; ?>)' class="btn btn-sm btn-warning">Clear Curang</button>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;">
                                <?php echo csrf(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $student['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus siswa ini?')">Hapus</button>
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
        <h2>Tambah Siswa</h2>
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
                <label>Cabang Inspiranet</label>
                <select name="inspira_branch" required class="form-control">
                    <option value="">Pilih Cabang</option>
                    <option value="Inspiranet_Cakrawala 1">Inspiranet_Cakrawala 1</option>
                    <option value="Inspiranet_Cakrawala 2">Inspiranet_Cakrawala 2</option>
                    <option value="Inspiranet_Cakrawala 3">Inspiranet_Cakrawala 3</option>
                    <option value="Inspiranet_Cakrawala 4">Inspiranet_Cakrawala 4</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kelas</label>
                <select name="class_level" required class="form-control">
                    <option value="">Pilih Kelas</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="Alumni">Alumni</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sekolah</label>
                <input type="text" name="school_name" class="form-control">
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
        <h2>Edit Siswa</h2>
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
                <label>Cabang Inspiranet</label>
                <select name="inspira_branch" id="edit_inspira_branch" required class="form-control">
                    <option value="">Pilih Cabang</option>
                    <option value="Inspiranet_Cakrawala 1">Inspiranet_Cakrawala 1</option>
                    <option value="Inspiranet_Cakrawala 2">Inspiranet_Cakrawala 2</option>
                    <option value="Inspiranet_Cakrawala 3">Inspiranet_Cakrawala 3</option>
                    <option value="Inspiranet_Cakrawala 4">Inspiranet_Cakrawala 4</option>
                </select>
            </div>
            <div class="form-group">
                <label>Kelas</label>
                <select name="class_level" id="edit_class_level" required class="form-control">
                    <option value="">Pilih Kelas</option>
                    <option value="10">10</option>
                    <option value="11">11</option>
                    <option value="12">12</option>
                    <option value="Alumni">Alumni</option>
                </select>
            </div>
            <div class="form-group">
                <label>Sekolah</label>
                <input type="text" name="school_name" id="edit_school_name" class="form-control">
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

function editStudent(student) {
    document.getElementById('edit_id').value = student.id;
    document.getElementById('edit_email').value = student.email;
    document.getElementById('edit_full_name').value = student.full_name;
    document.getElementById('edit_inspira_branch').value = student.inspira_branch || '';
    document.getElementById('edit_class_level').value = student.class_level || '';
    document.getElementById('edit_school_name').value = student.school_name || '';
    document.getElementById('edit_phone_number').value = student.phone_number || '';
    document.getElementById('editModal').style.display = 'block';
}

function resetPassword(id) {
    document.getElementById('reset_id').value = id;
    document.getElementById('resetPasswordModal').style.display = 'block';
}

function clearCheating(id) {
    if (confirm('Yakin ingin membersihkan status curang siswa ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="clear_cheating">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function clearAllCheating() {
    if (confirm('Yakin ingin membersihkan status curang SEMUA siswa?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="clear_all_cheating">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteAllStudents() {
    if (confirm('⚠️ PERINGATAN! Yakin ingin menghapus SEMUA siswa?\n\nTindakan ini akan menghapus:\n- Semua data siswa\n- Semua percobaan ujian mereka\n- Semua pembayaran mereka\n- Semua data terkait lainnya\n\nTindakan ini TIDAK BISA dibatalkan!')) {
        if (confirm('Konfirmasi sekali lagi: Hapus SEMUA siswa?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="delete_all">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
}

function viewProfile(id) {
    window.location.href = '<?php echo url('admin/student_profile.php?id='); ?>' + id;
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
