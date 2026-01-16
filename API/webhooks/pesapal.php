<?php
/**
 * PESAPAL WEBHOOK / IPN
 * Called ONLY by Pesapal servers
 */

header("Content-Type: application/json");

// ================================
// CONNECT TO DATABASE
// ================================
require_once __DIR__ . "/../config/database.php";

// ================================
// LOG CALLBACK (VERY IMPORTANT)
// ================================
file_put_contents(
    __DIR__ . "/pesapal.log",
    date("Y-m-d H:i:s ") . json_encode($_GET) . PHP_EOL,
    FILE_APPEND
);

// ================================
// READ CALLBACK PARAMETERS
// ================================
$orderTrackingId = $_GET['OrderTrackingId'] ?? null;
$merchantRef     = $_GET['OrderMerchantReference'] ?? null;

if (!$orderTrackingId) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing OrderTrackingId"]);
    exit;
}

// ================================
// LOAD PESAPAL CREDENTIALS
// (FROM YOUR DB / CONFIG)
// ================================
$environment = PESAPAL_ENV ?? "sandbox";

$apiBaseUrl = ($environment === "live")
    ? "https://api.pesapal.com/v3"
    : "https://cybqa.pesapal.com/pesapalv3";

// ================================
// STEP 1: REQUEST ACCESS TOKEN
// ================================
$tokenUrl = $apiBaseUrl . "/api/Auth/RequestToken";

$tokenPayload = [
    "consumer_key"    => PESAPAL_CONSUMER_KEY,
    "consumer_secret" => PESAPAL_CONSUMER_SECRET
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($tokenPayload),
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30
]);

$tokenResponse = curl_exec($ch);
curl_close($ch);

$tokenResult = json_decode($tokenResponse, true);

if (empty($tokenResult['token'])) {
    http_response_code(500);
    exit;
}

$accessToken = $tokenResult['token'];

// ================================
// STEP 2: VERIFY TRANSACTION STATUS
// ================================
$statusUrl = $apiBaseUrl .
    "/api/Transactions/GetTransactionStatus?orderTrackingId=" .
    urlencode($orderTrackingId);

$ch = curl_init($statusUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $accessToken,
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 30
]);

$statusResponse = curl_exec($ch);
curl_close($ch);

$statusResult = json_decode($statusResponse, true);

if (empty($statusResult['payment_status_description'])) {
    http_response_code(500);
    exit;
}

// ================================
// MAP PESAPAL STATUS
// ================================
$pesapalStatus = strtoupper($statusResult['payment_status_description']);

switch ($pesapalStatus) {
    case "COMPLETED":
        $finalStatus = "SUCCESS";
        break;
    case "FAILED":
    case "INVALID":
    case "REVERSED":
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
        payment_reference = :reference,
        updated_at = NOW()
    WHERE payment_extra2 = :tracking_id
");

$stmt->execute([
    "status"      => $finalStatus,
    "reference"   => $merchantRef,
    "tracking_id" => $orderTrackingId
]);

// ================================
// RESPOND TO PESAPAL
// ================================
echo json_encode([
    "status" => "OK",
    "message" => "Webhook processed"
]);
