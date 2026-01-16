<?php
// ================================
// Digitex Pay – Unified API Entry
// ================================

// Allow frontend requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

// Handle preflight (important for fetch)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// -------------------------------
// Load core files
// -------------------------------
require_once __DIR__ . "/config/database.php";
require_once __DIR__ . "/controllers/PaymentController.php";

// -------------------------------
// Basic router
// -------------------------------
$action = $_GET['action'] ?? null;

if (!$action) {
    echo json_encode([
        "status" => false,
        "message" => "No action specified"
    ]);
    exit;
}

$controller = new PaymentController();

// -------------------------------
// Route actions
// -------------------------------
switch ($action) {

    case "pay":
        $controller->initiate();
        break;

    case "verify":
        $controller->verify();
        break;

    default:
        echo json_encode([
            "status" => false,
            "message" => "Invalid API action"
        ]);
        break;
}
