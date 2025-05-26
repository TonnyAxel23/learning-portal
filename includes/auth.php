<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function restrictAccess($allowedRoles) {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
    if (!in_array(getUserRole(), $allowedRoles)) {
        die("Access denied. You do not have permission to view this page.");
    }
}

function login($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        return true;
    }
    return false;
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>