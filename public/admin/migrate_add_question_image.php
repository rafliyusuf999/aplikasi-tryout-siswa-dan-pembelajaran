<?php
require_once '../../config/config.php';

requireAuth('admin');

$pdo = getDB();

try {
    $stmt = $pdo->query("PRAGMA table_info(questions)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasQuestionImage = false;
    foreach ($columns as $column) {
        if ($column['name'] === 'question_image') {
            $hasQuestionImage = true;
            break;
        }
    }
    
    if (!$hasQuestionImage) {
        $pdo->exec("ALTER TABLE questions ADD COLUMN question_image VARCHAR(255)");
        echo "✅ Kolom question_image berhasil ditambahkan ke tabel questions<br>";
    } else {
        echo "ℹ️ Kolom question_image sudah ada di tabel questions<br>";
    }
    
    echo "<br><a href='" . url('admin/exams.php') . "'>← Kembali ke Daftar Try Out</a>";
    
} catch (PDOException $e) {
    die("❌ Error: " . $e->getMessage());
}
