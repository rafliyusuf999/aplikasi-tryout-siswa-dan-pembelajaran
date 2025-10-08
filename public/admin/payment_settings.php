<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();
$pageTitle = 'Pengaturan Pembayaran';

$stmt = $pdo->query("SELECT * FROM payment_settings ORDER BY id DESC LIMIT 1");
$settings = $stmt->fetch();

if (!$settings) {
    $stmt = $pdo->query("INSERT INTO payment_settings (id) VALUES (1)");
    $stmt = $pdo->query("SELECT * FROM payment_settings WHERE id = 1");
    $settings = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $qris_image = $settings['qris_image'] ?? '';
    
    if (isset($_FILES['qris_image']) && $_FILES['qris_image']['error'] === 0) {
        $upload_dir = '../../uploads/payment/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['qris_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            if ($settings['qris_image'] && file_exists($upload_dir . $settings['qris_image'])) {
                unlink($upload_dir . $settings['qris_image']);
            }
            
            $new_filename = 'qris_' . time() . '.' . $file_extension;
            move_uploaded_file($_FILES['qris_image']['tmp_name'], $upload_dir . $new_filename);
            $qris_image = $new_filename;
        }
    }
    
    $payment_instructions = sanitize($_POST['payment_instructions']);
    $bank_name = sanitize($_POST['bank_name']);
    $account_number = sanitize($_POST['account_number']);
    $account_name = sanitize($_POST['account_name']);
    
    $stmt = $pdo->prepare("UPDATE payment_settings SET qris_image = ?, payment_instructions = ?, bank_name = ?, account_number = ?, account_name = ?, updated_at = datetime('now') WHERE id = ?");
    $stmt->execute([$qris_image, $payment_instructions, $bank_name, $account_number, $account_name, $settings['id']]);
    
    setFlash('Pengaturan pembayaran berhasil diupdate', 'success');
    redirect('admin/payment_settings.php');
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Pengaturan Pembayaran</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf(); ?>
            
            <div class="form-group">
                <label>QRIS Image</label>
                <?php if ($settings['qris_image']): ?>
                <div style="margin-bottom: 1rem;">
                    <img src="<?php echo url('uploads/payment/' . $settings['qris_image']); ?>" alt="QRIS" style="max-width: 300px; border: 1px solid #ddd; padding: 10px; border-radius: 8px;">
                </div>
                <?php endif; ?>
                <input type="file" name="qris_image" accept="image/png,image/jpeg,image/jpg" class="form-control">
                <small class="text-muted">Format: JPG, JPEG, PNG. Upload gambar QRIS baru untuk mengganti yang lama.</small>
            </div>
            
            <div class="form-group">
                <label>Instruksi Pembayaran</label>
                <textarea name="payment_instructions" class="form-control" rows="5" placeholder="Contoh: 1. Scan QRIS di atas&#10;2. Masukkan nominal sesuai harga try out&#10;3. Simpan bukti transfer&#10;4. Upload bukti pembayaran"><?php echo htmlspecialchars($settings['payment_instructions'] ?? ''); ?></textarea>
            </div>
            
            <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Transfer Bank</h3>
            
            <div class="form-group">
                <label>Nama Bank</label>
                <input type="text" name="bank_name" value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>" class="form-control" placeholder="Contoh: BCA">
            </div>
            
            <div class="form-group">
                <label>Nomor Rekening</label>
                <input type="text" name="account_number" value="<?php echo htmlspecialchars($settings['account_number'] ?? ''); ?>" class="form-control" placeholder="Contoh: 1234567890">
            </div>
            
            <div class="form-group">
                <label>Nama Pemegang Rekening</label>
                <input type="text" name="account_name" value="<?php echo htmlspecialchars($settings['account_name'] ?? ''); ?>" class="form-control" placeholder="Contoh: PT Inspira Network">
            </div>
            
            <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
            <a href="<?php echo url('admin/payments.php'); ?>" class="btn btn-secondary">Kembali</a>
        </form>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
