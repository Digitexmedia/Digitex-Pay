<?php

require_once __DIR__ . "/../core/GatewayInterface.php";
require_once __DIR__ . "/../config/gateways.php";

class Binance implements GatewayInterface
{
    private $apiKey;
    private $secretKey;

    public function __construct()
    {
        $gateways = require __DIR__ . "/../config/gateways.php";

        if (!isset($gateways['binance'])) {
            throw new Exception("Binance Pay gateway disabled");
        }

        $this->apiKey    = $gateways['binance']['api_key'];
        $this->secretKey = $gateways['binance']['client_secret'];
    }

    /**
     * =========================
     * INITIATE BINANCE PAYMENT
     * =========================
     */
    public function initiatePayment(array $data): array
    {
        if (empty($data['amount']) || empty($data['currency'])) {
            return [
                "status" => false,
                "message" => "Amount and currency are required"
            ];
        }

        $nonce     = bin2hex(random_bytes(16));
        $timestamp = round(microtime(true) * 1000);
        $reference = $data['reference'] ?? uniqid("DP_");

        $request = [
            "env" => [
                "terminalType" => "APP"
            ],
            "merchantTradeNo" => $reference,
            "orderAmount" => $data['amount'],
            "currency" => $data['currency'],
            "goods" => [
                "goodsType" => "01",
                "goodsCategory" => "Z000",
                "referenceGoodsId" => $reference,
                "goodsName" => "Digitex Pay Payment",
                "goodsDetail" => "Digitex Pay Payment"
            ]
        ];

        $jsonRequest = json_encode($request);
        $payload     = $timestamp . "\n" . $nonce . "\n" . $jsonRequest . "\n";
        $signature   = strtoupper(hash_hmac("SHA512", $payload, $this->secretKey));

        $headers = [
            "Content-Type: application/json",
            "BinancePay-Timestamp: $timestamp",
            "BinancePay-Nonce: $nonce",
            "BinancePay-Certificate-SN: {$this->apiKey}",
            "BinancePay-Signature: $signature"
        ];

        $ch = curl_init("https://bpay.binanceapi.com/binancepay/openapi/v2/order");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return [
                "status" => false,
                "message" => "Unable to connect to Binance Pay"
            ];
        }

        $result = json_decode($response, true);

        if (isset($result['status']) && $result['status'] === "SUCCESS") {
            return [
                "status" => true,
                "redirect_url" => $result['data']['checkoutUrl'],
                "reference" => $reference
            ];
        }

        return [
            "status" => false,
            "message" => $result['errorMessage'] ?? "Binance payment failed"
        ];
    }

    /**
     * =========================
     * VERIFY BINANCE PAYMENT
     * =========================
     */
    public function verifyPayment(string $reference): array
    {
        $nonce     = bin2hex(random_bytes(16));
        $timestamp = round(microtime(true) * 1000);

        $request = [
            "merchantTradeNo" => $reference
        ];

        $jsonRequest = json_encode($request);
        $payload     = $timestamp . "\n" . $nonce . "\n" . $jsonRequest . "\n";
        $signature   = strtoupper(hash_hmac("SHA512", $payload, $this->secretKey));

        $headers = [
            "Content-Type: application/json",
            "BinancePay-Timestamp: $timestamp",
            "BinancePay-Nonce: $nonce",
            "BinancePay-Certificate-SN: {$this->apiKey}",
            "BinancePay-Signature: $signature"
        ];

        $ch = curl_init("https://bpay.binanceapi.com/binancepay/openapi/v2/order/query");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonRequest);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return [
                "status" => false,
                "message" => "Unable to verify Binance payment"
            ];
        }

        return json_decode($response, true);
    }

    /**
     * =========================
     * REFUND (NOT SUPPORTED)
     * =========================
     */
    public function refund(string $reference, float $amount): array
    {
        return [
            "status" => false,
            "message" => "Binance Pay refunds not supported"
        ];
    }
}
