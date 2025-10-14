<?php
require_once '../config/config.php';

requireLogin();

$pdo = getDB();
$user = getCurrentUser();
$pageTitle = 'Peringkat';

$exam_id = $_GET['exam_id'] ?? null;
$view_type = $_GET['view'] ?? 'branch';

$stmt = $pdo->query("SELECT id, title FROM exams WHERE is_active = true ORDER BY created_at DESC");
$exams = $stmt->fetchAll();

$leaderboard = [];
$exam_title = '';
$branches = [];

if ($user['role'] === 'admin' || $user['role'] === 'teacher') {
    $stmt = $pdo->query("SELECT DISTINCT inspira_branch FROM users WHERE inspira_branch IS NOT NULL AND inspira_branch != '' ORDER BY inspira_branch");
    $branches = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if ($exam_id) {
    $stmt = $pdo->prepare("SELECT title FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $exam = $stmt->fetch();
    $exam_title = $exam['title'] ?? '';
    
    if ($view_type === 'branch') {
        if ($user['role'] === 'student') {
            $query = "SELECT ea.*, u.full_name, u.email, u.inspira_branch, u.profile_photo, u.class_level, u.school_name
                      FROM exam_attempts ea
                      JOIN users u ON ea.user_id = u.id
                      WHERE ea.exam_id = ? AND ea.is_completed = true 
                      AND u.inspira_branch = ?
                      AND ea.id = (
                          SELECT id FROM exam_attempts 
                          WHERE exam_id = ? AND user_id = ea.user_id AND is_completed = true 
                          ORDER BY finished_at ASC LIMIT 1
                      )
                      ORDER BY ea.total_score DESC, ea.finished_at ASC";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$exam_id, $user['inspira_branch'], $exam_id]);
        } else {
            $branch = $_GET['branch'] ?? '';
            if ($branch) {
                $query = "SELECT ea.*, u.full_name, u.email, u.inspira_branch, u.profile_photo, u.class_level, u.school_name
                          FROM exam_attempts ea
                          JOIN users u ON ea.user_id = u.id
                          WHERE ea.exam_id = ? AND ea.is_completed = true 
                          AND u.inspira_branch = ?
                          AND ea.id = (
                              SELECT id FROM exam_attempts 
                              WHERE exam_id = ? AND user_id = ea.user_id AND is_completed = true 
                              ORDER BY finished_at ASC LIMIT 1
                          )
                          ORDER BY ea.total_score DESC, ea.finished_at ASC";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$exam_id, $branch, $exam_id]);
            } else {
                $query = "SELECT ea.*, u.full_name, u.email, u.inspira_branch, u.profile_photo, u.class_level, u.school_name
                          FROM exam_attempts ea
                          JOIN users u ON ea.user_id = u.id
                          WHERE ea.exam_id = ? AND ea.is_completed = true
                          AND ea.id = (
                              SELECT id FROM exam_attempts 
                              WHERE exam_id = ? AND user_id = ea.user_id AND is_completed = true 
                              ORDER BY finished_at ASC LIMIT 1
                          )
                          ORDER BY ea.total_score DESC, ea.finished_at ASC";
                $stmt = $pdo->prepare($query);
                $stmt->execute([$exam_id, $exam_id]);
            }
        }
    } else {
        $query = "SELECT ea.*, u.full_name, u.email, u.inspira_branch, u.profile_photo, u.class_level, u.school_name
                  FROM exam_attempts ea
                  JOIN users u ON ea.user_id = u.id
                  WHERE ea.exam_id = ? AND ea.is_completed = true
                  AND ea.id = (
                      SELECT id FROM exam_attempts 
                      WHERE exam_id = ? AND user_id = ea.user_id AND is_completed = true 
                      ORDER BY finished_at ASC LIMIT 1
                  )
                  ORDER BY ea.total_score DESC, ea.finished_at ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$exam_id, $exam_id]);
    }
    
    $leaderboard = $stmt->fetchAll();
}

include '../app/Views/includes/header.php';
include '../app/Views/includes/navbar.php';
?>

