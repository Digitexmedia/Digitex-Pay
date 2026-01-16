<?php
/**
 * Digitex Pay – Payment Controller
 * Routes payments to the correct gateway safely
 */

require_once __DIR__ . "/../core/Auth.php";
require_once __DIR__ . "/../config/gateways.php";

// Gateway classes
require_once __DIR__ . "/../gateways/Mpesa.php";
require_once __DIR__ . "/../gateways/MpesaPay.php";
require_once __DIR__ . "/../gateways/Pesapal.php";
require_once __DIR__ . "/../gateways/Pawapay.php";
require_once __DIR__ . "/../gateways/Binance.php";

class PaymentController
{
    /**
     * =========================
     * INITIATE PAYMENT
     * =========================
     */
    public function initiate(): void
    {
        // 🔐 Validate merchant API key
        $auth = Auth::validateApiKey();
        if (!$auth['status']) {
            echo json_encode($auth);
            return;
        }

        // Read JSON body
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data || !isset($data['gateway'], $data['amount'])) {
            echo json_encode([
                "status" => false,
                "message" => "Invalid request payload"
            ]);
            return;
        }

        $gatewayName = strtolower(trim($data['gateway']));

        // Load active gateways from DB
        $gateways = require __DIR__ . "/../config/gateways.php";

        if (!isset($gateways[$gatewayName])) {
            echo json_encode([
                "status" => false,
                "message" => "Payment gateway disabled or not found"
            ]);
            return;
        }

        // Select gateway
        switch ($gatewayName) {
            case "mpesa":
                $gateway = new Mpesa();
                break;

            case "mpesapay":
                $gateway = new MpesaPay();
                break;

            case "pesapal":
                $gateway = new Pesapal();
                break;

            case "pawapay":
                $gateway = new Pawapay();
                break;

            case "binance":
                $gateway = new Binance();
                break;

            default:
                echo json_encode([
                    "status" => false,
                    "message" => "Unsupported payment gateway"
                ]);
                return;
        }

        // Initiate payment
        $response = $gateway->initiatePayment($data);

        echo json_encode($response);
    }

    /**
     * =========================
     * VERIFY PAYMENT
     * =========================
     */
    public function verify(): void
    {
        // 🔐 Validate merchant API key
        $auth = Auth::validateApiKey();
        if (!$auth['status']) {
            echo json_encode($auth);
            return;
        }

        $reference = $_GET['reference'] ?? null;
        $gatewayName = $_GET['gateway'] ?? null;

        if (!$reference || !$gatewayName) {
            echo json_encode([
                "status" => false,
                "message" => "Missing reference or gateway"
            ]);
            return;
        }

        $gatewayName = strtolower(trim($gatewayName));

        switch ($gatewayName) {
            case "mpesa":
                $gateway = new Mpesa();
                break;

            case "mpesapay":
                $gateway = new MpesaPay();
                break;

            case "pesapal":
                $gateway = new Pesapal();
                break;

            case "pawapay":
                $gateway = new Pawapay();
                break;

            case "binance":
                $gateway = new Binance();
                break;

            default:
                echo json_encode([
                    "status" => false,
                    "message" => "Unsupported gateway"
                ]);
                return;
        }

        echo json_encode($gateway->verifyPayment($reference));
    }
}
