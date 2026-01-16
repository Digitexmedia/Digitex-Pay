<?php
// Protect page
require_once "../auth.php";
require_once "../../api/config/database.php";

// Fetch gateways
$stmt = $db->query("SELECT * FROM payment_gateways ORDER BY name ASC");
$gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Gateways | Digitex Pay</title>
</head>
<body>

<h2>Payment Gateways</h2>

<table border="1" cellpadding="10" cellspacing="0">
    <tr>
        <th>#</th>
        <th>Gateway</th>
        <th>Status</th>
        <th>Action</th>
    </tr>

    <?php foreach ($gateways as $index => $gateway): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= strtoupper($gateway['name']) ?></td>
            <td>
                <?= $gateway['status'] == 1 ? 'Enabled' : 'Disabled' ?>
            </td>
            <td>
                <a href="edit.php?id=<?= $gateway['id'] ?>">Edit</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<br>
<a href="../dashboard.php">⬅ Back to Dashboard</a>

</body>
</html>
