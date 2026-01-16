<?php

require_once __DIR__ . "/../core/GatewayInterface.php";

class Mpesa implements GatewayInterface
{
    /**
     * Convert phone number to 254 format
     */
    private function formatPhone(string $phone): string
    {
        // Remove spaces, +, non-numbers
        $phone = preg_replace('/\D/', '', $phone);

        // If starts with 0 → replace with 254
        if (substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }

        // If already starts with 254 → keep
        if (substr($phone, 0, 3) === '254') {
            return $phone;
        }

        // If starts with 7 or 1 → add 254
        if (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') {
            return '254' . $phone;
        }

        return $phone;
    }

    /**
     * =========================
     * INITIATE PAYMENT (STK)
     * =========================
     */
    public function initiatePayment(array $data): array
    {
        if (empty($data['phone']) || empty($data['amount'])) {
            return [
                "status" => false,
                "message" => "Phone number and amount are required"
            ];
        }

        $phone = $this->formatPhone($data['phone']);
        $amount = round($data['amount']);
        $reference = $data['reference'] ?? uniqid("DP_");

        // Payload sent to your STK API
        $payload = [
            "phone"       => $phone,
            "amount"      => $amount,
            "reference"   => $reference,
            "description" => "Digitex Pay Payment"
        ];

        // External STK API (same logic as your old system)
        $ch = curl_init("https://mpesa-stk.giftedtech.co.ke/api/payMaka.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return [
                "status" => false,
                "message" => "Unable to connect to M-PESA server"
            ];
        }

        $result = json_decode($response, true);

        return [
            "status" => true,
            "stk" => true,
            "reference" => $reference,
            "provider_response" => $result
        ];
    }

    /**
     * =========================
     * VERIFY PAYMENT (POLLING)
     * =========================
     */
    public function verifyPayment(string $reference): array
    {
        if (empty($reference)) {
            return [
                "status" => false,
                "message" => "Reference is required"
            ];
        }

        $ch = curl_init("https://mpesa-stk.giftedtech.co.ke/api/verify-transaction.php");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            "reference" => $reference
        ]));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || !$response) {
            return [
                "status" => false,
                "message" => "Verification server unreachable"
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
            "message" => "M-PESA refund not supported"
        ];
    }
}
