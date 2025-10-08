<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Database Migration - Phone Number Unique';

$status = [];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'migrate') {
    verifyCsrf();
    
    try {
        $pdo->exec('BEGIN TRANSACTION');
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND name='users_phone_number_unique'");
        $indexExists = $stmt->fetchColumn() > 0;
        
        if ($indexExists) {
            $status[] = '✓ Index sudah ada, tidak perlu migrasi';
        } else {
            $stmt = $pdo->query("
                SELECT phone_number, COUNT(*) as count 
                FROM users 
                WHERE role = 'student' AND phone_number IS NOT NULL AND phone_number != '' 
                GROUP BY phone_number 
                HAVING COUNT(*) > 1
            ");
            $duplicates = $stmt->fetchAll();
            
            if (count($duplicates) > 0) {
                $pdo->exec('ROLLBACK');
                $error = 'Masih ada ' . count($duplicates) . ' nomor telepon duplikat. Silakan bersihkan terlebih dahulu di <a href="' . url('admin/cleanup_duplicates.php') . '">Cleanup Duplicates</a>.';
            } else {
                $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS users_phone_number_unique ON users(phone_number) WHERE phone_number IS NOT NULL AND phone_number != ""');
                $status[] = '✓ Index UNIQUE berhasil dibuat untuk phone_number';
                $pdo->exec('COMMIT');
            }
        }
        
    } catch (Exception $e) {
        $pdo->exec('ROLLBACK');
        $error = 'Error: ' . $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='index' AND name='users_phone_number_unique'");
$indexExists = $stmt->fetchColumn() > 0;

$stmt = $pdo->query("
    SELECT phone_number, COUNT(*) as count 
    FROM users 
    WHERE role = 'student' AND phone_number IS NOT NULL AND phone_number != '' 
    GROUP BY phone_number 
    HAVING COUNT(*) > 1
");
$duplicates = $stmt->fetchAll();

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Database Migration - Phone Number Unique</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <h3>Status Migrasi</h3>
        
        <?php if ($indexExists): ?>
            <div class="alert alert-success">
                <strong>✓ Migrasi Sudah Selesai</strong>
                <p>Index UNIQUE untuk phone_number sudah ada. Sistem akan mencegah duplikasi nomor telepon.</p>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>⚠️ Migrasi Belum Dijalankan</strong>
                <p>Index UNIQUE untuk phone_number belum ada. Sistem masih memperbolehkan duplikasi.</p>
            </div>
        <?php endif; ?>
        
        <?php if (count($duplicates) > 0): ?>
            <div class="alert alert-danger">
                <strong>❌ Ada Duplikasi Data!</strong>
                <p>Ditemukan <?php echo count($duplicates); ?> nomor telepon yang duplikat:</p>
                <ul>
                    <?php foreach ($duplicates as $dup): ?>
                        <li><?php echo htmlspecialchars($dup['phone_number']); ?> - <?php echo $dup['count']; ?> kali</li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Wajib dibersihkan terlebih dahulu sebelum menjalankan migrasi!</strong></p>
                <a href="<?php echo url('admin/cleanup_duplicates.php'); ?>" class="btn btn-warning">🧹 Bersihkan Duplikasi</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($status)): ?>
            <div class="alert alert-success">
                <?php foreach ($status as $msg): ?>
                    <p><?php echo $msg; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$indexExists && count($duplicates) === 0): ?>
            <form method="POST">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="migrate">
                <button type="submit" class="btn btn-primary" onclick="return confirm('Yakin ingin menjalankan migrasi? Setelah ini, sistem tidak akan memperbolehkan nomor telepon duplikat.')">
                    🚀 Jalankan Migrasi
                </button>
            </form>
        <?php endif; ?>
        
        <hr>
        <a href="<?php echo url('admin/students.php'); ?>" class="btn btn-secondary">Kembali ke Kelola Siswa</a>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
