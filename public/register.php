<?php
require_once '../config/config.php';

if(isLoggedIn()) {
    redirect('index.php');
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request!', 'danger');
        redirect('register.php');
    }
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = sanitize($_POST['full_name'] ?? '');
    $inspira_branch = sanitize($_POST['inspira_branch'] ?? '');
    $class_level = sanitize($_POST['class_level'] ?? '');
    $school_name = sanitize($_POST['school_name'] ?? '');
    $phone_number = sanitize($_POST['phone_number'] ?? '');
    
    $pdo = getDB();
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetch()) {
        setFlash('Email sudah terdaftar!', 'danger');
        redirect('register.php');
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(full_name) = LOWER(?)");
    $stmt->execute([$full_name]);
    if($stmt->fetch()) {
        setFlash('Nama sudah terdaftar! Gunakan nama yang berbeda.', 'danger');
        redirect('register.php');
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone_number = ?");
    $stmt->execute([$phone_number]);
    if($stmt->fetch()) {
        setFlash('Nomor HP sudah terdaftar!', 'danger');
        redirect('register.php');
    }
    
    if(!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        setFlash('Foto profil wajib diupload!', 'danger');
        redirect('register.php');
    }
    
    $filename = uploadFile($_FILES['profile_photo'], 'profiles', 'profile_' . str_replace(['@', '.'], '', $email) . '_');
    if(!$filename) {
        setFlash('Gagal mengupload foto profil! Pastikan file berformat JPG/PNG dan ukuran maksimal 16MB.', 'danger');
        redirect('register.php');
    }
    
    $password_hash = hashPassword($password);
    
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, full_name, role, inspira_branch, class_level, school_name, phone_number, profile_photo) VALUES (?, ?, ?, 'student', ?, ?, ?, ?, ?)");
    $stmt->execute([$email, $password_hash, $full_name, $inspira_branch, $class_level, $school_name, $phone_number, $filename]);
    
    setFlash('Pendaftaran berhasil! Silakan login.', 'success');
    redirect('login.php');
}

$pageTitle = 'Daftar - INSPIRANET OFFICIAL TO';
include '../app/Views/includes/header.php';
include '../app/Views/includes/navbar.php';
?>

<div class="card" style="max-width: 600px; margin: 2rem auto;">
    <h2 style="text-align: center; margin-bottom: 2rem;">📝 Pendaftaran Siswa</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <div class="form-group">
            <label for="full_name">Nama Lengkap:</label>
            <input type="text" id="full_name" name="full_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" class="form-control" required minlength="6">
                <button type="button" onclick="togglePassword('password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem;">
                    👁️
                </button>
            </div>
            <small class="text-muted">Password minimal 6 karakter</small>
        </div>
        <div class="form-group">
            <label for="inspira_branch">Cabang:</label>
            <select id="inspira_branch" name="inspira_branch" class="form-control" required>
                <option value="">Pilih Cabang</option>
                <?php foreach(BRANCHES as $branch): ?>
                <option value="<?php echo $branch; ?>"><?php echo $branch; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="class_level">Kelas:</label>
            <select id="class_level" name="class_level" class="form-control" required>
                <option value="">Pilih Kelas</option>
                <?php foreach(CLASS_LEVELS as $level): ?>
                <option value="<?php echo $level; ?>"><?php echo $level; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="school_name">Nama Sekolah:</label>
            <input type="text" id="school_name" name="school_name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="phone_number">Nomor HP:</label>
            <input type="tel" id="phone_number" name="phone_number" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="profile_photo">Foto Profil <span style="color: red;">*</span>:</label>
            <input type="file" id="profile_photo" name="profile_photo" accept="image/*" class="form-control" required>
            <small class="text-muted">Upload foto profil Anda (JPG, PNG, max 5MB) - <strong>Wajib diisi</strong></small>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Daftar</button>
    </form>
    <div style="text-align: center; margin-top: 1.5rem;">
        <p>Sudah punya akun? <a href="<?php echo url('login.php'); ?>" style="color: var(--primary-color); font-weight: 600;">Login di sini</a></p>
    </div>
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
