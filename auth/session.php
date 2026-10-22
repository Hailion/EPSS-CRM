<?php
// auth/session.php

//session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); // Or wherever your login page is
    exit();
}

// Optionally, you can add more session-related checks here,
// such as checking for session timeouts or user roles.
?>