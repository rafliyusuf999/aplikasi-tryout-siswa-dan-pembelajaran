<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Cleanup Duplicate Data';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cleanup') {
    verifyCsrf();
    
    $pdo->exec('BEGIN TRANSACTION');
    
    try {
        $stmt = $pdo->query("
            SELECT phone_number, GROUP_CONCAT(id) as ids, COUNT(*) as count 
            FROM users 
            WHERE role = 'student' AND phone_number IS NOT NULL AND phone_number != '' 
            GROUP BY phone_number 
            HAVING COUNT(*) > 1
        ");
        $duplicates = $stmt->fetchAll();
        
        $cleaned = 0;
        foreach ($duplicates as $dup) {
            $ids = explode(',', $dup['ids']);
            array_shift($ids);
            
            foreach ($ids as $id) {
                $stmt = $pdo->prepare("UPDATE users SET phone_number = NULL WHERE id = ?");
                $stmt->execute([$id]);
                $cleaned++;
            }
        }
        
        $pdo->exec('COMMIT');
        setFlash("Berhasil membersihkan {$cleaned} data duplikat", 'success');
        redirect('admin/cleanup_duplicates.php');
        
    } catch (Exception $e) {
        $pdo->exec('ROLLBACK');
        setFlash('Error: ' . $e->getMessage(), 'danger');
    }
}

$stmt = $pdo->query("
    SELECT phone_number, COUNT(*) as count 
    FROM users 
    WHERE role = 'student' AND phone_number IS NOT NULL AND phone_number != '' 
    GROUP BY phone_number 
    HAVING COUNT(*) > 1
");
$duplicates = $stmt->fetchAll();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Cleanup Duplicate Phone Numbers</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <h3>Status Database</h3>
        <p>Total Siswa: <strong><?php echo $total_students; ?></strong></p>
        <p>Nomor Telepon Duplikat: <strong><?php echo count($duplicates); ?></strong></p>
        
        <?php if (count($duplicates) > 0): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Ditemukan Duplikasi!</strong>
                <p>Nomor telepon berikut terdaftar lebih dari sekali:</p>
                <ul>
                    <?php foreach ($duplicates as $dup): ?>
                        <li><?php echo htmlspecialchars($dup['phone_number']); ?> - <?php echo $dup['count']; ?> kali</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <form method="POST">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="cleanup">
                <button type="submit" class="btn btn-warning" onclick="return confirm('Yakin ingin membersihkan duplikasi? Data duplikat akan kehilangan nomor telepon mereka.')">
                    🧹 Bersihkan Duplikasi
                </button>
            </form>
        <?php else: ?>
            <div class="alert alert-success">
                <strong>✓ Data Bersih!</strong>
                <p>Tidak ada nomor telepon yang duplikat.</p>
                <p>Sistem siap untuk menerapkan constraint UNIQUE pada kolom phone_number.</p>
            </div>
            
            <a href="<?php echo url('admin/students.php'); ?>" class="btn btn-primary">Kembali ke Kelola Siswa</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
