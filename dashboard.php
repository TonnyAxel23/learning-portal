<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$role = getUserRole();
$user_id = $_SESSION['user_id'];
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

// Fetch enrolled courses with progress
// Requires 'category' column in 'courses' and 'last_accessed' in 'enrollments' (see sql/schema.sql)
$stmt = $pdo->prepare("
    SELECT c.id, c.title, c.category, e.last_accessed,
           COUNT(m.id) as total_materials,
           SUM(p.is_completed) as completed_materials
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    LEFT JOIN materials m ON c.id = m.course_id
    LEFT JOIN progress p ON m.id = p.material_id AND p.user_id = e.user_id
    WHERE e.user_id = ? AND c.title LIKE ? AND (? = '' OR c.category = ?)
    GROUP BY c.id
");
$stmt->execute([$user_id, "%$search%", $category, $category]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch recent activity (last accessed courses)
$stmt = $pdo->prepare("
    SELECT c.id, c.title, e.last_accessed
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.user_id = ? AND e.last_accessed IS NOT NULL
    ORDER BY e.last_accessed DESC
    LIMIT 3
");
$stmt->execute([$user_id]);
$recent_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all categories for filter
$stmt = $pdo->query("SELECT DISTINCT category FROM courses");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Learning Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <h4 class="text-center"><?php echo htmlspecialchars($_SESSION['name']); ?></h4>
                    <p class="text-center text-muted"><?php echo htmlspecialchars($_SESSION['role']); ?></p>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">Dashboard</a>
                        </li>
                        <?php if ($role === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="upload_material.php">Manage Materials</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="enroll.php">Manage Enrollments</a>
                            </li>
                        <?php elseif ($role === 'instructor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="upload_material.php">Upload Materials</a>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="enroll.php">Enroll in Course</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="enroll.php" class="btn btn-primary">Enroll in New Course</a>
                    </div>
                </div>

                <?php if ($role === 'student'): ?>
                    <!-- Search and Filter -->
                    <div class="mb-4">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>

                    <!-- Recent Activity -->
                    <?php if ($recent_courses): ?>
                        <h3>Recently Accessed</h3>
                        <div class="row mb-4">
                            <?php foreach ($recent_courses as $course): ?>
                                <div class="col-md-4">
                                    <div class="card course-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <p class="card-text text-muted">Last accessed: <?php echo $course['last_accessed'] ? date('M d, Y H:i', strtotime($course['last_accessed'])) : 'Never'; ?></p>
                                            <a href="view_materials.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">Continue</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Enrolled Courses -->
                    <h3>Your Courses</h3>
                    <?php if ($courses): ?>
                        <div class="row">
                            <?php foreach ($courses as $course): ?>
                                <?php
                                $progress = $course['total_materials'] ? ($course['completed_materials'] / $course['total_materials']) * 100 : 0;
                                $badge_class = $progress < 100 ? 'bg-warning' : 'bg-success';
                                ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card course-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <p class="card-text text-muted"><?php echo htmlspecialchars($course['category']); ?></p>
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div class="progress-bar <?php echo $badge_class; ?>" role="progressbar" style="width: <?php echo $progress; ?>%;" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                                    <?php echo round($progress); ?>%
                                                </div>
                                            </div>
                                            <a href="view_materials.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary">View Course</a>
                                            <?php if ($progress == 100): ?>
                                                <a href="certificate.php?course_id=<?php echo $course['id']; ?>" class="btn btn-outline-success mt-2">Download Certificate</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">You are not enrolled in any courses. <a href="enroll.php">Enroll now</a>.</p>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Admin/Instructor Dashboard -->
                    <h3><?php echo $role === 'admin' ? 'Admin' : 'Instructor'; ?> Dashboard</h3>
                    <div class="row">
                        <?php if ($role === 'admin'): ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Manage Materials</h5>
                                        <p class="card-text">Upload or edit course materials.</p>
                                        <a href="upload_material.php" class="btn btn-primary">Go to Materials</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Manage Enrollments</h5>
                                        <p class="card-text">Enroll users into courses.</p>
                                        <a href="enroll.php" class="btn btn-primary">Go to Enrollments</a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Upload Materials</h5>
                                        <p class="card-text">Add content to your courses.</p>
                                        <a href="upload_material.php" class="btn btn-primary">Go to Uploads</a>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
