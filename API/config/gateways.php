<?php
/**
 * Digitex Pay – Load Active Payment Gateways
 * This file is READ-ONLY (Admin edits DB, not this file)
 */

require_once __DIR__ . "/database.php";

$gateways = [];

try {

    $stmt = $db->prepare("
        SELECT 
            name,
            client_id,
            client_secret,
            api_key,
            status
        FROM payment_gateways
        WHERE status = 1
    ");

    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

        // Normalize gateway name (mpesa, pesapal, binance, etc.)
        $key = strtolower(trim($row['name']));

        $gateways[$key] = [
            "client_id"     => $row['client_id'],
            "client_secret" => $row['client_secret'],
            "api_key"       => $row['api_key'],
            "status"        => (int)$row['status']
        ];
    }

} catch (Exception $e) {

    // Fail silently (security)
    http_response_code(500);
    echo json_encode([
        "status" => false,
        "message" => "Failed to load payment gateways"
    ]);
    exit;
}

// This file MUST return the array
return $gateways;
