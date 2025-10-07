<?php
require_once '../../config/config.php';

requireAuth('student');

$pdo = getDB();
$user = getCurrentUser();
$pageTitle = 'Pembayaran Try Out';

$exam_id = $_GET['exam_id'] ?? null;

if (!$exam_id) {
    setFlash('ID Try Out tidak valid', 'danger');
    redirect('student/exams.php');
}

$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ? AND is_premium = true");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    setFlash('Try Out tidak ditemukan', 'danger');
    redirect('student/exams.php');
}

$stmt = $pdo->prepare("SELECT * FROM payments WHERE exam_id = ? AND user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$exam_id, $user['id']]);
$existing_payment = $stmt->fetch();

$stmt = $pdo->query("SELECT * FROM payment_settings ORDER BY id DESC LIMIT 1");
$payment_settings = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    $amount = (int)$_POST['amount'];
    
    if ($amount < $exam['price']) {
        setFlash('Jumlah pembayaran tidak sesuai', 'danger');
        redirect('student/pay.php?exam_id=' . $exam_id);
    }
    
    $payment_proof = null;
    
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
        $upload_dir = '../../storage/uploads/payments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = 'payment_' . $user['id'] . '_' . $exam_id . '_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES['payment_proof']['tmp_name'], $upload_dir . $new_filename)) {
                $payment_proof = $new_filename;
            }
        }
    }
    
    if ($existing_payment && $existing_payment['status'] === 'pending') {
        $stmt = $pdo->prepare("UPDATE payments SET amount = ?, payment_proof = COALESCE(?, payment_proof), updated_at = NOW() WHERE id = ?");
        $stmt->execute([$amount, $payment_proof, $existing_payment['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO payments (user_id, exam_id, amount, payment_proof, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$user['id'], $exam_id, $amount, $payment_proof]);
    }
    
    setFlash('Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.', 'success');
    redirect('student/exams.php');
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Pembayaran Try Out</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <h2><?php echo htmlspecialchars($exam['title']); ?></h2>
        <p class="text-muted"><?php echo htmlspecialchars($exam['description'] ?? ''); ?></p>
        
        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
            <h3 style="color: var(--primary-color); margin-bottom: 1rem;">💰 Harga: Rp <?php echo number_format($exam['price'], 0, ',', '.'); ?></h3>
        </div>
        
        <?php if ($existing_payment): ?>
        <div class="alert alert-<?php echo $existing_payment['status'] === 'approved' ? 'success' : ($existing_payment['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
            <?php if ($existing_payment['status'] === 'approved'): ?>
                ✅ Pembayaran Anda sudah disetujui! Silakan mulai Try Out.
            <?php elseif ($existing_payment['status'] === 'rejected'): ?>
                ❌ Pembayaran Anda ditolak. Silakan upload bukti pembayaran yang benar.
            <?php else: ?>
                ⏳ Pembayaran Anda sedang menunggu verifikasi admin.
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($existing_payment && $existing_payment['status'] === 'approved'): ?>
            <a href="<?php echo url('student/exam_detail.php?id=' . $exam_id); ?>" class="btn btn-primary">Mulai Try Out</a>
            <a href="<?php echo url('student/exams.php'); ?>" class="btn btn-secondary">Kembali</a>
        <?php else: ?>
        
        <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Cara Pembayaran</h3>
        
        <?php if ($payment_settings): ?>
            <?php if ($payment_settings['qris_image']): ?>
            <div style="margin-bottom: 2rem;">
                <h4>Scan QRIS</h4>
                <img src="<?php echo url('uploads/payment/' . $payment_settings['qris_image']); ?>" alt="QRIS" style="max-width: 300px; border: 1px solid #ddd; padding: 10px; border-radius: 8px;">
            </div>
            <?php endif; ?>
            
            <?php if ($payment_settings['bank_name']): ?>
            <div style="margin-bottom: 2rem;">
                <h4>Transfer Bank</h4>
                <table class="table" style="max-width: 500px;">
                    <tr>
                        <td><strong>Bank</strong></td>
                        <td><?php echo htmlspecialchars($payment_settings['bank_name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>No. Rekening</strong></td>
                        <td><?php echo htmlspecialchars($payment_settings['account_number']); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Atas Nama</strong></td>
                        <td><?php echo htmlspecialchars($payment_settings['account_name']); ?></td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if ($payment_settings['payment_instructions']): ?>
            <div style="margin-bottom: 2rem;">
                <h4>Instruksi Pembayaran</h4>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; white-space: pre-line;">
                    <?php echo htmlspecialchars($payment_settings['payment_instructions']); ?>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <h3 style="margin-top: 2rem; margin-bottom: 1rem;">Upload Bukti Pembayaran</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <?php echo csrf(); ?>
            
            <div class="form-group">
                <label>Jumlah Pembayaran (Rp)</label>
                <input type="number" name="amount" value="<?php echo $exam['price']; ?>" min="<?php echo $exam['price']; ?>" required class="form-control">
            </div>
            
            <div class="form-group">
                <label>Bukti Pembayaran (JPG, PNG, PDF)</label>
                <input type="file" name="payment_proof" accept="image/jpeg,image/jpg,image/png,application/pdf" required class="form-control">
                <small class="text-muted">Format: JPG, PNG, atau PDF. Maksimal 5MB.</small>
            </div>
            
            <button type="submit" class="btn btn-primary">Upload Bukti Pembayaran</button>
            <a href="<?php echo url('student/exams.php'); ?>" class="btn btn-secondary">Kembali</a>
        </form>
        
        <?php endif; ?>
    </div>
</div>

<?php include '../../app/Views/includes/footer.php'; ?>
