<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['agent_id']);
unset($_SESSION['agent_name']);
header("Location: ../login.php");
exit();
?>
