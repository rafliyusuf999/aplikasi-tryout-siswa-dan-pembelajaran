<?php
require_once '../../config/config.php';

requireAnyRole(['admin', 'teacher']);

$pdo = getDB();
$pageTitle = 'Kelola Pembayaran';

$status_filter = $_GET['status'] ?? 'approved';
$search = $_GET['search'] ?? '';

$query = "SELECT p.*, u.full_name as student_name, u.email as student_email, 
          e.title as exam_title, e.price as exam_price
          FROM payments p
          JOIN users u ON p.user_id = u.id
          JOIN exams e ON p.exam_id = e.id
          WHERE p.status = :status";

if ($search) {
    $query .= " AND (u.full_name LIKE :search OR u.email LIKE :search OR e.title LIKE :search)";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$params = ['status' => $status_filter];
if ($search) {
    $params['search'] = "%$search%";
}
$stmt->execute($params);
$payments = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, full_name, email FROM users WHERE role = 'student' ORDER BY full_name");
$students = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, title, price FROM exams WHERE is_premium = true ORDER BY title");
$exams = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'approve' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $user = getCurrentUser();
            
            $stmt = $pdo->prepare("UPDATE payments SET status = 'approved', approved_at = datetime('now'), approved_by = ? WHERE id = ?");
            $stmt->execute([$user['id'], $id]);
            
            setFlash('Pembayaran berhasil disetujui', 'success');
            redirect('admin/payments.php?status=' . $status_filter);
        }
        
        if ($_POST['action'] === 'approve_all') {
            $user = getCurrentUser();
            
            $stmt = $pdo->prepare("UPDATE payments SET status = 'approved', approved_at = datetime('now'), approved_by = ? WHERE status = 'pending'");
            $stmt->execute([$user['id']]);
            $affected = $stmt->rowCount();
            
            setFlash("Berhasil menyetujui $affected pembayaran", 'success');
            redirect('admin/payments.php?status=approved');
        }
        
        if ($_POST['action'] === 'reject_all') {
            $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected' WHERE status = 'pending'");
            $stmt->execute();
            $affected = $stmt->rowCount();
            
            setFlash("Berhasil menolak $affected pembayaran", 'success');
            redirect('admin/payments.php?status=rejected');
        }
        
        if ($_POST['action'] === 'reject' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            
            $stmt = $pdo->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlash('Pembayaran ditolak', 'success');
            redirect('admin/payments.php?status=' . $status_filter);
        }
        
        if ($_POST['action'] === 'add') {
            $user_id = (int)$_POST['user_id'];
            $exam_id = (int)$_POST['exam_id'];
            $amount = (int)$_POST['amount'];
            $current_user = getCurrentUser();
            
            $stmt = $pdo->prepare("INSERT INTO payments (user_id, exam_id, amount, status, approved_at, approved_by) VALUES (?, ?, ?, 'approved', datetime('now'), ?)");
            $stmt->execute([$user_id, $exam_id, $amount, $current_user['id']]);
            
            setFlash('Pembayaran manual berhasil ditambahkan', 'success');
            redirect('admin/payments.php?status=approved');
        }
        
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
            $stmt->execute([$id]);
            
            setFlash('Pembayaran berhasil dihapus', 'success');
            redirect('admin/payments.php?status=' . $status_filter);
        }
    }
}

