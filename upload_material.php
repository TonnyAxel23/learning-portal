<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

restrictAccess(['admin', 'instructor']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $title = $_POST['title'];
    $file = $_FILES['material'];

    // Validate file
    $allowed_types = ['application/pdf', 'application/msword', 'video/mp4'];
    $max_size = 10 * 1024 * 1024; // 10MB
    if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
        die("Invalid file type or size. Allowed: PDF, Doc, MP4 (max 10MB).");
    }

    // Secure file path
    $file_name = time() . '_' . basename($file['name']);
    $file_path = 'uploads/' . $file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $stmt = $pdo->prepare("INSERT INTO materials (course_id, title, file_path) VALUES (?, ?, ?)");
        $stmt->execute([$course_id, $title, $file_path]);
        echo "Material uploaded successfully!";
    } else {
        echo "Failed to upload file.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Material</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Upload Course Material</h2>
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="course_id" class="form-label">Course</label>
                <select name="course_id" class="form-select" required>
                    <?php
                    $stmt = $pdo->query("SELECT id, title FROM courses");
                    while ($course = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<option value='{$course['id']}'>{$course['title']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="title" class="form-label">Material Title</label>
                <input type="text" name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="material" class="form-label">Upload File</label>
                <input type="file" name="material" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Upload</button>
        </form>
    </div>
</body>
</html>