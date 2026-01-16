<?php
session_start();
require_once "../api/config/database.php";

if ($_POST) {
    $stmt = $db->prepare("SELECT * FROM admins WHERE email=? LIMIT 1");
    $stmt->execute([$_POST['email']]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($_POST['password'], $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header("Location: dashboard.php");
        exit;
    }
    $error = "Invalid login details";
}
?>

<form method="post">
    <h2>Digitex Pay Admin</h2>
    <?= $error ?? "" ?><br><br>
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
</form>
