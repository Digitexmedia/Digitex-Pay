<?php
/**
 * M-PESA STK CALLBACK WEBHOOK
 * This file is called ONLY by Safaricom
 */

header("Content-Type: application/json");

// ================================
// CONNECT TO DATABASE
// ================================
require_once __DIR__ . "/../config/database.php";

// ================================
// READ RAW CALLBACK DATA
// ================================
$rawData = file_get_contents("php://input");

// ALWAYS log callback (important for debugging)
file_put_contents(
    __DIR__ . "/mpesa.log",
    date("Y-m-d H:i:s ") . $rawData . PHP_EOL,
    FILE_APPEND
);

// Decode JSON
$data = json_decode($rawData, true);

// Validate structure
if (!isset($data['Body']['stkCallback'])) {
    echo json_encode([
        "ResultCode" => 0,
        "ResultDesc" => "Invalid callback"
    ]);
    exit;
}

$callback = $data['Body']['stkCallback'];

$checkoutRequestID = $callback['CheckoutRequestID'];
$resultCode        = $callback['ResultCode'];
$resultDesc        = $callback['ResultDesc'];

// Defaults
$amount = null;
$receipt = null;
$phone = null;

// Extract payment details if successful
if ($resultCode == 0 && isset($callback['CallbackMetadata']['Item'])) {
    foreach ($callback['CallbackMetadata']['Item'] as $item) {
        if ($item['Name'] === 'Amount') {
            $amount = $item['Value'];
        }
        if ($item['Name'] === 'MpesaReceiptNumber') {
            $receipt = $item['Value'];
        }
        if ($item['Name'] === 'PhoneNumber') {
            $phone = $item['Value'];
        }
    }
}

// ================================
// UPDATE PAYMENT IN DATABASE
// ================================
$status = ($resultCode == 0) ? "SUCCESS" : "FAILED";

$stmt = $db->prepare("
    UPDATE payments SET
        payment_status = :status,
        mpesa_receipt  = :receipt,
        phone          = :phone,
        result_desc    = :result_desc,
        updated_at     = NOW()
    WHERE checkout_request_id = :checkout_id
");

$stmt->execute([
    "status"       => $status,
    "receipt"      => $receipt,
    "phone"        => $phone,
    "result_desc"  => $resultDesc,
    "checkout_id"  => $checkoutRequestID
]);

// ================================
// RESPOND TO SAFARICOM (MANDATORY)
// ================================
echo json_encode([
    "ResultCode" => 0,
    "ResultDesc" => "Callback received successfully"
]);
