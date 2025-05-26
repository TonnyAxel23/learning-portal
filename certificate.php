<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'fpdf/fpdf.php'; // Ensure FPDF is in learning-portal/fpdf/

restrictAccess(['student']);
$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'] ?? null;

if (!$course_id) {
    die("Error: No course ID provided.");
}

// Check if all materials are completed
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total, SUM(p.is_completed) as completed
    FROM materials m
    LEFT JOIN progress p ON m.id = p.material_id AND p.user_id = ?
    WHERE m.course_id = ?
");
$stmt->execute([$user_id, $course_id]);
$progress = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$progress || $progress['total'] == 0) {
    die("Error: No materials found for this course.");
}

if ($progress['total'] == $progress['completed']) {
    $stmt = $pdo->prepare("SELECT title FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        die("Error: Course not found.");
    }

    // Generate PDF certificate
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->Cell(0, 10, 'Certificate of Completion', 0, 1, 'C');
    $pdf->Ln(20);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, "This certifies that " . htmlspecialchars($_SESSION['name']) . " has completed", 0, 1, 'C');
    $pdf->Cell(0, 10, htmlspecialchars($course['title']), 0, 1, 'C');
    $pdf->Cell(0, 10, "Date: " . date('Y-m-d'), 0, 1, 'C');
    $pdf->Output('D', 'certificate_' . $course_id . '.pdf');
} else {
    echo "Course not yet completed. Please complete all materials to download the certificate.";
}
?>
