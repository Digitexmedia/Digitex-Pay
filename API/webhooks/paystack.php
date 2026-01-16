<?php
/**
 * PAYSTACK WEBHOOK
 * Called ONLY by Paystack servers
 */

header("Content-Type: application/json");

// ================================
// CONNECT TO DATABASE
// ================================
require_once __DIR__ . "/../config/database.php";

// ================================
// PAYSTACK SECRET KEY
// (Store this securely in config / DB)
// ================================
$paystackSecretKey = PAYSTACK_SECRET_KEY;

// ================================
// READ RAW INPUT
// ================================
$rawInput = file_get_contents("php://input");

// ================================
// VERIFY PAYSTACK SIGNATURE (MANDATORY)
// ================================
$paystackSignature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

$computedSignature = hash_hmac('sha512', $rawInput, $paystackSecretKey);

if ($paystackSignature !== $computedSignature) {
    http_response_code(401);
    exit;
}

// ================================
// LOG CALLBACK (IMPORTANT)
// ================================
file_put_contents(
    __DIR__ . "/paystack.log",
    date("Y-m-d H:i:s ") . $rawInput . PHP_EOL,
    FILE_APPEND
);

// ================================
// DECODE PAYLOAD
// ================================
$data = json_decode($rawInput, true);

if (!$data || !isset($data['event'], $data['data'])) {
    http_response_code(400);
    exit;
}

// ================================
// HANDLE SUCCESSFUL PAYMENT
// ================================
if ($data['event'] === 'charge.success') {

    $reference = $data['data']['reference'];
    $amount    = $data['data']['amount'] / 100; // kobo → currency
    $currency  = $data['data']['currency'];

    // Fetch payment from DB
    $stmt = $db->prepare("
        SELECT * FROM payments 
        WHERE payment_extra = :reference 
        LIMIT 1
    ");
    $stmt->execute(["reference" => $reference]);
    $payment = $stmt->fetch();

    if ($payment && $payment['payment_status'] === 'PENDING') {

        // Validate amount & currency
        if (
            round($payment['payment_amount'], 2) == round($amount, 2)
            && $payment['payment_currency'] == $currency
        ) {
            // Mark payment as SUCCESS
            $update = $db->prepare("
                UPDATE payments SET
                    payment_status = 'SUCCESS',
                    updated_at = NOW()
                WHERE payment_extra = :reference
            ");
            $update->execute(["reference" => $reference]);
        }
    }
}

// ================================
// RESPOND TO PAYSTACK
// ================================
http_response_code(200);
echo json_encode(["status" => "OK"]);
