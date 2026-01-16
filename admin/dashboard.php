<?php
require "auth.php";
require_once "../api/config/database.php";

$totalPayments = $db->query("SELECT COUNT(*) FROM payments")->fetchColumn();
$success = $db->query("SELECT COUNT(*) FROM payments WHERE payment_status='SUCCESS'")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM payments WHERE payment_status='PENDING'")->fetchColumn();
?>

<h1>Digitex Pay – Admin Dashboard</h1>

<ul>
    <li>Total Payments: <?= $totalPayments ?></li>
    <li>Successful: <?= $success ?></li>
    <li>Pending: <?= $pending ?></li>
</ul>

<a href="gateways/index.php">Manage Gateways</a> |
<a href="transactions/index.php">View Transactions</a> |
<a href="logout.php">Logout</a>
