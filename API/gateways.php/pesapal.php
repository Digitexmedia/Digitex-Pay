<?php
if (!defined('ADDFUNDS')) {
    http_response_code(404);
    die();
}

// Get Pesapal configuration from method extras
$consumerKey = $methodExtras["consumerKey"];
$consumerSecret = $methodExtras["consumerSecret"];
$environment = $methodExtras["environment"] ?? "sandbox"; // sandbox or live

if (empty($consumerKey) || empty($consumerSecret)) {
    errorExit("Pesapal is not configured properly. Please contact administrator.");
}

// Set API base URL based on environment
$apiBaseUrl = ($environment === "live") 
    ? "https://api.pesapal.com/v3" 
    : "https://cybqa.pesapal.com/pesapalv3";

// Generate unique order ID
$orderId = "PESAPAL_" . md5(RAND_STRING(5) . time() . $user["client_id"]);

// Prepare callback URLs
$callbackURL = site_url("payment/" . $methodCallback);
$redirectURL = site_url("addfunds?status=success");

// Step 1: Get OAuth Access Token
$tokenUrl = $apiBaseUrl . "/api/Auth/RequestToken";

$tokenData = [
    "consumer_key" => $consumerKey,
    "consumer_secret" => $consumerSecret
];

$tokenHeaders = [
    "Content-Type: application/json",
    "Accept: application/json"
];

$tokenCurl = curl_init();
curl_setopt_array($tokenCurl, [
    CURLOPT_URL => $tokenUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($tokenData),
    CURLOPT_HTTPHEADER => $tokenHeaders,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30
]);

$tokenResponse = curl_exec($tokenCurl);
$tokenHttpCode = curl_getinfo($tokenCurl, CURLINFO_HTTP_CODE);
$tokenError = curl_error($tokenCurl);
curl_close($tokenCurl);

if ($tokenError) {
    errorExit("Failed to connect to Pesapal API: " . $tokenError);
}

$tokenResult = json_decode($tokenResponse, true);

if ($tokenHttpCode !== 200 || !isset($tokenResult["token"])) {
    $errorMessage = isset($tokenResult["error"]) ? $tokenResult["error"] : "Failed to get access token";
    errorExit("Pesapal authentication failed: " . $errorMessage);
}

$accessToken = $tokenResult["token"];

// Step 2: Submit Payment Order
$orderUrl = $apiBaseUrl . "/api/Transactions/SubmitOrderRequest";

// Format payment amount (Pesapal expects amount in the currency's smallest unit)
$paymentAmountFormatted = number_format($paymentAmount, 2, '.', '');

// Prepare order data
$orderData = [
    "id" => $orderId,
    "currency" => $methodCurrency,
    "amount" => floatval($paymentAmountFormatted),
    "description" => "Balance Recharge - " . $user["username"],
    "callback_url" => $callbackURL,
    "redirect_mode" => "",
    "notification_id" => "",
    "branch" => "",
    "billing_address" => [
        "email_address" => $user["email"],
        "phone_number" => $user["telephone"] ?? "",
        "country_code" => "",
        "first_name" => $user["name"] ?? "Customer",
        "middle_name" => "",
        "last_name" => "",
        "line_1" => "",
        "line_2" => "",
        "city" => "",
        "state" => "",
        "postal_code" => "",
        "zip_code" => ""
    ]
];

$orderHeaders = [
    "Authorization: Bearer " . $accessToken,
    "Content-Type: application/json",
    "Accept: application/json"
];

$orderCurl = curl_init();
curl_setopt_array($orderCurl, [
    CURLOPT_URL => $orderUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($orderData),
    CURLOPT_HTTPHEADER => $orderHeaders,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_TIMEOUT => 30
]);

$orderResponse = curl_exec($orderCurl);
$orderHttpCode = curl_getinfo($orderCurl, CURLINFO_HTTP_CODE);
$orderError = curl_error($orderCurl);
curl_close($orderCurl);

if ($orderError) {
    errorExit("Failed to create Pesapal order: " . $orderError);
}

$orderResult = json_decode($orderResponse, true);

if ($orderHttpCode !== 200 || !isset($orderResult["redirect_url"])) {
    $errorMessage = isset($orderResult["error"]) ? $orderResult["error"] : "Failed to create payment order";
    if (isset($orderResult["message"])) {
        $errorMessage = $orderResult["message"];
    }
    errorExit("Pesapal order creation failed: " . $errorMessage);
}

$redirectUrl = $orderResult["redirect_url"];
$orderTrackingId = $orderResult["order_tracking_id"] ?? $orderId;

// Insert payment record into database
$insert = $conn->prepare(
    "INSERT INTO payments SET
    client_id=:client_id,
    payment_amount=:amount,
    payment_method=:method,
    payment_mode=:mode,
    payment_create_date=:date,
    payment_ip=:ip,
    payment_extra=:extra,
    payment_extra2=:tracking_id"
);

$insert->execute([
    "client_id" => $user["client_id"],
    "amount" => $paymentAmount,
    "method" => $methodId,
    "mode" => "Automatic",
    "date" => date("Y.m.d H:i:s"),
    "ip" => GetIP(),
    "extra" => $orderId,
    "tracking_id" => $orderTrackingId
]);

// Create redirect form
$redirectForm .= '<script type="text/javascript">
    window.location.href = "' . $redirectUrl . '";
</script>';

$response["success"] = true;
$response["message"] = "Your payment has been initiated and you will now be redirected to the payment gateway.";
$response["content"] = $redirectForm;

?>
