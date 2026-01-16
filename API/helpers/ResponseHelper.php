<?php

class ResponseHelper
{
    public static function success(array $data = []): void
    {
        echo json_encode(array_merge([
            "status" => true
        ], $data));
        exit;
    }

    public static function error(string $message, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode([
            "status" => false,
            "message" => $message
        ]);
        exit;
    }
}
