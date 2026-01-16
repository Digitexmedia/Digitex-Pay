<?php
require "../auth.php";
require_once "../../api/config/database.php";

$payments = $db->query("
    SELECT * FROM payments ORDER BY id DESC LIMIT 100
")->fetchAll();
?>

<h2>Transactions</h2>

<table border="1" cellpadding="8">
<tr>
    <th>ID</th>
    <th>Reference</th>
    <th>Gateway</th>
    <th>Amount</th>
    <th>Status</th>
    <th>Date</th>
</tr>

<?php foreach ($payments as $p): ?>
<tr>
    <td><?= $p['id'] ?></td>
    <td><?= $p['payment_extra'] ?></td>
    <td><?= strtoupper($p['payment_method']) ?></td>
    <td><?= $p['payment_amount'] ?></td>
    <td><?= $p['payment_status'] ?></td>
    <td><?= $p['payment_create_date'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<a href="../dashboard.php">Back</a>