<div class="container" style="margin-top: 2rem;">
    <h1 style="text-align: center; color: #1a1a1a; margin-bottom: 2rem;">🏆 Peringkat Try Out</h1>
    
    <div class="card" style="margin-top: 1.5rem; padding: 2rem;">
        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label style="font-size: 1.1rem; font-weight: 600; color: #1a1a1a; margin-bottom: 0.75rem; display: block;">📋 Pilih Try Out</label>
            <select id="exam_select" class="form-control" style="font-size: 1rem; padding: 0.75rem; border: 2px solid var(--primary-color); border-radius: 8px; transition: all 0.3s ease;" onchange="location.href='?exam_id=' + this.value + '&view=<?php echo $view_type; ?><?php echo isset($_GET['branch']) ? '&branch=' . urlencode($_GET['branch']) : ''; ?>'">
                <option value="">-- Pilih Try Out --</option>
                <?php foreach ($exams as $exam): ?>
                <option value="<?php echo $exam['id']; ?>" <?php echo $exam_id == $exam['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($exam['title']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <?php if ($exam_id): ?>
        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem; flex-wrap: wrap; align-items: center;">
            <?php if ($user['role'] === 'student'): ?>
                <a href="?exam_id=<?php echo $exam_id; ?>&view=branch" class="btn <?php echo $view_type === 'branch' ? 'btn-primary' : 'btn-secondary'; ?>" style="transition: all 0.3s ease;">
                    📍 Peringkat Cabang (<?php echo htmlspecialchars($user['inspira_branch']); ?>)
                </a>
                <a href="?exam_id=<?php echo $exam_id; ?>&view=global" class="btn <?php echo $view_type === 'global' ? 'btn-primary' : 'btn-secondary'; ?>" style="transition: all 0.3s ease;">
                    🌍 Peringkat Global
                </a>
            <?php else: ?>
                <a href="?exam_id=<?php echo $exam_id; ?>&view=global" class="btn <?php echo $view_type === 'global' ? 'btn-primary' : 'btn-secondary'; ?>" style="transition: all 0.3s ease;">
                    🌍 Peringkat Global
                </a>
                <a href="?exam_id=<?php echo $exam_id; ?>&view=branch" class="btn <?php echo $view_type === 'branch' ? 'btn-primary' : 'btn-secondary'; ?>" style="transition: all 0.3s ease;">
                    📍 Peringkat Per Cabang
                </a>
                <?php if ($view_type === 'branch' && count($branches) > 0): ?>
                <select id="branch_select" class="form-control" style="max-width: 250px; border: 2px solid var(--primary-color); border-radius: 8px;" onchange="location.href='?exam_id=<?php echo $exam_id; ?>&view=branch&branch=' + this.value">
                    <option value="">-- Pilih Cabang --</option>
                    <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo htmlspecialchars($branch); ?>" <?php echo ($_GET['branch'] ?? '') == $branch ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($branch); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($exam_id && count($leaderboard) > 0): ?>
        <h2 style="text-align: center; margin-bottom: 1.5rem; color: #1a1a1a; animation: fadeIn 0.5s ease-in;">
            <?php echo htmlspecialchars($exam_title); ?>
            <?php if ($view_type === 'branch'): ?>
                <?php if ($user['role'] === 'student'): ?>
                <br><small style="color: #2d2d2d;">(Cabang: <?php echo htmlspecialchars($user['inspira_branch']); ?>)</small>
                <?php elseif (isset($_GET['branch']) && $_GET['branch']): ?>
                <br><small style="color: #2d2d2d;">(Cabang: <?php echo htmlspecialchars($_GET['branch']); ?>)</small>
                <?php endif; ?>
            <?php endif; ?>
        </h2>
        
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Peringkat</th>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Sekolah</th>
                        <?php if ($view_type === 'global'): ?>
                        <th>Cabang/Provinsi</th>
                        <?php endif; ?>
                        <th>Soal Benar</th>
                        <th>Nilai</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach ($leaderboard as $entry): 
                        $is_current_user = ($entry['user_id'] == $user['id']);
                        $row_class = $is_current_user ? 'style="background: rgba(59, 130, 246, 0.1);"' : '';
                    ?>
                    <tr <?php echo $row_class; ?>>
                        <td style="font-size: 1.5rem; font-weight: bold;">
                            <?php if ($rank <= 3): ?>
                                <?php if ($rank == 1): ?>🥇
                                <?php elseif ($rank == 2): ?>🥈
                                <?php else: ?>🥉
                                <?php endif; ?>
                            <?php else: ?>
                                #<?php echo $rank; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <?php if (!empty($entry['profile_photo'])): ?>
                                <img src="<?php echo url('storage/uploads/profiles/' . $entry['profile_photo']); ?>" 
                                     alt="<?php echo htmlspecialchars($entry['full_name']); ?>" 
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                    <?php echo strtoupper(substr($entry['full_name'], 0, 1)); ?>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($entry['full_name']); ?></strong>
                                    <?php if ($is_current_user): ?>
                                    <span class="badge badge-primary">Anda</span>
                                    <?php endif; ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($entry['email']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 500;"><?php echo htmlspecialchars($entry['class_level'] ?? '-'); ?></span>
                        </td>
                        <td>
                            <span style="font-size: 0.9rem;"><?php echo htmlspecialchars($entry['school_name'] ?? '-'); ?></span>
                        </td>
                        <?php if ($view_type === 'global'): ?>
                        <td>
                            <span style="font-weight: 500; color: var(--primary-color);">
                                <?php echo htmlspecialchars($entry['inspira_branch'] ?? '-'); ?>
                            </span>
                        </td>
                        <?php endif; ?>
                        <td style="font-size: 1.1rem; font-weight: 600; color: #28a745;">
                            <?php echo $entry['correct_count'] ?? 0; ?>
                        </td>
                        <td style="font-size: 1.25rem; font-weight: bold; color: var(--primary-color);">
                            <?php echo number_format($entry['total_score'], 1); ?>
                        </td>
                        <td style="white-space: nowrap;">
                            <?php 
                            if ($entry['finished_at']) {
                                $start = new DateTime($entry['started_at'], new DateTimeZone('UTC'));
                                $end = new DateTime($entry['finished_at'], new DateTimeZone('UTC'));
                                $diff = $start->diff($end);
                                echo $diff->h . 'j ' . $diff->i . 'm';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
        </div>
        <?php elseif ($exam_id): ?>
        <div style="text-align: center; padding: 3rem; color: #1a1a1a;">
            <p>Belum ada data peringkat untuk Try Out ini</p>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 3rem; color: #1a1a1a;">
            <p>Silakan pilih Try Out untuk melihat peringkat</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../app/Views/includes/footer.php'; ?>
