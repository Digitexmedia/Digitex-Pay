<?php
require "../auth.php";
require_once "../../api/config/database.php";

$gateways = $db->query("SELECT * FROM payment_gateways")->fetchAll();
?>

<h2>Payment Gateways</h2>

<table border="1" cellpadding="10">
<tr>
    <th>Name</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php foreach ($gateways as $g): ?>
<tr>
    <td><?= strtoupper($g['name']) ?></td>
    <td><?= $g['status'] ? "Enabled" : "Disabled" ?></td>
    <td><a href="edit.php?id=<?= $g['id'] ?>">Edit</a></td>
</tr>
<?php endforeach; ?>
</table>

<a href="../dashboard.php">Back</a>
