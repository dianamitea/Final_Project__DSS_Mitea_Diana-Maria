<?php
require_once dirname(__DIR__) . '/includes/auth.php';
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();
session_start();
setFlash('info', 'You have been logged out from the admin panel.');
header('Location: /Final_Project__DSS_Mitea_Diana-Maria/admin/login.php');
exit;