include '../../app/Views/includes/header.php';
include '../../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1>Kelola Pembayaran</h1>
    
    <div class="card" style="margin-top: 1.5rem;">
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
            <a href="?status=approved" class="btn <?php echo $status_filter === 'approved' ? 'btn-primary' : 'btn-secondary'; ?>">
                Disetujui
            </a>
            <a href="?status=rejected" class="btn <?php echo $status_filter === 'rejected' ? 'btn-primary' : 'btn-secondary'; ?>">
                Ditolak
            </a>
            <?php if (getCurrentUser()['role'] === 'admin'): ?>
            <a href="<?php echo url('admin/payment_settings.php'); ?>" class="btn btn-warning" style="margin-left: auto;">
                ⚙️ Pengaturan Pembayaran
            </a>
            <?php endif; ?>
        </div>
        
        <form method="GET" style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari pembayaran..." class="form-control" style="flex: 1;">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?php echo url('admin/payments.php?status=' . $status_filter); ?>" class="btn btn-secondary">Reset</a>
        </form>
        
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <?php if (getCurrentUser()['role'] === 'admin'): ?>
            <button onclick="showAddModal()" class="btn btn-primary">+ Tambah Pembayaran Manual</button>
            <?php 
            $stmt_pending_count = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'");
            $pending_count = $stmt_pending_count->fetchColumn();
            if ($pending_count > 0): 
            ?>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Setujui semua <?php echo $pending_count; ?> pembayaran pending?')">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="approve_all">
                <button type="submit" class="btn btn-success" style="font-size: 1.1rem; padding: 0.6rem 1.5rem;">
                    ✓ Setujui Semua (<?php echo $pending_count; ?> Pending)
                </button>
            </form>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Tolak semua <?php echo $pending_count; ?> pembayaran pending?')">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="reject_all">
                <button type="submit" class="btn btn-danger" style="font-size: 1.1rem; padding: 0.6rem 1.5rem;">
                    ✗ Tolak Semua (<?php echo $pending_count; ?> Pending)
                </button>
            </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Try Out</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Bukti</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars($payment['student_name']); ?><br>
                            <small><?php echo htmlspecialchars($payment['student_email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($payment['exam_title']); ?></td>
                        <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $payment['status'] === 'approved' ? 'success' : 
                                    ($payment['status'] === 'rejected' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($payment['payment_proof']): ?>
                                <?php 
                                $file_ext = strtolower(pathinfo($payment['payment_proof'], PATHINFO_EXTENSION));
                                $image_url = url('storage/uploads/payments/' . $payment['payment_proof']);
                                ?>
                                <?php if (in_array($file_ext, ['jpg', 'jpeg', 'png'])): ?>
                                    <img src="<?php echo $image_url; ?>" 
                                         alt="Bukti Pembayaran" 
                                         style="width: 80px; height: 80px; object-fit: cover; cursor: pointer; border-radius: 4px; border: 2px solid #ddd;" 
                                         onclick="showPaymentProof(<?php echo $payment['id']; ?>, '<?php echo $image_url; ?>', <?php echo $payment['status'] === 'pending' ? 'true' : 'false'; ?>, 'image')">
                                <?php else: ?>
                                    <a href="<?php echo $image_url; ?>" target="_blank" class="btn btn-sm btn-info" onclick="showPaymentProof(<?php echo $payment['id']; ?>, '<?php echo $image_url; ?>', <?php echo $payment['status'] === 'pending' ? 'true' : 'false'; ?>, 'pdf'); return false;">Lihat PDF</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-secondary" onclick="showPaymentProof(<?php echo $payment['id']; ?>, '', true, 'none')">Lihat</button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (getCurrentUser()['role'] === 'admin'): ?>
                            <form method="POST" style="display: inline;">
                                <?php echo csrf(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $payment['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Yakin hapus pembayaran ini?')">Hapus</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div id="proofModal" class="modal">
    <div class="modal-content" style="max-width: 800px;">
        <span class="close" onclick="closeModal('proofModal')">&times;</span>
        <h2>Bukti Pembayaran</h2>
        <div style="text-align: center; margin: 1.5rem 0;">
            <img id="proofImage" src="" alt="Bukti Pembayaran" style="max-width: 100%; max-height: 500px; border: 2px solid #ddd; border-radius: 8px; display: none;">
            <div id="proofPdf" style="display: none;">
                <p style="font-size: 1.2rem; color: #1a1a1a; margin: 2rem 0;">📄 Bukti pembayaran berupa file PDF</p>
                <a id="proofPdfLink" href="" target="_blank" class="btn btn-info">Buka PDF di Tab Baru</a>
            </div>
            <div id="proofNone" style="display: none;">
                <p style="font-size: 1.2rem; color: #1a1a1a; margin: 2rem 0;">⚠️ Tidak ada bukti pembayaran yang diupload</p>
            </div>
        </div>
        <div id="proofActions" style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem;">
            <form method="POST" id="approveForm">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="id" id="approvePaymentId">
                <button type="submit" class="btn btn-success" style="font-size: 1.1rem; padding: 0.8rem 2rem;">✓ Setujui</button>
            </form>
            <form method="POST" id="rejectForm">
                <?php echo csrf(); ?>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" id="rejectPaymentId">
                <button type="submit" class="btn btn-danger" style="font-size: 1.1rem; padding: 0.8rem 2rem;">✗ Tolak</button>
            </form>
        </div>
    </div>
</div>

<?php if (getCurrentUser()['role'] === 'admin'): ?>
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addModal')">&times;</span>
        <h2>Tambah Pembayaran Manual</h2>
        <p class="text-muted">Untuk siswa yang membayar langsung atau mendapat beasiswa</p>
        <form method="POST">
            <?php echo csrf(); ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Pilih Siswa</label>
                <select name="user_id" required class="form-control">
                    <option value="">-- Pilih Siswa --</option>
                    <?php foreach ($students as $student): ?>
                    <option value="<?php echo $student['id']; ?>">
                        <?php echo htmlspecialchars($student['full_name'] . ' (' . $student['email'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Pilih Try Out</label>
                <select name="exam_id" id="exam_select" required class="form-control" onchange="updateAmount()">
                    <option value="">-- Pilih Try Out --</option>
                    <?php foreach ($exams as $exam): ?>
                    <option value="<?php echo $exam['id']; ?>" data-price="<?php echo $exam['price']; ?>">
                        <?php echo htmlspecialchars($exam['title'] . ' - Rp ' . number_format($exam['price'], 0, ',', '.')); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Jumlah Pembayaran (Rp)</label>
                <input type="number" name="amount" id="amount_input" value="0" required class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function showAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function showPaymentProof(paymentId, imageUrl, isPending, type) {
    document.getElementById('proofImage').style.display = 'none';
    document.getElementById('proofPdf').style.display = 'none';
    document.getElementById('proofNone').style.display = 'none';
    
    if (type === 'image') {
        document.getElementById('proofImage').src = imageUrl;
        document.getElementById('proofImage').style.display = 'block';
    } else if (type === 'pdf') {
        document.getElementById('proofPdfLink').href = imageUrl;
        document.getElementById('proofPdf').style.display = 'block';
    } else {
        document.getElementById('proofNone').style.display = 'block';
    }
    
    document.getElementById('approvePaymentId').value = paymentId;
    document.getElementById('rejectPaymentId').value = paymentId;
    
    if (isPending) {
        document.getElementById('proofActions').style.display = 'flex';
    } else {
        document.getElementById('proofActions').style.display = 'none';
    }
    
    document.getElementById('proofModal').style.display = 'block';
}

function updateAmount() {
    const select = document.getElementById('exam_select');
    const selectedOption = select.options[select.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || 0;
    document.getElementById('amount_input').value = price;
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
