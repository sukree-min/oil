<?php
session_start();

// Auto logout if inactive more than 15 minutes
$timeout = 15 * 60;

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $timeout)) {
    session_unset();
    session_destroy();
    header("Location: /ok/kch-oil/auth/login.php?timeout=1");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /ok/kch-oil/auth/login.php");
        exit;
    }
}

function requireRole($roles = []) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: /ok/kch-oil/auth/login.php");
        exit;
    }

    if (!in_array($_SESSION['role_id'], $roles)) {
        header("Location: /ok/kch-oil/unauthorized.php");  //สำหรับการแจ้งเตือนแต่ละหน้า
        exit;
    }
}
?>