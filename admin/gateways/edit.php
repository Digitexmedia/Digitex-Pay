<?php
require "../auth.php";
require_once "../../api/config/database.php";

$id = $_GET['id'];
$stmt = $db->prepare("SELECT * FROM payment_gateways WHERE id=?");
$stmt->execute([$id]);
$gateway = $stmt->fetch();

if ($_POST) {
    $stmt = $db->prepare("
        UPDATE payment_gateways SET
            client_id=?,
            client_secret=?,
            api_key=?,
            status=?
        WHERE id=?
    ");
    $stmt->execute([
        $_POST['client_id'],
        $_POST['client_secret'],
        $_POST['api_key'],
        $_POST['status'],
        $id
    ]);
    header("Location: index.php");
    exit;
}
?>

<h2>Edit <?= strtoupper($gateway['name']) ?></h2>

<form method="post">
    Client ID / Public Key<br>
    <input name="client_id" value="<?= $gateway['client_id'] ?>"><br><br>

    Client Secret / Secret Key<br>
    <input name="client_secret" value="<?= $gateway['client_secret'] ?>"><br><br>

    API Key (optional)<br>
    <input name="api_key" value="<?= $gateway['api_key'] ?>"><br><br>

    Status<br>
    <select name="status">
        <option value="1" <?= $gateway['status']?'selected':'' ?>>Enable</option>
        <option value="0" <?= !$gateway['status']?'selected':'' ?>>Disable</option>
    </select><br><br>

    <button>Save</button>
</form>

<a href="index.php">Back</a>
