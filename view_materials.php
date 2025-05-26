<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

restrictAccess(['student']);

$course_id = $_GET['course_id'] ?? null;
$user_id = $_SESSION['user_id'];

// Update last_accessed in enrollments
$stmt = $pdo->prepare("UPDATE enrollments SET last_accessed = NOW() WHERE user_id = ? AND course_id = ?");
$stmt->execute([$user_id, $course_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_complete'])) {
    $material_id = $_POST['material_id'];
    $stmt = $pdo->prepare("INSERT INTO progress (user_id, material_id, is_completed) VALUES (?, ?, TRUE)
                           ON DUPLICATE KEY UPDATE is_completed = TRUE");
    $stmt->execute([$user_id, $material_id]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $material_id = $_POST['material_id'];
    $comment = $_POST['comment'];
    $stmt = $pdo->prepare("INSERT INTO comments (material_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->execute([$material_id, $user_id, $comment]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Materials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Course Materials</h2>
        <?php
        $stmt = $pdo->prepare("SELECT m.*, p.is_completed FROM materials m
                               LEFT JOIN progress p ON m.id = p.material_id AND p.user_id = ?
                               WHERE m.course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($materials as $material) {
            $completed = $material['is_completed'] ? 'Completed' : 'Mark as Completed';
            echo "<div class='card mb-3'>";
            echo "<div class='card-body'>";
            echo "<h5 class='card-title'>{$material['title']}</h5>";
            echo "<p><a href='{$material['file_path']}' target='_blank'>View Material</a></p>";
            echo "<form method='POST' class='d-inline'>";
            echo "<input type='hidden' name='material_id' value='{$material['id']}'>";
            echo "<button type='submit' name='mark_complete' class='btn btn-sm btn-success'>$completed</button>";
            echo "</form>";

            // Comment Section
            echo "<div class='mt-3'>";
            echo "<h6>Comments</h6>";
            $stmt = $pdo->prepare("SELECT c.comment, c.posted_at, u.name FROM comments c
                                   JOIN users u ON c.user_id = u.id
                                   WHERE c.material_id = ?");
            $stmt->execute([$material['id']]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($comments as $comment) {
                echo "<p><strong>{$comment['name']}</strong> ({$comment['posted_at']}): {$comment['comment']}</p>";
            }
            echo "<form method='POST'>";
            echo "<input type='hidden' name='material_id' value='{$material['id']}'>";
            echo "<div class='input-group'>";
            echo "<textarea name='comment' class='form-control' required></textarea>";
            echo "<button type='submit' class='btn btn-primary'>Post Comment</button>";
            echo "</div>";
            echo "</form>";
            echo "</div>";

            echo "</div></div>";
        }

        // Progress Bar
        $stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(is_completed) as completed
                               FROM materials m
                               LEFT JOIN progress p ON m.id = p.material_id AND p.user_id = ?
                               WHERE m.course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        $percentage = $progress['total'] ? ($progress['completed'] / $progress['total']) * 100 : 0;
        ?>
        <div class="progress mt-3">
            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%;" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                <?php echo round($percentage); ?>%
            </div>
        </div>
    </div>
</body>
</html>