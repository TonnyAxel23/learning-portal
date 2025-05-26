<?php
require_once 'auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Learning Portal</a>
            <div class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <?php if (getUserRole() === 'admin'): ?>
                        <a class="nav-link" href="upload_material.php">Manage Materials</a>
                        <a class="nav-link" href="enroll.php">Manage Enrollments</a>
                    <?php elseif (getUserRole() === 'instructor'): ?>
                        <a class="nav-link" href="upload_material.php">Upload Materials</a>
                    <?php else: ?>
                        <a class="nav-link" href="enroll.php">Enroll in Course</a>
                    <?php endif; ?>
                    <a class="nav-link" href="logout.php">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="index.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>