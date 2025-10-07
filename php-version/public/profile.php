<?php
require_once '../config/config.php';

requireLogin();

$user = getCurrentUser();
$pdo = getDB();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request!', 'danger');
        redirect('profile.php');
    }
    
    $full_name = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $inspira_branch = sanitize($_POST['inspira_branch'] ?? '');
    $class_level = sanitize($_POST['class_level'] ?? '');
    $school_name = sanitize($_POST['school_name'] ?? '');
    $phone_number = sanitize($_POST['phone_number'] ?? '');
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $user['id']]);
    if($stmt->fetch()) {
        setFlash('Email sudah digunakan oleh pengguna lain!', 'danger');
        redirect('profile.php');
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(full_name) = LOWER(?) AND id != ?");
    $stmt->execute([$full_name, $user['id']]);
    if($stmt->fetch()) {
        setFlash('Nama sudah digunakan oleh pengguna lain!', 'danger');
        redirect('profile.php');
    }
    
    $updateFields = ['full_name = ?', 'email = ?', 'inspira_branch = ?', 'class_level = ?', 'school_name = ?', 'phone_number = ?'];
    $params = [$full_name, $email, $inspira_branch, $class_level, $school_name, $phone_number];
    
    if($password) {
        $updateFields[] = 'password_hash = ?';
        $params[] = hashPassword($password);
    }
    
    if(isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $filename = uploadFile($_FILES['profile_photo'], 'profiles', 'profile_' . str_replace(['@', '.'], '', $email) . '_');
        if($filename) {
            $updateFields[] = 'profile_photo = ?';
            $params[] = $filename;
        }
    }
    
    $params[] = $user['id'];
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    setFlash('Profil berhasil diperbarui!', 'success');
    redirect('profile.php');
}

$user = getCurrentUser();

$pageTitle = 'Profil - INSPIRANET';
include '../app/Views/includes/header.php';
include '../app/Views/includes/navbar.php';
?>

<div class="card" style="max-width: 800px; margin: 2rem auto;">
    <h2 style="text-align: center; margin-bottom: 2rem;">👤 Profil Saya</h2>
    
    <div style="text-align: center; margin-bottom: 2rem;">
        <?php if(!empty($user['profile_photo'])): ?>
        <img src="<?php echo url('storage/uploads/profiles/' . $user['profile_photo']); ?>" 
             alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
             style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color);">
        <?php else: ?>
        <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 3rem; margin: 0 auto; border: 4px solid var(--primary-color);">
            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
        </div>
        <?php endif; ?>
    </div>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <div class="form-group">
            <label for="full_name">Nama Lengkap:</label>
            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password Baru (kosongkan jika tidak ingin mengubah):</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" class="form-control">
                <button type="button" onclick="togglePassword('password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem;">
                    👁️
                </button>
            </div>
        </div>
        
        <?php if($user['role'] == 'student'): ?>
        <div class="form-group">
            <label for="inspira_branch">Cabang:</label>
            <select id="inspira_branch" name="inspira_branch" class="form-control" required>
                <?php foreach(BRANCHES as $branch): ?>
                <option value="<?php echo $branch; ?>" <?php echo $user['inspira_branch'] == $branch ? 'selected' : ''; ?>>
                    <?php echo $branch; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="class_level">Kelas:</label>
            <select id="class_level" name="class_level" class="form-control" required>
                <?php foreach(CLASS_LEVELS as $level): ?>
                <option value="<?php echo $level; ?>" <?php echo $user['class_level'] == $level ? 'selected' : ''; ?>>
                    <?php echo $level; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="school_name">Nama Sekolah:</label>
            <input type="text" id="school_name" name="school_name" class="form-control" value="<?php echo htmlspecialchars($user['school_name'] ?? ''); ?>" required>
        </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="phone_number">Nomor HP:</label>
            <input type="tel" id="phone_number" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="profile_photo">Ubah Foto Profil (opsional):</label>
            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="form-control">
            <small class="text-muted">Upload foto baru jika ingin mengubah (JPG, PNG, max 16MB)</small>
        </div>
        
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Update Profil</button>
    </form>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
    } else {
        field.type = 'password';
    }
}
</script>

<?php include '../app/Views/includes/footer.php'; ?>
