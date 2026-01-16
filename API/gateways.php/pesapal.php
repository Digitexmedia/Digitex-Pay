<?php

// Security check
if (!defined('ADDFUNDS')) {
    http_response_code(404);
    exit;
}

// ================================
// LOAD PESAPAL CONFIG
// ================================
$consumerKey    = $methodExtras["consumerKey"] ?? null;
$consumerSecret = $methodExtras["consumerSecret"] ?? null;
$environment    = $methodExtras["environment"] ?? "sandbox"; // sandbox | live

if (empty($consumerKey) || empty($consumerSecret)) {
    errorExit("Pesapal is not configured properly. Please contact administrator.");
}

// ================================
// API BASE URL
// ================================
$apiBaseUrl = ($environment === "live")
    ? "https://api.pesapal.com/v3"
    : "https://cybqa.pesapal.com/pesapalv3";

// ================================
// GENERATE UNIQUE ORDER ID
// ================================
$orderId = "PESAPAL_" . md5(RAND_STRING(5) . time() . $user["client_id"]);

// ================================
// CALLBACK & REDIRECT URLS
// ================================
$callbackURL = site_url("payment/" . $methodCallback);
$redirectURL = site_url("addfunds?status=success");

// ================================
// STEP 1: REQUEST ACCESS TOKEN
// ================================
$tokenUrl = $apiBaseUrl . "/api/Auth/RequestToken";

$tokenPayload = [
    "consumer_key"    => $consumerKey,
    "consumer_secret" => $consumerSecret
];

$tokenHeaders = [
    "Content-Type: application/json",
    "Accept: application/json"
];

$ch = curl_init($tokenUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($tokenPayload),
    CURLOPT_HTTPHEADER     => $tokenHeaders,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30
]);

$tokenResponse = curl_exec($ch);
$tokenError    = curl_error($ch);
$httpCode      = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenError) {
    errorExit("Pesapal connection error: " . $tokenError);
}

$tokenResult = json_decode($tokenResponse, true);

if ($httpCode !== 200 || empty($tokenResult["token"])) {
    errorExit("Pesapal authentication failed.");
}

$accessToken = $tokenResult["token"];

// ================================
// STEP 2: SUBMIT PAYMENT ORDER
// ================================
$orderUrl = $apiBaseUrl . "/api/Transactions/SubmitOrderRequest";

$orderData = [
    "id" => $orderId,
    "currency" => $methodCurrency,
    "amount" => round($paymentAmount, 2),
    "description" => "Balance Recharge - " . $user["username"],
    "callback_url" => $callbackURL,
    "redirect_mode" => "",
    "notification_id" => "",
    "branch" => "",
    "billing_address" => [
        "email_address" => $user["email"],
        "phone_number"  => $user["telephone"] ?? "",
        "first_name"    => $user["name"] ?? "Customer",
        "last_name"     => "",
        "country_code"  => "",
        "line_1"        => "",
        "line_2"        => "",
        "city"          => "",
        "state"         => "",
        "postal_code"   => "",
        "zip_code"      => ""
    ]
];

$orderHeaders = [
    "Authorization: Bearer " . $accessToken,
    "Content-Type: application/json",
    "Accept: application/json"
];

$ch = curl_init($orderUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($orderData),
    CURLOPT_HTTPHEADER     => $orderHeaders,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT        => 30
]);

$orderResponse = curl_exec($ch);
$orderError    = curl_error($ch);
$orderHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($orderError) {
    errorExit("Pesapal order error: " . $orderError);
}

$orderResult = json_decode($orderResponse, true);

if ($orderHttpCode !== 200 || empty($orderResult["redirect_url"])) {
    errorExit("Pesapal order creation failed.");
}

// ================================
// SAVE PAYMENT TO DATABASE
// ================================
$insert = $conn->prepare("
    INSERT INTO payments SET
        client_id = :client_id,
        payment_amount = :amount,
        payment_method = :method,
        payment_mode = 'Automatic',
        payment_create_date = :date,
        payment_ip = :ip,
        payment_extra = :order_id,
        payment_extra2 = :tracking_id
");

$insert->execute([
    "client_id"   => $user["client_id"],
    "amount"      => $paymentAmount,
    "method"      => $methodId,
    "date"        => date("Y-m-d H:i:s"),
    "ip"          => GetIP(),
    "order_id"    => $orderId,
    "tracking_id" => $orderResult["order_tracking_id"] ?? $orderId
]);

// ================================
// REDIRECT USER TO PESAPAL
// ================================
$response["success"] = true;
$response["message"] = "Redirecting to Pesapal payment page...";
$response["content"] = '<script>window.location.href="' . $orderResult["redirect_url"] . '";</script>';

?>
