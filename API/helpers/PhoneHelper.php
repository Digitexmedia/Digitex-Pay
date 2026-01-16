<?php

class PhoneHelper
{
    /**
     * Convert Kenyan phone numbers to 254 format
     */
    public static function normalize(string $phone): string
    {
        // Remove spaces, +, non-digits
        $phone = preg_replace('/\D/', '', $phone);

        // Starts with 0 → replace with 254
        if (substr($phone, 0, 1) === '0') {
            return '254' . substr($phone, 1);
        }

        // Starts with 7 or 1 → prepend 254
        if (substr($phone, 0, 1) === '7' || substr($phone, 0, 1) === '1') {
            return '254' . $phone;
        }

        // Already correct
        if (substr($phone, 0, 3) === '254') {
            return $phone;
        }

        return $phone;
    }
}
