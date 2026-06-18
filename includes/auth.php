<?php
require_once __DIR__ . '/db.php';

function checkAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function checkAgent() {
    if (!isset($_SESSION['agent_id'])) {
        header("Location: ../login.php");
        exit();
    }
    
    // Check if approved
    global $conn;
    $agent_id = $_SESSION['agent_id'];
    $stmt = mysqli_prepare($conn, "SELECT status FROM agents WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $agent_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        if ($row['status'] !== 'Approved') {
            session_destroy();
            header("Location: ../login.php?error=unapproved");
            exit();
        }
    } else {
        session_destroy();
        header("Location: ../login.php");
        exit();
    }
}

function checkCustomer() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['admin_id']) || isset($_SESSION['agent_id']) || isset($_SESSION['user_id']);
}

function getLoggedInRole() {
    if (isset($_SESSION['admin_id'])) return 'admin';
    if (isset($_SESSION['agent_id'])) return 'agent';
    if (isset($_SESSION['user_id'])) return 'user';
    return null;
}
?>
