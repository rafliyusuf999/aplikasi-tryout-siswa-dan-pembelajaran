<?php
require_once '../config/config.php';

if(isLoggedIn()) {
    redirect('index.php');
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('Invalid request!', 'danger');
        redirect('login.php');
    }
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if($user && verifyPassword($password, $user['password_hash'])) {
        login($user);
        setFlash('Login berhasil!', 'success');
        redirect('index.php');
    } else {
        setFlash('Email atau password salah!', 'danger');
    }
}

$pageTitle = 'Login - INSPIRANET OFFICIAL TO';
include '../app/Views/includes/header.php';
include '../app/Views/includes/navbar.php';
?>

<div class="card" style="max-width: 500px; margin: 3rem auto;">
    <h2 style="text-align: center; margin-bottom: 2rem;">🔐 Login</h2>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <div style="position: relative;">
                <input type="password" id="password" name="password" class="form-control" required>
                <button type="button" onclick="togglePassword('password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem;">
                    👁️
                </button>
            </div>
        </div>
        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">Login</button>
    </form>
    <div style="text-align: center; margin-top: 1.5rem;">
        <p>Belum punya akun? <a href="<?php echo url('register.php'); ?>" style="color: var(--primary-color); font-weight: 600;">Daftar di sini</a></p>
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
