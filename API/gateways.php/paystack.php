<?php

require_once __DIR__ . "/../core/GatewayInterface.php";
require_once __DIR__ . "/../config/gateways.php";

class Paystack implements GatewayInterface
{
    private $publicKey;
    private $secretKey;

    public function __construct()
    {
        $gateways = require __DIR__ . "/../config/gateways.php";

        if (!isset($gateways['paystack'])) {
            throw new Exception("Paystack gateway is disabled");
        }

        $this->publicKey = $gateways['paystack']['client_id'];     // public key
        $this->secretKey = $gateways['paystack']['client_secret']; // secret key
    }

    /**
     * =========================
     * INITIATE PAYMENT
     * =========================
     */
    public function initiatePayment(array $data): array
    {
        if (empty($data['email']) || empty($data['amount'])) {
            return [
                "status" => false,
                "message" => "Email and amount are required"
            ];
        }

        $reference = $data['reference'] ?? uniqid("DP_");

        return [
            "status"   => true,
            "gateway"  => "paystack",
            "public_key" => $this->publicKey,
            "email"    => $data['email'],
            "amount"   => intval($data['amount'] * 100), // kobo
            "currency" => $data['currency'] ?? "NGN",
            "reference"=> $reference
        ];
    }

    /**
     * =========================
     * VERIFY PAYMENT (OPTIONAL)
     * =========================
     */
    public function verifyPayment(string $reference): array
    {
        if (!$reference) {
            return [
                "status" => false,
                "message" => "Reference required"
            ];
        }

        $url = "https://api.paystack.co/transaction/verify/" . urlencode($reference);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $this->secretKey
            ],
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return [
                "status" => false,
                "message" => "Unable to reach Paystack"
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
            "message" => "Refund not supported via Paystack API here"
        ];
    }
}
