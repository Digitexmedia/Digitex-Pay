<?php
/**
 * BINANCE PAY WEBHOOK
 * Called ONLY by Binance Pay servers
 */

header("Content-Type: application/json");

// ================================
// CONNECT TO DATABASE
// ================================
require_once __DIR__ . "/../config/database.php";

// ================================
// READ RAW INPUT
// ================================
$rawData = file_get_contents("php://input");

// ALWAYS log callback (very important)
file_put_contents(
    __DIR__ . "/binance.log",
    date("Y-m-d H:i:s ") . $rawData . PHP_EOL,
    FILE_APPEND
);

// Decode JSON
$data = json_decode($rawData, true);

if (!$data || !isset($data['bizStatus'], $data['merchantTradeNo'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid payload"]);
    exit;
}

$merchantTradeNo = $data['merchantTradeNo']; // your reference
$bizStatus       = strtoupper($data['bizStatus']); // PAY_SUCCESS, PAY_FAILED, etc.

// ================================
// MAP BINANCE STATUS
// ================================
switch ($bizStatus) {
    case "PAY_SUCCESS":
        $finalStatus = "SUCCESS";
        break;

    case "PAY_FAILED":
    case "EXPIRED":
    case "CLOSED":
        $finalStatus = "FAILED";
        break;

    default:
        $finalStatus = "PENDING";
        break;
}

// ================================
// UPDATE PAYMENT IN DATABASE
// ================================
$stmt = $db->prepare("
    UPDATE payments SET
        payment_status = :status,
        updated_at = NOW()
    WHERE payment_extra = :reference
");

$stmt->execute([
    "status"    => $finalStatus,
    "reference" => $merchantTradeNo
]);

// ================================
// RESPOND TO BINANCE
// ================================
echo json_encode([
    "returnCode" => "SUCCESS",
    "returnMessage" => "OK"
]);
