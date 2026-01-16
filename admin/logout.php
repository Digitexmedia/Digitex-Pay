<?php
session_start();

// Destroy admin session
session_unset();
session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
