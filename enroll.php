<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

restrictAccess(['admin', 'student']); // Admins can enroll others, students can enroll themselves

$user_id = $_SESSION['user_id'];
$role = getUserRole();
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $target_user_id = ($role === 'admin' && isset($_POST['user_id'])) ? $_POST['user_id'] : $user_id;

    // Check if already enrolled
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->execute([$target_user_id, $course_id]);
    if ($stmt->fetch()) {
        $message = "User is already enrolled in this course.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO enrollments (user_id, course_id) VALUES (?, ?)");
        $stmt->execute([$target_user_id, $course_id]);
        $message = "Enrollment successful!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Enroll in Course</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h2>Enroll in a Course</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="course_id" class="form-label">Select Course</label>
                <select name="course_id" class="form-select" required>
                    <?php
                    $stmt = $pdo->query("SELECT id, title FROM courses");
                    while ($course = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$course['id']}'>{$course['title']}</option>";
                    }
                    ?>
                </select>
            </div>
            <?php if ($role === 'admin'): ?>
                <div class="mb-3">
                    <label for="user_id" class="form-label">Select User</label>
                    <select name="user_id" class="form-select" required>
                        <?php
                        $stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'student'");
                        while ($user = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='{$user['id']}'>{$user['name']}</option>";
                        }
                        ?>
                    </select>
                </div>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Enroll</button>
        </form>
    </div>
    <?php include 'includes/footer.php'; ?>
</body>
</html>