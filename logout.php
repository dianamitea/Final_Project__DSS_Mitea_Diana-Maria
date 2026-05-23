<?php
require_once __DIR__ . '/includes/auth.php';
// Customer logout
session_unset();
session_destroy();
session_start();
setFlash('info', 'You have been logged out successfully.');
header('Location: /Final_Project__DSS_Mitea_Diana-Maria/index.php');
exit;
