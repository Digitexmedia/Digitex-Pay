<?php
/**
 * Digitex Pay – Authentication Core
 * Handles:
 * 1. Admin login sessions
 * 2. Merchant API key validation
 */

require_once __DIR__ . "/../config/database.php";

class Auth
{
    /**
     * =========================
     * ADMIN AUTHENTICATION
     * =========================
     */

    public static function adminLogin(string $email, string $password): bool
    {
        global $db;

        $stmt = $db->prepare("
            SELECT id, password 
            FROM admins 
            WHERE email = ? 
            LIMIT 1
        ");
        $stmt->execute([$email]);

        $admin = $stmt->fetch();

        if (!$admin) {
            return false;
        }

        if (!password_verify($password, $admin['password'])) {
            return false;
        }

        // Start session securely
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['admin_id'] = $admin['id'];
        return true;
    }

    public static function adminCheck(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['admin_id']);
    }

    public static function adminLogout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
    }

    /**
     * =========================
     * MERCHANT API AUTH
     * =========================
     */

    public static function validateApiKey(): array
    {
        global $db;

        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            return [
                "status" => false,
                "message" => "Missing API key"
            ];
        }

        // Expect: Authorization: Bearer API_KEY
        $token = trim(str_replace("Bearer", "", $headers['Authorization']));

        $stmt = $db->prepare("
            SELECT id, status 
            FROM merchants 
            WHERE api_key = ? 
            LIMIT 1
        ");
        $stmt->execute([$token]);

        $merchant = $stmt->fetch();

        if (!$merchant) {
            return [
                "status" => false,
                "message" => "Invalid API key"
            ];
        }

        if ((int)$merchant['status'] !== 1) {
            return [
                "status" => false,
                "message" => "Merchant account disabled"
            ];
        }

        return [
            "status" => true,
            "merchant_id" => $merchant['id']
        ];
    }
}
